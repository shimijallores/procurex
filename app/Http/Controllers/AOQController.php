<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\AOQTemplateExport;
use App\Http\Requests\StoreAOQRequest;
use App\Http\Requests\UpdateAOQRequest;
use App\Imports\AOQMatrixImport;
use App\Models\AOQ;
use App\Models\Batch;
use App\Models\Calendar;
use App\Models\RFQ;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use App\Models\Supplier;
use App\Services\SvpMatrixSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AOQController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AOQ::with([
            'rfq.purchaseRequest.office',
            'rfq.suppliers.supplier',
            'winnerSupplier',
            'batch',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->whereHas('rfq', function ($rfq) use ($search): void {
                    $rfq->where('svp_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('purchaseRequest', fn ($pr) => $pr->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('office', fn ($o) => $o->where('name', 'like', sprintf('%%%s%%', $search))));
                })->orWhereHas('winnerSupplier', fn ($s) => $s->where('name', 'like', sprintf('%%%s%%', $search)));
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->whereHas('rfq.purchaseRequest', fn ($pr) => $pr->where('office_id', $officeId));
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereYear('aoq_date', $fiscalYear);
            })
            ->when($request->batch_id, function ($q, string $batchId): void {
                $q->where('batch_id', $batchId);
            });

        $lengthAwarePaginator = (clone $query)
            ->latest('aoq_date')
            ->paginate(10)
            ->withQueryString()
            ->through(function (AOQ $aoq): AOQ {
                $calculation = $this->calculateSupplierTotals($aoq->rfq);
                $aoq->setAttribute('calculated_supplier_count', $calculation['calculated_supplier_count']);
                $aoq->setAttribute('calculation_mode', $calculation['calculation_mode']);

                return $aoq;
            });

        $all = (clone $query)->get();
        $singleCalculated = 0;
        $lowestCalculated = 0;
        foreach ($all as $aoq) {
            $calculation = $this->calculateSupplierTotals($aoq->rfq);
            if ($calculation['calculation_mode'] === 'single_calculated') {
                ++$singleCalculated;
            }

            if ($calculation['calculation_mode'] === 'lowest_calculated') {
                ++$lowestCalculated;
            }
        }

        $stats = [
            'total' => $all->count(),
            'single_calculated' => $singleCalculated,
            'lowest_calculated' => $lowestCalculated,
            'without_winner' => (clone $query)->whereNull('winner_supplier_id')->count(),
        ];

        $offices = \App\Models\Office::orderBy('name')->get(['id', 'name']);
        $batches = Batch::withCount('aoqs')
            ->orderByDesc('id')
            ->get(['id', 'batch_no']);
        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year): array => [$year => $year])
            ->reverse();

        return Inertia::render('AOQs/Index', [
            'aoqs' => $lengthAwarePaginator,
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
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'contact_number']);

        $batches = Batch::withCount('aoqs')
            ->orderByDesc('id')
            ->get();

        $batch = Batch::whereNotNull('earmark_date_from')
            ->whereNotNull('earmark_date_to')
            ->where('is_locked', false)
            ->latest('id')
            ->first();

        $displayBatch = $batch;
        $activeEarmarkBatch = $batch;

        return Inertia::render('AOQs/Create', [
            'suppliers' => $suppliers,
            'batches' => $batches,
            'defaultAoqDate' => $this->suggestNextWorkingDay()->toDateString(),
            'activeEarmarkBatch' => $activeEarmarkBatch,
            'displayBatch' => $displayBatch,
        ]);
    }

    public function checkActiveEarmark(): JsonResponse
    {
        $today = Carbon::now('Asia/Manila')->toDateString();

        $batch = Batch::whereNotNull('earmark_date_from')
            ->whereNotNull('earmark_date_to')
            ->where('earmark_date_from', '<=', $today)
            ->where('earmark_date_to', '>=', $today)
            ->where('is_locked', false)
            ->latest('id')
            ->first();

        return response()->json([
            'batch' => $batch,
        ]);
    }

    public function findRfqBySvp(Request $request): JsonResponse
    {
        $svpNo = $request->query('svp_no');

        if (! $svpNo) {
            return response()->json(['error' => 'SVP number is required.'], 422);
        }

        $rfq = RFQ::with([
            'purchaseRequest.office',
            'items.purchaseRequestItem',
        ])
            ->where('svp_no', $svpNo)
            ->whereNotNull('pr_id')
            ->whereDoesntHave('aoq')
            ->first();

        if (! $rfq) {
            return response()->json(['error' => 'RFQ not found or already has an AOQ.'], 404);
        }

        return response()->json(['rfq' => $rfq]);
    }

    public function findOrCreateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'earmark_date_from' => ['required', 'date'],
            'earmark_date_to' => ['required', 'date', 'after_or_equal:earmark_date_from'],
        ]);

        $earmarkFrom = $validated['earmark_date_from'];
        $earmarkTo = $validated['earmark_date_to'];

        // If today falls within an existing non-locked batch's earmark range,
        // use that batch regardless of the submitted dates
        $activeBatch = Batch::whereNotNull('earmark_date_from')
            ->whereNotNull('earmark_date_to')
            ->where('earmark_date_from', '<=', Carbon::now('Asia/Manila')->toDateString())
            ->where('earmark_date_to', '>=', Carbon::now('Asia/Manila')->toDateString())
            ->where('is_locked', false)
            ->latest('id')
            ->first();

        if ($activeBatch) {
            return response()->json([
                'batch' => $activeBatch,
                'is_new' => false,
            ]);
        }

        // If no active batch covers today, look for an exact match of the submitted earmark dates.
        $existing = Batch::where('earmark_date_from', $earmarkFrom)
            ->where('earmark_date_to', $earmarkTo)
            ->where('is_locked', false)
            ->latest('id') // Add latest('id') for consistency
            ->first();

        if ($existing) {
            return response()->json([
                'batch' => $existing,
                'is_new' => false,
            ]);
        }

        // If no active or exact match, create a new batch.
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

        $batch = Batch::create([
            'batch_no' => $batchNo,
            'earmark_date_from' => $earmarkFrom,
            'earmark_date_to' => $earmarkTo,
        ]);

        return response()->json([
            'batch' => $batch,
            'is_new' => true,
        ]);
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

    public function destroyBatch(Batch $batch): JsonResponse
    {
        abort_if($batch->aoqs()->exists(), 422, 'Cannot delete a batch with existing AOQs.');

        $batch->delete();

        return response()->json(['success' => true]);
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

    public function store(StoreAOQRequest $storeAOQRequest): RedirectResponse
    {
        $validated = $storeAOQRequest->validated();

        DB::beginTransaction();
        try {
            $rfq = RFQ::with([
                'purchaseRequest.items',
                'suppliers.supplier',
                'suppliers.supplierItems.rfqItem.purchaseRequestItem',
            ])->findOrFail($validated['rfq_id']);

            if (! $rfq->purchaseRequest) {
                return redirect()->back()->with('error', 'The selected RFQ is not linked to a Purchase Request.');
            }

            if ($rfq->aoq()->exists()) {
                return redirect()->back()->with('error', 'An AOQ already exists for this RFQ.');
            }

            $rfqItemIds = $rfq->items->pluck('id')->map(fn ($id): int => (int) $id)->all();
            if (count($rfqItemIds) === 0) {
                return redirect()->back()->with('error', 'This RFQ has no items to evaluate.');
            }

            $supplierTotals = [];
            foreach ($validated['quotations'] as $quotation) {
                $unitPrices = $quotation['unit_prices'] ?? [];
                $hasPrice = false;
                $runningTotal = 0.0;

                foreach ($rfq->items as $rfqItem) {
                    $rawPrice = $unitPrices[$rfqItem->id] ?? null;

                    if ($rawPrice === null || $rawPrice === '') {
                        continue;
                    }

                    $hasPrice = true;
                    $runningTotal += (float) $rawPrice * (int) $rfqItem->quantity;
                }

                if (! $hasPrice) {
                    continue;
                }

                $supplierTotals[] = [
                    'supplier_id' => (int) $quotation['supplier_id'],
                    'total_amount' => round($runningTotal, 2),
                ];
            }

            if ($supplierTotals === []) {
                return redirect()->back()->with('error', 'Please enter at least one supplier quotation with unit prices.');
            }

            usort($supplierTotals, fn (array $a, array $b): int => $a['total_amount'] <=> $b['total_amount']);
            $winnerSupplierId = $supplierTotals[0]['supplier_id'];

            // Reset previous RFQ quotation records so recreated AOQs don't reuse stale supplier items.
            $rfq->loadMissing('suppliers.supplierItems');
            foreach ($rfq->suppliers as $existingSupplier) {
                $existingSupplier->supplierItems()->delete();
            }

            $rfq->suppliers()->delete();

            foreach ($validated['quotations'] as $quotation) {
                $rfqSupplier = RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $quotation['supplier_id'],
                ]);

                $submittedAt = $quotation['submitted_at'] ?? null;
                $isLate = false;
                if ($submittedAt && $rfq->submission_deadline) {
                    $isLate = Carbon::parse($submittedAt)->greaterThan(Carbon::parse($rfq->submission_deadline)->endOfDay());
                }

                $rfqSupplier->update([
                    'submitted_at' => $submittedAt,
                    'is_late' => $isLate,
                    'remarks' => $quotation['remarks'] ?? null,
                ]);

                $rfqSupplier->supplierItems()->delete();

                $unitPrices = $quotation['unit_prices'] ?? [];
                foreach ($rfq->items as $rfqItem) {
                    $rawPrice = $unitPrices[$rfqItem->id] ?? null;

                    RFQSupplierItem::create([
                        'rfq_supplier_id' => $rfqSupplier->id,
                        'rfq_item_id' => $rfqItem->id,
                        'unit_price' => $rawPrice === '' ? null : $rawPrice,
                    ]);
                }
            }

            $aoq = AOQ::create([
                'rfq_id' => $rfq->id,
                'batch_id' => $validated['batch_id'],
                'aoq_date' => $validated['aoq_date'],
                'winner_supplier_id' => $winnerSupplierId,
            ]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create AOQ. Please try again.');
        }

        SvpMatrixSyncService::syncAbstractValue($aoq);

        return redirect()->route('aoqs.show', $aoq)
            ->with('success', 'AOQ created successfully.');
    }

    public function show(AOQ $aoq): Response
    {
        $aoq->load([
            'rfq.purchaseRequest.office',
            'rfq.purchaseRequest.items.emanatingItem.ppmpItem',
            'rfq.items.purchaseRequestItem.emanatingItem.ppmpItem',
            'rfq.suppliers.supplier',
            'rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'winnerSupplier',
            'batch',
        ]);

        $calculation = $this->calculateSupplierTotals($aoq->rfq);

        return Inertia::render('AOQs/Show', [
            'aoq' => $aoq,
            'calculation' => $calculation,
        ]);
    }

    public function destroy(AOQ $aoq): RedirectResponse
    {
        SvpMatrixSyncService::syncAbstractValue($aoq);

        DB::transaction(function () use ($aoq): void {
            $aoq->loadMissing('rfq.suppliers.supplierItems');

            foreach ($aoq->rfq?->suppliers ?? [] as $supplier) {
                $supplier->supplierItems()->delete();
            }

            $aoq->rfq?->suppliers()->delete();
            $aoq->delete();
        });

        return redirect()->route('aoqs.index')->with('success', 'AOQ deleted successfully.');
    }

    public function downloadTemplate(AOQ $aoq): BinaryFileResponse
    {
        $aoq->load([
            'rfq.items.purchaseRequestItem',
            'rfq.suppliers.supplier',
        ]);

        $base = $aoq->rfq->suppliers->pluck('supplier.name')->filter()->values()->all();
        $supplierNames = [];
        foreach ($base as $i => $name) {
            $supplierNames[$i] = $name;
        }

        $supplierCount = count($supplierNames);

        $aoqTemplateExport = new AOQTemplateExport($aoq->rfq, $supplierCount, $supplierNames);

        return Excel::download($aoqTemplateExport, sprintf('aoq-template-%s.xlsx', $aoq->rfq->svp_no ?? $aoq->id));
    }

    public function importMatrix(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:10240'],
            'rfq_id' => ['required', 'integer', 'exists:rfqs,id'],
            'supplier_count' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $rfq = RFQ::with('items.purchaseRequestItem')->findOrFail((int) $request->rfq_id);
        $supplierCount = (int) $request->supplier_count;

        $aoqMatrixImport = new AOQMatrixImport($rfq, $supplierCount);
        Excel::import($aoqMatrixImport, $request->file('file'));

        return response()->json([
            'unit_prices' => $aoqMatrixImport->parsedUnitPrices,
        ]);
    }

    public function edit(AOQ $aoq): Response
    {
        $aoq->load([
            'rfq.purchaseRequest.office',
            'rfq.items.purchaseRequestItem',
            'rfq.suppliers.supplier',
            'rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'contact_number']);

        $batches = Batch::withCount('aoqs')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('AOQs/Edit', [
            'aoq' => $aoq,
            'suppliers' => $suppliers,
            'batches' => $batches,
        ]);
    }

    public function update(UpdateAOQRequest $updateAOQRequest, AOQ $aoq): RedirectResponse
    {
        $validated = $updateAOQRequest->validated();

        DB::beginTransaction();
        try {
            $rfq = $aoq->rfq()->with([
                'items.purchaseRequestItem',
                'suppliers.supplierItems',
            ])->firstOrFail();

            if (! $rfq->purchaseRequest) {
                return redirect()->back()->with('error', 'The associated RFQ is not linked to a Purchase Request.');
            }

            // Clear existing supplier quotation data
            foreach ($rfq->suppliers as $existingSupplier) {
                $existingSupplier->supplierItems()->delete();
            }

            $rfq->suppliers()->delete();

            // Recreate from form data
            foreach ($validated['quotations'] as $quotation) {
                $rfqSupplier = RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $quotation['supplier_id'],
                ]);

                $submittedAt = $quotation['submitted_at'] ?? null;
                $isLate = false;
                if ($submittedAt && $rfq->submission_deadline) {
                    $isLate = Carbon::parse($submittedAt)->greaterThan(Carbon::parse($rfq->submission_deadline)->endOfDay());
                }

                $rfqSupplier->update([
                    'submitted_at' => $submittedAt,
                    'is_late' => $isLate,
                    'remarks' => $quotation['remarks'] ?? null,
                ]);

                $unitPrices = $quotation['unit_prices'] ?? [];
                foreach ($rfq->items as $rfqItem) {
                    $rawPrice = $unitPrices[$rfqItem->id] ?? null;

                    RFQSupplierItem::create([
                        'rfq_supplier_id' => $rfqSupplier->id,
                        'rfq_item_id' => $rfqItem->id,
                        'unit_price' => $rawPrice === '' ? null : $rawPrice,
                    ]);
                }
            }

            // Recalculate winner
            $rfq->load('suppliers.supplierItems.rfqItem');
            $supplierTotals = [];
            foreach ($rfq->suppliers as $rfqSupplier) {
                $total = 0.0;
                $hasPrice = false;

                foreach ($rfqSupplier->supplierItems as $supplierItem) {
                    if ($supplierItem->unit_price === null) {
                        continue;
                    }

                    $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
                    $total += $quantity * (float) $supplierItem->unit_price;
                    $hasPrice = true;
                }

                if (! $hasPrice) {
                    continue;
                }

                $supplierTotals[] = [
                    'supplier_id' => $rfqSupplier->supplier_id,
                    'total_amount' => round($total, 2),
                ];
            }

            $winnerSupplierId = null;
            if ($supplierTotals !== []) {
                usort($supplierTotals, fn (array $a, array $b): int => $a['total_amount'] <=> $b['total_amount']);
                $winnerSupplierId = $supplierTotals[0]['supplier_id'];
            }

            $aoq->update([
                'batch_id' => $validated['batch_id'],
                'aoq_date' => $validated['aoq_date'],
                'winner_supplier_id' => $winnerSupplierId,
            ]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to update AOQ. Please try again.');
        }

        SvpMatrixSyncService::syncAbstractValue($aoq);

        return redirect()->route('aoqs.show', $aoq)
            ->with('success', 'AOQ updated successfully.');
    }

    public function printPdf(AOQ $aoq): \Spatie\LaravelPdf\PdfBuilder
    {
        $aoq->load([
            'rfq.purchaseRequest.office',
            'rfq.items.purchaseRequestItem.emanatingItem.ppmpItem',
            'rfq.suppliers.supplier',
            'rfq.suppliers.supplierItems.rfqItem.purchaseRequestItem',
            'winnerSupplier',
        ]);

        $calculation = $this->calculateSupplierTotals($aoq->rfq);

        return Pdf::view('pdf.aoq', [
            'aoq' => $aoq,
            'rfq' => $aoq->rfq,
            'calculation' => $calculation,
        ])
            ->format('a4')
            ->landscape()
            ->name('AOQ-'.($aoq->rfq?->svp_no ?? $aoq->id).'.pdf')
            ->inline();
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

        usort($supplierTotals, fn (array $a, array $b): int => $a['total_amount'] <=> $b['total_amount']);

        $count = count($supplierTotals);
        $winner = $count > 0 ? $supplierTotals[0] : null;

        $calculationMode = 'single_calculated';
        if ($count >= 2) {
            $calculationMode = 'lowest_calculated';
        }

        return [
            'supplier_totals' => $supplierTotals,
            'calculated_supplier_count' => $count,
            'calculation_mode' => $calculationMode,
            'winner_supplier_id' => $winner['supplier_id'] ?? null,
            'winner_total_amount' => $winner['total_amount'] ?? null,
        ];
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

    protected function today(): Carbon
    {
        return now()->startOfDay();
    }
}
