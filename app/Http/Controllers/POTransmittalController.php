<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePOTransmittalRequest;
use App\Http\Requests\UpdatePOTransmittalRequest;
use App\Models\Office;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class POTransmittalController extends Controller
{
    public function index(Request $request): Response
    {
        $query = POTransmittal::with([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('transmittal_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('purchaseOrder', function ($po) use ($search): void {
                        $po->where('po_no', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('purchaseOrder.noa.bacResolution', function ($resolution) use ($search): void {
                        $resolution->where('project_name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->type, fn($q, string $type) => $q->where('type', $type))
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('transmittal_date', $fiscalYear);
            });

        $poTransmittals = (clone $query)->latest('transmittal_date')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'coa_count' => (clone $query)->where('type', 'coa')->count(),
            'opg_count' => (clone $query)->where('type', 'opg')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('POTransmittals/Index', [
            'poTransmittals' => $poTransmittals,
            'stats' => $stats,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'type' => $request->type,
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
        ])->latest('po_date')->get();

        return Inertia::render('POTransmittals/Create', [
            'purchaseOrders' => $purchaseOrders,
            'defaults' => [
                'transmittal_date' => now()->toDateString(),
                'coa' => [
                    'type' => 'coa',
                    'header_text' => $this->defaultHeaderText('coa'),
                    'signatory_name' => 'NOEL R. ROCAFORT',
                    'signatory_title' => 'PGDH – GSO',
                    'coa_circular_no' => '',
                ],
                'opg' => [
                    'type' => 'opg',
                    'header_text' => $this->defaultHeaderText('opg'),
                    'signatory_name' => 'NOEL R. ROCAFORT',
                    'signatory_title' => 'PGDH – GSO',
                    'coa_circular_no' => '',
                ],
            ],
        ]);
    }

    public function store(StorePOTransmittalRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $purchaseOrderId = (int) $validated['purchase_order_id'];

        $existingTypes = POTransmittal::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->pluck('type')
            ->all();

        if (in_array('coa', $existingTypes, true) || in_array('opg', $existingTypes, true)) {
            return redirect()->back()->withErrors([
                'purchase_order_id' => 'COA and OPG transmittals already exist for this Purchase Order.',
            ])->withInput();
        }

        DB::beginTransaction();

        try {
            $coaTransmittal = POTransmittal::create([
                'purchase_order_id' => $purchaseOrderId,
                'type' => 'coa',
                'transmittal_no' => $validated['coa']['transmittal_no'] ?? null,
                'transmittal_date' => $validated['coa']['transmittal_date'],
                'header_text' => $validated['coa']['header_text'] ?? null,
                'signatory_name' => $validated['coa']['signatory_name'] ?? null,
                'signatory_title' => $validated['coa']['signatory_title'] ?? null,
                'coa_circular_no' => $validated['coa']['coa_circular_no'] ?? null,
            ]);

            $opgTransmittal = POTransmittal::create([
                'purchase_order_id' => $purchaseOrderId,
                'type' => 'opg',
                'transmittal_no' => $validated['opg']['transmittal_no'] ?? null,
                'transmittal_date' => $validated['opg']['transmittal_date'],
                'header_text' => $validated['opg']['header_text'] ?? null,
                'signatory_name' => $validated['opg']['signatory_name'] ?? null,
                'signatory_title' => $validated['opg']['signatory_title'] ?? null,
                'coa_circular_no' => $validated['opg']['coa_circular_no'] ?? null,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create PO Transmittals. Please try again.');
        }

        return redirect()->route('po-transmittals.show', $coaTransmittal)
            ->with('success', 'COA and OPG PO Transmittals created successfully.');
    }

    public function show(POTransmittal $poTransmittal): Response
    {
        $poTransmittal->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->orderByRaw("case when type = 'coa' then 1 else 2 end")
            ->get(['id', 'type', 'transmittal_no']);

        return Inertia::render('POTransmittals/Show', [
            'poTransmittal' => $poTransmittal,
            'relatedTransmittals' => $relatedTransmittals,
        ]);
    }

    public function update(UpdatePOTransmittalRequest $request, POTransmittal $poTransmittal): RedirectResponse
    {
        $poTransmittal->update($request->validated());

        return redirect()->route('po-transmittals.show', $poTransmittal)
            ->with('success', 'PO Transmittal updated successfully.');
    }

    public function destroy(POTransmittal $poTransmittal): RedirectResponse
    {
        $poTransmittal->delete();

        return redirect()->route('po-transmittals.index')
            ->with('success', 'PO Transmittal deleted successfully.');
    }

    public function printPdf(POTransmittal $poTransmittal)
    {
        $poTransmittal->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        $view = $poTransmittal->type === 'opg'
            ? 'pdf.po-transmittal-opg'
            : 'pdf.po-transmittal-coa';

        return Pdf::view($view, [
            'poTransmittal' => $poTransmittal,
            'purchaseOrder' => $poTransmittal->purchaseOrder,
            'noa' => $poTransmittal->purchaseOrder?->noa,
            'resolution' => $poTransmittal->purchaseOrder?->noa?->bacResolution,
            'aoq' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq,
            'rfq' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq?->rfq,
            'winnerSupplier' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('PO-Transmittal-' . ($poTransmittal->transmittal_no ?: $poTransmittal->id) . '.pdf')
            ->inline();
    }

    private function defaultHeaderText(string $type): string
    {
        if ($type === 'opg') {
            return "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa’am,";
        }

        return "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa’am,";
    }
}
