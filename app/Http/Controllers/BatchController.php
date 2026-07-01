<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchRequest;
use App\Http\Requests\UpdateBatchRequest;
use App\Models\AOQ;
use App\Models\Batch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BatchController extends Controller
{
    public function index(Request $request): Response
    {
        Batch::where('is_locked', false)
            ->whereNotNull('earmark_date_to')
            ->whereDate('earmark_date_to', '<', Carbon::now('Asia/Manila')->toDateString())
            ->update(['is_locked' => true]);

        $query = Batch::withCount('aoqs')
            ->when($request->search, function ($q, string $search): void {
                $q->where('batch_no', 'like', sprintf('%%%s%%', $search));
            });

        $batches = (clone $query)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'with_aoqs' => (clone $query)->whereHas('aoqs')->count(),
        ];

        return Inertia::render('Batches/Index', [
            'batches' => $batches,
            'stats' => $stats,
            'filters' => [
                'search' => $request->search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Batches/Create');
    }

    public function store(StoreBatchRequest $storeBatchRequest): RedirectResponse
    {
        $batch = Batch::create($storeBatchRequest->validated());

        return redirect()->route('batches.show', $batch)
            ->with('success', 'Batch created successfully.');
    }

    public function show(Batch $batch): Response
    {
        if (
            ! $batch->is_locked
            && $batch->earmark_date_to
            && $batch->earmark_date_to->toDateString() < Carbon::now('Asia/Manila')->toDateString()
        ) {
            $batch->update(['is_locked' => true]);
        }

        $batch->load(['aoqs.rfq.purchaseRequest.office', 'aoqs.winnerSupplier']);

        return Inertia::render('Batches/Show', [
            'batch' => $batch,
        ]);
    }

    public function edit(Batch $batch): Response
    {
        if (
            ! $batch->is_locked
            && $batch->earmark_date_to
            && $batch->earmark_date_to->toDateString() < Carbon::now('Asia/Manila')->toDateString()
        ) {
            $batch->update(['is_locked' => true]);
        }

        $batch->load(['aoqs.rfq.purchaseRequest.office', 'aoqs.winnerSupplier']);

        return Inertia::render('Batches/Edit', [
            'batch' => $batch,
        ]);
    }

    public function update(UpdateBatchRequest $updateBatchRequest, Batch $batch): RedirectResponse
    {
        $batch->update($updateBatchRequest->validated());

        return redirect()->route('batches.show', $batch)
            ->with('success', 'Batch updated successfully.');
    }

    public function destroy(Request $request, Batch $batch): JsonResponse|RedirectResponse
    {
        if ($batch->aoqs()->exists()) {
            if ($request->expectsJson()) {
                abort(422, 'Cannot delete a batch with existing AOQs.');
            }

            return redirect()->back()->with('error', 'Cannot delete a batch with existing AOQs.');
        }

        $batch->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('batches.index')
            ->with('success', 'Batch deleted successfully.');
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $customBatchNo = $request->input('batch_no');

        if ($customBatchNo !== null && $customBatchNo !== '') {
            $existing = Batch::where('batch_no', $customBatchNo)->first();

            if ($existing) {
                return response()->json($existing, 409);
            }

            $batch = Batch::create(['batch_no' => $customBatchNo]);

            return response()->json($batch);
        }

        $year = now()->format('y');
        $prefix = $year;

        $latest = Batch::query()
            ->where('batch_no', 'like', $prefix.'%')
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $next = 1;
        if ($latest && preg_match('/^\d{2}(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        $batchNo = sprintf('%s%04d', $prefix, $next);

        while (Batch::where('batch_no', $batchNo)->exists()) {
            ++$next;
            $batchNo = sprintf('%s%04d', $prefix, $next);
        }

        $batch = Batch::create(['batch_no' => $batchNo]);

        return response()->json($batch);
    }

    public function recentBatches(Request $request): JsonResponse
    {
        $query = Batch::query();

        if ($request->input('context') === 'po-transmittal') {
            $query->whereHas('aoqs', function ($q): void {
                $q->whereHas('noa.purchaseOrder', function ($po): void {
                    $po->whereDoesntHave('poTransmittals');
                });
            });
        }

        if ($request->input('context') === 'bac-resolution') {
            $query->has('aoqs')
                ->whereDoesntHave('aoqs.bacResolution')
                ->whereDoesntHave('aoqs.bacResolutions');
        }

        $batches = $query->latest()
            ->take(5)
            ->get(['id', 'batch_no']);

        return response()->json(['batches' => $batches]);
    }

    public function updateDates(Request $request, Batch $batch): JsonResponse
    {
        $validated = $request->validate([
            'rfq_date' => ['nullable', 'date'],
            'aoq_date' => ['nullable', 'date'],
            'bac_date' => ['nullable', 'date'],
            'noa_date' => ['nullable', 'date'],
            'po_date' => ['nullable', 'date'],
        ]);

        $batch->update($validated);

        return response()->json(['success' => true, 'batch' => $batch]);
    }

    public function suggestBatch(): JsonResponse
    {
        $year = now()->format('y');
        $prefix = $year;

        $latest = Batch::query()
            ->where('batch_no', 'like', $prefix.'%')
            ->orderByDesc('batch_no')
            ->value('batch_no');

        $next = 1;
        if ($latest && preg_match('/^\d{2}(\d{4})$/', $latest, $matches) === 1) {
            $next = (int) $matches[1] + 1;
        }

        $batchNo = sprintf('%s%04d', $prefix, $next);

        return response()->json(['batch_no' => $batchNo]);
    }

    public function availableAoqs(Request $request): JsonResponse
    {
        $search = $request->query('search');

        $aoqs = AOQ::with(['rfq.purchaseRequest.office', 'winnerSupplier'])
            ->whereNull('batch_id')
            ->when($search, function ($q, string $search): void {
                $q->whereHas('rfq', fn ($rfq) => $rfq->where('project_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('svp_no', 'like', sprintf('%%%s%%', $search)));
            })
            ->latest('aoq_date')
            ->get()
            ->map(function (AOQ $aoq): array {
                return [
                    'id' => $aoq->id,
                    'svp_no' => $aoq->rfq?->svp_no ?? '—',
                    'project_name' => $aoq->rfq?->project_name ?? '—',
                    'office_name' => $aoq->rfq?->purchaseRequest?->office?->name ?? '—',
                    'winner_supplier' => $aoq->winnerSupplier?->name ?? '—',
                    'aoq_date' => $aoq->aoq_date?->format('Y-m-d'),
                ];
            })
            ->values();

        return response()->json(['aoqs' => $aoqs]);
    }
}
