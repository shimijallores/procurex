<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\SvpMatrix;
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
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('po_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('noa', function ($noa) use ($search): void {
                        $noa->where('noa_no', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('noa.bacResolution', function ($br) use ($search): void {
                        $br->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('project_name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('noa.bacResolution.aoq.rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('po_date', $fiscalYear);
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
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
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
        $eligibleNoas = NOA::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items.purchaseRequestItem',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
            'aoq.winnerSupplier',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.rfq.items.purchaseRequestItem',
            'bacResolution.aoq.rfq.suppliers.supplier',
            'bacResolution.aoq.rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'bacResolution.aoq.winnerSupplier',
        ])
            ->whereDoesntHave('purchaseOrder')
            ->latest('noa_date')
            ->get();

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('PurchaseOrders/Create', [
            'eligibleNoas' => $eligibleNoas,
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

        $aoq = $noa->aoq ?: $noa->bacResolution?->aoq;
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

        $allowedRfqItemIds = $rfq->items->pluck('id')->map(fn($id) => (int) $id)->all();
        $submittedRfqItemIds = collect($validated['items'])->pluck('rfq_item_id')->map(fn($id) => (int) $id)->all();
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
            ->keyBy(fn($item) => (int) $item->rfq_item_id);

        $rfqItemsById = $rfq->items->keyBy(fn($item) => (int) $item->id);

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

            SvpMatrix::query()->firstOrCreate([
                'purchase_order_id' => $purchaseOrder->id,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create Purchase Order. Please try again.');
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load([
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
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem.emanatingItem.ppmpItem',
        ]);

        return Pdf::view('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'noa' => $purchaseOrder->noa,
            'resolution' => $purchaseOrder->noa?->bacResolution,
            'aoq' => $purchaseOrder->noa?->bacResolution?->aoq,
            'rfq' => $purchaseOrder->noa?->bacResolution?->aoq?->rfq,
            'winnerSupplier' => $purchaseOrder->noa?->bacResolution?->aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('PO-' . $purchaseOrder->po_no . '.pdf')
            ->inline();
    }

    private function generatePoNumber(string $poDate): string
    {
        $date = Carbon::parse($poDate);
        $prefix = $date->format('my') . '-';

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
            $poNo = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
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
        $amount = max(0, round($amount, 2));

        $whole = (int) floor($amount);
        $cents = (int) round(($amount - $whole) * 100);

        $wholeWords = 'Zero';

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
            $wholeWords = ucfirst((string) $formatter->format($whole));
        }

        if ($cents > 0) {
            return sprintf('%s Pesos and %02d/100 Only', $wholeWords, $cents);
        }

        return sprintf('%s Pesos Only', $wholeWords);
    }
}
