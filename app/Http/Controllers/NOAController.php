<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreNOARequest;
use App\Models\BACResolution;
use App\Models\NOA;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return Inertia::render('NOAs/Create', [
            'eligibleResolutions' => $eligibleResolutions,
        ]);
    }

    public function store(StoreNOARequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $resolution = BACResolution::with('aoq.rfq')->findOrFail($validated['bac_resolution_id']);

            if ($resolution->noa()->exists()) {
                return redirect()->back()->with('error', 'A Notice of Award already exists for this BAC Resolution.');
            }

            $noa = NOA::create([
                'bac_resolution_id' => $resolution->id,
                'noa_no' => $validated['noa_no'],
                'noa_date' => $validated['noa_date'],
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

        return Pdf::view('pdf.noa', [
            'noa' => $noa,
            'resolution' => $noa->bacResolution,
            'aoq' => $noa->bacResolution?->aoq,
            'rfq' => $noa->bacResolution?->aoq?->rfq,
            'winnerSupplier' => $noa->bacResolution?->aoq?->winnerSupplier,
        ])
            ->format('a4')
            ->name('NOA-' . $noa->noa_no . '.pdf')
            ->inline();
    }
}
