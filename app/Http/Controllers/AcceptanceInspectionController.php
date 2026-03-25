<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcceptanceInspectionRequest;
use App\Http\Requests\UpdateAcceptanceInspectionRequest;
use App\Models\AcceptanceInspection;
use App\Models\Office;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class AcceptanceInspectionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AcceptanceInspection::with([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('air_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('invoice_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseOrder', function ($po) use ($search): void {
                            $po->where('po_no', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('purchaseOrder.noa.bacResolution', function ($resolution) use ($search): void {
                            $resolution->where('project_name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('created_at', $fiscalYear);
            });

        $acceptanceInspections = (clone $query)->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'complete' => (clone $query)->where('acceptance_status', 'complete')->count(),
            'partial' => (clone $query)->where('acceptance_status', 'partial')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('AcceptanceInspections/Index', [
            'acceptanceInspections' => $acceptanceInspections,
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
            ->whereDoesntHave('acceptanceInspection')
            ->latest('po_date')
            ->get();

        return Inertia::render('AcceptanceInspections/Create', [
            'purchaseOrders' => $purchaseOrders,
            'defaults' => [
                'acceptance_date_received' => now()->toDateString(),
                'inspection_date_inspected' => now()->toDateString(),
                'inspection_findings_text' => null,
                'inspection_status_ok' => null,
                'property_officer_title' => 'Property Officer',
                'inspection_officer_title' => 'Inspection Officer/Committee Officer',
            ],
        ]);
    }

    public function store(StoreAcceptanceInspectionRequest $request): RedirectResponse
    {
        $acceptanceInspection = AcceptanceInspection::create($request->validated());

        return redirect()->route('acceptance-inspections.show', $acceptanceInspection)
            ->with('success', 'Acceptance & Inspection report created successfully.');
    }

    public function show(AcceptanceInspection $acceptanceInspection): Response
    {
        $acceptanceInspection->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.items.rfqItem.purchaseRequestItem',
        ]);

        return Inertia::render('AcceptanceInspections/Show', [
            'acceptanceInspection' => $acceptanceInspection,
        ]);
    }

    public function update(UpdateAcceptanceInspectionRequest $request, AcceptanceInspection $acceptanceInspection): RedirectResponse
    {
        $acceptanceInspection->update($request->validated());

        return redirect()->route('acceptance-inspections.show', $acceptanceInspection)
            ->with('success', 'Acceptance & Inspection report updated successfully.');
    }

    public function destroy(AcceptanceInspection $acceptanceInspection): RedirectResponse
    {
        $acceptanceInspection->delete();

        return redirect()->route('acceptance-inspections.index')
            ->with('success', 'Acceptance & Inspection report deleted successfully.');
    }

    public function printPdf(AcceptanceInspection $acceptanceInspection)
    {
        $acceptanceInspection->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
            'purchaseOrder.items.rfqItem.purchaseRequestItem',
        ]);

        return Pdf::view('pdf.acceptance-inspection', [
            'acceptanceInspection' => $acceptanceInspection,
            'purchaseOrder' => $acceptanceInspection->purchaseOrder,
            'winnerSupplier' => $acceptanceInspection->purchaseOrder?->noa?->bacResolution?->aoq?->winnerSupplier,
            'office' => $acceptanceInspection->purchaseOrder?->noa?->bacResolution?->aoq?->rfq?->purchaseRequest?->office,
        ])
            ->format('a4')
            ->name('Acceptance-Inspection-' . ($acceptanceInspection->purchaseOrder?->po_no ?: $acceptanceInspection->id) . '.pdf')
            ->inline();
    }
}
