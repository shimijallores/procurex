<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RoleType;
use App\Exports\PurchaseRequestMatrixExport;
use App\Models\Account;
use App\Models\Office;
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
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $budgetingAdminUsers = User::query()
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::BUDGETING_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $matrixRows = $this->buildMatrixQuery($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn(PurchaseRequestItem $item): array => $this->transformMatrixRow($item, $defaultPrAdminId, $defaultBudgetingAdminId));

        $currentYear = now()->year;
        $fiscalYears = collect(range($currentYear - 4, $currentYear + 1))
            ->mapWithKeys(fn(int $year): array => [(string) $year => (string) $year])
            ->reverse();

        return Inertia::render('PurchaseRequestMatrix/Index', [
            'matrixRows' => $matrixRows,
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
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id']);

        $budgetingAdminUsers = User::query()
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::BUDGETING_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $rows = $this->buildMatrixQuery($request)
            ->latest('id')
            ->get()
            ->map(fn(PurchaseRequestItem $item): array => $this->transformMatrixRow($item, $defaultPrAdminId, $defaultBudgetingAdminId))
            ->all();

        $fileName = sprintf('pr-matrix-%s.xlsx', $selectedFiscalYear !== '' ? $selectedFiscalYear : 'all-years');

        return Excel::download(new PurchaseRequestMatrixExport($rows), $fileName);
    }

    public function show(PurchaseRequestItem $purchaseRequestItem): Response
    {
        $purchaseRequestItem->load([
            'purchaseRequest.office',
            'purchaseRequest.emanating.account',
            'emanatingItem',
            'matrixAccount',
            'matrixPrAdminUser',
            'matrixBudgetingAdminUser',
        ]);

        return Inertia::render('PurchaseRequestMatrix/Show', [
            'matrixRow' => $this->transformMatrixRow($purchaseRequestItem, null, null),
        ]);
    }

    public function edit(PurchaseRequestItem $purchaseRequestItem): Response
    {
        $prAdminUsers = User::query()
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::PR_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $budgetingAdminUsers = User::query()
            ->whereHas('role', function ($query): void {
                $query->where('name', RoleType::BUDGETING_ADMIN->value);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultPrAdminId = $prAdminUsers->count() === 1 ? (int) $prAdminUsers->first()->id : null;
        $defaultBudgetingAdminId = $budgetingAdminUsers->count() === 1 ? (int) $budgetingAdminUsers->first()->id : null;

        $purchaseRequestItem->load([
            'purchaseRequest.office',
            'purchaseRequest.emanating.account',
            'emanatingItem',
        ]);

        return Inertia::render('PurchaseRequestMatrix/Edit', [
            'matrixRow' => $this->transformMatrixRow($purchaseRequestItem, $defaultPrAdminId, $defaultBudgetingAdminId),
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

        // Always keep account tied to the linked emanating source record.
        $validated['matrix_account_id'] = $purchaseRequestItem->purchaseRequest?->emanating?->account_id
            ?? ($validated['matrix_account_id'] ?? null);

        $purchaseRequestItem->update($validated);

        return redirect()->route('purchase-request-matrix.show', $purchaseRequestItem)
            ->with('success', 'PR Matrix row updated successfully.');
    }

    private function transformMatrixRow(
        PurchaseRequestItem $item,
        ?int $defaultPrAdminId,
        ?int $defaultBudgetingAdminId,
    ): array {
        $sourceAmount = (float) ($item->emanatingItem?->total_price ?? 0);
        $amountBelow = $item->matrix_amount_below_1m;
        $amountAbove = $item->matrix_amount_above_1m;

        if ($amountBelow === null && $amountAbove === null && $sourceAmount > 0) {
            if ($sourceAmount < 1000000) {
                $amountBelow = $sourceAmount;
            } else {
                $amountAbove = $sourceAmount;
            }
        }

        $emanatingAccountId = $item->purchaseRequest?->emanating?->account_id;
        $emanatingAccountName = $item->purchaseRequest?->emanating?->account?->name;

        return [
            'id' => $item->id,
            'control_no' => $item->purchaseRequest?->emanating?->emanating_no,
            'office_name' => $item->purchaseRequest?->office?->name,
            'item_description' => $item->emanatingItem?->name,
            'pr_no' => $item->purchaseRequest?->pr_no,
            'pr_date' => $item->purchaseRequest?->pr_date?->toDateString(),
            'amount_below_1m' => $amountBelow,
            'amount_above_1m' => $amountAbove,
            'new_amount' => $item->matrix_new_amount ?? $item->line_total,
            'account_id' => $emanatingAccountId ?? $item->matrix_account_id,
            'account_name' => $emanatingAccountName ?? $item->matrixAccount?->name,
            'pr_admin_user_id' => $item->matrix_pr_admin_user_id ?? $defaultPrAdminId,
            'pr_admin_name' => $item->matrixPrAdminUser?->name,
            'budgeting_admin_user_id' => $item->matrix_budgeting_admin_user_id ?? $defaultBudgetingAdminId,
            'budgeting_admin_name' => $item->matrixBudgetingAdminUser?->name,
            'date_release' => $item->matrix_date_release?->toDateString(),
            'new_date_release' => $item->matrix_new_date_release?->toDateString(),
            'remarks' => $item->matrix_remarks,
        ];
    }

    private function buildMatrixQuery(Request $request): Builder
    {
        $selectedFiscalYear = (string) $request->input('fiscal_year', (string) now()->year);
        $selectedOfficeId = $request->input('office_id');
        $selectedAccountId = $request->input('account_id');

        return PurchaseRequestItem::query()
            ->with([
                'purchaseRequest.office',
                'purchaseRequest.emanating.account',
                'emanatingItem',
                'matrixAccount',
                'matrixPrAdminUser',
                'matrixBudgetingAdminUser',
            ])
            ->whereHas('purchaseRequest', function ($query): void {
                $query->whereIn('status', ['draft', 'approved', 'returned']);
            })
            ->when($request->filled('office_id'), function (Builder $query) use ($selectedOfficeId): void {
                $query->whereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($selectedOfficeId): void {
                    $purchaseRequestQuery->where('office_id', $selectedOfficeId);
                });
            })
            ->when($request->filled('account_id'), function (Builder $query) use ($selectedAccountId): void {
                $query->whereHas('purchaseRequest.emanating', function (Builder $emanatingQuery) use ($selectedAccountId): void {
                    $emanatingQuery->where('account_id', $selectedAccountId);
                });
            })
            ->when($selectedFiscalYear !== '', function (Builder $query) use ($selectedFiscalYear): void {
                $query->whereHas('purchaseRequest.emanating', function (Builder $emanatingQuery) use ($selectedFiscalYear): void {
                    $emanatingQuery->where('fiscal_year', $selectedFiscalYear);
                });
            })
            ->when($request->search, function (Builder $query, string $search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery->whereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                        $purchaseRequestQuery->where('pr_no', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('emanating', function (Builder $emanatingQuery) use ($search): void {
                                $emanatingQuery->where('emanating_no', 'like', sprintf('%%%s%%', $search))
                                    ->orWhereHas('account', function (Builder $accountQuery) use ($search): void {
                                        $accountQuery->where('name', 'like', sprintf('%%%s%%', $search));
                                    });
                            })
                            ->orWhereHas('office', function (Builder $officeQuery) use ($search): void {
                                $officeQuery->where('name', 'like', sprintf('%%%s%%', $search));
                            });
                    })->orWhereHas('emanatingItem', function (Builder $emanatingItemQuery) use ($search): void {
                        $emanatingItemQuery->where('name', 'like', sprintf('%%%s%%', $search));
                    });
                });
            });
    }
}
