<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAOQRequest;
use App\Models\AOQ;
use App\Models\RFQ;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class AOQController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AOQ::with([
            'rfq.purchaseRequest.office',
            'rfq.suppliers.supplier',
            'winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->whereHas('rfq', function ($rfq) use ($search): void {
                    $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseRequest', fn($pr) => $pr->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('office', fn($o) => $o->where('name', 'like', sprintf('%%%s%%', $search))));
                })->orWhereHas('winnerSupplier', fn($s) => $s->where('name', 'like', sprintf('%%%s%%', $search)));
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('aoq_date', $fiscalYear);
            });

        $aoqs = (clone $query)
            ->latest('aoq_date')
            ->paginate(10)
            ->withQueryString()
            ->through(function (AOQ $aoq): AOQ {
                $calculation = $this->calculateSupplierTotals($aoq->rfq);
                $aoq->setAttribute('calculated_supplier_count', $calculation['calculated_supplier_count']);
                $aoq->setAttribute('calculation_mode', $calculation['calculation_mode']);

                return $aoq;
            });

        $all = (clone $query)->get();
        $singleCalculated = 0;
        $lowestCalculated = 0;
        foreach ($all as $aoq) {
            $calculation = $this->calculateSupplierTotals($aoq->rfq);
            if ($calculation['calculation_mode'] === 'single_calculated') {
                $singleCalculated++;
            }
            if ($calculation['calculation_mode'] === 'lowest_calculated') {
                $lowestCalculated++;
            }
        }

        $stats = [
            'total' => $all->count(),
            'single_calculated' => $singleCalculated,
            'lowest_calculated' => $lowestCalculated,
            'without_winner' => (clone $query)->whereNull('winner_supplier_id')->count(),
        ];

        $offices = \App\Models\Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('AOQs/Index', [
            'aoqs' => $aoqs,
            'stats' => $stats,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
            ],
        ]);
    }

    public function create(): Response
    {
        $eligibleRfqs = RFQ::with([
            'purchaseRequest.office',
            'purchaseRequest.items.emanatingItem.ppmpItem',
            'items.purchaseRequestItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ])
            ->whereDoesntHave('aoq')
            ->latest('rfq_date')
            ->get()
            ->filter(function (RFQ $rfq): bool {
                return $this->calculateSupplierTotals($rfq)['calculated_supplier_count'] > 0;
            })
            ->values();

        return Inertia::render('AOQs/Create', [
            'eligibleRfqs' => $eligibleRfqs,
            'defaultAoqDate' => now()->toDateString(),
        ]);
    }

    public function store(StoreAOQRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $rfq = RFQ::with([
                'suppliers.supplier',
                'suppliers.supplierItems.rfqItem.purchaseRequestItem',
            ])->findOrFail($validated['rfq_id']);

            if ($rfq->aoq()->exists()) {
                return redirect()->back()->with('error', 'An AOQ already exists for this RFQ.');
            }

            $calculation = $this->calculateSupplierTotals($rfq);
            if ($calculation['calculated_supplier_count'] === 0) {
                return redirect()->back()->with('error', 'This RFQ has no submitted supplier quotation yet.');
            }

            $winnerSupplierId = $calculation['winner_supplier_id'];

            $aoq = AOQ::create([
                'rfq_id' => $rfq->id,
                'aoq_date' => $validated['aoq_date'],
                'winner_supplier_id' => $winnerSupplierId,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AOQ creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to create AOQ. Please try again.');
        }

        return redirect()->route('aoqs.show', $aoq)
            ->with('success', 'AOQ created successfully.');
    }

    public function show(AOQ $aoq): Response
    {
        $aoq->load([
            'rfq.purchaseRequest.office',
            'rfq.purchaseRequest.items.emanatingItem.ppmpItem',
            'rfq.items.purchaseRequestItem.emanatingItem.ppmpItem',
            'rfq.suppliers.supplier',
            'rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'winnerSupplier',
        ]);

        $calculation = $this->calculateSupplierTotals($aoq->rfq);

        return Inertia::render('AOQs/Show', [
            'aoq' => $aoq,
            'calculation' => $calculation,
        ]);
    }

    public function destroy(AOQ $aoq): RedirectResponse
    {
        $aoq->delete();

        return redirect()->route('aoqs.index')->with('success', 'AOQ deleted successfully.');
    }

    public function printPdf(AOQ $aoq)
    {
        $aoq->load([
            'rfq.purchaseRequest.office',
            'rfq.items.purchaseRequestItem.emanatingItem.ppmpItem',
            'rfq.suppliers.supplier',
            'rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'winnerSupplier',
        ]);

        $calculation = $this->calculateSupplierTotals($aoq->rfq);

        return Pdf::view('pdf.aoq', [
            'aoq' => $aoq,
            'rfq' => $aoq->rfq,
            'calculation' => $calculation,
        ])
            ->format('a4')
            ->name('AOQ-' . ($aoq->rfq?->svp_no ?? $aoq->id) . '.pdf')
            ->inline();
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSupplierTotals(RFQ $rfq): array
    {
        $rfq->loadMissing([
            'items.purchaseRequestItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        $supplierTotals = [];

        foreach ($rfq->suppliers as $rfqSupplier) {
            if (! $rfqSupplier->submitted_at) {
                continue;
            }

            $total = 0.0;
            $hasAtLeastOnePrice = false;

            foreach ($rfqSupplier->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price === null) {
                    continue;
                }

                $quantity = (float) ($supplierItem->rfqItem?->purchaseRequestItem?->quantity ?? 0);
                $lineTotal = $quantity * (float) $supplierItem->unit_price;
                $total += $lineTotal;
                $hasAtLeastOnePrice = true;
            }

            if (! $hasAtLeastOnePrice) {
                continue;
            }

            $supplierTotals[] = [
                'rfq_supplier_id' => $rfqSupplier->id,
                'supplier_id' => $rfqSupplier->supplier_id,
                'supplier_name' => $rfqSupplier->supplier?->name,
                'total_amount' => round($total, 2),
            ];
        }

        usort($supplierTotals, fn($a, $b) => $a['total_amount'] <=> $b['total_amount']);

        $count = count($supplierTotals);
        $winner = $count > 0 ? $supplierTotals[0] : null;

        return [
            'supplier_totals' => $supplierTotals,
            'calculated_supplier_count' => $count,
            'calculation_mode' => $count <= 1 ? 'single_calculated' : 'lowest_calculated',
            'winner_supplier_id' => $winner['supplier_id'] ?? null,
            'winner_total_amount' => $winner['total_amount'] ?? null,
        ];
    }
}
