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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

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

        $resolutions = (clone $query)
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
            ->mapWithKeys(fn ($year) => [$year => $year])
            ->reverse();

        return Inertia::render('BACResolutions/Index', [
            'resolutions' => $resolutions,
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
        $eligibleBatches = Batch::with([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.rfq.suppliers.supplierItems.rfqItem',
            'aoqs.winnerSupplier',
        ])
            ->has('aoqs')
            ->whereDoesntHave('aoqs.bacResolution')
            ->whereDoesntHave('aoqs.bacResolutions')
            ->withCount('aoqs')
            ->orderByDesc('id')
            ->get()
            ->map(function (Batch $batch): Batch {
                $batch->aoqs->each(function (AOQ $aoq): void {
                    $calculatedSupplierCount = $this->countCalculatedSuppliers($aoq);
                    $calculationLabel = $calculatedSupplierCount <= 1
                        ? 'Single Calculated'
                        : 'Lowest Calculated';

                    $aoq->setAttribute('calculated_supplier_count', $calculatedSupplierCount);
                    $aoq->setAttribute('calculation_label', $calculationLabel);
                    $aoq->setAttribute('winner_amount', $this->calculateWinnerAmount($aoq));
                });

                return $batch;
            })
            ->values();

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('BACResolutions/Create', [
            'eligibleBatches' => $eligibleBatches,
            'defaultResolutionDate' => $suggestedDate,
            'defaultMeetingDate' => $suggestedDate,
        ]);
    }

    public function store(StoreBACResolutionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

            $alreadyLinked = $aoqs->first(function (AOQ $aoq): bool {
                return $aoq->bacResolution()->exists() || $aoq->bacResolutions()->exists();
            });

            if ($alreadyLinked) {
                return redirect()->back()->with('error', 'One or more AOQs in this batch are already linked to an existing BAC Resolution.');
            }

            $primaryAoq = $aoqs->first();
            $winnerAmount = $aoqs->sum(fn (AOQ $aoq) => $this->calculateWinnerAmount($aoq));
            $calculationLabel = 'Lowest/Single Calculated';

            $projectName = (string) ($validated['project_name'] ?? 'Batch of Projects');
            $winnerSupplierName = (string) ($validated['winner_supplier_name'] ?? 'Multiple Suppliers');

            $resolutionDate = Carbon::parse($validated['resolution_date']);

            $resolution = BACResolution::create([
                'aoq_id' => $primaryAoq?->id,
                'resolution_no' => $this->generateResolutionNumber($resolutionDate),
                'resolution_date' => $validated['resolution_date'],
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
                'finalized_at' => now(),
            ]);

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

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create BAC Resolution. Please try again.');
        }

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

    public function update(UpdateBACResolutionRequest $request, BACResolution $bacResolution): RedirectResponse
    {
        if ($bacResolution->isFinalized()) {
            return redirect()->back()->with('error', 'Finalized BAC Resolution can no longer be edited.');
        }

        $validated = $request->validated();

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

        return redirect()->route('bac-resolutions.show', $bacResolution)
            ->with('success', 'BAC Resolution updated successfully.');
    }

    public function finalize(BACResolution $bacResolution): RedirectResponse
    {
        if ($bacResolution->isFinalized()) {
            return redirect()->back()->with('error', 'BAC Resolution is already finalized.');
        }

        $bacResolution->update([
            'finalized_at' => now(),
        ]);

        return redirect()->route('bac-resolutions.show', $bacResolution)
            ->with('success', 'BAC Resolution finalized successfully.');
    }

    public function destroy(BACResolution $bacResolution): RedirectResponse
    {
        $bacResolution->delete();

        return redirect()->route('bac-resolutions.index')
            ->with('success', 'BAC Resolution deleted successfully.');
    }

    public function printPdf(BACResolution $bacResolution)
    {
        $bacResolution->load([
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

        $batchAoqs = $bacResolution->aoqs;
        if ($batchAoqs->isEmpty() && $bacResolution->aoq) {
            $batchAoqs = collect([$bacResolution->aoq]);
        }

        $summaryRows = $batchAoqs->map(function (AOQ $aoq): array {
            return [
                'office_name' => (string) ($aoq->rfq?->purchaseRequest?->office?->name ?? 'OFFICE'),
                'project_name' => (string) ($aoq->rfq?->project_name ?? 'PROJECT'),
                'abc_amount' => (float) ($aoq->rfq?->abc_amount ?? 0),
            ];
        })->values();

        $abstracts = $batchAoqs->map(function (AOQ $aoq): array {
            $rfq = $aoq->rfq;
            if (! $rfq) {
                return [
                    'svp_no' => 'N/A',
                    'rfq_date' => null,
                    'project_name' => 'PROJECT',
                    'suppliers' => collect(),
                    'items' => collect(),
                    'abc_total' => 0.0,
                ];
            }

            $supplierTotals = collect($this->calculateSupplierTotals($rfq)['supplier_totals'] ?? []);
            $rankedSuppliers = $supplierTotals->values()->map(function (array $row, int $index): array {
                $rank = $index + 1;
                $rankLabel = $rank === 1 ? '1ST' : ($rank === 2 ? '2ND' : ($rank === 3 ? '3RD' : $rank.'TH'));

                return [
                    'supplier_id' => (int) $row['supplier_id'],
                    'supplier_name' => strtoupper((string) ($row['supplier_name'] ?? 'N/A')),
                    'total_amount' => (float) ($row['total_amount'] ?? 0),
                    'rank_label' => $rankLabel,
                ];
            });

            $items = collect($rfq?->items ?? [])->map(function ($item) use ($rfq, $rankedSuppliers): array {
                $quantity = (float) ($item->quantity ?? 0);
                $approvedBudget = $quantity * (float) ($item->purchaseRequestItem?->unit_cost ?? 0);

                $supplierColumns = $rankedSuppliers->map(function (array $supplier) use ($rfq, $item, $quantity): array {
                    $entry = collect($rfq?->suppliers ?? [])->firstWhere('supplier_id', $supplier['supplier_id']);
                    $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $item->id);
                    $unitCost = $supplierItem?->unit_price;
                    $lineTotal = $unitCost !== null ? ((float) $unitCost * $quantity) : null;

                    return [
                        'unit_cost' => $unitCost !== null ? (float) $unitCost : null,
                        'total_amount' => $lineTotal,
                    ];
                })->values();

                return [
                    'quantity' => $quantity,
                    'unit' => (string) ($item->unit ?? ''),
                    'particulars' => (string) ($item->item_name ?? ''),
                    'approved_budget' => (float) $approvedBudget,
                    'supplier_columns' => $supplierColumns,
                ];
            })->values();

            return [
                'svp_no' => (string) ($rfq?->svp_no ?? 'N/A'),
                'rfq_date' => $rfq?->rfq_date,
                'project_name' => (string) ($rfq?->project_name ?? 'PROJECT'),
                'suppliers' => $rankedSuppliers,
                'items' => $items,
                'abc_total' => (float) ($rfq?->abc_amount ?? 0),
            ];
        })->values();

        return Pdf::view('pdf.bac-resolution', [
            'resolution' => $bacResolution,
            'batchAoqs' => $batchAoqs,
            'summaryRows' => $summaryRows,
            'abstracts' => $abstracts,
        ])
            ->format('a4')
            ->name('BAC-Resolution-'.$bacResolution->resolution_no.'.pdf')
            ->inline();
    }

    private function generateResolutionNumber(Carbon $resolutionDate): string
    {
        $year = $resolutionDate->format('Y');
        $prefix = $year.'-';

        $latest = BACResolution::query()
            ->where('resolution_no', 'like', $prefix.'%')
            ->orderByDesc('resolution_no')
            ->value('resolution_no');

        $next = 1;
        if ($latest && preg_match('/^\d{4}-(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        do {
            $resolutionNo = sprintf('%s%04d', $prefix, $next);
            $next++;
        } while (BACResolution::where('resolution_no', $resolutionNo)->exists());

        return $resolutionNo;
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
                $count++;
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

        usort($supplierTotals, fn ($left, $right) => $left['total_amount'] <=> $right['total_amount']);

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
