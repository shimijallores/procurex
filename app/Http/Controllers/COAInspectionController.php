<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCOAInspectionRequest;
use App\Http\Requests\UpdateCOAInspectionRequest;
use App\Models\COAInspection;
use App\Models\Office;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class COAInspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = COAInspection::with([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('svp_header_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('bidding_header_text', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseOrder', function ($po) use ($search): void {
                            $po->where('po_no', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('purchaseOrder.noa.bacResolution', function ($resolution) use ($search): void {
                            $resolution->where('project_name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('created_at', $fiscalYear);
            });

        $coaInspections = (clone $query)->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'svp_filled' => (clone $query)->whereNotNull('svp_header_text')->count(),
            'bidding_filled' => (clone $query)->whereNotNull('bidding_header_text')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        return Inertia::render('COAInspections/Index', [
            'coaInspections' => $coaInspections,
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
        $purchaseOrders = PurchaseOrder::with([
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'items.rfqItem.purchaseRequestItem',
        ])
            ->whereDoesntHave('coaInspection')
            ->latest('po_date')
            ->get();

        return Inertia::render('COAInspections/Create', [
            'purchaseOrders' => $purchaseOrders,
            'defaults' => [
                'svp' => [
                    'header_text' => $this->defaultHeaderText('svp'),
                    'salutation' => "Ma'am:",
                ],
                'bidding' => [
                    'header_text' => $this->defaultHeaderText('bidding'),
                    'salutation' => 'Dear Governor Recto:',
                ],
                'signatory_name' => 'NOEL R. ROCAFORT',
                'signatory_title' => 'PGDH-GSO',
            ],
        ]);
    }

    public function store(StoreCOAInspectionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $coaInspection = COAInspection::create([
            'purchase_order_id' => $validated['purchase_order_id'],
            'svp_header_text' => $validated['svp']['header_text'] ?? null,
            'svp_salutation' => $validated['svp']['salutation'] ?? null,
            'bidding_header_text' => $validated['bidding']['header_text'] ?? null,
            'bidding_salutation' => $validated['bidding']['salutation'] ?? null,
            'signatory_name' => $validated['signatory_name'] ?? null,
            'signatory_title' => $validated['signatory_title'] ?? null,
        ]);

        return redirect()->route('coa-inspections.show', $coaInspection)
            ->with('success', 'COA Inspection (SVP + Bidding) created successfully.');
    }

    public function show(COAInspection $coaInspection): Response
    {
        $coaInspection->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.items.rfqItem.purchaseRequestItem',
        ]);

        return Inertia::render('COAInspections/Show', [
            'coaInspection' => $coaInspection,
        ]);
    }

    public function update(UpdateCOAInspectionRequest $request, COAInspection $coaInspection): RedirectResponse
    {
        $validated = $request->validated();

        $coaInspection->update([
            'purchase_order_id' => $validated['purchase_order_id'],
            'svp_header_text' => $validated['svp']['header_text'] ?? null,
            'svp_salutation' => $validated['svp']['salutation'] ?? null,
            'bidding_header_text' => $validated['bidding']['header_text'] ?? null,
            'bidding_salutation' => $validated['bidding']['salutation'] ?? null,
            'signatory_name' => $validated['signatory_name'] ?? null,
            'signatory_title' => $validated['signatory_title'] ?? null,
        ]);

        return redirect()->route('coa-inspections.show', $coaInspection)
            ->with('success', 'COA Inspection updated successfully.');
    }

    public function destroy(COAInspection $coaInspection): RedirectResponse
    {
        $coaInspection->delete();

        return redirect()->route('coa-inspections.index')
            ->with('success', 'COA Inspection deleted successfully.');
    }

    public function printPdf(COAInspection $coaInspection)
    {
        $coaInspection->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.items.rfqItem.purchaseRequestItem',
        ]);

        $purchaseOrder = $coaInspection->purchaseOrder;

        return Pdf::view('pdf.coa-inspection-combined', [
            'coaInspection' => $coaInspection,
            'purchaseOrder' => $purchaseOrder,
            'winnerSupplier' => $purchaseOrder?->noa?->bacResolution?->aoq?->winnerSupplier,
            'office' => $purchaseOrder?->noa?->bacResolution?->aoq?->rfq?->purchaseRequest?->office,
            'itemSummary' => $this->buildItemSummary($purchaseOrder),
        ])
            ->format('a4')
            ->name('COA-Inspection-'.($purchaseOrder?->po_no ?: $coaInspection->id).'.pdf')
            ->inline();
    }

    private function defaultHeaderText(string $type): string
    {
        if ($type === 'bidding') {
            return "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City";
        }

        return "MARIA VANESSA C. BRIONES - VEGAS\nOIC - SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City";
    }

    private function buildItemSummary(?PurchaseOrder $purchaseOrder): string
    {
        if (! $purchaseOrder) {
            return 'items';
        }

        $items = $purchaseOrder->items
            ->map(function ($item): string {
                $quantity = (float) ($item->quantity_snapshot ?? 0);
                $unit = trim((string) ($item->rfqItem?->purchaseRequestItem?->unit ?? ''));
                $name = trim((string) ($item->rfqItem?->purchaseRequestItem?->item_name ?? 'item'));

                $quantityText = rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');

                return trim($quantityText.' '.$unit.' of '.$name);
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            return 'items';
        }

        if ($items->count() === 1) {
            return $items->first();
        }

        $last = $items->pop();

        return $items->implode(', ').' and '.$last;
    }
}
