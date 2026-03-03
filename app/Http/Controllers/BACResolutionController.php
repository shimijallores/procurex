<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBACResolutionRequest;
use App\Http\Requests\UpdateBACResolutionRequest;
use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\Calendar;
use App\Models\Office;
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
            'aoq.rfq.purchaseRequest.office',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('winner_supplier_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('aoq.rfq', function ($rfq) use ($search): void {
                            $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('aoq.rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('resolution_date', $fiscalYear);
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
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('BACResolutions/Index', [
            'resolutions' => $resolutions,
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
        $eligibleAoqs = AOQ::with([
            'rfq.purchaseRequest.office',
            'rfq.suppliers.supplierItems.rfqItem',
            'winnerSupplier',
        ])
            ->whereDoesntHave('bacResolution')
            ->latest('aoq_date')
            ->get()
            ->map(function (AOQ $aoq): AOQ {
                $calculatedSupplierCount = $this->countCalculatedSuppliers($aoq);
                $calculationLabel = $calculatedSupplierCount <= 1
                    ? 'Single Calculated'
                    : 'Lowest Calculated';

                $aoq->setAttribute('calculated_supplier_count', $calculatedSupplierCount);
                $aoq->setAttribute('calculation_label', $calculationLabel);
                $aoq->setAttribute('winner_amount', $this->calculateWinnerAmount($aoq));

                return $aoq;
            })
            ->values();

        $suggestedDate = $this->suggestNextWorkingDay()->toDateString();

        return Inertia::render('BACResolutions/Create', [
            'eligibleAoqs' => $eligibleAoqs,
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
            $aoq = AOQ::with([
                'rfq',
                'rfq.suppliers.supplierItems',
                'winnerSupplier',
            ])->findOrFail($validated['aoq_id']);

            if ($aoq->bacResolution()->exists()) {
                return redirect()->back()->with('error', 'A BAC Resolution already exists for this AOQ.');
            }

            $winnerAmount = $this->calculateWinnerAmount($aoq);
            $calculationLabel = $aoq->rfq?->suppliers?->filter(fn($entry) => (bool) $entry->submitted_at)->count() <= 1
                ? 'Single Calculated'
                : 'Lowest Calculated';

            $resolutionDate = Carbon::parse($validated['resolution_date']);

            $resolution = BACResolution::create([
                'aoq_id' => $aoq->id,
                'resolution_no' => $this->generateResolutionNumber($resolutionDate),
                'resolution_date' => $validated['resolution_date'],
                'meeting_date' => $validated['meeting_date'] ?? null,
                'project_name' => (string) $validated['project_name'],
                'winner_supplier_name' => (string) $validated['winner_supplier_name'],
                'winner_amount' => (float) ($validated['winner_amount'] ?? $winnerAmount),
                'calculation_label' => (string) ($validated['calculation_label'] ?? $calculationLabel),
                'justification' => $validated['justification']
                    ?? sprintf(
                        'for being the supplier with the %s and Responsive Quotation which is advantageous to the Provincial Government of Batangas.',
                        $calculationLabel
                    ),
                'signatory_chairperson' => $validated['signatory_chairperson'] ?? 'BAC Chairperson',
                'signatory_member_one' => $validated['signatory_member_one'] ?? 'BAC Member',
                'signatory_member_two' => $validated['signatory_member_two'] ?? 'BAC Member',
                'signatory_member_three' => $validated['signatory_member_three'] ?? 'BAC Member',
                'finalized_at' => now(),
            ]);

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
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items',
            'aoq.rfq.suppliers.supplier',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
        ]);

        $rfq = $bacResolution->aoq?->rfq;
        $bidderRows = collect();

        if ($rfq) {
            $bidderRows = $rfq->suppliers
                ->filter(fn($entry) => (bool) $entry->submitted_at)
                ->map(function ($entry): array {
                    $total = 0.0;

                    foreach ($entry->supplierItems as $supplierItem) {
                        if ($supplierItem->unit_price === null) {
                            continue;
                        }

                        $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
                        $total += $quantity * (float) $supplierItem->unit_price;
                    }

                    return [
                        'supplier_name' => strtoupper((string) ($entry->supplier?->name ?? 'N/A')),
                        'amount' => round($total, 2),
                    ];
                })
                ->sortBy('amount')
                ->values()
                ->map(function (array $row, int $index): array {
                    $rank = $index + 1;

                    return [
                        'supplier_name' => $row['supplier_name'],
                        'amount' => $row['amount'],
                        'rank_label' => $rank === 1 ? '1st' : ($rank === 2 ? '2nd' : ($rank === 3 ? '3rd' : $rank . 'th')),
                    ];
                });
        }

        return Pdf::view('pdf.bac-resolution', [
            'resolution' => $bacResolution,
            'aoq' => $bacResolution->aoq,
            'rfq' => $rfq,
            'bidderRows' => $bidderRows,
        ])
            ->format('a4')
            ->name('BAC-Resolution-' . $bacResolution->resolution_no . '.pdf')
            ->inline();
    }

    private function generateResolutionNumber(Carbon $resolutionDate): string
    {
        $year = $resolutionDate->format('Y');
        $prefix = $year . '-';

        $latest = BACResolution::query()
            ->where('resolution_no', 'like', $prefix . '%')
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

        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isWeekend()) {
            return false;
        }

        $calendarEntry = Calendar::where('date', $date)->first();
        if (! $calendarEntry) {
            return true;
        }

        return (bool) $calendarEntry->is_working_day;
    }

    private function suggestNextWorkingDay(): Carbon
    {
        $date = now()->addDay()->startOfDay();

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
}
