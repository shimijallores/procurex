<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Http\Requests\UpdatePurchaseRequestRequest;
use App\Models\Calendar;
use App\Models\Emanating;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Services\PpmpBudgetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelPdf\Facades\Pdf;

class PurchaseRequestController extends Controller
{
    /** @var array<int, string> */
    private const BUDGET_COUNTED_STATUSES = ['draft', 'for_budget_review', 'approved'];

    public function __construct(private readonly PpmpBudgetService $ppmpBudgetService) {}

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
        'Purchase of [name of account] (item1, item2, item3, etc.) for use of [Office Name].',
        'Repair and maintenance of Service Vehicle with Plate No. [Plate No.] and Property No. [Property No.].',
        'Supply and Installation of Four (4) units Airconditioning units.',
        'Purchase of Food to be served during [Event Name] on [Date of Event].',
        '[Office Name] publication of [Year] budget for use of [Office Name].',
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

        $lengthAwarePaginator = (clone $query)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'returned' => (clone $query)->where('status', 'returned')->count(),
        ];

        $offices = Office::orderBy('name')->get(['id', 'name']);

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn ($year): array => [$year => $year])
            ->reverse();

        return Inertia::render('PurchaseRequests/Index', [
            'purchaseRequests' => $lengthAwarePaginator,
            'stats' => $stats,
            'offices' => $offices,
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'status' => $request->status,
                'fiscal_year' => $request->fiscal_year,
            ],
        ]);
    }

    public function create(): Response
    {
        // Only approved AND canvassed emanatings without an existing PR
        $eligibleEmanatings = Emanating::with([
            'project.fund.office',
            'fund.office',
            'fund.projectCode',
            'account',
            'ppmpCategory',
            'emanatingItems.ppmpItem',
        ])
            ->where('is_approved', true)
            ->where('is_canvassed', true)
            ->whereDoesntHave('purchaseRequest')
            ->latest()
            ->get();

        $suggestedPrDate = $this->suggestNextPrDate();

        return Inertia::render('PurchaseRequests/Create', [
            'eligibleEmanatings' => $eligibleEmanatings,
            'commonPurposes' => self::COMMON_PURPOSES,
            'suggestedPrDate' => $suggestedPrDate->toDateString(),
            'suggestedPrNo' => $this->previewNextPrNo($suggestedPrDate),
            'returnReasons' => self::RETURN_REASONS,
        ]);
    }

    public function suggestPrNo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pr_date' => ['required', 'date'],
        ]);

        $prDate = Carbon::parse((string) $validated['pr_date']);

        return response()->json([
            'pr_no' => $this->previewNextPrNo($prDate),
        ]);
    }

    public function store(StorePurchaseRequestRequest $storePurchaseRequestRequest): RedirectResponse
    {
        $validated = $storePurchaseRequestRequest->validated();

        DB::beginTransaction();
        try {
            $emanating = Emanating::with([
                'project.fund.office',
                'project.fund',
                'fund.office',
                'fund.projectCode',
            ])->findOrFail($validated['emanating_id']);

            $prDate = Carbon::parse((string) $validated['pr_date']);
            $generatedPrNo = $this->generateNextPrNo($prDate);

            $officeId = (int) ($validated['office_id'] ?: ($emanating->project?->fund?->office_id ?? $emanating->fund?->office_id));
            $fundId = (int) ($validated['fund_id'] ?: ($emanating->project?->fund?->id ?? $emanating->fund?->id));

            $requestedByName = trim((string) ($validated['requested_by_name'] ?? ''));
            $requestedByDesignation = trim((string) ($validated['requested_by_designation'] ?? ''));

            // Compute total (with VAT where applicable)
            $total = 0;
            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                if (! empty($item['vat_applicable'])) {
                    $vatRate = (float) ($item['vat_rate'] ?? 0.12);
                    $lineTotal *= 1 + $vatRate;
                }

                $total += $lineTotal;
            }

            $pr = PurchaseRequest::create([
                'emanating_id' => $validated['emanating_id'],
                'office_id' => $officeId,
                'fund_id' => $fundId,
                'pr_no' => $generatedPrNo,
                'pr_date' => $prDate->toDateString(),
                'sai_no' => $validated['sai_no'] ?? null,
                'sai_date' => $validated['sai_date'] ?? null,
                'requested_by_name' => $requestedByName !== '' ? $requestedByName : $emanating->requesting_officer_name,
                'requested_by_designation' => $requestedByDesignation !== '' ? $requestedByDesignation : $emanating->requesting_officer_title,
                'purpose' => $validated['purpose'] ?? null,
                'total_amount' => round($total, 2),
                'status' => $validated['status'] ?? 'draft',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $emanating->update(['pr_no' => $generatedPrNo]);

            $defaultPrAdminId = $this->resolveSingleUserIdByRole(RoleType::PR_ADMIN->value);
            $defaultBudgetingAdminId = $this->resolveSingleUserIdByRole(RoleType::PO_ADMIN->value);

            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                $vatRate = empty($item['vat_applicable']) ? 0 : (float) ($item['vat_rate'] ?? 0.12);
                if (! empty($item['vat_applicable'])) {
                    $lineTotal *= 1 + $vatRate;
                }

                $emanatingItem = $emanating->emanatingItems->firstWhere('id', (int) $item['emanating_item_id']);
                $sourceAmount = (float) ($emanatingItem?->total_price ?? 0);

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'emanating_item_id' => $item['emanating_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => round($lineTotal, 2),
                    'vat_applicable' => ! empty($item['vat_applicable']),
                    'vat_rate' => empty($item['vat_applicable']) ? 0 : $vatRate,
                    'remarks' => $item['remarks'] ?? null,
                    'matrix_amount_below_1m' => $sourceAmount > 0 && $sourceAmount < 1000000 ? round($sourceAmount, 2) : null,
                    'matrix_amount_above_1m' => $sourceAmount >= 1000000 ? round($sourceAmount, 2) : null,
                    'matrix_new_amount' => round($lineTotal, 2),
                    'matrix_account_id' => $emanating->account_id,
                    'matrix_pr_admin_user_id' => $defaultPrAdminId,
                    'matrix_budgeting_admin_user_id' => $defaultBudgetingAdminId,
                ]);
            }

            $this->ppmpBudgetService->recalculateForPurchaseRequest($pr);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

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
            'emanating.fund.office',
            'emanating.fund.projectCode',
            'emanating.ppmpCategory',
            'emanating.emanatingItems.ppmpItem',
            'office',
            'fund',
            'items.emanatingItem.ppmpItem',
        ]);

        return Inertia::render('PurchaseRequests/Show', [
            'purchaseRequest' => $purchaseRequest,
            'returnReasons' => self::RETURN_REASONS,
        ]);
    }

    public function edit(PurchaseRequest $purchaseRequest): Response
    {
        $purchaseRequest->load([
            'emanating.project.fund.office',
            'emanating.fund.office',
            'emanating.fund.projectCode',
            'emanating.account',
            'emanating.emanatingItems.ppmpItem',
            'items.emanatingItem.ppmpItem',
            'office',
            'fund',
        ]);

        return Inertia::render('PurchaseRequests/Edit', [
            'purchaseRequest' => $purchaseRequest,
            'commonPurposes' => self::COMMON_PURPOSES,
        ]);
    }

    public function update(UpdatePurchaseRequestRequest $updatePurchaseRequestRequest, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $validated = $updatePurchaseRequestRequest->validated();

        DB::beginTransaction();
        try {
            // Recalculate total if items are provided
            if (! empty($validated['items'])) {
                $purchaseRequest->loadMissing('emanating.emanatingItems');

                $existingMatrixByEmanatingItemId = $purchaseRequest->items()
                    ->get([
                        'emanating_item_id',
                        'matrix_amount_below_1m',
                        'matrix_amount_above_1m',
                        'matrix_new_amount',
                        'matrix_account_id',
                        'matrix_pr_admin_user_id',
                        'matrix_budgeting_admin_user_id',
                        'matrix_date_release',
                        'matrix_new_date_release',
                        'matrix_remarks',
                    ])
                    ->keyBy('emanating_item_id');

                $defaultPrAdminId = $this->resolveSingleUserIdByRole(RoleType::PR_ADMIN->value);
                $defaultBudgetingAdminId = $this->resolveSingleUserIdByRole(RoleType::PO_ADMIN->value);

                $total = 0;
                foreach ($validated['items'] as $item) {
                    $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                    if (! empty($item['vat_applicable'])) {
                        $vatRate = (float) ($item['vat_rate'] ?? 0.12);
                        $lineTotal *= 1 + $vatRate;
                    }

                    $total += $lineTotal;
                }

                $validated['total_amount'] = round($total, 2);

                // Sync items: delete all then recreate
                $purchaseRequest->items()->delete();
                foreach ($validated['items'] as $item) {
                    $lineTotal = (float) $item['unit_cost'] * (int) $item['quantity'];
                    $vatRate = empty($item['vat_applicable']) ? 0 : (float) ($item['vat_rate'] ?? 0.12);
                    if (! empty($item['vat_applicable'])) {
                        $lineTotal *= 1 + $vatRate;
                    }

                    $emanatingItemId = (int) $item['emanating_item_id'];
                    $existingMatrix = $existingMatrixByEmanatingItemId->get($emanatingItemId);
                    $emanatingItem = $purchaseRequest->emanating?->emanatingItems?->firstWhere('id', $emanatingItemId);
                    $sourceAmount = (float) ($emanatingItem?->total_price ?? 0);

                    PurchaseRequestItem::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'emanating_item_id' => $item['emanating_item_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'line_total' => round($lineTotal, 2),
                        'vat_applicable' => ! empty($item['vat_applicable']),
                        'vat_rate' => empty($item['vat_applicable']) ? 0 : $vatRate,
                        'remarks' => $item['remarks'] ?? null,
                        'matrix_amount_below_1m' => $existingMatrix?->matrix_amount_below_1m ?? ($sourceAmount > 0 && $sourceAmount < 1000000 ? round($sourceAmount, 2) : null),
                        'matrix_amount_above_1m' => $existingMatrix?->matrix_amount_above_1m ?? ($sourceAmount >= 1000000 ? round($sourceAmount, 2) : null),
                        'matrix_new_amount' => $existingMatrix?->matrix_new_amount ?? round($lineTotal, 2),
                        'matrix_account_id' => $existingMatrix?->matrix_account_id ?? $purchaseRequest->emanating?->account_id,
                        'matrix_pr_admin_user_id' => $existingMatrix?->matrix_pr_admin_user_id ?? $defaultPrAdminId,
                        'matrix_budgeting_admin_user_id' => $existingMatrix?->matrix_budgeting_admin_user_id ?? $defaultBudgetingAdminId,
                        'matrix_date_release' => $existingMatrix?->matrix_date_release,
                        'matrix_new_date_release' => $existingMatrix?->matrix_new_date_release,
                        'matrix_remarks' => $existingMatrix?->matrix_remarks,
                    ]);
                }
            }

            $wasBudgetCounted = in_array($purchaseRequest->status, self::BUDGET_COUNTED_STATUSES, true);

            $purchaseRequest->update(collect($validated)->except(['items'])->toArray());

            if ($wasBudgetCounted || in_array($purchaseRequest->status, self::BUDGET_COUNTED_STATUSES, true)) {
                $this->ppmpBudgetService->recalculateForPurchaseRequest($purchaseRequest);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to update Purchase Request.');
        }

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase Request updated successfully.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $ppmpItemIds = [];
            $ppmpCategoryIds = [];

            if (in_array($purchaseRequest->status, self::BUDGET_COUNTED_STATUSES, true)) {
                $purchaseRequest->loadMissing('emanating:id,ppmp_category_id');

                $ppmpItemIds = $purchaseRequest->items()
                    ->with('emanatingItem:id,ppmp_item_id')
                    ->get()
                    ->pluck('emanatingItem.ppmp_item_id')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                $categoryId = (int) ($purchaseRequest->emanating?->ppmp_category_id ?? 0);
                if ($categoryId > 0) {
                    $ppmpCategoryIds[] = $categoryId;
                }
            }

            $purchaseRequest->delete();

            if ($ppmpItemIds !== []) {
                $this->ppmpBudgetService->recalculateForPpmpItemIds($ppmpItemIds);
            }

            if ($ppmpCategoryIds !== []) {
                $this->ppmpBudgetService->recalculateForPpmpCategoryIds($ppmpCategoryIds);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete Purchase Request.');
        }

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Purchase Request deleted.');
    }

    /**
     * Approve the purchase request (draft → approved).
     */
    public function approve(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        if ($purchaseRequest->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Only draft PRs can be approved.');
        }

        DB::beginTransaction();
        try {
            $purchaseRequest->update(['status' => 'approved']);
            $this->ppmpBudgetService->recalculateForPurchaseRequest($purchaseRequest);

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to approve Purchase Request.');
        }

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase Request approved successfully.');
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

        if (! in_array($purchaseRequest->status, ['draft', 'approved'], true)) {
            return redirect()->back()
                ->with('error', 'Only draft or approved PRs can be returned.');
        }

        DB::beginTransaction();
        try {
            $reason = $request->reason;
            $wasBudgetCounted = in_array($purchaseRequest->status, self::BUDGET_COUNTED_STATUSES, true);

            // Mark the PR as returned
            $purchaseRequest->update([
                'status' => 'returned',
                'remarks' => $reason,
            ]);

            // Set the emanating back to pending (not rejected)
            $emanating = $purchaseRequest->emanating;
            $emanating->update([
                'is_approved' => false,
                'rejection_reason' => $reason,
                'status' => 'pending',  // Reset to pending
            ]);

            // Reject the PPMP
            $ppmp = $emanating->ppmp;
            if ($ppmp) {
                $ppmp->update([
                    'is_approved' => false,
                    'rejection_reason' => $reason,
                    'status' => 'rejected',
                ]);
            }

            if ($wasBudgetCounted) {
                $this->ppmpBudgetService->recalculateForPurchaseRequest($purchaseRequest);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to return Purchase Request.');
        }

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Purchase Request returned to office for addendum.');
    }

    /**
     * Generate and stream the PR as a PDF (inline for browser print dialog).
     */
    public function printPdf(PurchaseRequest $purchaseRequest): \Spatie\LaravelPdf\PdfBuilder
    {
        $purchaseRequest->load([
            'office',
            'fund',
            'emanating',
            'items.emanatingItem.ppmpItem',
        ]);

        $printItems = collect($purchaseRequest->items)->map(function ($item): array {
            $quantity = (int) ($item->quantity ?? 0);
            $unitCost = (float) ($item->unit_cost ?? 0);
            $vatRate = $item->vat_applicable ? (float) ($item->vat_rate ?? 0.12) : 0.0;
            $effectiveUnitCost = $unitCost * (1 + $vatRate);

            $ppmpBudget = (float) (
                $item->emanatingItem?->ppmpItem?->remaining_budget
                ?? $item->emanatingItem?->ppmpItem?->estimated_budget
                ?? 0
            );
            $adjustedQuantity = $quantity;
            $adjustedLineTotal = (float) ($item->line_total ?? 0);

            if ($ppmpBudget > 0 && $effectiveUnitCost > 0 && $adjustedLineTotal > $ppmpBudget) {
                $allowedQuantity = (int) floor($ppmpBudget / $effectiveUnitCost);
                $adjustedQuantity = max(0, min($quantity, $allowedQuantity));
                $adjustedLineTotal = round($adjustedQuantity * $effectiveUnitCost, 2);
            }

            return [
                'id' => $item->id,
                'unit' => $item->unit ?? $item->emanatingItem?->unit,
                'description' => $item->item_name ?? $item->emanatingItem?->name ?? $item->emanatingItem?->ppmpItem?->name,
                'quantity' => $adjustedQuantity,
                'unit_cost' => $unitCost,
                'line_total' => $adjustedLineTotal,
            ];
        })->values();

        $printTotal = (float) $printItems->sum('line_total');

        return Pdf::view('pdf.purchase-request', [
            'pr' => $purchaseRequest,
            'printItems' => $printItems,
            'printTotal' => $printTotal,
            'approvedBy' => 'VILMA SANTOS-RECTO',
            'approvedByDesig' => 'Governor',
            'requestedBy' => $purchaseRequest->requested_by_name ?: ($purchaseRequest->emanating?->requesting_officer_name ?? ''),
            'requestedByDesig' => $purchaseRequest->requested_by_designation ?: ($purchaseRequest->emanating?->requesting_officer_title ?? ''),
        ])
            ->format('a4')
            ->landscape()
            ->name('PR-'.($purchaseRequest->pr_no ?? $purchaseRequest->id).'.pdf')
            ->inline();
    }

    private function suggestNextPrDate(): Carbon
    {
        $date = now()->startOfDay();

        while (! $this->isWorkingDay($date->toDateString())) {
            $date->addDay();
        }

        return $date;
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

    private function generateNextPrNo(Carbon $prDate): string
    {
        return $this->buildPrNo($prDate, true);
    }

    private function previewNextPrNo(Carbon $prDate): string
    {
        return $this->buildPrNo($prDate, false);
    }

    private function buildPrNo(Carbon $prDate, bool $lockRows): string
    {
        $monthPrefix = $prDate->format('m');
        $yearPrefix = $prDate->format('y');
        $prefix = $monthPrefix.$yearPrefix;

        $builder = PurchaseRequest::query()
            ->whereYear('pr_date', (int) $prDate->format('Y'))
            ->whereNotNull('pr_no');

        if ($lockRows) {
            $builder->lockForUpdate();
        }

        $existingNumbers = $builder->pluck('pr_no');

        $latestCounter = 0;

        foreach ($existingNumbers as $existingNumber) {
            if (! is_string($existingNumber)) {
                continue;
            }

            if (! preg_match('/^(\d{4})-(\d{4})$/', $existingNumber, $matches)) {
                continue;
            }

            $numberPrefix = $matches[1] ?? null;
            $counterPart = $matches[2] ?? null;

            if ($counterPart === null) {
                continue;
            }

            if (! str_ends_with($numberPrefix, $yearPrefix)) {
                continue;
            }

            $latestCounter = max($latestCounter, (int) $counterPart);
        }

        $nextCounter = $latestCounter + 1;

        return sprintf('%s-%04d', $prefix, $nextCounter);
    }

    private function resolveSingleUserIdByRole(string $roleName): ?int
    {
        $users = User::query()
            ->whereHas('roles', function ($query) use ($roleName): void {
                $query->where('name', $roleName);
            })
            ->orderBy('name')
            ->pluck('id');

        if ($users->count() !== 1) {
            return null;
        }

        return (int) $users->first();
    }
}
