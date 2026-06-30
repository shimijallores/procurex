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

        $lengthAwarePaginator = (clone $query)->latest('created_at')->paginate(10)->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'with_opg_count' => (clone $query)->whereHas('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
            'missing_opg_count' => (clone $query)->whereDoesntHave('purchaseOrder.poTransmittals', fn ($q) => $q->where('type', 'opg'))->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);

        return Inertia::render('POTransmittals/Index', [
            'poTransmittals' => $lengthAwarePaginator,
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
        return Inertia::render('POTransmittals/Create');
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
            ->map(function (PurchaseOrder $purchaseOrder): PurchaseOrder {
                $svpNo = $purchaseOrder->noa?->aoq?->rfq?->svp_no
                    ?? $purchaseOrder->noa?->bacResolution?->aoq?->rfq?->svp_no
                    ?? '';

                $purchaseOrder->setAttribute('_svp_no', $svpNo);
                $purchaseOrder->setAttribute('_coa_transmittal_no', $svpNo !== '' ? 'COA-'.$svpNo : '');
                $purchaseOrder->setAttribute('_opg_transmittal_no', $svpNo !== '' ? 'OPG-'.$svpNo : '');

                return $purchaseOrder;
            })
            ->values();

        return response()->json(['purchaseOrders' => $purchaseOrders]);
    }

    public function store(StorePOTransmittalRequest $storePOTransmittalRequest): RedirectResponse
    {
        $validated = $storePOTransmittalRequest->validated();

        $errors = [];
        $created = [];

        DB::beginTransaction();
        try {
            foreach ($validated['purchase_orders'] as $poData) {
                $purchaseOrderId = (int) $poData['id'];

                $existingTypes = POTransmittal::query()
                    ->where('purchase_order_id', $purchaseOrderId)
                    ->pluck('type')
                    ->all();

                if (in_array('coa', $existingTypes, true) && in_array('opg', $existingTypes, true)) {
                    continue;
                }

                if (! in_array('coa', $existingTypes, true)) {
                    POTransmittal::create([
                        'purchase_order_id' => $purchaseOrderId,
                        'type' => 'coa',
                        'transmittal_no' => $poData['coa']['transmittal_no'] ?? null,
                        'header_text' => $poData['coa']['header_text'] ?? null,
                        'signatory_name' => $poData['coa']['signatory_name'] ?? null,
                        'signatory_title' => $poData['coa']['signatory_title'] ?? null,
                    ]);
                }

                if (! in_array('opg', $existingTypes, true)) {
                    POTransmittal::create([
                        'purchase_order_id' => $purchaseOrderId,
                        'type' => 'opg',
                        'transmittal_no' => $poData['opg']['transmittal_no'] ?? null,
                        'header_text' => $poData['opg']['header_text'] ?? null,
                        'signatory_name' => $poData['opg']['signatory_name'] ?? null,
                        'signatory_title' => $poData['opg']['signatory_title'] ?? null,
                    ]);
                }

                $created[] = $purchaseOrderId;
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create PO Transmittals. Please try again.');
        }

        if (empty($created)) {
            return redirect()->back()->with('info', 'All selected POs already have transmittals.');
        }

        $firstPo = PurchaseOrder::with('noa.aoq.batch', 'noa.bacResolution.aoq.batch')->find($created[0]);
        $batch = $firstPo?->noa?->aoq?->batch ?? $firstPo?->noa?->bacResolution?->aoq?->batch;

        if (! $batch) {
            return redirect()->back()->with('error', 'Could not find batch for created transmittals.');
        }

        return redirect()->route('po-transmittals.index')
            ->with('print_batch_id', $batch->id)
            ->with('success', 'PO Transmittals created successfully. Printing batch PDF...');
    }

    public function printBatchPdf(Batch $batch): \Spatie\LaravelPdf\PdfBuilder
    {
        $purchaseOrders = PurchaseOrder::with([
            'noa.aoq.rfq.purchaseRequest.office',
            'noa.aoq.winnerSupplier',
            'noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'noa.bacResolution.aoq.winnerSupplier',
            'poTransmittals',
        ])
            ->where(fn ($q) => $q
                ->whereHas('noa.aoq', fn ($aoq) => $aoq->where('batch_id', $batch->id))
                ->orWhereHas('noa.bacResolution.aoq', fn ($aoq) => $aoq->where('batch_id', $batch->id))
            )
            ->whereHas('poTransmittals')
            ->latest('po_date')
            ->get();

        $poGroups = $purchaseOrders->map(function (PurchaseOrder $purchaseOrder): array {
            $relatedTransmittals = $purchaseOrder->poTransmittals;
            $coa = $relatedTransmittals->firstWhere('type', 'coa');
            $opg = $relatedTransmittals->firstWhere('type', 'opg');
            $noa = $purchaseOrder->noa;
            $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
            $rfq = $aoq?->rfq;

            return [
                'purchaseOrder' => $purchaseOrder,
                'coaTransmittal' => $coa,
                'opgTransmittal' => $opg,
                'winnerSupplier' => $aoq?->winnerSupplier,
                'projectName' => $rfq?->project_name ?? $noa?->bacResolution?->project_name ?? '—',
            ];
        })->values();

        return Pdf::view('pdf.po-transmittals-batch', [
            'poGroups' => $poGroups,
            'batch' => $batch,
        ])
            ->format('a4')
            ->name('PO-Transmittals-Batch-'.$batch->batch_no.'.pdf')
            ->inline();
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
            'relatedTransmittals' => $relatedTransmittals->map(fn (POTransmittal $poTransmittal): array => [
                'id' => $poTransmittal->id,
                'type' => $poTransmittal->type,
                'transmittal_no' => $poTransmittal->transmittal_no,
            ])->values(),
        ]);
    }

    public function update(UpdatePOTransmittalRequest $updatePOTransmittalRequest, POTransmittal $poTransmittal): RedirectResponse
    {
        $validated = $updatePOTransmittalRequest->validated();

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

    public function printPdf(POTransmittal $poTransmittal): \Spatie\LaravelPdf\PdfBuilder
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
