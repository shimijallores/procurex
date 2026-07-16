<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\WordDocuments\BuildPoTransmittalWordDocument;
use App\Http\Requests\StorePOTransmittalRequest;
use App\Http\Requests\UpdatePOTransmittalRequest;
use App\Models\Batch;
use App\Models\Office;
use App\Models\POTransmittal;
use App\Models\PurchaseOrder;
use App\Services\SvpMatrixSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        if ($created === []) {
            return redirect()->back()->with('info', 'All selected POs already have transmittals.');
        }

        $pos = PurchaseOrder::with('noa.aoq.batch', 'noa.bacResolution.aoq.batch')->whereIn('id', $created)->get();
        $batch = $pos->first()?->noa?->aoq?->batch ?? $pos->first()?->noa?->bacResolution?->aoq?->batch;

        if (! $batch) {
            return redirect()->back()->with('error', 'Could not find batch for created transmittals.');
        }

        foreach ($pos as $po) {
            SvpMatrixSyncService::syncTransmittalValue($po);
        }

        return redirect()->route('po-transmittals.index')
            ->with('print_batch_id', $batch->id)
            ->with('success', 'PO Transmittals created successfully. Printing batch PDF...');
    }

    public function exportBatch(Batch $batch): BinaryFileResponse|RedirectResponse
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

        if ($purchaseOrders->isEmpty()) {
            return redirect()->back()->with('error', 'No PO Transmittals found in this batch.');
        }

        $zipArchive = new \ZipArchive;
        $zipFileName = 'PO-Transmittals-Batch-'.$batch->batch_no.'-'.now()->format('Y-m-d-His').'.zip';
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if ($zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Failed to create ZIP archive.');
        }

        foreach ($purchaseOrders as $po) {
            try {
                $docxPath = $tempDir.\DIRECTORY_SEPARATOR.'po-transmittal-'.$po->id.'.docx';

                $relatedTransmittals = $po->poTransmittals;
                $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa');
                $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');
                $noa = $po->noa;
                $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
                $rfq = $aoq?->rfq;

                $phpWord = new PhpWord;
                Settings::setOutputEscapingEnabled(true);
                $section = $phpWord->addSection();
                $section->addText('PO Transmittal', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
                $section->addTextBreak();
                $section->addText('PO No.: '.$po->po_no);
                $section->addText('Supplier: '.strtoupper((string) ($aoq?->winnerSupplier?->name ?? '—')));
                $section->addText('Project: '.($rfq?->project_name ?? '—'));
                $section->addText('Amount: Php '.number_format((float) ($po->total_amount ?? 0), 2));
                $section->addTextBreak();

                if ($coaTransmittal) {
                    $section->addText('COA Transmittal No.: '.$coaTransmittal->transmittal_no, ['bold' => true]);
                    $section->addText(sprintf('COA Signatory: %s - %s', $coaTransmittal->signatory_name, $coaTransmittal->signatory_title));
                }

                if ($opgTransmittal) {
                    $section->addText('OPG Transmittal No.: '.$opgTransmittal->transmittal_no, ['bold' => true]);
                    $section->addText(sprintf('OPG Signatory: %s - %s', $opgTransmittal->signatory_name, $opgTransmittal->signatory_title));
                }

                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($docxPath);

                $zipArchive->addFile($docxPath, 'PO-Transmittal-'.($po->po_no ?: $po->id).'.docx');
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($purchaseOrders as $purchaseOrder) {
            $docxPath = $tempDir.\DIRECTORY_SEPARATOR.'po-transmittal-'.$purchaseOrder->id.'.docx';
            if (file_exists($docxPath)) {
                unlink($docxPath);
            }
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
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

        SvpMatrixSyncService::syncTransmittalValue($coaTransmittal->purchaseOrder);

        return redirect()->route('po-transmittals.show', $coaTransmittal)
            ->with('success', 'PO Transmittal updated successfully.');
    }

    public function destroy(POTransmittal $poTransmittal): RedirectResponse
    {
        $purchaseOrderId = $poTransmittal->purchase_order_id;

        POTransmittal::query()
            ->where('purchase_order_id', $purchaseOrderId)
            ->delete();

        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
        if ($purchaseOrder) {
            SvpMatrixSyncService::syncTransmittalValue($purchaseOrder);
        }

        return redirect()->route('po-transmittals.index')
            ->with('success', 'PO Transmittal deleted successfully.');
    }

    public function export(POTransmittal $poTransmittal): BinaryFileResponse
    {
        $tempFile = app(BuildPoTransmittalWordDocument::class)->handle($poTransmittal);

        return response()->download($tempFile, 'PO-Transmittal-'.($poTransmittal->purchaseOrder?->po_no ?? $poTransmittal->id).'.docx')
            ->deleteFileAfterSend(true);
    }

    public function downloadFiles(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $builder = POTransmittal::with([
            'purchaseOrder.noa.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.aoq.winnerSupplier',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        if ($validated['date_from']) {
            $builder->whereDate('created_at', '>=', $validated['date_from']);
        }

        if ($validated['date_to']) {
            $builder->whereDate('created_at', '<=', $validated['date_to']);
        }

        $transmittals = $builder->latest('created_at')->get();

        if ($transmittals->isEmpty()) {
            return redirect()->back()->with('error', 'No PO Transmittals found for the selected filters.');
        }

        $zipArchive = new \ZipArchive;
        $zipFileName = 'PO-Transmittals-'.now()->format('Y-m-d-His').'.zip';
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if ($zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Failed to create ZIP archive.');
        }

        foreach ($transmittals as $poTransmittal) {
            try {
                $docxPath = $tempDir.\DIRECTORY_SEPARATOR.'po-transmittal-'.$poTransmittal->id.'.docx';

                $relatedTransmittals = POTransmittal::query()
                    ->where('purchase_order_id', $poTransmittal->purchase_order_id)
                    ->get();

                $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
                $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');
                $purchaseOrder = $poTransmittal->purchaseOrder;
                $noa = $purchaseOrder?->noa;
                $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;

                $phpWord = new PhpWord;
                Settings::setOutputEscapingEnabled(true);
                $section = $phpWord->addSection();
                $section->addText('PO Transmittal', ['bold' => true, 'size' => 14]);
                $section->addTextBreak();
                $section->addText('PO No.: '.$purchaseOrder?->po_no);
                $section->addText('Supplier: '.strtoupper((string) ($aoq?->winnerSupplier?->name ?? '—')));
                $projectName = $poTransmittal->purchaseOrder?->noa?->aoq?->rfq?->project_name ?? $poTransmittal->purchaseOrder?->noa?->bacResolution?->project_name ?? '—';
                $section->addText('Project: '.$projectName);
                $section->addText('Amount: Php '.number_format((float) ($purchaseOrder?->total_amount ?? 0), 2));

                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($docxPath);

                $zipArchive->addFile($docxPath, 'PO-Transmittal-'.($purchaseOrder?->po_no ?: $poTransmittal->id).'.docx');
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($transmittals as $transmittal) {
            $docxPath = $tempDir.\DIRECTORY_SEPARATOR.'po-transmittal-'.$transmittal->id.'.docx';
            if (file_exists($docxPath)) {
                unlink($docxPath);
            }
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }
}
