<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNOARequest;
use App\Models\BACResolution;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class NOAController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NOA::with([
            'bacResolution.aoq.rfq.purchaseRequest.office',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('noa_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('bacResolution', function ($br) use ($search): void {
                        $br->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('project_name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('bacResolution.aoq.rfq', function ($rfq) use ($search): void {
                        $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('bacResolution.aoq.rfq.purchaseRequest', fn($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('noa_date', $fiscalYear);
            });

        $noas = (clone $query)
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
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('NOAs/Index', [
            'noas' => $noas,
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
        $eligibleResolutions = BACResolution::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
        ])
            ->whereDoesntHave('noa')
            ->latest('resolution_date')
            ->get();

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
            'eligibleResolutions' => $eligibleResolutions,
            'suppliers' => $suppliers,
            'defaultResolutionDate' => $suggestedDate,
            'defaultNoaDate' => $suggestedDate,
        ]);
    }

    public function store(StoreNOARequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! $this->isWorkingDay($validated['resolution_date'] ?? null)) {
            return redirect()->back()->withErrors([
                'resolution_date' => 'Resolution date must be a working day (not weekend/holiday).',
            ])->withInput();
        }

        if (! $this->isWorkingDay($validated['noa_date'] ?? null)) {
            return redirect()->back()->withErrors([
                'noa_date' => 'NOA date must be a working day (not weekend/holiday).',
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            $resolution = BACResolution::with([
                'aoq.rfq',
                'aoq.winnerSupplier',
            ])->findOrFail($validated['bac_resolution_id']);

            if ($resolution->noa()->exists()) {
                return redirect()->back()->with('error', 'A Notice of Award already exists for this BAC Resolution.');
            }

            $rfq = $resolution->aoq?->rfq;
            if (! $rfq) {
                return redirect()->back()->with('error', 'The selected BAC Resolution has no linked RFQ.');
            }

            $resolutionDate = Carbon::parse($validated['resolution_date'] ?? $resolution->resolution_date);
            $noaDate = Carbon::parse($validated['noa_date']);

            if ($noaDate->lt($resolutionDate)) {
                return redirect()->back()->withErrors([
                    'noa_date' => 'NOA date must be on or after the BAC Resolution date.',
                ])->withInput();
            }

            $supplierName = trim((string) ($validated['winner_supplier_name'] ?? ''));
            if ($supplierName === '') {
                $supplierName = (string) ($resolution->aoq?->winnerSupplier?->name ?? $resolution->winner_supplier_name ?? 'N/A');
            }

            $resolution->update([
                'resolution_no' => (string) ($validated['resolution_no'] ?? $resolution->resolution_no),
                'resolution_date' => $resolutionDate->toDateString(),
                'project_name' => (string) ($rfq->project_name ?? $resolution->project_name),
                'winner_supplier_name' => $supplierName,
                'calculation_label' => (string) ($validated['calculation_label'] ?? $resolution->calculation_label),
            ]);

            $noaNo = (string) ($rfq->svp_no ?? '');
            if ($noaNo === '') {
                return redirect()->back()->with('error', 'Unable to generate NOA number from RFQ SVP number.');
            }

            $noa = NOA::create([
                'bac_resolution_id' => $resolution->id,
                'noa_no' => $noaNo,
                'noa_date' => $noaDate->toDateString(),
                'recipient_name' => (string) ($validated['recipient_name'] ?? ''),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create Notice of Award. Please try again.');
        }

        return redirect()->route('noas.show', $noa)
            ->with('success', 'Notice of Award created successfully.');
    }

    public function show(NOA $noa): Response
    {
        $noa->load([
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.winnerSupplier',
        ]);

        return Inertia::render('NOAs/Show', [
            'noa' => $noa,
        ]);
    }

    public function destroy(NOA $noa): RedirectResponse
    {
        $noa->delete();

        return redirect()->route('noas.index')
            ->with('success', 'Notice of Award deleted successfully.');
    }

    public function printPdf(NOA $noa)
    {
        $noa->load([
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.winnerSupplier',
        ]);

        $resolution = $noa->bacResolution;
        $supplierName = (string) ($resolution?->winner_supplier_name ?? $resolution?->aoq?->winnerSupplier?->name ?? '');
        $addressedSupplier = null;

        if ($supplierName !== '') {
            $addressedSupplier = Supplier::query()
                ->where('name', $supplierName)
                ->first();
        }

        return Pdf::view('pdf.noa', [
            'noa' => $noa,
            'resolution' => $resolution,
            'aoq' => $resolution?->aoq,
            'rfq' => $resolution?->aoq?->rfq,
            'winnerSupplier' => $resolution?->aoq?->winnerSupplier,
            'addressedSupplier' => $addressedSupplier,
        ])
            ->format('a4')
            ->name('NOA-' . $noa->noa_no . '.pdf')
            ->inline();
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
}
