<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Illuminate\Support\Facades\Schema;

class PpmpBudgetService
{
    private static ?bool $hasRemainingBudgetColumn = null;
    private static ?bool $hasCategoryRemainingBudgetColumn = null;

    /** @var array<int, string> */
    private const CONSUMED_PR_STATUSES = ['draft', 'for_budget_review', 'approved'];

    public function recalculateForPurchaseRequest(PurchaseRequest $purchaseRequest): void
    {
        $purchaseRequest->loadMissing('emanating:id,ppmp_category_id');

        $ppmpItemIds = $purchaseRequest->items()
            ->with('emanatingItem:id,ppmp_item_id')
            ->get()
            ->pluck('emanatingItem.ppmp_item_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $ppmpCategoryIds = [];

        $emanatingCategoryId = (int) ($purchaseRequest->emanating?->ppmp_category_id ?? 0);
        if ($emanatingCategoryId > 0) {
            $ppmpCategoryIds[] = $emanatingCategoryId;
        }

        $ppmpCategoryIds = array_values(array_unique($ppmpCategoryIds));

        $this->recalculateForPpmpItemIds($ppmpItemIds);
        $this->recalculateForPpmpCategoryIds($ppmpCategoryIds);
    }

    /**
     * @param  array<int, int>  $ppmpItemIds
     */
    public function recalculateForPpmpItemIds(array $ppmpItemIds): void
    {
        if (! $this->hasRemainingBudgetColumn()) {
            return;
        }

        $ppmpItemIds = collect($ppmpItemIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ppmpItemIds->isEmpty()) {
            return;
        }

        foreach ($ppmpItemIds as $ppmpItemId) {
            $ppmpItem = PPMPItem::query()->find($ppmpItemId);
            if (! $ppmpItem) {
                continue;
            }

            $consumedBudget = (float) PurchaseRequestItem::query()
                ->whereHas('purchaseRequest', function ($query): void {
                    $query->whereIn('status', self::CONSUMED_PR_STATUSES);
                })
                ->whereHas('emanatingItem', function ($query) use ($ppmpItemId): void {
                    $query->where('ppmp_item_id', $ppmpItemId);
                })
                ->sum('line_total');

            $remainingBudget = max(0, round((float) $ppmpItem->estimated_budget - $consumedBudget, 2));

            $ppmpItem->update([
                'remaining_budget' => $remainingBudget,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $ppmpCategoryIds
     */
    public function recalculateForPpmpCategoryIds(array $ppmpCategoryIds): void
    {
        if (! $this->hasCategoryRemainingBudgetColumn()) {
            return;
        }

        $ppmpCategoryIds = collect($ppmpCategoryIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ppmpCategoryIds->isEmpty()) {
            return;
        }

        foreach ($ppmpCategoryIds as $ppmpCategoryId) {
            $ppmpCategory = PPMPCategory::query()->find($ppmpCategoryId);
            if (! $ppmpCategory) {
                continue;
            }

            $consumedBudget = (float) PurchaseRequest::query()
                ->whereIn('status', self::CONSUMED_PR_STATUSES)
                ->whereHas('emanating', function ($query) use ($ppmpCategoryId): void {
                    $query->where('ppmp_category_id', $ppmpCategoryId);
                })
                ->sum('total_amount');

            $remainingBudget = max(0, round((float) $ppmpCategory->estimated_budget - $consumedBudget, 2));

            $ppmpCategory->update([
                'remaining_budget' => $remainingBudget,
            ]);
        }
    }

    private function hasRemainingBudgetColumn(): bool
    {
        if (self::$hasRemainingBudgetColumn === null) {
            self::$hasRemainingBudgetColumn = Schema::hasColumn('ppmp_items', 'remaining_budget');
        }

        return self::$hasRemainingBudgetColumn;
    }

    private function hasCategoryRemainingBudgetColumn(): bool
    {
        if (self::$hasCategoryRemainingBudgetColumn === null) {
            self::$hasCategoryRemainingBudgetColumn = Schema::hasColumn('ppmp_categories', 'remaining_budget');
        }

        return self::$hasCategoryRemainingBudgetColumn;
    }
}
