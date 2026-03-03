<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRFQRequest;
use App\Http\Requests\StoreRFQSupplierSubmissionRequest;
use App\Models\PurchaseRequest;
use App\Models\RFQ;
use App\Models\RFQItem;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use NumberFormatter;
use Spatie\LaravelPdf\Facades\Pdf;

class RFQController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RFQ::with([
            'purchaseRequest.office',
            'purchaseRequest.earmark',
            'suppliers.supplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('svp_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('purchaseRequest', function ($pr) use ($search): void {
                        $pr->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('office', fn($o) => $o->where('name', 'like', sprintf('%%%s%%', $search)));
                    })
                    ->orWhereHas('suppliers.supplier', fn($s) => $s->where('name', 'like', sprintf('%%%s%%', $search)));
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('rfq_date', $fiscalYear);
            });

        $rfqs = (clone $query)->latest('rfq_date')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'open' => (clone $query)
                ->where(function ($q): void {
                    $q->whereNull('submission_deadline')->orWhereDate('submission_deadline', '>=', now()->toDateString());
                })->count(),
            'with_late_submission' => RFQSupplier::where('is_late', true)->distinct('rfq_id')->count('rfq_id'),
            'submitted_supplier_count' => RFQSupplier::whereNotNull('submitted_at')->count(),
        ];

        $offices = \App\Models\Office::orderBy('name')->get(['id', 'name']);

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('RFQs/Index', [
            'rfqs' => $rfqs,
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
        $eligiblePurchaseRequests = PurchaseRequest::with([
            'office',
            'fund',
            'emanating.project',
            'items.emanatingItem.ppmpItem',
            'earmark',
        ])
            ->where('status', 'approved')
            ->whereHas('earmark')
            ->whereDoesntHave('rfq')
            ->latest()
            ->get();

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'contact_number']);

        return Inertia::render('RFQs/Create', [
            'eligiblePurchaseRequests' => $eligiblePurchaseRequests,
            'suppliers' => $suppliers,
            'defaultRfqDate' => now()->toDateString(),
            'defaultSubmissionDeadline' => now()->addWeek()->toDateString(),
        ]);
    }

    public function store(StoreRFQRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $purchaseRequest = PurchaseRequest::with([
                'earmark',
                'items',
                'emanating.project',
            ])->findOrFail($validated['pr_id']);

            if ($purchaseRequest->status !== 'approved' || ! $purchaseRequest->earmark) {
                return redirect()->back()->with('error', 'Only approved Purchase Requests with issued Earmarks can create an RFQ.');
            }

            if ($purchaseRequest->rfq()->exists()) {
                return redirect()->back()->with('error', 'An RFQ already exists for this Purchase Request.');
            }

            $rfqDate = Carbon::parse($validated['rfq_date']);
            $submissionDeadline = ! empty($validated['submission_deadline'])
                ? Carbon::parse($validated['submission_deadline'])
                : $rfqDate->copy()->addWeek();

            $rfq = RFQ::create([
                'pr_id' => $purchaseRequest->id,
                'svp_no' => $this->generateSvpNo($rfqDate),
                'rfq_date' => $rfqDate->toDateString(),
                'submission_deadline' => $submissionDeadline->toDateString(),
                'project_name' => $purchaseRequest->emanating?->project?->name
                    ?? ($purchaseRequest->purpose ? mb_strimwidth($purchaseRequest->purpose, 0, 200, '...') : 'N/A'),
                'abc_amount' => (float) ($purchaseRequest->earmark->certified_amount ?? $purchaseRequest->total_amount),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $rfqItems = [];
            foreach ($purchaseRequest->items as $prItem) {
                $rfqItems[] = RFQItem::create([
                    'rfq_id' => $rfq->id,
                    'pr_item_id' => $prItem->id,
                ]);
            }

            foreach ($validated['supplier_ids'] as $supplierId) {
                $rfqSupplier = RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $supplierId,
                ]);

                foreach ($rfqItems as $rfqItem) {
                    RFQSupplierItem::create([
                        'rfq_supplier_id' => $rfqSupplier->id,
                        'rfq_item_id' => $rfqItem->id,
                        'unit_price' => null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create RFQ. Please try again.');
        }

        return redirect()->route('rfqs.show', $rfq)
            ->with('success', 'RFQ created successfully. SVP number was generated automatically.');
    }

    public function show(RFQ $rfq): Response
    {
        $rfq->load([
            'purchaseRequest.office',
            'purchaseRequest.fund',
            'purchaseRequest.earmark',
            'purchaseRequest.emanating.project',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        return Inertia::render('RFQs/Show', [
            'rfq' => $rfq,
        ]);
    }

    public function destroy(RFQ $rfq): RedirectResponse
    {
        $rfq->delete();

        return redirect()->route('rfqs.index')->with('success', 'RFQ deleted successfully.');
    }

    public function submitSupplier(StoreRFQSupplierSubmissionRequest $request, RFQ $rfq, RFQSupplier $rfqSupplier): RedirectResponse
    {
        if ($rfqSupplier->rfq_id !== $rfq->id) {
            return redirect()->back()->with('error', 'Invalid RFQ supplier record.');
        }

        $validated = $request->validated();
        $submittedAt = Carbon::parse($validated['submitted_at']);

        $isLate = false;
        if ($rfq->submission_deadline) {
            $deadline = Carbon::parse($rfq->submission_deadline)->endOfDay();
            $isLate = $submittedAt->greaterThan($deadline);
        }

        DB::beginTransaction();
        try {
            $rfqSupplier->update([
                'submitted_at' => $submittedAt,
                'is_late' => $isLate,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $unitPrices = $validated['unit_prices'] ?? [];
            if (! empty($unitPrices)) {
                $supplierItems = $rfqSupplier->supplierItems()->get()->keyBy('rfq_item_id');
                foreach ($unitPrices as $rfqItemId => $unitPrice) {
                    if (! isset($supplierItems[$rfqItemId])) {
                        continue;
                    }

                    $supplierItems[$rfqItemId]->update([
                        'unit_price' => $unitPrice === null || $unitPrice === '' ? null : $unitPrice,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to save supplier submission.');
        }

        $message = $isLate
            ? 'Supplier submission saved and tagged as late filing (subject for RFQ Admin/Superadmin approval).'
            : 'Supplier submission saved successfully.';

        return redirect()->route('rfqs.show', $rfq)->with('success', $message);
    }

    public function printPdf(Request $request, RFQ $rfq)
    {
        $rfq->load([
            'purchaseRequest.office',
            'items.purchaseRequestItem.emanatingItem.ppmpItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        $selectedSupplierId = $request->integer('supplier_id');

        $supplierEntry = $rfq->suppliers
            ->when($selectedSupplierId, fn($collection) => $collection->where('supplier_id', $selectedSupplierId))
            ->first();

        if (! $supplierEntry) {
            return redirect()->back()->with('error', 'No supplier record found for this RFQ.');
        }

        $supplierEntry->load([
            'supplier',
            'supplierItems.rfqItem.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        $totalQuotedAmount = $supplierEntry->supplierItems->sum(function (RFQSupplierItem $item): float {
            $quantity = (int) ($item->rfqItem?->purchaseRequestItem?->quantity ?? 0);
            $unitPrice = (float) ($item->unit_price ?? 0);

            return $quantity * $unitPrice;
        });

        return Pdf::view('pdf.rfq', [
            'rfq' => $rfq,
            'supplierEntry' => $supplierEntry,
            'totalQuotedAmount' => $totalQuotedAmount,
            'totalAmountInWords' => $this->amountInWords($totalQuotedAmount),
        ])
            ->format('a4')
            ->name('RFQ-' . $rfq->svp_no . '.pdf')
            ->inline();
    }

    private function generateSvpNo(Carbon $rfqDate): string
    {
        $year = $rfqDate->format('Y');
        $prefix = 'SVP-' . $year . '-';

        $latest = RFQ::query()
            ->where('svp_no', 'like', $prefix . '%')
            ->orderByDesc('svp_no')
            ->value('svp_no');

        $next = 1;
        if ($latest && preg_match('/^SVP-\d{4}-(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        do {
            $svpNo = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (RFQ::where('svp_no', $svpNo)->exists());

        return $svpNo;
    }

    private function amountInWords(float $amount): string
    {
        if ($amount <= 0) {
            return 'Zero Pesos Only';
        }

        if (class_exists(NumberFormatter::class)) {
            $whole = (int) floor($amount);
            $cents = (int) round(($amount - $whole) * 100);

            $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);
            $words = ucwords((string) $formatter->format($whole)) . ' Pesos';

            if ($cents > 0) {
                $words .= ' and ' . str_pad((string) $cents, 2, '0', STR_PAD_LEFT) . '/100';
            }

            return $words . ' Only';
        }

        return number_format($amount, 2) . ' Pesos Only';
    }
}
