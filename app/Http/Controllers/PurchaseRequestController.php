<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\UpdatePurchaseRequestRequest;
use App\Models\Emanating;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class PurchaseRequestController extends Controller
{
    /** Common rejection/return reasons */
    private const RETURN_REASONS = [
        'Insufficient Fund',
        'Mismatch',
        'Budget Exceeded',
        'Incomplete Documentation',
        'Wrong Charging',
        'Duplicate Request',
        'Invalid SAI',
        'Items Not in PPMP',
    ];

    /** Common PR purposes */
    private const COMMON_PURPOSES = [
        'For the use of the office in its day-to-day operations',
        'For the implementation of the project',
        'For maintenance and repair of equipment',
        'For training and capacity building',
        'For official meetings and conferences',
        'Capital outlay for equipment procurement',
        'Supplies and materials for field operations',
        'Information and communications technology supplies',
    ];

    public function index(Request $request): Response
    {
        $query = PurchaseRequest::with([
            'emanating.project.fund.office',
            'office',
            'fund',
        ])
            ->when($request->search, function ($q, string $search): void {
                $q->where('pr_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('sai_no', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('purpose', 'like', sprintf('%%%s%%', $search))
                    ->orWhereHas('office', function ($o) use ($search): void {
                        $o->where('name', 'like', sprintf('%%%s%%', $search));
                    });
            })
            ->when($request->office_id, function ($q, string $officeId): void {
                $q->where('office_id', $officeId);
            })
            ->when($request->status, function ($q, string $status): void {
                $q->where('status', $status);
            })
            ->when($request->fiscal_year, function ($q, string $fiscalYear): void {
                $q->whereHas('emanating', function ($e) use ($fiscalYear): void {
                    $e->where('fiscal_year', $fiscalYear);
                });
            });

        $paginator = (clone $query)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total'    => (clone $query)->count(),
            'draft'    => (clone $query)->where('status', 'draft')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'returned' => (clone $query)->where('status', 'returned')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn($year) => [$year => $year])
            ->reverse();

        return Inertia::render('PurchaseRequests/Index', [
            'purchaseRequests' => $paginator,
            'stats'            => $stats,
            'offices'          => $offices,
            'fiscalYears'      => $fiscalYears,
            'filters'          => [
                'search'      => $request->search,
                'office_id'   => $request->office_id,
                'status'      => $request->status,
                'fiscal_year' => $request->fiscal_year,
            ],
        ]);
    }

    public function create(): Response
    {
        // Only approved AND canvassed emanatings without an existing PR
        $eligibleEmanatings = Emanating::with([
            'project.fund.office',
            'ppmpCategory',
            'emanatingItems.ppmpItem',
        ])
            ->where('is_approved', true)
            ->where('is_canvassed', true)
            ->whereDoesntHave('purchaseRequest')
            ->latest()
            ->get();

        return Inertia::render('PurchaseRequests/Create', [
            'eligibleEmanatings' => $eligibleEmanatings,
            'commonPurposes'     => self::COMMON_PURPOSES,
            'returnReasons'      => self::RETURN_REASONS,
        ]);
    }

    public function store(StorePurchaseRequestRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $emanating = Emanating::with(['project.fund.office', 'project.fund'])->findOrFail($validated['emanating_id']);

            // Compute total (with VAT where applicable)
            $total = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                if (! empty($item['vat_applicable'])) {
                    $vatRate = (float) ($item['vat_rate'] ?? 0.12);
                    $lineTotal = $lineTotal * (1 + $vatRate);
                }
                $total += $lineTotal;
            }

            $pr = PurchaseRequest::create([
                'emanating_id' => $validated['emanating_id'],
                'office_id'    => $validated['office_id'],
                'fund_id'      => $validated['fund_id'],
                'pr_no'        => $validated['pr_no'] ?? null,
                'pr_date'      => $validated['pr_date'] ?? null,
                'sai_no'       => $validated['sai_no'] ?? null,
                'sai_date'     => $validated['sai_date'] ?? null,
                'purpose'      => $validated['purpose'] ?? null,
                'total_amount' => round($total, 2),
                'status'       => $validated['status'] ?? 'draft',
                'remarks'      => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                $vatRate   = ! empty($item['vat_applicable']) ? (float) ($item['vat_rate'] ?? 0.12) : 0;
                if (! empty($item['vat_applicable'])) {
                    $lineTotal = $lineTotal * (1 + $vatRate);
                }

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'emanating_item_id'   => $item['emanating_item_id'],
                    'quantity'            => $item['quantity'],
                    'unit_cost'           => $item['unit_cost'],
                    'line_total'          => round($lineTotal, 2),
                    'vat_applicable'      => ! empty($item['vat_applicable']),
                    'vat_rate'            => ! empty($item['vat_applicable']) ? $vatRate : 0,
                    'remarks'             => $item['remarks'] ?? null,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Request creation failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to create Purchase Request. Please try again.');
        }

        return redirect()->route('purchase-requests.show', $pr)
            ->with('success', 'Purchase Request created successfully.');
    }

    public function show(PurchaseRequest $purchaseRequest): Response
    {
        $purchaseRequest->load([
            'emanating.project.fund.office',
            'emanating.ppmpCategory',
            'emanating.emanatingItems.ppmpItem',
            'office',
            'fund',
            'items.emanatingItem.ppmpItem',
        ]);

        return Inertia::render('PurchaseRequests/Show', [
            'purchaseRequest' => $purchaseRequest,
            'returnReasons'   => self::RETURN_REASONS,
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): Response
    {
        $purchaseRequest->load([
            'emanating.project.fund.office',
            'emanating.emanatingItems.ppmpItem',
            'items.emanatingItem.ppmpItem',
            'office',
            'fund',
        ]);

        return Inertia::render('PurchaseRequests/Edit', [
            'purchaseRequest' => $purchaseRequest,
            'commonPurposes'  => self::COMMON_PURPOSES,
        ]);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Recalculate total if items are provided
            if (! empty($validated['items'])) {
                $total = 0;
                foreach ($validated['items'] as $item) {
                    $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                    if (! empty($item['vat_applicable'])) {
                        $vatRate   = (float) ($item['vat_rate'] ?? 0.12);
                        $lineTotal = $lineTotal * (1 + $vatRate);
                    }
                    $total += $lineTotal;
                }
                $validated['total_amount'] = round($total, 2);

                // Sync items: delete all then recreate
                $purchaseRequest->items()->delete();
                foreach ($validated['items'] as $item) {
                    $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                    $vatRate   = ! empty($item['vat_applicable']) ? (float) ($item['vat_rate'] ?? 0.12) : 0;
                    if (! empty($item['vat_applicable'])) {
                        $lineTotal = $lineTotal * (1 + $vatRate);
                    }
                    PurchaseRequestItem::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'emanating_item_id'   => $item['emanating_item_id'],
                        'quantity'            => $item['quantity'],
                        'unit_cost'           => $item['unit_cost'],
                        'line_total'          => round($lineTotal, 2),
                        'vat_applicable'      => ! empty($item['vat_applicable']),
                        'vat_rate'            => ! empty($item['vat_applicable']) ? $vatRate : 0,
                        'remarks'             => $item['remarks'] ?? null,
                    ]);
                }
            }

            $purchaseRequest->update(collect($validated)->except(['items'])->toArray());

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Purchase Request update failed', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->with('error', 'Failed to update Purchase Request.');
        }

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase Request updated successfully.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $purchaseRequest->delete();

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Purchase Request deleted.');
    }

    /**
     * Approve the purchase request (mark as approved, subject for printing).
     */
    public function approve(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if ($purchaseRequest->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Only draft PRs can be approved.');
        }

        $purchaseRequest->update(['status' => 'approved']);

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase Request approved and ready for printing.');
    }

    /**
     * Return the PR to the office for addendum.
     * Also rejects the associated Emanating and PPMP with the provided reason.
     */
    public function returnToOffice(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($purchaseRequest->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Only draft PRs can be returned.');
        }

        DB::beginTransaction();
        try {
            $reason = $request->reason;

            // Mark the PR as returned
            $purchaseRequest->update([
                'status'  => 'returned',
                'remarks' => $reason,
            ]);

            // Set the emanating back to pending (not rejected)
            $emanating = $purchaseRequest->emanating;
            $emanating->update([
                'is_approved'      => false,
                'rejection_reason' => $reason,
                'status'           => 'pending',  // Reset to pending
            ]);

            // Reject the PPMP
            $ppmp = $emanating->ppmp;
            if ($ppmp) {
                $ppmp->update([
                    'is_approved'      => false,
                    'rejection_reason' => $reason,
                    'status'           => 'rejected',
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('PR return failed', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to return Purchase Request.');
        }

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Purchase Request returned to office for addendum.');
    }

    /**
     * Generate and stream the PR as a PDF (inline for browser print dialog).
     */
    public function printPdf(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'office',
            'fund',
            'items.emanatingItem.ppmpItem',
        ]);

        return Pdf::view('pdf.purchase-request', [
            'pr'               => $purchaseRequest,
            'approvedBy'       => 'VILMA SANTOS-RECTO',
            'approvedByDesig'  => 'Governor',
            'requestedBy'      => $purchaseRequest->office?->head ?? '',
            'requestedByDesig' => 'Department Head',
        ])
            ->format('a4')
            ->name('PR-' . ($purchaseRequest->pr_no ?? $purchaseRequest->id) . '.pdf')
            ->inline();
    }
}
