<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNOARequest;
use App\Models\AOQ;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\NOA;
use App\Models\Office;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Http\JsonResponse;
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
            'aoq.batch',
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
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('aoq.rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('noa_date', $fiscalYear);
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->whereHas('aoq', fn ($aoq) => $aoq->where('batch_id', $batchId));
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
        $batches = Batch::withCount(['aoqs' => function ($q): void {
            $q->whereDoesntHave('noa');
        }])
            ->whereHas('aoqs', fn ($q) => $q->whereDoesntHave('noa'))
            ->latest()
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
            'batches' => $batches,
            'suppliers' => $suppliers,
            'defaultNoaDate' => $suggestedDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'noas' => ['required', 'array', 'min:1'],
            'noas.*.aoq_id' => ['required', 'integer', 'exists:aoqs,id'],
            'noas.*.noa_date' => ['required', 'date'],
            'noas.*.recipient_name' => ['nullable', 'string', 'max:255'],
            'noas.*.recipient_title' => ['nullable', 'string', 'max:255'],
        ]);

        $batch = Batch::with('aoqs')->findOrFail($validated['batch_id']);
        $batchAoqIds = $batch->aoqs->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $errors = [];
        $created = [];

        DB::beginTransaction();
        try {
            foreach ($validated['noas'] as $index => $noaData) {
                $aoqId = (int) $noaData['aoq_id'];

                if (! in_array($aoqId, $batchAoqIds, true)) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf('AOQ #%d is not in the selected batch.', $aoqId);

                    continue;
                }

                if (NOA::where('aoq_id', $aoqId)->exists()) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = 'NOA already exists for this AOQ.';

                    continue;
                }

                if (! $this->isWorkingDay($noaData['noa_date'] ?? null)) {
                    $errors[sprintf('noas.%s.noa_date', $index)] = 'NOA date must be a working day (not weekend/holiday).';

                    continue;
                }

                $aoq = AOQ::with(['rfq', 'winnerSupplier'])->find($aoqId);
                if (! $aoq?->rfq) {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf('AOQ #%d has no linked RFQ.', $aoqId);

                    continue;
                }

                $noaNo = (string) ($aoq->rfq->svp_no ?? '');
                if ($noaNo === '') {
                    $errors[sprintf('noas.%s.aoq_id', $index)] = sprintf('Unable to generate NOA number from AOQ #%d.', $aoqId);

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

            return redirect()->back()->with('error', 'Failed to create Notices of Award. Please try again.');
        }

        $count = count($created);

        return redirect()->route('noas.index')
            ->with('success', $count . ' Notice(s) of Award created successfully.');
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
                $aoq->setAttribute('winner_amount', $this->calculateWinnerAmount($aoq));

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

        return response()->json([
            'aoqs' => $aoqs,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(UpdateNOARequest $updateNOARequest, NOA $noa): RedirectResponse
    {
        $validated = $updateNOARequest->validated();

        $noa->update([
            'noa_date' => $validated['noa_date'],
            'recipient_name' => (string) ($validated['recipient_name'] ?? ''),
            'recipient_title' => (string) ($validated['recipient_title'] ?? ''),
        ]);

        return redirect()->route('noas.show', $noa)
            ->with('success', 'Notice of Award updated successfully.');
    }

    public function printBatch(Batch $batch)
    {
        $noas = NOA::with([
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.winnerSupplier',
        ])
            ->whereHas('aoq', fn ($q) => $q->where('batch_id', $batch->id))
            ->get();

        if ($noas->isEmpty()) {
            return redirect()->back()->with('error', 'No NOAs found in this batch.');
        }

        $pdf = DomPdf::loadView('pdf.noas-batch', [
            'noas' => $noas,
            'batch' => $batch,
        ]);

        return $pdf->setPaper('a4')
            ->stream(sprintf('NOAs-Batch-%s.pdf', $batch->batch_no));
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

    public function printPdf(NOA $noa): \Spatie\LaravelPdf\PdfBuilder
    {
        $noa->load([
            'aoq.rfq.purchaseRequest.office',
            'aoq.winnerSupplier',
            'bacResolution.aoq.rfq.purchaseRequest.office',
            'bacResolution.aoq.winnerSupplier',
        ]);

        $resolution = $noa->bacResolution;
        $aoq = $noa->aoq ?? $resolution?->aoq;
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
