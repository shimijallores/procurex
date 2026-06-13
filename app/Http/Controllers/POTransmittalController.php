<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePOTransmittalRequest;
use App\Http\Requests\UpdatePOTransmittalRequest;
use App\Models\Batch;
use App\Models\Office;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
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
            'purchaseOrder.noa.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.aoq.batch',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.batch',
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
                        })
                        ->orWhereHas('purchaseOrder.noa.aoq.rfq', function ($rfq) use ($search): void {
                            $rfq->where('project_name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->where(function ($inner) use ($officeId): void {
                    $inner->whereHas('purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId))
                        ->orWhereHas('purchaseOrder.noa.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
                });
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->where(function ($inner) use ($batchId): void {
                    $inner->whereHas('purchaseOrder.noa.aoq', fn ($aoq) => $aoq->where('batch_id', $batchId))
                        ->orWhereHas('purchaseOrder.noa.bacResolution.aoq', fn ($aoq) => $aoq->where('batch_id', $batchId));
                });
            });

        $poTransmittals = (clone $query)->latest('created_at')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'with_opg_count' => (clone $query)->whereHas('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
            'missing_opg_count' => (clone $query)->whereDoesntHave('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);

        return Inertia::render('POTransmittals/Index', [
            'poTransmittals' => $poTransmittals,
            'stats' => $stats,
            'offices' => $offices,
            'batches' => $batches,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'batch_id' => $request->batch_id,
            ],
        ]);
    }

    public function create(): Response
    {
        $batches = Batch::with([
            'aoqs.rfq',
        ])
            ->whereHas('aoqs', function ($q): void {
                $q->whereHas('noa.purchaseOrder', function ($po): void {
                    $po->whereDoesntHave('poTransmittals');
                });
            })
            ->withCount(['aoqs' => function ($q): void {
                $q->whereHas('noa.purchaseOrder', function ($po): void {
                    $po->whereDoesntHave('poTransmittals');
                });
            }])
            ->latest()
            ->get();

        return Inertia::render('POTransmittals/Create', [
            'batches' => $batches,
        ]);
    }

    public function batchPurchaseOrders(Batch $batch): JsonResponse
    {
        $purchaseOrders = PurchaseOrder::with([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
        ])
            ->where(fn ($q) => $q
                ->whereHas('noa.aoq', fn ($aoq) => $aoq->where('batch_id', $batch->id))
                ->orWhereHas('noa.bacResolution.aoq', fn ($aoq) => $aoq->where('batch_id', $batch->id))
            )
            ->whereDoesntHave('poTransmittals')
            ->latest('po_date')
            ->get()
            ->map(function (PurchaseOrder $po): PurchaseOrder {
                $svpNo = $po->noa?->aoq?->rfq?->svp_no
                    ?? $po->noa?->bacResolution?->aoq?->rfq?->svp_no
                    ?? '';

                $po->setAttribute('_svp_no', $svpNo);
                $po->setAttribute('_coa_transmittal_no', $svpNo !== '' ? 'COA-'.$svpNo : '');
                $po->setAttribute('_opg_transmittal_no', $svpNo !== '' ? 'OPG-'.$svpNo : '');

                return $po;
            })
            ->values();

        return response()->json(['purchaseOrders' => $purchaseOrders]);
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
                'header_text' => $validated['coa']['header_text'] ?? null,
                'signatory_name' => $validated['coa']['signatory_name'] ?? null,
                'signatory_title' => $validated['coa']['signatory_title'] ?? null,
            ]);

            $opgTransmittal = POTransmittal::create([
                'purchase_order_id' => $purchaseOrderId,
                'type' => 'opg',
                'transmittal_no' => $validated['opg']['transmittal_no'] ?? null,
                'header_text' => $validated['opg']['header_text'] ?? null,
                'signatory_name' => $validated['opg']['signatory_name'] ?? null,
                'signatory_title' => $validated['opg']['signatory_title'] ?? null,
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
            'purchaseOrder.noa.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.aoq.winnerSupplier',
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
                'header_text' => $validated['coa']['header_text'] ?? null,
                'signatory_name' => $validated['coa']['signatory_name'] ?? null,
                'signatory_title' => $validated['coa']['signatory_title'] ?? null,
            ]);

            if ($opgTransmittal) {
                $opgTransmittal->update([
                    'transmittal_no' => $validated['opg']['transmittal_no'] ?? null,
                    'header_text' => $validated['opg']['header_text'] ?? null,
                    'signatory_name' => $validated['opg']['signatory_name'] ?? null,
                    'signatory_title' => $validated['opg']['signatory_title'] ?? null,
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
            'purchaseOrder.noa.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.aoq.winnerSupplier',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->get();

        $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');

        $purchaseOrder = $poTransmittal->purchaseOrder;
        $noa = $purchaseOrder?->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;

        return Pdf::view('pdf.po-transmittal-combined', [
            'coaTransmittal' => $coaTransmittal,
            'opgTransmittal' => $opgTransmittal,
            'purchaseOrder' => $purchaseOrder,
            'noa' => $noa,
            'resolution' => $noa?->bacResolution,
            'aoq' => $aoq,
            'rfq' => $aoq?->rfq,
            'winnerSupplier' => $aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('PO-Transmittal-'.($purchaseOrder?->po_no ?: $poTransmittal->id).'.pdf')
            ->inline();
    }
}
