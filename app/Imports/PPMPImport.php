<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\APPCategory;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\PPMPItemMonth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PPMPImport implements ToCollection, WithCustomCsvSettings, WithStartRow
{
    protected PPMP $ppmp;

    protected ?PPMPCategory $currentCategory = null;

    protected int $categoriesCreated = 0;

    protected int $itemsCreated = 0;

    protected array $budgetNotices = [];

    public function __construct(PPMP $ppmp)
    {
        $this->ppmp = $ppmp;
    }

    public function startRow(): int
    {
        // Skip the first 13 rows (metadata and headers)
        return 14;
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape_character' => '\\',
            'contiguous' => false,
            'use_bom' => false,
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            // Convert row to array for easier access
            $rowData = $row->toArray();

            // Skip completely empty rows
            if (array_filter($rowData, fn ($val): bool => ! empty($val)) === []) {
                continue;
            }

            // Map columns by index
            $code = trim((string) ($rowData[0] ?? ''));
            $name = trim((string) ($rowData[1] ?? ''));
            $quantity = trim((string) ($rowData[2] ?? ''));
            $unit = trim((string) ($rowData[3] ?? ''));
            $estimatedBudget = $this->parseAmount((string) ($rowData[4] ?? '0'));
            $modeOfProcurement = trim((string) ($rowData[5] ?? ''));

            // Month columns (indices 6-17 for Jan-Dec)
            $monthQuantities = [];
            for ($i = 6; $i <= 17; ++$i) {
                $monthValue = trim((string) ($rowData[$i] ?? ''));
                $monthQuantities[] = $monthValue !== '' ? (int) $monthValue : null;
            }

            // Check if this is a category row
            // Categories have codes like "5-02-02-010" and mode of procurement
            $isCategory = $this->isCategoryCode($code) && $modeOfProcurement !== '';

            if ($isCategory) {
                // Cross-check budget with APP category
                $budgetStatus = $this->checkBudget($code, $estimatedBudget);

                // Create category
                $this->currentCategory = PPMPCategory::create([
                    'ppmp_id' => $this->ppmp->id,
                    'code' => $this->normalizeCode($code),
                    'name' => $name,
                    'estimated_budget' => $estimatedBudget,
                ]);

                ++$this->categoriesCreated;

                // Store budget notice
                $this->budgetNotices[] = [
                    'category_code' => $code,
                    'category_name' => $name,
                    'ppmp_budget' => $estimatedBudget,
                    'app_budget' => $budgetStatus['app_budget'],
                    'status' => $budgetStatus['status'],
                    'message' => $budgetStatus['message'],
                ];
            } elseif ($name !== '' && $name !== '0' && $this->currentCategory && ! str_contains(strtolower($name), 'total')) {
                // This is an item row
                $ppmpItem = PPMPItem::create([
                    'ppmp_category_id' => $this->currentCategory->id,
                    'name' => $name,
                    'quantity' => $this->parseQuantity($quantity),
                    'unit' => $unit !== '' ? $unit : 'pcs',
                    'estimated_budget' => $estimatedBudget,
                    'mode_of_procurement' => $this->normalizeModeOfProcurement($modeOfProcurement),
                ]);

                // Create month entries for this item
                foreach ($monthQuantities as $monthIndex => $plannedQuantity) {
                    if ($plannedQuantity !== null && $plannedQuantity > 0) {
                        PPMPItemMonth::create([
                            'ppmp_item_id' => $ppmpItem->id,
                            'month' => $monthIndex + 1, // 1-12 for Jan-Dec
                            'planned_quantity' => $plannedQuantity,
                        ]);
                    }
                }

                ++$this->itemsCreated;
            }
        }
    }

    /**
     * Check if code follows category pattern (e.g., 5-02-02-010)
     */
    private function isCategoryCode(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        // Check if it matches pattern like "5-02-02-010" or "1-07-07-010"
        $pattern = '/^\d+-\d+-\d+-\d+$/';

        return preg_match($pattern, str_replace(' ', '', $code)) === 1;
    }

    /**
     * Check budget against APP category
     */
    private function checkBudget(string $code, float $ppmpBudget): array
    {
        $normalizedCode = $this->normalizeCode($code);

        // Find matching APP category by pap_code for the same office and fiscal year
        $appCategory = APPCategory::where('pap_code', $normalizedCode)
            ->whereHas('APP', function ($query): void {
                $query->where('office_id', $this->ppmp->office_id)
                    ->where('fiscal_year', $this->ppmp->fiscal_year);
            })
            ->with('APPItems')
            ->first();

        if (! $appCategory) {
            return [
                'status' => 'no_app',
                'app_budget' => null,
                'message' => 'No matching APP category found for this office and fiscal year.',
            ];
        }

        $appBudget = (float) $appCategory->estimated_budget;

        // If category budget is 0 or null, calculate from items
        if ($appBudget <= 0) {
            $appBudget = $appCategory->APPItems->sum('estimated_budget');
        }

        if ($ppmpBudget <= $appBudget) {
            return [
                'status' => 'within_budget',
                'app_budget' => $appBudget,
                'message' => sprintf(
                    'Budget is within APP allocation (APP: ₱%s).',
                    number_format($appBudget, 2)
                ),
            ];
        }

        return [
            'status' => 'over_budget',
            'app_budget' => $appBudget,
            'message' => sprintf(
                'Budget exceeds APP allocation by ₱%s (APP: ₱%s).',
                number_format($ppmpBudget - $appBudget, 2),
                number_format($appBudget, 2)
            ),
        ];
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
     * Normalize code by removing spaces
     */
    private function normalizeCode(string $code): string
    {
        return str_replace(' ', '', trim($code));
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
