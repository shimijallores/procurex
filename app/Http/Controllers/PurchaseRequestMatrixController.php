<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Exports\PurchaseRequestMatrixExport;
use App\Models\Account;
use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseRequestMatrixController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        $prAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $budgetingAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PO_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $lengthAwarePaginator = $this->buildMatrixQuery($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (PurchaseRequest $purchaseRequest): array => $this->transformMatrixRow($purchaseRequest, $defaultPrAdminId, $defaultBudgetingAdminId));

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year])
            ->reverse();

        return Inertia::render('PurchaseRequestMatrix/Index', [
            'matrixRows' => $lengthAwarePaginator,
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'fiscalYears' => $fiscalYears,
            'filters' => [
                'search' => $request->search,
                'office_id' => $request->office_id,
                'account_id' => $request->account_id,
                'fiscal_year' => $selectedFiscalYear,
            ],
        ]);
    }

    public function exportXlsx(Request $request): BinaryFileResponse
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);

        $prAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id']);

        $budgetingAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PO_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $rows = $this->buildMatrixQuery($request)
            ->latest('id')
            ->get()
            ->map(fn (PurchaseRequest $purchaseRequest): array => $this->transformMatrixRow($purchaseRequest, $defaultPrAdminId, $defaultBudgetingAdminId))
            ->all();

        $fileName = sprintf('pr-matrix-%s.xlsx', $selectedFiscalYear !== '' ? $selectedFiscalYear : 'all-years');

        return Excel::download(new PurchaseRequestMatrixExport($rows), $fileName);
    }

    public function show(PurchaseRequestItem $purchaseRequestItem): Response
    {
        $purchaseRequestItem->load([
            'purchaseRequest.office',
            'purchaseRequest.emanating.account',
            'purchaseRequest.emanating.project',
            'purchaseRequest.emanating.ppmpCategory',
            'purchaseRequest.items.emanatingItem.ppmpItem',
            'purchaseRequest.items.matrixAccount',
            'purchaseRequest.items.matrixPrAdminUser',
            'purchaseRequest.items.matrixBudgetingAdminUser',
        ]);

        $purchaseRequest = $purchaseRequestItem->purchaseRequest;

        if (! $purchaseRequest) {
            abort(404);
        }

        return Inertia::render('PurchaseRequestMatrix/Show', [
            'matrixRow' => $this->transformMatrixRow($purchaseRequest, null, null),
        ]);
    }

    public function edit(PurchaseRequestItem $purchaseRequestItem): Response
    {
        $prAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $budgetingAdminUsers = User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', RoleType::PO_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $purchaseRequestItem->load([
            'purchaseRequest.office',
            'purchaseRequest.emanating.account',
            'purchaseRequest.emanating.project',
            'purchaseRequest.emanating.ppmpCategory',
            'purchaseRequest.items.emanatingItem.ppmpItem',
            'purchaseRequest.items.matrixAccount',
            'purchaseRequest.items.matrixPrAdminUser',
            'purchaseRequest.items.matrixBudgetingAdminUser',
        ]);

        $purchaseRequest = $purchaseRequestItem->purchaseRequest;

        if (! $purchaseRequest) {
            abort(404);
        }

        return Inertia::render('PurchaseRequestMatrix/Edit', [
            'matrixRow' => $this->transformMatrixRow($purchaseRequest, $defaultPrAdminId, $defaultBudgetingAdminId),
            'accounts' => Account::query()->orderBy('name')->get(['id', 'name']),
            'prAdminUsers' => $prAdminUsers,
            'budgetingAdminUsers' => $budgetingAdminUsers,
        ]);
    }

    public function update(Request $request, PurchaseRequestItem $purchaseRequestItem): RedirectResponse
    {
        $validated = $request->validate([
            'matrix_amount_below_1m' => ['nullable', 'numeric', 'min:0'],
            'matrix_amount_above_1m' => ['nullable', 'numeric', 'min:0'],
            'matrix_new_amount' => ['nullable', 'numeric', 'min:0'],
            'matrix_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'matrix_pr_admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'matrix_budgeting_admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'matrix_date_release' => ['nullable', 'date'],
            'matrix_new_date_release' => ['nullable', 'date'],
            'matrix_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchaseRequestItem->loadMissing('purchaseRequest.emanating');

        $purchaseRequest = $purchaseRequestItem->purchaseRequest;

        if (! $purchaseRequest) {
            abort(404);
        }

        // Always keep account tied to the linked emanating source record.
        $validated['matrix_account_id'] = $purchaseRequest->emanating?->account_id
            ?? ($validated['matrix_account_id'] ?? null);

        $purchaseRequest->items()->update($validated);

        $representativeItem = $purchaseRequest->items()->orderBy('id')->first();

        if (! $representativeItem) {
            abort(404);
        }

        return redirect()->route('purchase-request-matrix.show', $representativeItem)
            ->with('success', 'PR Matrix row updated successfully.');
    }

    private function transformMatrixRow(
        PurchaseRequest $purchaseRequest,
        ?int $defaultPrAdminId,
        ?int $defaultBudgetingAdminId,
    ): array {
        $representativeItem = $purchaseRequest->items
            ->sortBy('id')
            ->first();

        $ppmpEstimatedBudget = (float) ($purchaseRequest->emanating?->ppmpCategory?->estimated_budget ?? 0);
        if ($ppmpEstimatedBudget <= 0) {
            $ppmpEstimatedBudget = (float) $purchaseRequest->items
                ->pluck('emanatingItem.ppmpItem')
                ->filter()
                ->unique('id')
                ->sum(fn ($ppmpItem): float => (float) ($ppmpItem->estimated_budget ?? 0));
        }

        $amountBelow = $ppmpEstimatedBudget > 0 && $ppmpEstimatedBudget < 1000000
            ? round($ppmpEstimatedBudget, 2)
            : null;
        $amountAbove = $ppmpEstimatedBudget >= 1000000
            ? round($ppmpEstimatedBudget, 2)
            : null;

        $emanatingAccountId = $purchaseRequest->emanating?->account_id;
        $emanatingAccountName = $purchaseRequest->emanating?->account?->name;
        $projectName = $purchaseRequest->emanating?->project?->name;

        return [
            'id' => $representativeItem?->id,
            'control_no' => $purchaseRequest->emanating?->emanating_no,
            'office_name' => $purchaseRequest->office?->name,
            'item_description' => $projectName,
            'pr_no' => $purchaseRequest->pr_no,
            'pr_date' => $purchaseRequest->pr_date?->toDateString(),
            'amount_below_1m' => $amountBelow,
            'amount_above_1m' => $amountAbove,
            'new_amount' => (float) ($purchaseRequest->total_amount ?? 0),
            'account_id' => $emanatingAccountId ?? $representativeItem?->matrix_account_id,
            'account_name' => $emanatingAccountName ?? $representativeItem?->matrixAccount?->name,
            'pr_admin_user_id' => $representativeItem?->matrix_pr_admin_user_id ?? $defaultPrAdminId,
            'pr_admin_name' => $representativeItem?->matrixPrAdminUser?->name,
            'budgeting_admin_user_id' => $representativeItem?->matrix_budgeting_admin_user_id ?? $defaultBudgetingAdminId,
            'budgeting_admin_name' => $representativeItem?->matrixBudgetingAdminUser?->name,
            'date_release' => $representativeItem?->matrix_date_release?->toDateString(),
            'new_date_release' => $representativeItem?->matrix_new_date_release?->toDateString(),
            'remarks' => $representativeItem?->matrix_remarks,
        ];
    }

    private function buildMatrixQuery(Request $request): Builder
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);
        $selectedOfficeId = $request->input('office_id');
        $selectedAccountId = $request->input('account_id');

        return PurchaseRequest::query()
            ->with([
                'office',
                'emanating.project',
                'emanating.account',
                'emanating.ppmpCategory',
                'items.emanatingItem.ppmpItem',
                'items.matrixAccount',
                'items.matrixPrAdminUser',
                'items.matrixBudgetingAdminUser',
            ])
            ->whereIn('status', ['draft', 'approved', 'returned'])
            ->whereHas('items')
            ->when($request->filled('office_id'), function (Builder $builder) use ($selectedOfficeId): void {
                $builder->where('office_id', $selectedOfficeId);
            })
            ->when($request->filled('account_id'), function (Builder $builder) use ($selectedAccountId): void {
                $builder->whereHas('emanating', function (Builder $builder) use ($selectedAccountId): void {
                    $builder->where('account_id', $selectedAccountId);
                });
            })
            ->when($selectedFiscalYear !== '', function (Builder $builder) use ($selectedFiscalYear): void {
                $builder->whereHas('emanating', function (Builder $builder) use ($selectedFiscalYear): void {
                    $builder->where('fiscal_year', $selectedFiscalYear);
                });
            })
            ->when($request->search, function (Builder $builder, string $search): void {
                $builder->where(function (Builder $builder) use ($search): void {
                    $builder->where('pr_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('office', function (Builder $builder) use ($search): void {
                            $builder->where('name', 'like', sprintf('%%%s%%', $search));
                        })
                        ->orWhereHas('emanating', function (Builder $builder) use ($search): void {
                            $builder->where('emanating_no', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('account', function (Builder $builder) use ($search): void {
                                    $builder->where('name', 'like', sprintf('%%%s%%', $search));
                                })
                                ->orWhereHas('project', function (Builder $builder) use ($search): void {
                                    $builder->where('name', 'like', sprintf('%%%s%%', $search));
                                });
                        });
                });
            });
    }
}
