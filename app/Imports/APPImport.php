<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\APP;
use App\Models\APPCategory;
use App\Models\APPItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class APPImport implements ToCollection, WithCustomCsvSettings, WithStartRow
{
    protected APP $app;

    protected ?APPCategory $currentCategory = null;

    protected int $categoriesCreated = 0;

    protected int $itemsCreated = 0;

    public function __construct(APP $app)
    {
        $this->app = $app;
    }

    public function startRow(): int
    {
        // Skip the first 10 rows (metadata and headers)
        return 11;
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

            // Map columns by index since headers might have issues with merged cells
            $papCode = trim((string) ($rowData[0] ?? ''));
            $name = trim((string) ($rowData[1] ?? ''));
            $pmoEndUser = trim((string) ($rowData[2] ?? ''));
            $earlyProcurement = trim((string) ($rowData[3] ?? ''));
            $modeOfProcurement = trim((string) ($rowData[4] ?? ''));
            $scheduleAdPost = trim((string) ($rowData[5] ?? ''));
            $scheduleSubmission = trim((string) ($rowData[6] ?? ''));
            $scheduleNotice = trim((string) ($rowData[7] ?? ''));
            $scheduleSigning = trim((string) ($rowData[8] ?? ''));
            $sourceOfFund = trim((string) ($rowData[9] ?? ''));
            $totalBudget = $this->parseAmount((string) ($rowData[10] ?? '0'));
            $mooeAmount = $this->parseAmount((string) ($rowData[11] ?? '0'));
            $coAmount = $this->parseAmount((string) ($rowData[12] ?? '0'));
            $remarks = trim((string) ($rowData[13] ?? ''));

            // Check if this is a category row
            // Categories have proper PAP codes (more than 3 characters or contains dashes/spaces)
            // Items might have simple numbers like "1", "2", "3"
            $isSimpleNumber = $papCode !== '' && $papCode !== '0' && is_numeric($papCode) && strlen($papCode) <= 3;
            $isCategory = $papCode !== '' && $papCode !== '0' && ($name !== '' && $name !== '0') && ! $isSimpleNumber;

            if ($isCategory) {
                // Extract schedule months
                $scheduleFromMonth = $this->extractMonthNumber($scheduleAdPost);
                $scheduleToMonth = $this->extractMonthNumber($scheduleSigning);

                // Create category
                $this->currentCategory = APPCategory::create([
                    'app_id' => $this->app->id,
                    'pap_code' => $papCode,
                    'name' => $name,
                    'early_procurement' => strtoupper($earlyProcurement) === 'YES',
                    'mode_of_procurement' => $modeOfProcurement,
                    'schedule_from_month' => $scheduleFromMonth,
                    'schedule_to_month' => $scheduleToMonth,
                    'source_of_fund' => $sourceOfFund,
                    'estimated_budget' => $totalBudget,
                    'mooe_amount' => $mooeAmount,
                    'co_amount' => $coAmount,
                    'remarks' => $remarks,
                ]);
                ++$this->categoriesCreated;
            } elseif ($name !== '' && $name !== '0' && $this->currentCategory) {
                // This is an item row (no PAP code or simple number, but has name)
                APPItem::create([
                    'app_category_id' => $this->currentCategory->id,
                    'name' => $name,
                    'estimated_budget' => $totalBudget,
                    'mooe_amount' => $mooeAmount,
                    'co_amount' => $coAmount,
                    'remarks' => $remarks,
                ]);
                ++$this->itemsCreated;
            }
        }
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
     * Extract month number from schedule string
     * Handles formats like "January-December", "March", "March,June,September"
     */
    private function extractMonthNumber(?string $schedule): ?int
    {
        if (in_array($schedule, [null, '', '0'], true)) {
            return null;
        }

        $months = [
            'january' => 1,
            'february' => 2,
            'march' => 3,
            'april' => 4,
            'may' => 5,
            'june' => 6,
            'july' => 7,
            'august' => 8,
            'september' => 9,
            'october' => 10,
            'november' => 11,
            'december' => 12,
        ];

        // Extract first month mentioned
        $schedule = strtolower($schedule);
        foreach ($months as $month => $number) {
            if (strpos($schedule, $month) !== false) {
                return $number;
            }
        }

        return null;
    }

    public function getCategoriesCreated(): int
    {
        return $this->categoriesCreated;
    }

    public function getItemsCreated(): int
    {
        return $this->itemsCreated;
    }
}
