<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\NumberToWords;
use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\SvpMatrix;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PurchaseOrder::with([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.batch',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('po_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('noa', function ($noa) use ($search): void {
                        $noa->where('noa_no', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('noa.aoq.rfq', function ($rfq) use ($search): void {
                        $rfq->where('project_name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('svp_no', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('noa.bacResolution', function ($br) use ($search): void {
                        $br->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('project_name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->where(function ($officeQuery) use ($officeId): void {
                    $officeQuery
                        ->whereHas('noa.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId))
                        ->orWhereHas('noa.bacResolution.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
                });
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('po_date', $fiscalYear);
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->whereHas('noa.aoq', fn ($aoq) => $aoq->where('batch_id', $batchId));
            });

        $purchaseOrders = (clone $query)
            ->latest('po_date')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'this_month' => (clone $query)
                ->whereMonth('po_date', now()->month)
                ->whereYear('po_date', now()->year)
                ->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'stats' => $stats,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'batches' => $batches,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'fiscal_year' => $request->fiscal_year,
                'batch_id' => $request->batch_id,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $batchId = $request->query('batch_id');
        $noaId = $request->query('noa_id');

        $batches = Batch::withCount(['aoqs' => function ($q): void {
            $q->whereHas('noa')->whereDoesntHave('noa.purchaseOrder');
        }])
            ->whereHas('aoqs', fn ($q) => $q->whereHas('noa')->whereDoesntHave('noa.purchaseOrder'))
            ->latest()
            ->get(['id', 'batch_no', 'created_at']);

        $eligibleNoas = NOA::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items.purchaseRequestItem',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
            'aoq.winnerSupplier',
            'aoq.batch',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.rfq.items.purchaseRequestItem',
            'bacResolution.aoq.rfq.suppliers.supplier',
            'bacResolution.aoq.rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'bacResolution.aoq.winnerSupplier',
        ])
            ->whereDoesntHave('purchaseOrder')
            ->when($batchId, fn ($q) => $q->whereHas('aoq', fn ($aoq) => $aoq->where('batch_id', $batchId)))
            ->latest('noa_date')
            ->get()
            ->map(function (NOA $noa): NOA {
                $aoq = $noa->aoq ?? $noa->bacResolution?->aoq;
                $noa->setAttribute('_project_name', $aoq?->rfq?->project_name ?? '—');
                $noa->setAttribute('_winner_supplier_name', $aoq?->winnerSupplier?->name ?? '—');
                $noa->setAttribute('_office_name', $aoq?->rfq?->purchaseRequest?->office?->name ?? '—');

                return $noa;
            });

        $suggestedDate = $noaId
            ? Carbon::parse(NOA::findOrFail((int) $noaId)->noa_date)->addDay()->toDateString()
            : $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('PurchaseOrders/Create', [
            'batches' => $batches,
            'eligibleNoas' => $eligibleNoas,
            'selectedBatchId' => $batchId,
            'selectedNoaId' => $noaId,
            'defaults' => [
                'po_date' => $suggestedDate,
                'mode_of_procurement' => 'Small Value',
                'delivery_term_days' => 15,
                'payment_term' => 'upon 100% completion /delivery',
                'po_no' => $this->generatePoNumber($suggestedDate),
            ],
        ]);
    }

    public function suggestPoNo(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'po_date' => ['required', 'date'],
        ]);

        return response()->json([
            'po_no' => $this->generatePoNumber($validated['po_date']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $noa = NOA::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items.purchaseRequestItem',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
            'aoq.winnerSupplier',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.rfq.items.purchaseRequestItem',
            'bacResolution.aoq.rfq.suppliers.supplierItems.rfqItem',
            'bacResolution.aoq.winnerSupplier',
        ])->findOrFail($validated['noa_id']);

        $aoq = $noa->aoq ?? $noa->bacResolution?->aoq;
        $rfq = $aoq?->rfq;
        $winnerSupplierId = $aoq?->winner_supplier_id;

        if (! $rfq || ! $winnerSupplierId) {
            return redirect()->back()->withErrors([
                'noa_id' => 'Selected NOA has incomplete AOQ/RFQ data.',
            ]);
        }

        if ($noa->purchaseOrder()->exists()) {
            return redirect()->back()->withErrors([
                'noa_id' => 'A Purchase Order already exists for this NOA.',
            ]);
        }

        $allowedRfqItemIds = $rfq->items->pluck('id')->map(fn ($id) => (int) $id)->all();
        $submittedRfqItemIds = collect($validated['items'])->pluck('rfq_item_id')->map(fn ($id) => (int) $id)->all();
        $hasInvalidItems = collect($submittedRfqItemIds)->diff($allowedRfqItemIds)->isNotEmpty();
        $hasMissingItems = collect($allowedRfqItemIds)->diff($submittedRfqItemIds)->isNotEmpty();

        if ($hasInvalidItems || $hasMissingItems) {
            return redirect()->back()->withErrors([
                'items' => 'PO item set must exactly match the AOQ-awarded item list for the selected NOA.',
            ]);
        }

        $winnerQuote = $rfq->suppliers
            ->firstWhere('supplier_id', (int) $winnerSupplierId);

        if (! $winnerQuote) {
            return redirect()->back()->withErrors([
                'noa_id' => 'Unable to locate winner quotation details for this NOA.',
            ]);
        }

        $winnerSupplierItems = $winnerQuote->supplierItems
            ->keyBy(fn ($item) => (int) $item->rfq_item_id);

        $rfqItemsById = $rfq->items->keyBy(fn ($item) => (int) $item->id);

        $computedItems = collect($validated['items'])
            ->map(function (array $item) use ($winnerSupplierItems, $rfqItemsById): array {
                $rfqItemId = (int) $item['rfq_item_id'];
                $supplierItem = $winnerSupplierItems->get($rfqItemId);
                $rfqItem = $rfqItemsById->get($rfqItemId);

                $quantity = (int) ($rfqItem?->purchaseRequestItem?->quantity ?? 0);
                $unitCost = (float) ($supplierItem?->unit_price ?? 0);
                $amount = $quantity * $unitCost;

                return [
                    'rfq_item_id' => $rfqItemId,
                    'quantity_snapshot' => $quantity,
                    'unit_cost_snapshot' => $unitCost,
                    'amount_snapshot' => $amount,
                ];
            })
            ->values();

        $computedTotalAmount = (float) $computedItems->sum('amount_snapshot');
        $computedTotalAmountWords = $this->convertAmountToWords($computedTotalAmount);

        $officeName = $rfq->purchaseRequest?->office?->name;

        if (! $officeName) {
            return redirect()->back()->withErrors([
                'noa_id' => 'Unable to determine office/place of delivery from the selected NOA.',
            ]);
        }

        DB::beginTransaction();
        try {
            $purchaseOrder = PurchaseOrder::create([
                'noa_id' => $validated['noa_id'],
                'po_no' => $this->generatePoNumber($validated['po_date']),
                'po_date' => $validated['po_date'],
                'mode_of_procurement' => $validated['mode_of_procurement'],
                'place_of_delivery' => $officeName,
                'delivery_term_days' => $validated['delivery_term_days'] ?? 15,
                'payment_term' => $validated['payment_term'] ?? null,
                'total_amount' => $computedTotalAmount,
                'total_amount_words' => $computedTotalAmountWords,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($computedItems as $item) {
                $purchaseOrder->items()->create([
                    'rfq_item_id' => $item['rfq_item_id'],
                    'quantity_snapshot' => $item['quantity_snapshot'],
                    'unit_cost_snapshot' => $item['unit_cost_snapshot'],
                    'amount_snapshot' => $item['amount_snapshot'],
                ]);
            }

            SvpMatrix::query()->firstOrCreate(
                ['purchase_order_id' => $purchaseOrder->id],
                ['admin_value' => auth()->user()?->name],
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create Purchase Order. Please try again.');
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order created successfully.');
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem',
        ]);

        $aoq = $purchaseOrder->noa?->aoq ?? $purchaseOrder->noa?->bacResolution?->aoq;
        $abcAmount = (float) ($aoq?->rfq?->abc_amount ?? 0);

        $suggestedPoDate = $purchaseOrder->noa?->noa_date
            ? Carbon::parse($purchaseOrder->noa->noa_date)->addDay()->toDateString()
            : $this->suggestNextWorkingDay()->toDateString();
        $suggestedDeliveryDays = $abcAmount < 200000 ? 15 : 30;

        return Inertia::render('PurchaseOrders/Edit', [
            'purchaseOrder' => $purchaseOrder,
            'abcAmount' => $abcAmount,
            'defaults' => [
                'suggested_po_date' => $suggestedPoDate,
                'suggested_delivery_days' => $suggestedDeliveryDays,
            ],
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $purchaseOrder->update([
                'po_date' => $validated['po_date'],
                'mode_of_procurement' => $validated['mode_of_procurement'],
                'place_of_delivery' => $validated['place_of_delivery'],
                'delivery_term_days' => $validated['delivery_term_days'] ?? 15,
                'payment_term' => $validated['payment_term'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if (! empty($validated['items'])) {
                $totalAmount = 0;
                $existingItemIds = $purchaseOrder->items()->pluck('id');

                foreach ($validated['items'] as $itemData) {
                    $quantity = (int) ($itemData['quantity_snapshot'] ?? 0);
                    $unitCost = (float) ($itemData['unit_cost_snapshot'] ?? 0);
                    $amount = $quantity * $unitCost;
                    $totalAmount += $amount;

                    $purchaseOrder->items()->updateOrCreate(
                        ['rfq_item_id' => $itemData['rfq_item_id']],
                        [
                            'quantity_snapshot' => $quantity,
                            'unit_cost_snapshot' => $unitCost,
                            'amount_snapshot' => $amount,
                        ],
                    );
                }

                $purchaseOrder->update([
                    'total_amount' => $totalAmount,
                    'total_amount_words' => $this->convertAmountToWords($totalAmount),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update Purchase Order. Please try again.');
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order updated successfully.');
    }

    public function printBatch(Batch $batch)
    {
        $purchaseOrders = PurchaseOrder::with([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem',
        ])
            ->whereHas('noa.aoq', fn ($q) => $q->where('batch_id', $batch->id))
            ->get();

        if ($purchaseOrders->isEmpty()) {
            return redirect()->back()->with('error', 'No Purchase Orders found in this batch.');
        }

        $pdf = DomPdf::loadView('pdf.purchase-orders-batch', [
            'purchaseOrders' => $purchaseOrders,
            'batch' => $batch,
        ]);

        return $pdf->setPaper('a4')
            ->stream("POs-Batch-{$batch->batch_no}.pdf");
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        return Inertia::render('PurchaseOrders/Show', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase Order deleted successfully.');
    }

    public function printPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        $noa = $purchaseOrder->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;

        return Pdf::view('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'noa' => $noa,
            'resolution' => $noa?->bacResolution,
            'aoq' => $aoq,
            'rfq' => $aoq?->rfq,
            'winnerSupplier' => $aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('PO-'.$purchaseOrder->po_no.'.pdf')
            ->inline();
    }

    private function generatePoNumber(string $poDate): string
    {
        $date = Carbon::parse($poDate);
        $prefix = $date->format('my').'-';

        $currentYearSequenceMax = PurchaseOrder::query()
            ->whereYear('po_date', $date->year)
            ->pluck('po_no')
            ->map(function ($poNo): int {
                if (preg_match('/^\d{4}-(\d{4})$/', (string) $poNo, $matches) === 1) {
                    return (int) $matches[1];
                }

                return 0;
            })
            ->max();

        $next = ((int) $currentYearSequenceMax) + 1;

        do {
            $poNo = $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (PurchaseOrder::where('po_no', $poNo)->exists());

        return $poNo;
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

    private function convertAmountToWords(float $amount): string
    {
        return NumberToWords::convert($amount);
    }
}
