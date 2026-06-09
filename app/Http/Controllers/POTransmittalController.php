<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePOTransmittalRequest;
use App\Http\Requests\UpdatePOTransmittalRequest;
use App\Models\Calendar;
use App\Models\Office;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'purchaseOrder.poTransmittals',
        ])
            ->where('type', 'coa')
            ->when($request->search, function ($q, string $search): void {
                $q->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('transmittal_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseOrder', function ($po) use ($search): void {
                            $po->where('po_no', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('purchaseOrder.poTransmittals', function ($transmittalQuery) use ($search): void {
                            $transmittalQuery->where('transmittal_no', 'like', sprintf('%%%s%%', $search));
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
                $q->whereYear('transmittal_date', $fiscalYear);
            });

        $poTransmittals = (clone $query)->latest('transmittal_date')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'with_opg_count' => (clone $query)->whereHas('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
            'missing_opg_count' => (clone $query)->whereDoesntHave('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        return Inertia::render('POTransmittals/Index', [
            'poTransmittals' => $poTransmittals,
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
        ])->latest('po_date')->get();

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('POTransmittals/Create', [
            'purchaseOrders' => $purchaseOrders,
            'defaults' => [
                'transmittal_date' => $suggestedDate,
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
        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->orderByRaw("case when type = 'coa' then 1 else 2 end")
            ->get();

        $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');

        $coaTransmittal->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        return Inertia::render('POTransmittals/Show', [
            'poTransmittal' => $coaTransmittal,
            'coaTransmittal' => $coaTransmittal,
            'opgTransmittal' => $opgTransmittal,
            'relatedTransmittals' => $relatedTransmittals->map(fn (POTransmittal $entry): array => [
                'id' => $entry->id,
                'type' => $entry->type,
                'transmittal_no' => $entry->transmittal_no,
            ])->values(),
        ]);
    }

    public function update(UpdatePOTransmittalRequest $request, POTransmittal $poTransmittal): RedirectResponse
    {
        $validated = $request->validated();

        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->get();

        $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');

        DB::transaction(function () use ($validated, $coaTransmittal, $opgTransmittal): void {
            $coaTransmittal->update([
                'transmittal_no' => $validated['coa']['transmittal_no'] ?? null,
                'transmittal_date' => $validated['coa']['transmittal_date'],
                'header_text' => $validated['coa']['header_text'] ?? null,
                'signatory_name' => $validated['coa']['signatory_name'] ?? null,
                'signatory_title' => $validated['coa']['signatory_title'] ?? null,
                'coa_circular_no' => $validated['coa']['coa_circular_no'] ?? null,
            ]);

            if ($opgTransmittal) {
                $opgTransmittal->update([
                    'transmittal_no' => $validated['opg']['transmittal_no'] ?? null,
                    'transmittal_date' => $validated['opg']['transmittal_date'],
                    'header_text' => $validated['opg']['header_text'] ?? null,
                    'signatory_name' => $validated['opg']['signatory_name'] ?? null,
                    'signatory_title' => $validated['opg']['signatory_title'] ?? null,
                    'coa_circular_no' => $validated['opg']['coa_circular_no'] ?? null,
                ]);
            }
        });

        return redirect()->route('po-transmittals.show', $coaTransmittal)
            ->with('success', 'PO Transmittal updated successfully.');
    }

    public function destroy(POTransmittal $poTransmittal): RedirectResponse
    {
        POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->delete();

        return redirect()->route('po-transmittals.index')
            ->with('success', 'PO Transmittal deleted successfully.');
    }

    public function printPdf(POTransmittal $poTransmittal)
    {
        $poTransmittal->load([
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->get();

        $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');

        return Pdf::view('pdf.po-transmittal-combined', [
            'coaTransmittal' => $coaTransmittal,
            'opgTransmittal' => $opgTransmittal,
            'purchaseOrder' => $poTransmittal->purchaseOrder,
            'noa' => $poTransmittal->purchaseOrder?->noa,
            'resolution' => $poTransmittal->purchaseOrder?->noa?->bacResolution,
            'aoq' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq,
            'rfq' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq?->rfq,
            'winnerSupplier' => $poTransmittal->purchaseOrder?->noa?->bacResolution?->aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('PO-Transmittal-'.($poTransmittal->purchaseOrder?->po_no ?: $poTransmittal->id).'.pdf')
            ->inline();
    }

    private function defaultHeaderText(string $type): string
    {
        if ($type === 'opg') {
            return "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa’am,";
        }

        return "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa’am,";
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
