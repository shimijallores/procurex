<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\PPMPItemMonth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PPMPImport implements ToCollection, WithStartRow
{
    private static ?bool $hasRemainingBudgetColumn = null;

    private static ?bool $hasCategoryRemainingBudgetColumn = null;

    protected PPMP $ppmp;

    protected ?PPMPCategory $currentCategory = null;

    protected int $categoriesCreated = 0;

    protected int $itemsCreated = 0;

    protected array $budgetNotices = [];

    protected ?Collection $accountsByCode = null;

    protected ?Collection $accountsByName = null;

    public function __construct(PPMP $ppmp)
    {
        $this->ppmp = $ppmp;
    }

    public function startRow(): int
    {
        // Data rows (categories/items) start at row 8 in the updated XLSX layout
        return 8;
    }

    public function collection(Collection $rows): void
    {
        $accounts = Account::query()->get(['id', 'code', 'name']);
        $this->accountsByCode = $accounts->keyBy(function (Account $account): string {
            return $this->normalizeCode((string) $account->code);
        });
        $this->accountsByName = $accounts->keyBy(function (Account $account): string {
            return mb_strtolower(trim((string) $account->name));
        });

        foreach ($rows as $index => $row) {
            // Convert row to array for easier access
            $rowData = $row->toArray();
            $sheetRow = $this->startRow() + $index;
            $columnAValue = trim((string) ($rowData[0] ?? ''));

            if ($this->shouldStopParsing($columnAValue)) {
                break;
            }

            // Skip completely empty rows
            if (array_filter($rowData, fn ($val): bool => ! empty($val)) === []) {
                continue;
            }

            // Updated XLSX column map:
            // A: code, B: empty, C: category/item name, D: empty
            // E+: quantity, unit, unit cost, estimated budget, mode of procurement, month columns
            $code = $columnAValue;
            $name = trim((string) ($rowData[2] ?? ''));
            $quantity = trim((string) ($rowData[4] ?? ''));
            $unit = trim((string) ($rowData[5] ?? ''));
            $estimatedBudget = $this->parseAmount((string) ($rowData[7] ?? '0'));
            $modeOfProcurement = trim((string) ($rowData[8] ?? ''));

            if ($this->shouldSkipSectionHeaderRow($name)) {
                continue;
            }

            // Month columns (indices 9-20 for Jan-Dec)
            $monthQuantities = [];
            for ($i = 9; $i <= 20; ++$i) {
                $monthValue = trim((string) ($rowData[$i] ?? ''));
                $monthQuantities[] = $this->parseMonthQuantity($monthValue);
            }

            // Category rows are identified strictly by account/category code in column A
            // Example: 10707010 (normalized X-XX-XX-XXX format)
            $isCategory = $this->isCategoryCode($code);

            if ($isCategory) {
                $accountId = $this->resolveAccountId($code, $name);

                if (! $accountId) {
                    $this->currentCategory = null;

                    $this->budgetNotices[] = [
                        'category_code' => $code,
                        'category_name' => $name,
                        'ppmp_budget' => $estimatedBudget,
                        'status' => 'no_account_match',
                        'message' => 'No matching account found. Category was skipped.',
                    ];

                    continue;
                }

                $existingCategory = PPMPCategory::query()
                    ->where('ppmp_id', $this->ppmp->id)
                    ->where('account_id', $accountId)
                    ->first();

                if ($existingCategory) {
                    $this->currentCategory = $existingCategory;

                    if ($estimatedBudget > 0 && (float) $existingCategory->estimated_budget === 0.0) {
                        $updatePayload = [
                            'estimated_budget' => $estimatedBudget,
                        ];

                        if ($this->hasCategoryRemainingBudgetColumn()) {
                            $updatePayload['remaining_budget'] = $estimatedBudget;
                        }

                        $existingCategory->update($updatePayload);
                    }
                } else {
                    // Create category
                    $categoryPayload = [
                        'ppmp_id' => $this->ppmp->id,
                        'account_id' => $accountId,
                        'estimated_budget' => $estimatedBudget,
                    ];

                    if ($this->hasCategoryRemainingBudgetColumn()) {
                        $categoryPayload['remaining_budget'] = $estimatedBudget;
                    }

                    $this->currentCategory = PPMPCategory::create($categoryPayload);

                    ++$this->categoriesCreated;
                }
            } elseif ($name !== '' && $name !== '0' && $this->currentCategory && ! str_contains(strtolower($name), 'total')) {
                // This is an item row
                $ppmpItemPayload = [
                    'ppmp_category_id' => $this->currentCategory->id,
                    'name' => $name,
                    'quantity' => $this->parseQuantity($quantity),
                    'unit' => $unit !== '' ? $unit : 'pcs',
                    'estimated_budget' => $estimatedBudget,
                    'mode_of_procurement' => $this->normalizeModeOfProcurement($modeOfProcurement),
                ];

                if ($this->hasRemainingBudgetColumn()) {
                    $ppmpItemPayload['remaining_budget'] = $estimatedBudget;
                }

                $ppmpItem = PPMPItem::create($ppmpItemPayload);

                // Create month entries for this item
                $monthsCreated = 0;
                foreach ($monthQuantities as $monthIndex => $plannedQuantity) {
                    if ($plannedQuantity !== null && $plannedQuantity > 0) {
                        PPMPItemMonth::create([
                            'ppmp_item_id' => $ppmpItem->id,
                            'month' => $monthIndex + 1, // 1-12 for Jan-Dec
                            'planned_quantity' => $plannedQuantity,
                        ]);

                        ++$monthsCreated;
                    }
                }

                ++$this->itemsCreated;
            }
        }
    }

    /**
     * Check if code follows category pattern (e.g., 5-02-02-010 or 50202010)
     */
    private function isCategoryCode(string $code): bool
    {
        $normalizedDigits = $this->normalizeCode($code);

        if ($normalizedDigits === '') {
            return false;
        }

        // Category code is always expected as 8 digits (X-XX-XX-XXX)
        return preg_match('/^\d{8}$/', $normalizedDigits) === 1;
    }

    private function shouldSkipSectionHeaderRow(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        $normalized = mb_strtoupper(trim($name));

        return in_array($normalized, [
            'MAINTENANCE AND OTHER OPERATING EXPENSES',
            'PERSONAL SERVICES',
            'REPAIR AND MAINTENANCE',
            'CAPITAL OUTLAY',
        ], true);
    }

    private function shouldStopParsing(string $columnAValue): bool
    {
        if ($columnAValue === '') {
            return false;
        }

        return str_contains(mb_strtoupper($columnAValue), 'PREPARED BY:');
    }

    private function parseMonthQuantity(string $monthValue): ?int
    {
        if ($monthValue === '' || mb_strtolower($monthValue) === 'x') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9]/', '', $monthValue);

        if ($cleaned === '') {
            return null;
        }

        return (int) $cleaned;
    }

    private function resolveAccountId(string $code, string $name): ?int
    {
        $normalizedCode = $this->normalizeCode($code);
        $normalizedName = mb_strtolower(trim($name));

        if ($normalizedCode !== '' && $this->accountsByCode?->has($normalizedCode)) {
            return $this->accountsByCode->get($normalizedCode)->id;
        }

        if ($normalizedName !== '' && $this->accountsByName?->has($normalizedName)) {
            return $this->accountsByName->get($normalizedName)->id;
        }

        return null;
    }

    /**
     * Parse amount from CSV (removes commas, spaces, and currency symbols)
     */
    private function parseAmount(string $amount): float
    {
        // Remove commas, spaces, and currency symbols
        $cleaned = preg_replace('/[^0-9.]/', '', $amount);

        return $cleaned !== '' ? (float) $cleaned : 0.0;
    }

    /**
     * Parse quantity from string
     */
    private function parseQuantity(string $quantity): int
    {
        $cleaned = preg_replace('/[^0-9]/', '', $quantity);

        return $cleaned !== '' ? (int) $cleaned : 0;
    }

    /**
     * Normalize code to digits-only format (e.g., 5-02-01-010 => 50201010)
     */
    private function normalizeCode(string $code): string
    {
        return preg_replace('/[^0-9]/', '', trim($code)) ?? '';
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

    /**
     * Normalize mode of procurement
     */
    private function normalizeModeOfProcurement(string $mode): string
    {
        $mode = strtolower(trim($mode));

        if (str_contains($mode, 'bidding')) {
            return 'bidding';
        }

        if (str_contains($mode, 'small')) {
            return 'small value';
        }

        if (str_contains($mode, 'direct contracting')) {
            return 'direct_contracting';
        }

        if (str_contains($mode, 'direct')) {
            return 'direct';
        }

        return 'bidding'; // default
    }

    public function getCategoriesCreated(): int
    {
        return $this->categoriesCreated;
    }

    public function getItemsCreated(): int
    {
        return $this->itemsCreated;
    }

    public function getBudgetNotices(): array
    {
        return $this->budgetNotices;
    }
}
