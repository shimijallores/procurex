<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Account;
use App\Models\APP;
use App\Models\APPCategory;
use App\Models\APPItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class APPImport implements ToCollection, WithStartRow
{
    protected APP $app;

    protected ?APPCategory $currentCategory = null;

    protected int $categoriesCreated = 0;

    protected int $itemsCreated = 0;

    /**
     * @var array<string, array{id:int,code:string,name:string}>
     */
    protected array $accountsByNormalizedCode = [];

    /**
     * @var array<string, array{id:int,code:string,name:string}>
     */
    protected array $accountsByNormalizedName = [];

    public function __construct(APP $app)
    {
        $this->app = $app;

        Account::query()
            ->get(['id', 'code', 'name'])
            ->each(function (Account $account): void {
                $accountData = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                ];

                $normalizedCode = $this->normalizeAccountCode($account->code);
                $normalizedName = $this->normalizeAccountName($account->name);

                if ($normalizedCode !== '') {
                    $this->accountsByNormalizedCode[$normalizedCode] = $accountData;
                }

                if ($normalizedName !== '') {
                    $this->accountsByNormalizedName[$normalizedName] = $accountData;
                }
            });

        Log::info('[APP Import] Account lookup maps prepared', [
            'app_id' => $this->app->id,
            'accounts_by_code_count' => count($this->accountsByNormalizedCode),
            'accounts_by_name_count' => count($this->accountsByNormalizedName),
        ]);
    }

    public function startRow(): int
    {
        // Skip the first 10 rows (metadata and headers)
        return 11;
    }

    public function collection(Collection $rows): void
    {
        Log::info('[APP Import] Starting XLSX collection parse', [
            'app_id' => $this->app->id,
            'rows_count' => $rows->count(),
            'start_row' => $this->startRow(),
        ]);

        foreach ($rows as $index => $row) {
            // Convert row to array for easier access
            $rowData = $row->toArray();
            $columnOffset = $this->resolveColumnOffset($rowData);
            $sheetRow = $this->startRow() + $index;

            // Skip completely empty rows
            if (array_filter($rowData, fn($val): bool => ! empty($val)) === []) {
                continue;
            }

            // Map columns by index since headers might have issues with merged cells
            $papCode = trim((string) ($rowData[$columnOffset] ?? ''));
            $name = $this->extractName($rowData, $columnOffset);
            $earlyProcurement = trim((string) ($rowData[$columnOffset + 3] ?? ''));
            $modeOfProcurement = trim((string) ($rowData[$columnOffset + 4] ?? ''));
            $scheduleAdPost = trim((string) ($rowData[$columnOffset + 5] ?? ''));
            $scheduleSigning = trim((string) ($rowData[$columnOffset + 8] ?? ''));
            $sourceOfFund = trim((string) ($rowData[$columnOffset + 9] ?? ''));
            $totalBudget = $this->parseAmount((string) ($rowData[$columnOffset + 10] ?? '0'));
            $mooeAmount = $this->parseAmount((string) ($rowData[$columnOffset + 11] ?? '0'));
            $coAmount = $this->parseAmount((string) ($rowData[$columnOffset + 12] ?? '0'));
            $remarks = trim((string) ($rowData[$columnOffset + 13] ?? ''));

            $normalizedPapCode = $this->normalizePapCode($papCode);
            $matchedAccount = $this->findMatchingAccount($normalizedPapCode, $name);

            // Check if this is a category row
            // Categories have proper PAP codes (more than 3 characters or contains dashes/spaces)
            // Items might have simple numbers like "1", "2", "3"
            $isSimpleNumber = $papCode !== '' && $papCode !== '0' && is_numeric($papCode) && strlen($papCode) <= 3;
            $looksLikeAccountCode = $this->looksLikeAccountCode($papCode);
            $isCategory = ($matchedAccount !== null || $looksLikeAccountCode)
                && ($name !== '' && $name !== '0')
                && ! $isSimpleNumber;

            if ($isCategory) {
                // Extract schedule months
                $scheduleFromMonth = $this->extractMonthNumber($scheduleAdPost);
                $scheduleToMonth = $this->extractMonthNumber($scheduleSigning);

                if (! $matchedAccount) {
                    Log::warning('[APP Import] No account match found for category', [
                        'app_id' => $this->app->id,
                        'sheet_row' => $sheetRow,
                        'pap_code' => $normalizedPapCode,
                        'name' => $name,
                    ]);
                }

                // Create category
                $this->currentCategory = APPCategory::create([
                    'app_id' => $this->app->id,
                    'account_id' => $matchedAccount['id'] ?? null,
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

                Log::info('[APP Import] Category created', [
                    'app_id' => $this->app->id,
                    'sheet_row' => $sheetRow,
                    'app_category_id' => $this->currentCategory->id,
                    'account_id' => $this->currentCategory->account_id,
                    'imported_pap_code' => $normalizedPapCode,
                    'imported_name' => $name,
                ]);
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

                Log::info('[APP Import] Item created', [
                    'app_id' => $this->app->id,
                    'sheet_row' => $sheetRow,
                    'app_category_id' => $this->currentCategory->id,
                    'item_name' => $name,
                ]);
            } else {
                Log::warning('[APP Import] Row skipped', [
                    'app_id' => $this->app->id,
                    'sheet_row' => $sheetRow,
                    'column_offset' => $columnOffset,
                    'pap_code_raw' => $papCode,
                    'normalized_pap_code' => $normalizedPapCode,
                    'name' => $name,
                    'matched_account_id' => $matchedAccount['id'] ?? null,
                    'is_category' => $isCategory,
                    'has_current_category' => $this->currentCategory !== null,
                ]);
            }
        }

        Log::info('[APP Import] Completed XLSX parse', [
            'app_id' => $this->app->id,
            'categories_created' => $this->categoriesCreated,
            'items_created' => $this->itemsCreated,
        ]);
    }

    /**
     * Resolve column offset for files where data starts at column B.
     */
    private function resolveColumnOffset(array $rowData): int
    {
        $firstColumn = trim((string) ($rowData[0] ?? ''));
        $secondColumn = trim((string) ($rowData[1] ?? ''));

        if ($firstColumn === '' && $secondColumn !== '') {
            return 1;
        }

        return 0;
    }

    /**
     * Extract name for category/item, with fallback columns for XLSX layouts.
     */
    private function extractName(array $rowData, int $columnOffset): string
    {
        $primaryName = trim((string) ($rowData[$columnOffset + 1] ?? ''));

        if ($primaryName !== '' && $primaryName !== '0') {
            return $primaryName;
        }

        $fallbackName = trim((string) ($rowData[$columnOffset + 2] ?? ''));

        return $fallbackName;
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
     * Normalize PAP code by converting spaces to dashes
     * e.g., "5- 02- 03- 010" becomes "5-02-03-010"
     */
    private function normalizePapCode(string $papCode): string
    {
        // Remove all dashes first
        $normalized = str_replace('-', '', $papCode);
        // Remove extra spaces and normalize to single spaces between parts
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        // Replace spaces with dashes
        $normalized = str_replace(' ', '-', trim($normalized));

        return $normalized;
    }

    /**
     * Detect whether a value resembles an account code format.
     */
    private function looksLikeAccountCode(string $papCode): bool
    {
        $normalized = $this->normalizeAccountCode($papCode);

        return strlen($normalized) >= 9;
    }

    /**
     * Normalize account code to digits-only for robust matching.
     */
    private function normalizeAccountCode(string $code): string
    {
        return preg_replace('/[^0-9]/', '', $code) ?? '';
    }

    /**
     * Normalize account name for case-insensitive matching.
     */
    private function normalizeAccountName(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name) ?? ''));
    }

    /**
     * @return array{id:int,code:string,name:string}|null
     */
    private function findMatchingAccount(string $papCode, string $name): ?array
    {
        $normalizedPapCode = $this->normalizeAccountCode($papCode);

        if ($normalizedPapCode !== '' && isset($this->accountsByNormalizedCode[$normalizedPapCode])) {
            return $this->accountsByNormalizedCode[$normalizedPapCode];
        }

        $normalizedName = $this->normalizeAccountName($name);

        if ($normalizedName !== '' && isset($this->accountsByNormalizedName[$normalizedName])) {
            return $this->accountsByNormalizedName[$normalizedName];
        }

        return null;
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
