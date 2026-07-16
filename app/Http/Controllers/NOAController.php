<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\WordDocuments\BuildNoaWordDocument;
use App\Http\Requests\UpdateNOARequest;
use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\RFQ;
use App\Models\Supplier;
use App\Services\SvpMatrixSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class NOAController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NOA::with([
            'aoq.rfq.purchaseRequest.office',

            'aoq.batch',

            'bacResolution.aoq.rfq.purchaseRequest.office',
        ])

            ->when($request->search, function ($q, string $search): void {
                $q->where('noa_no', 'like', sprintf('%%%s%%', $search))

                    ->orWhereHas('bacResolution', function ($br) use (
                        $search,
                    ): void {
                        $br->where(
                            'resolution_no',
                            'like',
                            sprintf('%%%s%%', $search),
                        )->orWhere(
                            'project_name',
                            'like',
                            sprintf('%%%s%%', $search),
                        );
                    })

                    ->orWhereHas('aoq.rfq', function ($rfq) use (
                        $search,
                    ): void {
                        $rfq->where(
                            'project_name',
                            'like',
                            sprintf('%%%s%%', $search),
                        )->orWhere(
                            'svp_no',
                            'like',
                            sprintf('%%%s%%', $search),
                        );
                    });
            })

            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas(
                    'aoq.rfq.purchaseRequest',
                    fn ($pr) => $pr->where('office_id', $officeId),
                );
            })

            ->when($request->fiscal_year, function (
                $q,
                string $fiscalYear,
            ): void {
                $q->whereYear('noa_date', $fiscalYear);
            })

            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->whereHas(
                    'aoq',
                    fn ($aoq) => $aoq->where('batch_id', $batchId),
                );
            });

        $lengthAwarePaginator = (clone $query)

            ->latest('noa_date')

            ->paginate(10)

            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),

            'this_month' => (clone $query)

                ->whereMonth('noa_date', now()->month)

                ->whereYear('noa_date', now()->year)

                ->count(),

            'this_year' => (clone $query)

                ->whereYear('noa_date', now()->year)

                ->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);

        $currentYear = now()->year;

        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year): array => [$year => $year])

            ->reverse();

        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);

        return Inertia::render('NOAs/Index', [
            'noas' => $lengthAwarePaginator,

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

    public function create(): Response
    {
        $suppliers = Supplier::query()

            ->where('is_active', true)

            ->orderBy('name')

            ->get([
                'id',

                'name',

                'proprietor',

                'authorized_representative',

                'owner',
            ]);

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('NOAs/Create', [
            'suppliers' => $suppliers,

            'defaultNoaDate' => $suggestedDate,

            'defaultNoaNo' => $this->generateNoaNo(
                Carbon::parse($suggestedDate),
            ),
        ]);
    }

    private function generateNoaNo(Carbon $date): string
    {
        $prefix = $date->format('Y').'-';

        $latest = NOA::query()

            ->where('noa_no', 'like', $prefix.'%')

            ->orderByDesc('noa_no')

            ->value('noa_no');

        $next = 1;

        if (
            $latest &&
            preg_match('/^\d{4}-(\d{4})$/', $latest, $matches) === 1
        ) {
            $next = (int) $matches[1] + 1;
        }

        do {
            $noaNo = sprintf('%s%04d', $prefix, $next);

            ++$next;
        } while (NOA::where('noa_no', $noaNo)->exists());

        return $noaNo;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],

            'noas' => ['required', 'array', 'min:1'],

            'noas.*.aoq_id' => ['required', 'integer', 'exists:aoqs,id'],

            'noas.*.noa_no' => ['nullable', 'string', 'max:255'],

            'noas.*.noa_date' => ['required', 'date'],

            'noas.*.recipient_name' => ['nullable', 'string', 'max:255'],

            'noas.*.recipient_title' => ['nullable', 'string', 'max:255'],
        ]);

        $batch = Batch::with('aoqs')->findOrFail($validated['batch_id']);

        $batchAoqIds = $batch->aoqs
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $errors = [];

        $created = [];

        DB::beginTransaction();

        try {
            foreach ($validated['noas'] as $index => $noaData) {
                $aoqId = (int) $noaData['aoq_id'];

                if (! in_array($aoqId, $batchAoqIds, true)) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf(
                        'AOQ #%d is not in the selected batch.',
                        $aoqId,
                    );

                    continue;
                }

                if (NOA::where('aoq_id', $aoqId)->exists()) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] =
                        'NOA already exists for this AOQ.';

                    continue;
                }

                if (! $this->isWorkingDay($noaData['noa_date'] ?? null)) {
                    $errors[sprintf('noas.%s.noa_date', $index)] =
                        'NOA date must be a working day (not weekend/holiday).';

                    continue;
                }

                $aoq = AOQ::with(['rfq', 'winnerSupplier'])->find($aoqId);

                if (! $aoq?->rfq) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf(
                        'AOQ #%d has no linked RFQ.',
                        $aoqId,
                    );

                    continue;
                }

                $noaNo = (string) ($noaData['noa_no'] ?? '');

                if ($noaNo === '') {
                    $noaNo = (string) ($aoq->rfq->svp_no ?? '');
                }

                if ($noaNo === '') {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf(
                        'Unable to generate NOA number from AOQ #%d.',
                        $aoqId,
                    );

                    continue;
                }

                if (
                    NOA::where('noa_no', $noaNo)
                        ->where('aoq_id', '!=', $aoqId)
                        ->exists()
                ) {
                    $errors[sprintf('noas.%s.noa_no', $index)] = sprintf(
                        'NOA number "%s" is already in use.',
                        $noaNo,
                    );

                    continue;
                }

                $winnerAmount = $this->calculateWinnerAmount($aoq);

                $noa = NOA::create([
                    'aoq_id' => $aoqId,

                    'noa_no' => $noaNo,

                    'noa_date' => $noaData['noa_date'],

                    'winner_amount' => $winnerAmount,

                    'recipient_name' => (string) ($noaData['recipient_name'] ?? ''),

                    'recipient_title' => (string) ($noaData['recipient_title'] ?? ''),
                ]);

                $created[] = $noa;
            }

            if ($errors !== []) {
                DB::rollBack();

                return redirect()->back()->withErrors($errors)->withInput();
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Failed to create Notices of Award. Please try again.',
                );
        }

        $count = count($created);

        foreach ($created as $noa) {
            SvpMatrixSyncService::syncNoaValue($noa);
        }

        return redirect()
            ->route('noas.index')

            ->with(
                'success',
                $count.' Notice(s) of Award created successfully.',
            )

            ->with('print_batch_id', $batch->id);
    }

    public function edit(NOA $noa): Response
    {
        $noa->load([
            'aoq.rfq.purchaseRequest.office',

            'aoq.rfq.suppliers.supplierItems.rfqItem',

            'aoq.winnerSupplier',

            'bacResolution.aoq.rfq.purchaseRequest.office',

            'bacResolution.aoq.winnerSupplier',
        ]);

        $aoq = $noa->aoq ?? $noa->bacResolution?->aoq;

        $winnerAmount = $aoq ? $this->calculateWinnerAmount($aoq) : 0;

        $suppliers = Supplier::query()

            ->where('is_active', true)

            ->orderBy('name')

            ->get([
                'id',

                'name',

                'proprietor',

                'authorized_representative',

                'owner',
            ]);

        return Inertia::render('NOAs/Edit', [
            'noa' => $noa,

            'suppliers' => $suppliers,

            'winnerAmount' => $winnerAmount,
        ]);
    }

    public function batchAoqs(Batch $batch): JsonResponse
    {
        $aoqs = AOQ::with([
            'rfq.purchaseRequest.office',

            'rfq.suppliers.supplierItems.rfqItem',

            'winnerSupplier',
        ])

            ->where('batch_id', $batch->id)

            ->whereDoesntHave('noa')

            ->latest('aoq_date')

            ->get()

            ->map(function (AOQ $aoq): AOQ {
                $aoq->setAttribute(
                    'winner_amount',
                    $this->calculateWinnerAmount($aoq),
                );

                return $aoq;
            })

            ->values();

        $suppliers = Supplier::query()

            ->where('is_active', true)

            ->orderBy('name')

            ->get([
                'id',

                'name',

                'proprietor',

                'authorized_representative',

                'owner',
            ]);

        $bacResolution = BACResolution::query()

            ->whereHas('aoqs', fn ($q) => $q->where('batch_id', $batch->id))

            ->orWhereHas('aoq', fn ($q) => $q->where('batch_id', $batch->id))

            ->latest('resolution_date')

            ->first(['id', 'resolution_no']);

        return response()->json([
            'aoqs' => $aoqs,

            'suppliers' => $suppliers,

            'batch' => [
                'id' => $batch->id,

                'batch_no' => $batch->batch_no,

                'noa_date' => $batch->noa_date?->toDateString(),
            ],

            'bac_resolution' => $bacResolution,
        ]);
    }

    public function findBatchByNoa(Request $request): JsonResponse
    {
        $noaNo = $request->query('noa_no');

        if (! $noaNo) {
            return response()->json(
                ['error' => 'NOA number is required.'],
                422,
            );
        }

        $noa = NOA::with('aoq')->where('noa_no', $noaNo)->first();

        if (! $noa) {
            return response()->json(
                ['error' => 'No NOA found with that number.'],
                404,
            );
        }

        if ($noa->purchaseOrder()->exists()) {
            return response()->json(
                ['error' => 'This NOA already has a Purchase Order.'],
                422,
            );
        }

        $aoq = $noa->aoq;

        if (! $aoq || ! $aoq->batch_id) {
            return response()->json(
                ['error' => 'This NOA has no corresponding batch.'],
                404,
            );
        }

        $batch = Batch::find($aoq->batch_id);

        return response()->json([
            'batch' => [
                'id' => $batch->id,

                'batch_no' => $batch->batch_no,
            ],
        ]);
    }

    public function findBatchBySvp(Request $request): JsonResponse
    {
        $svpNo = $request->query('svp_no');

        if (! $svpNo) {
            return response()->json(
                ['error' => 'SVP number is required.'],
                422,
            );
        }

        $rfq = RFQ::where('svp_no', $svpNo)->first();

        if (! $rfq) {
            return response()->json(['error' => 'SVP not found.'], 404);
        }

        $aoq = AOQ::where('rfq_id', $rfq->id)->first();

        if (! $aoq || ! $aoq->batch_id) {
            return response()->json(
                [
                    'error' => 'SVP not found. Make sure the AOQ has a batch assigned.',
                ],
                404,
            );
        }

        $batch = Batch::find($aoq->batch_id);

        return response()->json([
            'batch' => [
                'id' => $batch->id,

                'batch_no' => $batch->batch_no,
            ],
        ]);
    }

    public function recentNoas(Request $request): JsonResponse
    {
        $noas = NOA::query()

            ->whereNotNull('noa_no')

            ->when($request->input('context') === 'po', function ($q): void {
                $q->whereDoesntHave('purchaseOrder');
            })

            ->latest()

            ->take(5)

            ->pluck('noa_no');

        return response()->json(['noas' => $noas]);
    }

    public function update(
        UpdateNOARequest $updateNOARequest,
        NOA $noa,
    ): RedirectResponse {
        $validated = $updateNOARequest->validated();

        $noa->update([
            'noa_date' => $validated['noa_date'],

            'recipient_name' => (string) ($validated['recipient_name'] ?? ''),

            'recipient_title' => (string) ($validated['recipient_title'] ?? ''),
        ]);

        SvpMatrixSyncService::syncNoaValue($noa);

        return redirect()
            ->route('noas.show', $noa)

            ->with('success', 'Notice of Award updated successfully.');
    }

    public function destroy(NOA $noa): RedirectResponse
    {
        if ($noa->purchaseOrder()->exists()) {
            return redirect()->route('noas.index')
                ->with('error', 'Cannot delete NOA with an existing Purchase Order. Remove the PO first.');
        }

        $noa->delete();

        return redirect()->route('noas.index')
            ->with('success', 'NOA deleted successfully.');
    }

    public function export(NOA $noa): BinaryFileResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');

        app(BuildNoaWordDocument::class)->handle(
            $noa,
            $tempFile,
        );

        return response()
            ->download($tempFile, 'NOA-'.$noa->noa_no.'.docx')

            ->deleteFileAfterSend(true);
    }

    public function downloadFiles(
        Request $request,
    ): BinaryFileResponse|RedirectResponse {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],

            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $builder = NOA::with([
            'aoq.rfq.purchaseRequest.office',

            'aoq.rfq.suppliers.supplierItems.rfqItem',

            'aoq.winnerSupplier',

            'bacResolution.aoq.rfq.purchaseRequest.office',

            'bacResolution.aoq.winnerSupplier',
        ]);

        if ($validated['date_from']) {
            $builder->whereDate('noa_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to']) {
            $builder->whereDate('noa_date', '<=', $validated['date_to']);
        }

        $noas = $builder->latest('noa_date')->get();

        if ($noas->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'No NOAs found for the selected filters.');
        }

        $zipArchive = new \ZipArchive;

        $zipFileName = 'NOAs-'.now()->format('Y-m-d-His').'.zip';

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if (
            $zipArchive->open(
                $zipPath,
                \ZipArchive::CREATE | \ZipArchive::OVERWRITE,
            ) !== true
        ) {
            return redirect()
                ->back()
                ->with('error', 'Failed to create ZIP archive.');
        }

        foreach ($noas as $noa) {
            try {
                $docxPath =
                    $tempDir.
                    \DIRECTORY_SEPARATOR.
                    sprintf('noa-%s.docx', $noa->id);

                app(
                    BuildNoaWordDocument::class,
                )->handle($noa, $docxPath);

                $zipArchive->addFile(
                    $docxPath,
                    'NOA-'.$noa->noa_no.'.docx',
                );
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($noas as $noa) {
            $docxPath =
                $tempDir.
                \DIRECTORY_SEPARATOR.
                sprintf('noa-%s.docx', $noa->id);

            if (file_exists($docxPath)) {
                unlink($docxPath);
            }
        }

        return response()
            ->download($zipPath, $zipFileName)
            ->deleteFileAfterSend(true);
    }

    public function exportBatch(
        Batch $batch,
    ): BinaryFileResponse|RedirectResponse {
        $noas = NOA::with([
            'aoq.rfq.purchaseRequest.office',

            'aoq.rfq.suppliers.supplierItems.rfqItem',

            'aoq.winnerSupplier',

            'bacResolution.aoq.rfq.purchaseRequest.office',

            'bacResolution.aoq.winnerSupplier',
        ])

            ->whereHas('aoq', fn ($q) => $q->where('batch_id', $batch->id))

            ->get();

        if ($noas->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'No NOAs found in this batch.');
        }

        $zipArchive = new \ZipArchive;

        $zipFileName =
            'NOAs-Batch-'.
            $batch->batch_no.
            '-'.
            now()->format('Y-m-d-His').
            '.zip';

        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if (
            $zipArchive->open(
                $zipPath,
                \ZipArchive::CREATE | \ZipArchive::OVERWRITE,
            ) !== true
        ) {
            return redirect()
                ->back()
                ->with('error', 'Failed to create ZIP archive.');
        }

        foreach ($noas as $noa) {
            try {
                $docxPath =
                    $tempDir.
                    \DIRECTORY_SEPARATOR.
                    sprintf('noa-%s.docx', $noa->id);

                app(
                    BuildNoaWordDocument::class,
                )->handle($noa, $docxPath);

                $zipArchive->addFile(
                    $docxPath,
                    'NOA-'.$noa->noa_no.'.docx',
                );
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($noas as $noa) {
            $docxPath =
                $tempDir.
                \DIRECTORY_SEPARATOR.
                sprintf('noa-%s.docx', $noa->id);

            if (file_exists($docxPath)) {
                unlink($docxPath);
            }
        }

        return response()
            ->download($zipPath, $zipFileName)
            ->deleteFileAfterSend(true);
    }

    public function calculateWinnerAmount(AOQ $aoq): float
    {
        $aoq->loadMissing([
            'rfq.suppliers.supplierItems.rfqItem',
            'winnerSupplier',
        ]);

        if (! $aoq->winner_supplier_id) {
            return 0.0;
        }

        $winnerEntry = $aoq->rfq?->suppliers?->firstWhere(
            'supplier_id',
            $aoq->winner_supplier_id,
        );
        if (! $winnerEntry) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($winnerEntry->supplierItems as $supplierItem) {
            if ($supplierItem->unit_price === null) {
                continue;
            }

            $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
            $total += $quantity * (float) $supplierItem->unit_price;
        }

        return round($total, 2);
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
