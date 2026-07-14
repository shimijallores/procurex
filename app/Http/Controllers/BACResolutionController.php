<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBACResolutionRequest;
use App\Http\Requests\UpdateBACResolutionRequest;
use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\RFQ;
use App\Services\SvpMatrixSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BACResolutionController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BACResolution::with([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.batch',
            'aoq.rfq.purchaseRequest.office',
            'aoq.batch',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('winner_supplier_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('aoqs.rfq', function ($rfq) use ($search): void {
                            $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('aoq.rfq', function ($rfq) use ($search): void {
                            $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->where(function ($inner) use ($officeId): void {
                    $inner->whereHas('aoqs.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId))
                        ->orWhereHas('aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
                });
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('resolution_date', $fiscalYear);
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->where(function ($inner) use ($batchId): void {
                    $inner->whereHas('aoqs', fn ($aoq) => $aoq->where('batch_id', $batchId))
                        ->orWhereHas('aoq', fn ($aoq) => $aoq->where('batch_id', $batchId));
                });
            });

        $lengthAwarePaginator = (clone $query)
            ->latest('resolution_date')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->whereNull('finalized_at')->count(),
            'finalized' => (clone $query)->whereNotNull('finalized_at')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);
        $batches = Batch::orderByDesc('batch_no')->get(['id', 'batch_no']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year): array => [$year => $year])
            ->reverse();

        return Inertia::render('BACResolutions/Index', [
            'resolutions' => $lengthAwarePaginator,
            'stats' => $stats,
            'offices' => $offices,
            'batches' => $batches,
            'fiscalYears' => $fiscalYears,
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
        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('BACResolutions/Create', [
            'defaultResolutionDate' => $suggestedDate,
            'defaultMeetingDate' => $suggestedDate,
        ]);
    }

    public function store(StoreBACResolutionRequest $storeBACResolutionRequest): RedirectResponse
    {
        $validated = $storeBACResolutionRequest->validated();
        $saveDraft = (bool) ($validated['save_draft'] ?? false);

        if (! $saveDraft) {
            if (! $this->isWorkingDay($validated['resolution_date'] ?? null)) {
                return redirect()->back()->withErrors([
                    'resolution_date' => 'Resolution date must be a working day (not weekend/holiday).',
                ])->withInput();
            }

            if (! $this->isWorkingDay($validated['meeting_date'] ?? null)) {
                return redirect()->back()->withErrors([
                    'meeting_date' => 'Meeting date must be a working day (not weekend/holiday).',
                ])->withInput();
            }
        }

        DB::beginTransaction();

        try {
            $batch = Batch::with([
                'aoqs.rfq',
                'aoqs.rfq.purchaseRequest.office',
                'aoqs.rfq.items.purchaseRequestItem',
                'aoqs.rfq.suppliers.supplier',
                'aoqs.rfq.suppliers.supplierItems.rfqItem',
                'aoqs.rfq.suppliers.supplierItems',
                'aoqs.winnerSupplier',
                'aoqs.bacResolution',
                'aoqs.bacResolutions',
            ])->findOrFail($validated['batch_id']);

            $aoqs = $batch->aoqs;

            if ($aoqs->isEmpty()) {
                return redirect()->back()->withErrors([
                    'batch_id' => 'The selected batch has no AOQs.',
                ])->withInput();
            }

            if (! $saveDraft) {
                $alreadyLinked = $aoqs->first(function (AOQ $aoq): bool {
                    return $aoq->bacResolution()->exists() || $aoq->bacResolutions()->exists();
                });

                if ($alreadyLinked) {
                    return redirect()->back()->with('error', 'One or more AOQs in this batch are already linked to an existing BAC Resolution.');
                }
            }

            $primaryAoq = $aoqs->first();
            $winnerAmount = $aoqs->sum(fn (AOQ $aoq): float => $this->calculateWinnerAmount($aoq));
            $calculationLabel = 'Lowest/Single Calculated';

            $projectName = (string) ($validated['project_name'] ?? 'Batch of Projects');
            $winnerSupplierName = (string) ($validated['winner_supplier_name'] ?? 'Multiple Suppliers');

            $resolution = BACResolution::create([
                'aoq_id' => $primaryAoq?->id,
                'resolution_no' => $batch->generateResolutionNo(),
                'resolution_date' => $validated['resolution_date'] ?? now()->toDateString(),
                'meeting_date' => $validated['meeting_date'] ?? null,
                'project_name' => $projectName,
                'winner_supplier_name' => $winnerSupplierName,
                'winner_amount' => (float) ($validated['winner_amount'] ?? $winnerAmount),
                'calculation_label' => (string) ($validated['calculation_label'] ?? $calculationLabel),
                'justification' => $validated['justification']
                    ?? sprintf(
                        'for being the suppliers with the %s and Responsive Quotations which are advantageous to the Provincial Government of Batangas.',
                        $calculationLabel
                    ),
                'signatory_chairperson' => $validated['signatory_chairperson'] ?? 'BAC Chairperson',
                'signatory_member_one' => $validated['signatory_member_one'] ?? 'BAC Member',
                'signatory_member_two' => $validated['signatory_member_two'] ?? 'BAC Member',
                'signatory_member_three' => $validated['signatory_member_three'] ?? 'BAC Member',
                'finalized_at' => $saveDraft ? null : now(),
            ]);

            if ($saveDraft) {
                DB::commit();

                SvpMatrixSyncService::syncResolutionValue($resolution);

                return redirect()->route('bac-resolutions.show', $resolution)
                    ->with('success', 'BAC Resolution draft saved successfully.');
            }

            $syncPayload = [];
            foreach ($aoqs as $index => $aoq) {
                $syncPayload[(int) $aoq->id] = ['sort_order' => $index + 1];
            }

            $resolution->aoqs()->sync($syncPayload);

            // Link existing NOAs that reference the batched AOQs
            $aoqIds = $aoqs->pluck('id')->toArray();
            NOA::whereIn('aoq_id', $aoqIds)
                ->whereNull('bac_resolution_id')
                ->update(['bac_resolution_id' => $resolution->id]);

            // Lock the batch so no new AOQs can be added
            $batch->update(['is_locked' => true]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create BAC Resolution. Please try again.');
        }

        SvpMatrixSyncService::syncResolutionValue($resolution);

        return redirect()->route('bac-resolutions.show', $resolution)
            ->with('success', 'BAC Resolution created successfully.');
    }

    public function show(BACResolution $bacResolution): Response
    {
        $bacResolution->load([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.winnerSupplier',
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq',
            'aoq.winnerSupplier',
        ]);

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('BACResolutions/Show', [
            'resolution' => $bacResolution,
            'defaultResolutionDate' => $suggestedDate,
            'defaultMeetingDate' => $suggestedDate,
        ]);
    }

    public function edit(BACResolution $bacResolution): Response
    {
        if ($bacResolution->isFinalized()) {
            return redirect()->route('bac-resolutions.show', $bacResolution)
                ->with('error', 'Finalized BAC Resolution cannot be edited.');
        }

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('BACResolutions/Edit', [
            'resolution' => $bacResolution,
            'defaultResolutionDate' => $suggestedDate,
            'defaultMeetingDate' => $suggestedDate,
        ]);
    }

    public function update(UpdateBACResolutionRequest $updateBACResolutionRequest, BACResolution $bacResolution): RedirectResponse
    {
        if ($bacResolution->isFinalized()) {
            return redirect()->back()->with('error', 'Finalized BAC Resolution can no longer be edited.');
        }

        $validated = $updateBACResolutionRequest->validated();

        if (! $this->isWorkingDay($validated['resolution_date'] ?? null)) {
            return redirect()->back()->withErrors([
                'resolution_date' => 'Resolution date must be a working day (not weekend/holiday).',
            ])->withInput();
        }

        if (! $this->isWorkingDay($validated['meeting_date'] ?? null)) {
            return redirect()->back()->withErrors([
                'meeting_date' => 'Meeting date must be a working day (not weekend/holiday).',
            ])->withInput();
        }

        $bacResolution->update($validated);

        SvpMatrixSyncService::syncResolutionValue($bacResolution);

        return redirect()->route('bac-resolutions.show', $bacResolution)
            ->with('success', 'BAC Resolution updated successfully.');
    }

    public function finalize(BACResolution $bacResolution): RedirectResponse
    {
        if ($bacResolution->isFinalized()) {
            return redirect()->back()->with('error', 'BAC Resolution is already finalized.');
        }

        DB::beginTransaction();

        try {
            $bacResolution->update([
                'finalized_at' => now(),
            ]);

            // Sync AOQs and link NOAs if not already done (e.g., saved as draft)
            $aoqs = $bacResolution->aoqs;
            if ($aoqs->isEmpty() && $bacResolution->aoq) {
                $aoqs = $bacResolution->aoq->batch?->aoqs ?? collect([$bacResolution->aoq]);
            }

            if ($aoqs->isNotEmpty()) {
                // Sync AOQs with sort_order
                $syncPayload = [];
                foreach ($aoqs as $index => $aoq) {
                    $syncPayload[(int) $aoq->id] = ['sort_order' => $index + 1];
                }

                $bacResolution->aoqs()->syncWithoutDetaching($syncPayload);

                // Link NOAs
                $aoqIds = $aoqs->pluck('id')->toArray();
                NOA::whereIn('aoq_id', $aoqIds)
                    ->whereNull('bac_resolution_id')
                    ->update(['bac_resolution_id' => $bacResolution->id]);

                // Lock the batch
                $batchId = $aoqs->first()->batch_id;
                if ($batchId) {
                    Batch::where('id', $batchId)->update(['is_locked' => true]);
                }
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to finalize BAC Resolution.');
        }

        SvpMatrixSyncService::syncResolutionValue($bacResolution);

        return redirect()->route('bac-resolutions.show', $bacResolution)
            ->with('success', 'BAC Resolution finalized successfully.');
    }

    public function regenerate(BACResolution $bacResolution): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $primaryAoq = $bacResolution->aoq;
            if (! $primaryAoq?->batch) {
                return redirect()->back()->with('error', 'This BAC Resolution has no associated batch.');
            }

            $batch = $primaryAoq->batch->load(['aoqs.rfq.purchaseRequest.office']);

            $aoqs = $batch->aoqs;

            $syncPayload = [];
            foreach ($aoqs as $index => $aoq) {
                $syncPayload[(int) $aoq->id] = ['sort_order' => $index + 1];
            }

            $bacResolution->aoqs()->sync($syncPayload);

            NOA::whereIn('aoq_id', $aoqs->pluck('id'))
                ->whereNull('bac_resolution_id')
                ->update(['bac_resolution_id' => $bacResolution->id]);

            $batch->update(['is_locked' => true]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to regenerate BAC Resolution.');
        }

        SvpMatrixSyncService::syncResolutionValue($bacResolution);

        return redirect()->route('bac-resolutions.show', $bacResolution)
            ->with('success', 'BAC Resolution regenerated successfully.');
    }

    public function destroy(BACResolution $bacResolution): RedirectResponse
    {
        SvpMatrixSyncService::syncResolutionValue($bacResolution);

        DB::beginTransaction();

        try {
            // Unlink NOAs without deleting them
            NOA::where('bac_resolution_id', $bacResolution->id)
                ->update(['bac_resolution_id' => null]);

            $bacResolution->delete();

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete BAC Resolution.');
        }

        return redirect()->route('bac-resolutions.index')
            ->with('success', 'BAC Resolution deleted successfully.');
    }

    public function fetchBatchAoqs(Batch $batch): JsonResponse
    {
        $batch->load([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.rfq.suppliers.supplierItems.rfqItem',
            'aoqs.winnerSupplier',
        ]);

        $aoqs = $batch->aoqs->map(function (AOQ $aoq): AOQ {
            $calculatedSupplierCount = $this->countCalculatedSuppliers($aoq);
            $calculationLabel = $calculatedSupplierCount <= 1
                ? 'Single Calculated'
                : 'Lowest Calculated';

            $aoq->setAttribute('calculated_supplier_count', $calculatedSupplierCount);
            $aoq->setAttribute('calculation_label', $calculationLabel);
            $aoq->setAttribute('winner_amount', $this->calculateWinnerAmount($aoq));

            return $aoq;
        })->values();

        return response()->json([
            'batch' => $batch,
            'aoqs' => $aoqs,
        ]);
    }

    public function export(BACResolution $bacResolution): BinaryFileResponse
    {
        $tempFile = app(\App\Actions\WordDocuments\BuildBacResolutionWordDocument::class)->handle($bacResolution);

        return response()->download($tempFile, 'BAC-Resolution-'.$bacResolution->resolution_no.'.docx')
            ->deleteFileAfterSend(true);
    }

    public function downloadFiles(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $builder = BACResolution::with([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.rfq.items.purchaseRequestItem',
            'aoqs.rfq.suppliers.supplier',
            'aoqs.rfq.suppliers.supplierItems.rfqItem',
            'aoqs.winnerSupplier',
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items',
            'aoq.rfq.suppliers.supplier',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
        ]);

        if ($validated['date_from']) {
            $builder->whereDate('resolution_date', '>=', $validated['date_from']);
        }

        if ($validated['date_to']) {
            $builder->whereDate('resolution_date', '<=', $validated['date_to']);
        }

        $resolutions = $builder->latest('resolution_date')->get();

        if ($resolutions->isEmpty()) {
            return redirect()->back()->with('error', 'No BAC Resolutions found for the selected filters.');
        }

        $zipArchive = new \ZipArchive;
        $zipFileName = 'BAC-Resolutions-'.now()->format('Y-m-d-His').'.zip';
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir.\DIRECTORY_SEPARATOR.$zipFileName;

        if ($zipArchive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'Failed to create ZIP archive.');
        }

        $tempPaths = [];

        foreach ($resolutions as $resolution) {
            try {
                $tempPath = app(\App\Actions\WordDocuments\BuildBacResolutionWordDocument::class)->handle($resolution);
                $tempPaths[] = $tempPath;

                $zipArchive->addFile($tempPath, 'BAC-Resolution-'.$resolution->resolution_no.'.docx');
            } catch (\Throwable) {
                continue;
            }
        }

        $zipArchive->close();

        foreach ($tempPaths as $tempPath) {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
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

    private function calculateWinnerAmount(AOQ $aoq): float
    {
        $aoq->loadMissing([
            'rfq.suppliers.supplierItems.rfqItem',
            'winnerSupplier',
        ]);

        if (! $aoq->winner_supplier_id) {
            return 0.0;
        }

        $winnerEntry = $aoq->rfq?->suppliers?->firstWhere('supplier_id', $aoq->winner_supplier_id);
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

    private function countCalculatedSuppliers(AOQ $aoq): int
    {
        $aoq->loadMissing([
            'rfq.suppliers.supplierItems.rfqItem',
        ]);

        $count = 0;

        foreach ($aoq->rfq?->suppliers ?? collect() as $entry) {
            if (! $entry->submitted_at) {
                continue;
            }

            $hasAtLeastOnePrice = false;
            foreach ($entry->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price !== null) {
                    $hasAtLeastOnePrice = true;
                    break;
                }
            }

            if ($hasAtLeastOnePrice) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSupplierTotals(RFQ $rfq): array
    {
        $rfq->loadMissing([
            'items.purchaseRequestItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        $supplierTotals = [];

        foreach ($rfq->suppliers as $rfqSupplier) {
            $total = 0.0;
            $hasAtLeastOnePrice = false;

            foreach ($rfqSupplier->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price === null) {
                    continue;
                }

                $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
                $lineTotal = $quantity * (float) $supplierItem->unit_price;
                $total += $lineTotal;
                $hasAtLeastOnePrice = true;
            }

            if (! $hasAtLeastOnePrice) {
                continue;
            }

            $supplierTotals[] = [
                'rfq_supplier_id' => $rfqSupplier->id,
                'supplier_id' => $rfqSupplier->supplier_id,
                'supplier_name' => $rfqSupplier->supplier?->name,
                'total_amount' => round($total, 2),
            ];
        }

        usort($supplierTotals, fn (array $left, array $right): int => $left['total_amount'] <=> $right['total_amount']);

        $count = count($supplierTotals);
        $winner = $count > 0 ? $supplierTotals[0] : null;
        $calculationMode = $count >= 2 ? 'lowest_calculated' : 'single_calculated';

        return [
            'supplier_totals' => $supplierTotals,
            'calculated_supplier_count' => $count,
            'calculation_mode' => $calculationMode,
            'winner_supplier_id' => $winner['supplier_id'] ?? null,
            'winner_total_amount' => $winner['total_amount'] ?? null,
        ];
    }
}
