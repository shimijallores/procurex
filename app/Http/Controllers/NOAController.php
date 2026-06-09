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
            'aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.rfq.purchaseRequest.office',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('noa_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('bacResolution', function ($br) use ($search): void {
                        $br->where('resolution_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('project_name', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('aoq.rfq', function ($rfq) use ($search): void {
                        $rfq->where('project_name', 'like', sprintf('%%%s%%', $search))
                            ->orWhere('svp_no', 'like', sprintf('%%%s%%', $search));
                    })
                    ->orWhereHas('bacResolution.aoq.rfq', function ($rfq) use ($search): void {
                        $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->where(function ($officeQuery) use ($officeId): void {
                    $officeQuery
                        ->whereHas('aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId))
                        ->orWhereHas('bacResolution.aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
                });
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
            ->mapWithKeys(fn ($year) => [$year => $year])
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
            'noas:bac_resolution_id,aoq_id',
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.rfq.suppliers.supplierItems.rfqItem',
            'aoqs.winnerSupplier',
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
        ])
            ->latest('resolution_date')
            ->get()
            ->map(function (BACResolution $resolution): BACResolution {
                $allAoqs = $resolution->aoqs;
                if ($allAoqs->isEmpty() && $resolution->aoq) {
                    $allAoqs = collect([$resolution->aoq]);
                }

                $usedAoqIds = $resolution->noas
                    ->pluck('aoq_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $remainingAoqs = $allAoqs
                    ->filter(fn ($aoq) => ! in_array((int) $aoq->id, $usedAoqIds, true))
                    ->values();

                $resolution->setAttribute('remaining_aoqs', $remainingAoqs);

                return $resolution;
            })
            ->filter(fn (BACResolution $resolution) => collect($resolution->remaining_aoqs)->isNotEmpty())
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

        if (! $this->isWorkingDay($validated['noa_date'] ?? null)) {
            return redirect()->back()->withErrors([
                'noa_date' => 'NOA date must be a working day (not weekend/holiday).',
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            $resolution = BACResolution::with([
                'noas:bac_resolution_id,aoq_id',
                'aoqs.rfq',
                'aoqs.rfq.suppliers.supplierItems.rfqItem',
                'aoqs.winnerSupplier',
                'aoq.rfq',
                'aoq.rfq.suppliers.supplierItems.rfqItem',
                'aoq.winnerSupplier',
            ])->findOrFail($validated['bac_resolution_id']);

            $selectedAoqId = (int) ($validated['selected_aoq_id'] ?? 0);
            $selectedAoq = $resolution->aoqs->firstWhere('id', $selectedAoqId);

            if (! $selectedAoq && $resolution->aoq && (int) $resolution->aoq->id === $selectedAoqId) {
                $selectedAoq = $resolution->aoq;
            }

            if (! $selectedAoq) {
                return redirect()->back()->withErrors([
                    'selected_aoq_id' => 'The selected project does not belong to this BAC Resolution.',
                ])->withInput();
            }

            if (NOA::where('aoq_id', $selectedAoqId)->exists()) {
                return redirect()->back()->withErrors([
                    'selected_aoq_id' => 'A Notice of Award already exists for the selected project.',
                ])->withInput();
            }

            $rfq = $selectedAoq->rfq;
            if (! $rfq) {
                return redirect()->back()->with('error', 'The selected BAC Resolution has no linked RFQ.');
            }

            $resolutionDate = Carbon::parse((string) $resolution->resolution_date);
            $noaDate = Carbon::parse($validated['noa_date']);

            if ($noaDate->lt($resolutionDate)) {
                return redirect()->back()->withErrors([
                    'noa_date' => 'NOA date must be on or after the BAC Resolution date.',
                ])->withInput();
            }

            if (array_key_exists('calculation_label', $validated) && $validated['calculation_label']) {
                $resolution->update([
                    'calculation_label' => (string) $validated['calculation_label'],
                ]);
            }

            $noaNo = (string) ($rfq->svp_no ?? '');
            if ($noaNo === '') {
                return redirect()->back()->with('error', 'Unable to generate NOA number from RFQ SVP number.');
            }

            $noa = NOA::create([
                'bac_resolution_id' => $resolution->id,
                'aoq_id' => $selectedAoq->id,
                'noa_no' => $noaNo,
                'noa_date' => $noaDate->toDateString(),
                'recipient_name' => (string) ($validated['recipient_name'] ?? ''),
                'recipient_title' => (string) ($validated['recipient_title'] ?? ''),
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
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
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
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.winnerSupplier',
        ]);

        $resolution = $noa->bacResolution;
        $aoq = $noa->aoq ?: $resolution?->aoq;
        $rfq = $aoq?->rfq;
        $supplierName = (string) ($aoq?->winnerSupplier?->name ?? $resolution?->winner_supplier_name ?? '');
        $addressedSupplier = null;

        if ($supplierName !== '') {
            $addressedSupplier = Supplier::query()
                ->where('name', $supplierName)
                ->first();
        }

        $recipientTitle = trim((string) ($noa->recipient_title ?? ''));

        if ($recipientTitle === '' && $addressedSupplier) {
            $recipientName = trim((string) ($noa->recipient_name ?? ''));

            if ($recipientName !== '' && strcasecmp($recipientName, (string) $addressedSupplier->proprietor) === 0) {
                $recipientTitle = 'Proprietor';
            } elseif ($recipientName !== '' && strcasecmp($recipientName, (string) $addressedSupplier->authorized_representative) === 0) {
                $recipientTitle = 'Authorized Representative';
            } elseif ($recipientName !== '' && strcasecmp($recipientName, (string) $addressedSupplier->owner) === 0) {
                $recipientTitle = 'Owner';
            }
        }

        if ($recipientTitle === '') {
            $recipientTitle = 'Proprietor / Authorized Representative / Owner';
        }

        return Pdf::view('pdf.noa', [
            'noa' => $noa,
            'resolution' => $resolution,
            'aoq' => $aoq,
            'rfq' => $rfq,
            'winnerSupplier' => $aoq?->winnerSupplier,
            'addressedSupplier' => $addressedSupplier,
            'recipientTitle' => $recipientTitle,
        ])
            ->format('a4')
            ->name('NOA-'.$noa->noa_no.'.pdf')
            ->inline();
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
