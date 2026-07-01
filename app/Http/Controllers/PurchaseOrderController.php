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
use App\Services\SvpMatrixSyncService;
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

        $lengthAwarePaginator = (clone $query)
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
            ->mapWithKeys(fn ($year): array => [$year => $year])
            ->reverse();

        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $lengthAwarePaginator,
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

        $batchNoas = [];

        if ($batchId) {
            $batch = Batch::find($batchId);

            if ($batch) {
                $suggestedDate = $batch->po_date?->toDateString() ?? $this->suggestNextWorkingDay()->toDateString();

                $prefix = Carbon::parse($suggestedDate)->format('my').'-';
                $nextSequence = PurchaseOrder::query()
                    ->whereYear('po_date', Carbon::parse($suggestedDate)->year)
                    ->pluck('po_no')
                    ->map(fn ($poNo): int => preg_match('/^\d{4}-(\d{4})$/', (string) $poNo, $m) === 1 ? (int) $m[1] : 0)
                    ->max() + 1;

                $noas = NOA::with([
                    'aoq.winnerSupplier',
                    'aoq.rfq.purchaseRequest.office',
                    'aoq.rfq.items.purchaseRequestItem',
                    'aoq.rfq.suppliers.supplier',
                    'aoq.rfq.suppliers.supplierItems',
                ])
                    ->whereDoesntHave('purchaseOrder')
                    ->whereHas('aoq', fn ($q) => $q->where('batch_id', $batchId))
                    ->latest('noa_date')
                    ->get();

                foreach ($noas as $noa) {
                    $aoq = $noa->aoq;
                    $rfq = $aoq?->rfq;
                    $winnerSupplierId = $aoq?->winner_supplier_id;
                    $winnerAmount = (float) ($noa->winner_amount ?? 0);

                    $winnerQuote = $rfq?->suppliers
                        ->firstWhere('supplier_id', (int) $winnerSupplierId);

                    $supplierName = $winnerQuote?->supplier?->name ?? '—';
                    $supplierAddress = $winnerQuote?->supplier?->address ?? '—';
                    $winnerSupplierItems = $winnerQuote?->supplierItems
                        ->keyBy(fn ($i): int => (int) $i->rfq_item_id) ?? collect();

                    $items = collect();
                    if ($rfq) {
                        $items = $rfq->items->map(function ($rfqItem) use ($winnerSupplierItems): array {
                            $supplierItem = $winnerSupplierItems->get((int) $rfqItem->id);
                            $quantity = (int) ($rfqItem->purchaseRequestItem?->quantity ?? 0);
                            $unitCost = (float) ($supplierItem?->unit_price ?? 0);

                            return [
                                'item_name' => $rfqItem->purchaseRequestItem?->item_name ?? '—',
                                'unit' => $rfqItem->purchaseRequestItem?->unit ?? '',
                                'quantity' => $quantity,
                                'unit_cost' => $unitCost,
                                'amount' => $quantity * $unitCost,
                            ];
                        });
                    }

                    $deliveryDays = $winnerAmount >= 200000 ? 30 : 15;
                    $purposeDate = $this->extractDateFromPurpose($rfq?->purchaseRequest?->purpose);
                    $purposeDateLabel = null;
                    if ($purposeDate instanceof \Illuminate\Support\Carbon) {
                        $diffDays = (int) Carbon::parse($suggestedDate)->diffInDays($purposeDate, false);
                        if ($diffDays >= 1 && $diffDays <= 365) {
                            $deliveryDays = $diffDays;
                            $purposeDateLabel = $purposeDate->format('F j, Y');
                        }
                    }

                    $batchNoas[] = [
                        'id' => $noa->id,
                        'noa_no' => $noa->noa_no,
                        'noa_date' => $noa->noa_date?->toDateString(),
                        'project_name' => $rfq?->project_name ?? '—',
                        'supplier_name' => $supplierName,
                        'supplier_address' => $supplierAddress,
                        'office_name' => $rfq?->purchaseRequest?->office?->name ?? '—',
                        'pr_no' => $rfq?->purchaseRequest?->pr_no ?? '',
                        'winner_amount' => $winnerAmount,
                        'items' => $items->values()->all(),
                        'suggested_po_no' => $prefix.str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT),
                        'suggested_delivery_days' => $deliveryDays,
                        'purpose_date_label' => $purposeDateLabel,
                    ];

                    ++$nextSequence;
                }
            }
        }

        return Inertia::render('PurchaseOrders/Create', [
            'batchNoas' => $batchNoas,
            'batchId' => $batchId,
            'defaults' => [
                'po_date' => $suggestedDate ?? $this->suggestNextWorkingDay()->toDateString(),
                'mode_of_procurement' => 'Small Value',
                'payment_term' => 'upon 100% completion /delivery',
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

    public function store(StorePurchaseOrderRequest $storePurchaseOrderRequest): RedirectResponse
    {
        $validated = $storePurchaseOrderRequest->validated();
        $noaEntries = $validated['noas'];

        $noaIds = collect($noaEntries)->pluck('noa_id')->all();

        $noas = NOA::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items.purchaseRequestItem',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
            'aoq.winnerSupplier',
        ])
            ->whereIn('id', $noaIds)
            ->whereDoesntHave('purchaseOrder')
            ->get()
            ->keyBy('id');

        $created = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($noaEntries as $noaEntry) {
                $noa = $noas->get($noaEntry['noa_id']);

                if (! $noa) {
                    $errors[] = sprintf('NOA ID %s not found or already has a PO.', $noaEntry['noa_id']);

                    continue;
                }

                $aoq = $noa->aoq;
                $rfq = $aoq?->rfq;
                $winnerSupplierId = $aoq?->winner_supplier_id;

                if (! $rfq || ! $winnerSupplierId) {
                    $errors[] = sprintf('NOA %s has incomplete AOQ/RFQ data.', $noa->noa_no);

                    continue;
                }

                $winnerQuote = $rfq->suppliers
                    ->firstWhere('supplier_id', (int) $winnerSupplierId);

                if (! $winnerQuote) {
                    $errors[] = sprintf('Unable to locate winner quotation for NOA %s.', $noa->noa_no);

                    continue;
                }

                $winnerSupplierItems = $winnerQuote->supplierItems
                    ->keyBy(fn ($item): int => (int) $item->rfq_item_id);

                $computedItems = $rfq->items->map(function ($rfqItem) use ($winnerSupplierItems): array {
                    $rfqItemId = (int) $rfqItem->id;
                    $supplierItem = $winnerSupplierItems->get($rfqItemId);

                    $quantity = (int) ($rfqItem->purchaseRequestItem?->quantity ?? 0);
                    $unitCost = (float) ($supplierItem?->unit_price ?? 0);

                    return [
                        'rfq_item_id' => $rfqItemId,
                        'quantity_snapshot' => $quantity,
                        'unit_cost_snapshot' => $unitCost,
                        'amount_snapshot' => $quantity * $unitCost,
                    ];
                });

                $totalAmount = (float) $computedItems->sum('amount_snapshot');
                $officeName = $rfq->purchaseRequest?->office?->name ?? '';

                $poNo = (string) ($noaEntry['po_no'] ?? '');
                if ($poNo === '') {
                    $poNo = $this->generatePoNumber($noaEntry['po_date']);
                }
                if (PurchaseOrder::where('po_no', $poNo)->where('noa_id', '!=', $noa->id)->exists()) {
                    $errors[] = sprintf('PO number "%s" is already in use.', $poNo);

                    continue;
                }

                $po = PurchaseOrder::create([
                    'noa_id' => $noa->id,
                    'po_no' => $poNo,
                    'po_date' => $noaEntry['po_date'],
                    'mode_of_procurement' => $noaEntry['mode_of_procurement'],
                    'place_of_delivery' => $noaEntry['place_of_delivery'] ?: $officeName,
                    'delivery_term_days' => $noaEntry['delivery_term_days'] ?? 15,
                    'payment_term' => $noaEntry['payment_term'] ?? null,
                    'total_amount' => $totalAmount,
                    'total_amount_words' => NumberToWords::convert($totalAmount),
                    'remarks' => $noaEntry['remarks'] ?? null,
                ]);

                foreach ($computedItems as $computedItem) {
                    $po->items()->create($computedItem);
                }

                SvpMatrixSyncService::createOrSyncFromPo($po);

                $created[] = $po;
            }

            if ($errors !== []) {
                DB::rollBack();

                return redirect()->back()->withErrors([
                    'noas' => implode(' ', $errors),
                ]);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create Purchase Orders. Please try again.');
        }

        $batchId = null;
        $firstPo = $created[0] ?? null;
        if ($firstPo) {
            $firstPo->load('noa.aoq');
            $batchId = $firstPo->noa?->aoq?->batch_id;
        }

        $redirectResponse = redirect()->route('purchase-orders.index')
            ->with('success', count($created).' Purchase Order(s) created successfully.');

        if ($batchId) {
            $redirectResponse->with('print_batch_id', $batchId);
        }

        return $redirectResponse;
    }

    public function recentPos(): \Illuminate\Http\JsonResponse
    {
        $pos = PurchaseOrder::query()
            ->whereNotNull('po_no')
            ->latest()
            ->take(5)
            ->pluck('po_no');

        return response()->json(['pos' => $pos]);
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

    public function update(UpdatePurchaseOrderRequest $updatePurchaseOrderRequest, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $updatePurchaseOrderRequest->validated();

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
        } catch (\Throwable $throwable) {
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

        return $pdf->setPaper('legal')
            ->stream(sprintf('POs-Batch-%s.pdf', $batch->batch_no));
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

    public function printPdf(PurchaseOrder $purchaseOrder): \Spatie\LaravelPdf\PdfBuilder
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
            ++$next;
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

    private function extractDateFromPurpose(?string $purpose): ?Carbon
    {
        if (! $purpose) {
            return null;
        }

        $patterns = [
            '/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{1,2},?\s+\d{4}\b/i',
            '/\b\d{1,2}\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}\b/i',
            '/\b\d{4}-\d{2}-\d{2}\b/',
            '/\b\d{1,2}\/\d{1,2}\/\d{4}\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $purpose, $matches) === 1) {
                try {
                    $date = Carbon::parse($matches[0]);

                    if ($date->isFuture()) {
                        return $date;
                    }
                } catch (\Exception) {
                    continue;
                }
            }
        }

        return null;
    }
}
