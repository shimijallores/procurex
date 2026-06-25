<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Models\AOQ;
use App\Models\Batch;
use App\Models\BatchAoqRequest;
use App\Models\RFQ;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BatchAoqRequestController extends Controller
{
    public function index(): Response
    {
        $lengthAwarePaginator = BatchAoqRequest::with([
            'batch',
            'requester',
            'approver',
        ])
            ->latest()
            ->paginate(20);

        return Inertia::render('BatchAoqRequests/Index', [
            'requests' => $lengthAwarePaginator,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'request_data' => ['required', 'array'],
            'request_data.rfq_id' => ['required', 'integer', 'exists:rfqs,id'],
            'request_data.aoq_date' => ['required', 'date'],
            'request_data.quotations' => ['required', 'array', 'min:1'],
        ]);

        $existing = BatchAoqRequest::where('batch_id', $validated['batch_id'])
            ->where('requested_by', Auth::id())
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            return redirect()->back()->with('error', 'You already have a pending request for this batch.');
        }

        $batch = Batch::findOrFail($validated['batch_id']);

        if (! $batch->is_locked) {
            return redirect()->back()->with('error', 'This batch is not locked. You can assign AOQs directly.');
        }

        BatchAoqRequest::create([
            'batch_id' => $batch->id,
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'reason' => $validated['reason'] ?? null,
            'request_data' => $validated['request_data'],
        ]);

        return redirect()->back()->with('success', 'Your request has been submitted to SuperAdmin for approval.');
    }

    public function approve(BatchAoqRequest $batchAoqRequest): RedirectResponse
    {
        if (! Auth::user()?->hasRole(RoleType::SUPERADMIN->value)) {
            abort(403, 'Only SuperAdmin can approve requests.');
        }

        if ($batchAoqRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $data = $batchAoqRequest->request_data;

        if (! $data || ! isset($data['rfq_id'])) {
            return redirect()->back()->with('error', 'Invalid request data.');
        }

        DB::beginTransaction();

        try {
            $rfq = RFQ::with([
                'purchaseRequest.items',
                'suppliers.supplier',
                'suppliers.supplierItems.rfqItem.purchaseRequestItem',
            ])->findOrFail($data['rfq_id']);

            if (! $rfq->purchaseRequest) {
                DB::rollBack();

                return redirect()->back()->with('error', 'The selected RFQ is not linked to a Purchase Request.');
            }

            if ($rfq->aoq()->exists()) {
                DB::rollBack();

                return redirect()->back()->with('error', 'An AOQ already exists for this RFQ.');
            }

            $rfqItemIds = $rfq->items->pluck('id')->map(fn ($id): int => (int) $id)->all();
            if (count($rfqItemIds) === 0) {
                DB::rollBack();

                return redirect()->back()->with('error', 'This RFQ has no items to evaluate.');
            }

            $supplierTotals = [];
            foreach ($data['quotations'] as $quotation) {
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
                DB::rollBack();

                return redirect()->back()->with('error', 'Please enter at least one supplier quotation with unit prices.');
            }

            usort($supplierTotals, fn (array $a, array $b): int => $a['total_amount'] <=> $b['total_amount']);
            $winnerSupplierId = $supplierTotals[0]['supplier_id'];

            // Reset previous RFQ quotation records
            $rfq->loadMissing('suppliers.supplierItems');
            foreach ($rfq->suppliers as $existingSupplier) {
                $existingSupplier->supplierItems()->delete();
            }

            $rfq->suppliers()->delete();

            foreach ($data['quotations'] as $quotation) {
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
                'batch_id' => $batchAoqRequest->batch_id,
                'aoq_date' => $data['aoq_date'],
                'winner_supplier_id' => $winnerSupplierId,
            ]);

            $batchAoqRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to approve request. '.$throwable->getMessage());
        }

        return redirect()->route('batch-aoq-requests.index')
            ->with('success', sprintf('Request approved. AOQ #%s has been created.', $aoq->id));
    }

    public function reject(Request $request, BatchAoqRequest $batchAoqRequest): RedirectResponse
    {
        if (! Auth::user()?->hasRole(RoleType::SUPERADMIN->value)) {
            abort(403, 'Only SuperAdmin can reject requests.');
        }

        if ($batchAoqRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'This request has already been processed.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $batchAoqRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return redirect()->route('batch-aoq-requests.index')
            ->with('success', 'Request rejected.');
    }

    public function lockedBatches(): JsonResponse
    {
        $batches = Batch::where('is_locked', true)
            ->orderByDesc('id')
            ->get(['id', 'batch_no']);

        return response()->json($batches);
    }

    public function myRequests(): Response
    {
        $requests = BatchAoqRequest::with([
            'batch',
            'requester',
            'approver',
        ])
            ->where('requested_by', Auth::id())
            ->latest()
            ->paginate(20);

        return Inertia::render('BatchAoqRequests/MyRequests', [
            'requests' => $requests,
        ]);
    }
}
