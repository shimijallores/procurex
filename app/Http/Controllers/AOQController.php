<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAOQRequest;
use App\Models\AOQ;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\RFQ;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'batch',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->whereHas('rfq', function ($rfq) use ($search): void {
                    $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseRequest', fn ($pr) => $pr->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('office', fn ($o) => $o->where('name', 'like', sprintf('%%%s%%', $search))));
                })->orWhereHas('winnerSupplier', fn ($s) => $s->where('name', 'like', sprintf('%%%s%%', $search)));
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('aoq_date', $fiscalYear);
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->where('batch_id', $batchId);
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
        $batches = Batch::withCount('aoqs')
            ->orderByDesc('id')
            ->get(['id', 'batch_no']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        return Inertia::render('AOQs/Index', [
            'aoqs' => $aoqs,
            'stats' => $stats,
            'offices' => $offices,
            'batches' => $batches,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
                'batch_id' => $request->batch_id,
            ],
        ]);
    }

    public function create(): Response
    {
        $eligibleRfqs = RFQ::with([
            'purchaseRequest.office',
            'items.purchaseRequestItem',
        ])
            ->whereNotNull('pr_id')
            ->whereDoesntHave('aoq')
            ->latest('rfq_date')
            ->get();

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'contact_number']);

        $batches = Batch::withCount('aoqs')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('AOQs/Create', [
            'eligibleRfqs' => $eligibleRfqs,
            'suppliers' => $suppliers,
            'batches' => $batches,
            'defaultAoqDate' => $this->suggestNextWorkingDay()->toDateString(),
        ]);
    }

    public function storeBatch(): JsonResponse
    {
        $year = now()->format('y');
        $prefix = $year;

        $latest = Batch::query()
            ->where('batch_no', 'like', $prefix.'%')
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $next = 1;
        if ($latest && preg_match('/^\d{2}(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        $batchNo = sprintf('%s%04d', $prefix, $next);

        while (Batch::where('batch_no', $batchNo)->exists()) {
            $next++;
            $batchNo = sprintf('%s%04d', $prefix, $next);
        }

        $batch = Batch::create(['batch_no' => $batchNo]);

        return response()->json($batch);
    }

    public function destroyBatch(Batch $batch): JsonResponse
    {
        abort_if($batch->aoqs()->exists(), 422, 'Cannot delete a batch with existing AOQs.');

        $batch->delete();

        return response()->json(['success' => true]);
    }

    public function suggestBatch(): JsonResponse
    {
        $year = now()->format('y');
        $prefix = $year;

        $latest = Batch::query()
            ->where('batch_no', 'like', $prefix.'%')
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $next = 1;
        if ($latest && preg_match('/^\d{2}(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        $batchNo = sprintf('%s%04d', $prefix, $next);

        return response()->json(['batch_no' => $batchNo]);
    }

    public function store(StoreAOQRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $rfq = RFQ::with([
                'purchaseRequest.items',
                'suppliers.supplier',
                'suppliers.supplierItems.rfqItem.purchaseRequestItem',
            ])->findOrFail($validated['rfq_id']);

            if (! $rfq->purchaseRequest) {
                return redirect()->back()->with('error', 'The selected RFQ is not linked to a Purchase Request.');
            }

            if ($rfq->aoq()->exists()) {
                return redirect()->back()->with('error', 'An AOQ already exists for this RFQ.');
            }

            $rfqItemIds = $rfq->items->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (count($rfqItemIds) === 0) {
                return redirect()->back()->with('error', 'This RFQ has no items to evaluate.');
            }

            $supplierTotals = [];
            foreach ($validated['quotations'] as $quotation) {
                $unitPrices = $quotation['unit_prices'] ?? [];
                $hasPrice = false;
                $runningTotal = 0.0;

                foreach ($rfq->items as $rfqItem) {
                    $rawPrice = $unitPrices[$rfqItem->id] ?? null;

                    if ($rawPrice === null || $rawPrice === '') {
                        continue;
                    }

                    $hasPrice = true;
                    $runningTotal += (float) $rawPrice * (int) $rfqItem->quantity;
                }

                if (! $hasPrice) {
                    continue;
                }

                $supplierTotals[] = [
                    'supplier_id' => (int) $quotation['supplier_id'],
                    'total_amount' => round($runningTotal, 2),
                ];
            }

            if (count($supplierTotals) === 0) {
                return redirect()->back()->with('error', 'Please enter at least one supplier quotation with unit prices.');
            }

            usort($supplierTotals, fn ($a, $b) => $a['total_amount'] <=> $b['total_amount']);
            $winnerSupplierId = $supplierTotals[0]['supplier_id'];

            // Reset previous RFQ quotation records so recreated AOQs don't reuse stale supplier items.
            $rfq->loadMissing('suppliers.supplierItems');
            foreach ($rfq->suppliers as $existingSupplier) {
                $existingSupplier->supplierItems()->delete();
            }
            $rfq->suppliers()->delete();

            foreach ($validated['quotations'] as $quotation) {
                $rfqSupplier = RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $quotation['supplier_id'],
                ]);

                $submittedAt = $quotation['submitted_at'] ?? null;
                $isLate = false;
                if ($submittedAt && $rfq->submission_deadline) {
                    $isLate = Carbon::parse($submittedAt)->greaterThan(Carbon::parse($rfq->submission_deadline)->endOfDay());
                }

                $rfqSupplier->update([
                    'submitted_at' => $submittedAt,
                    'is_late' => $isLate,
                    'remarks' => $quotation['remarks'] ?? null,
                ]);

                $rfqSupplier->supplierItems()->delete();

                $unitPrices = $quotation['unit_prices'] ?? [];
                foreach ($rfq->items as $rfqItem) {
                    $rawPrice = $unitPrices[$rfqItem->id] ?? null;

                    RFQSupplierItem::create([
                        'rfq_supplier_id' => $rfqSupplier->id,
                        'rfq_item_id' => $rfqItem->id,
                        'unit_price' => $rawPrice === '' ? null : $rawPrice,
                    ]);
                }
            }

            $aoq = AOQ::create([
                'rfq_id' => $rfq->id,
                'batch_id' => $validated['batch_id'] ?? null,
                'aoq_date' => $validated['aoq_date'],
                'winner_supplier_id' => $winnerSupplierId,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

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
        DB::transaction(function () use ($aoq): void {
            $aoq->loadMissing('rfq.suppliers.supplierItems');

            foreach ($aoq->rfq?->suppliers ?? [] as $supplier) {
                $supplier->supplierItems()->delete();
            }

            $aoq->rfq?->suppliers()->delete();
            $aoq->delete();
        });

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
            ->landscape()
            ->name('AOQ-'.($aoq->rfq?->svp_no ?? $aoq->id).'.pdf')
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
            $total = 0.0;
            $hasAtLeastOnePrice = false;

            foreach ($rfqSupplier->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price === null) {
                    continue;
                }

                $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
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

        usort($supplierTotals, fn ($a, $b) => $a['total_amount'] <=> $b['total_amount']);

        $count = count($supplierTotals);
        $winner = $count > 0 ? $supplierTotals[0] : null;

        $calculationMode = 'single_calculated';
        if ($count >= 2) {
            $calculationMode = 'lowest_calculated';
        }

        return [
            'supplier_totals' => $supplierTotals,
            'calculated_supplier_count' => $count,
            'calculation_mode' => $calculationMode,
            'winner_supplier_id' => $winner['supplier_id'] ?? null,
            'winner_total_amount' => $winner['total_amount'] ?? null,
        ];
    }

    private function isWorkingDay(?string $date): bool
    {
        if (! $date) {
            return true;
        }

        $calendarEntry = Calendar::whereDate('date', $date)->first();
        if ($calendarEntry) {
            return (bool) $calendarEntry->is_working_day;
        }

        return ! Carbon::parse($date)->isWeekend();
    }

    private function suggestNextWorkingDay(): Carbon
    {
        $date = now()->startOfDay();

        while (! $this->isWorkingDay($date->toDateString())) {
            $date->addDay();
        }

        return $date;
    }
}
