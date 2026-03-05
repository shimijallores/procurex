<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\APP;
use App\Models\APPItem;
use App\Models\Account;
use App\Models\Emanating;
use App\Models\EmanatingItem;
use App\Models\Fund;
use App\Models\Office;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class EmanatingImport implements ToCollection
{
    protected ?Fund $fund = null;

    protected ?PPMP $ppmp = null;

    protected ?PPMPCategory $ppmpCategory = null;

    protected ?int $ppmpCategoryId = null;

    protected int $itemsCreated = 0;

    protected bool $itemsMatchPPMP = true;

    protected array $matchingResults = [];

    protected ?string $requestingOfficerName = null;

    protected ?string $requestingOfficerTitle = null;

    public function __construct(?PPMP $ppmp = null, ?int $ppmpCategoryId = null, ?Fund $fund = null)
    {
        $this->ppmp = $ppmp;
        $this->ppmpCategoryId = $ppmpCategoryId;
        $this->fund = $fund;

        if ($ppmpCategoryId) {
            $this->ppmpCategory = PPMPCategory::find($ppmpCategoryId);
        }
    }

    public function collection(Collection $rows): void
    {
        $rowArray = $rows->toArray();

        $metadata = $this->parseMetadata($rowArray);

        if (! $this->ppmp && isset($metadata['office_name'], $metadata['fiscal_year'])) {
            $office = Office::query()
                ->where('name', 'like', '%' . $metadata['office_name'] . '%')
                ->first();

            if ($office) {
                $this->ppmp = PPMP::query()
                    ->where('office_id', $office->id)
                    ->where('fiscal_year', (int) $metadata['fiscal_year'])
                    ->latest()
                    ->first();
            }
        }

        if (! $this->fund && $this->ppmp) {
            $fundQuery = Fund::query()
                ->where('office_id', $this->ppmp->office_id)
                ->where('fiscal_year', $this->ppmp->fiscal_year)
                ->where('project_code_id', $this->ppmp->project_code_id);

            $this->fund = (clone $fundQuery)->latest()->first();

            if (! $this->fund) {
                $this->fund = Fund::query()
                    ->where('office_id', $this->ppmp->office_id)
                    ->where('fiscal_year', $this->ppmp->fiscal_year)
                    ->where('type', 'general')
                    ->whereNull('project_code_id')
                    ->latest()
                    ->first();
            }
        }

        if (! $this->fund) {
            throw new RuntimeException('No matching Fund found for selected PPMP (office, project code, fiscal year).');
        }

        $accountId = $this->resolveAccountId($metadata);

        if (! $accountId) {
            throw new RuntimeException('No matching Account found from the XLSX Account line.');
        }

        $emanating = Emanating::create([
            'fund_id' => $this->fund->id,
            'ppmp_id' => $this->ppmp?->id,
            'project_id' => $this->fund->project_id,
            'account_id' => $accountId,
            'ppmp_category_id' => $this->ppmpCategory?->id ?? $this->ppmpCategoryId,
            'charged_to_code' => $metadata['charged_to_code'] ?? null,
            'pr_no' => $metadata['pr_no'] ?? null,
            'fiscal_year' => $metadata['fiscal_year'] ?? (int) ($this->ppmp?->fiscal_year ?? now()->year),
            'quarter' => $metadata['quarter'] ?? null,
            'month' => $metadata['month'] ?? null,
            'is_addendum' => false,
            'reimbursement' => false,
            'requesting_officer_name' => $metadata['requesting_officer_name'] ?? null,
            'requesting_officer_title' => $metadata['requesting_officer_title'] ?? null,
            'items_match_ppmp' => false,
        ]);

        $this->parseItems($rowArray, $emanating);

        $emanating->update([
            'items_match_ppmp' => $this->itemsMatchPPMP,
        ]);
    }

    /**
     * Parse metadata from XLSX rows
     */
    private function parseMetadata(array $rows): array
    {
        $metadata = [
            'fiscal_year' => (int) ($this->ppmp?->fiscal_year ?? now()->year),
        ];

        $underlineRow = null;
        $detectedMarkers = [];

        foreach ($rows as $index => $row) {
            $columnA = trim((string) ($row[0] ?? ''));
            $columnB = trim((string) ($row[1] ?? ''));
            $combinedText = trim($columnA . ' ' . $columnB);

            if ($columnA !== '' && preg_match('/PR\s*NO\.?/i', $columnA) === 1 && $columnB !== '') {
                $metadata['pr_no'] = $columnB;
                $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'pr_no', 'value' => $columnB];
            }

            if (preg_match('/\b(20\d{2})\b/', $combinedText, $yearMatches) === 1) {
                $metadata['fiscal_year'] = (int) $yearMatches[1];
                $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'fiscal_year', 'value' => $metadata['fiscal_year']];
            }

            if (! isset($metadata['quarter']) && preg_match('/for\s+the\s+(\d{1,2})(?:st|nd|rd|th)\b/i', $combinedText, $quarterMatches) === 1) {
                $quarter = (int) $quarterMatches[1];

                if ($quarter >= 1 && $quarter <= 4) {
                    $metadata['quarter'] = $quarter;
                    $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'quarter', 'value' => $quarter];
                }
            }

            if (! isset($metadata['month']) && preg_match('/quarter\s*\/\s*month\s+of\s+([A-Za-z]+)/i', $combinedText, $monthMatches) === 1) {
                try {
                    $metadata['month'] = Carbon::parse('1 ' . $monthMatches[1])->month;
                    $detectedMarkers[] = [
                        'row' => $index + 1,
                        'marker' => 'month',
                        'value' => $metadata['month'],
                    ];
                } catch (\Throwable) {
                }
            }

            if ($columnA !== '' && str_contains(mb_strtoupper($columnA), 'CHARGE TO:')) {
                $metadata['charged_to_raw'] = $columnB;

                if (preg_match('/\b(\d{4,})\b/', $columnB, $codeMatches) === 1) {
                    $metadata['charged_to_code'] = $codeMatches[1];
                } else {
                    $metadata['charged_to_code'] = $columnB;
                }

                $metadata['office_name'] = trim((string) preg_replace('/\(.*$/', '', $columnB));
                $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'charge_to', 'value' => $columnB];
            }

            if ($columnA !== '' && preg_match('/^account\b/i', $columnA) === 1) {
                if (preg_match('/([\d\-]+)\s*\(([^\)]+)\)/', $columnB, $accountMatches) === 1) {
                    $metadata['account_code'] = $accountMatches[1];
                    $metadata['account_name'] = trim($accountMatches[2]);
                    $metadata['ppmp_category_code'] = $accountMatches[1];
                    $metadata['ppmp_category_name'] = trim($accountMatches[2]);
                    $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'account', 'value' => $columnB];
                } elseif (preg_match('/([\d\-]+)/', $columnB, $codeOnlyMatches) === 1) {
                    $metadata['account_code'] = $codeOnlyMatches[1];
                    $metadata['ppmp_category_code'] = $codeOnlyMatches[1];
                    $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'account_code_only', 'value' => $columnB];
                }
            }

            if ($columnB !== '' && preg_match('/_{3,}/', $columnB) === 1) {
                $underlineRow = $index;
                $detectedMarkers[] = ['row' => $index + 1, 'marker' => 'underline', 'value' => $columnB];
            }
        }

        if ($underlineRow !== null) {
            $name = null;
            $title = null;

            for ($i = $underlineRow + 1; $i < count($rows); $i++) {
                $candidate = trim((string) ($rows[$i][1] ?? ''));
                if ($candidate === '') {
                    continue;
                }

                if (! $name) {
                    $name = $candidate;
                    continue;
                }

                $title = $candidate;
                break;
            }

            $metadata['requesting_officer_name'] = $name;
            $metadata['requesting_officer_title'] = $title;

            $this->requestingOfficerName = $name;
            $this->requestingOfficerTitle = $title;
        }

        if (isset($metadata['ppmp_category_code']) && $this->ppmp) {
            $normalizedCategoryCode = $this->normalizeCode($metadata['ppmp_category_code']);

            $this->ppmpCategory = PPMPCategory::query()
                ->where('ppmp_id', $this->ppmp->id)
                ->with('account')
                ->get()
                ->first(function (PPMPCategory $category) use ($normalizedCategoryCode): bool {
                    return $this->normalizeCode((string) $category->account?->code) === $normalizedCategoryCode;
                });
        }

        return $metadata;
    }

    /**
     * Parse items from XLSX
     */
    private function parseItems(array $rows, Emanating $emanating): array
    {
        $items = [];
        $ppmpItems = $this->ppmpCategory
            ? PPMPItem::query()->where('ppmp_category_id', $this->ppmpCategory->id)->get()
            : collect();

        $appItems = collect();
        if ($this->ppmp) {
            $app = APP::query()
                ->where('office_id', $this->ppmp->office_id)
                ->where('fiscal_year', $this->ppmp->fiscal_year)
                ->latest()
                ->first();

            if ($app) {
                $appItems = APPItem::query()
                    ->whereHas('appCategory', fn($query) => $query->where('app_id', $app->id))
                    ->get();
            }
        }

        $hasStartedItemSection = false;
        $skippedBeforeStart = 0;
        $skippedMissingDescription = 0;
        $breakReason = null;

        foreach ($rows as $index => $row) {
            $columnA = trim((string) ($row[0] ?? ''));
            $columnB = trim((string) ($row[1] ?? ''));
            $columnC = trim((string) ($row[2] ?? ''));

            if ($columnA !== '' && str_contains(mb_strtoupper($columnA), 'CHARGE TO:')) {
                $breakReason = 'charge_to_marker';
                break;
            }

            if (! $hasStartedItemSection && ($columnA === '' || preg_match('/\d/', $columnA) !== 1)) {
                $skippedBeforeStart++;
                continue;
            }

            if ($columnA === '' && $columnB === '' && $hasStartedItemSection) {
                $breakReason = 'blank_row_after_start';
                break;
            }

            if ($columnB === '') {
                $skippedMissingDescription++;
                continue;
            }

            $hasStartedItemSection = true;

            [$quantity, $unit] = $this->parseQuantityAndUnit($columnA);
            $ppmpItem = $this->findMatchingPPMPItem($columnB, $ppmpItems);
            $appItem = $this->findMatchingAPPItem($columnB, $appItems);

            $exceedsPPMPQuantity = $ppmpItem && $quantity > ((int) $ppmpItem->quantity);
            $missingInPpmp = ! $ppmpItem;
            $missingInApp = ! $appItem;

            if ($missingInPpmp || $missingInApp || $exceedsPPMPQuantity) {
                $this->itemsMatchPPMP = false;
            }

            $messages = [];
            if ($missingInPpmp) {
                $messages[] = 'Missing in PPMP';
            }
            if ($missingInApp) {
                $messages[] = 'Missing in APP';
            }
            if ($exceedsPPMPQuantity) {
                $messages[] = sprintf('Quantity exceeds PPMP (%d > %d)', $quantity, (int) $ppmpItem->quantity);
            }

            $this->matchingResults[] = [
                'description' => $columnB,
                'quantity' => $quantity,
                'unit' => $unit,
                'matched' => $messages === [],
                'messages' => $messages,
            ];

            $emanatingItem = EmanatingItem::create([
                'emanating_id' => $emanating->id,
                'ppmp_item_id' => $ppmpItem?->id,
                'name' => $columnB,
                'quantity' => $quantity,
                'unit' => $unit,
                'total_price' => $this->parseAmount($columnC),
            ]);

            $items[] = $emanatingItem;
            $this->itemsCreated++;
        }

        return $items;
    }

    /**
     * Find matching PPMP item by description.
     */
    private function findMatchingPPMPItem(string $description, Collection $ppmpItems): ?PPMPItem
    {
        if (! $this->ppmpCategory || $ppmpItems->isEmpty()) {
            return null;
        }

        $normalizedDescription = $this->normalizeItemText($description);
        if ($normalizedDescription === '') {
            return null;
        }

        $exact = $ppmpItems->first(function (PPMPItem $item) use ($normalizedDescription): bool {
            return $this->normalizeItemText((string) $item->name) === $normalizedDescription;
        });

        if ($exact) {
            return $exact;
        }

        return $ppmpItems->first(function (PPMPItem $item) use ($normalizedDescription): bool {
            $normalizedItemName = $this->normalizeItemText((string) $item->name);

            return str_contains($normalizedItemName, $normalizedDescription)
                || str_contains($normalizedDescription, $normalizedItemName);
        });
    }

    private function findMatchingAPPItem(string $description, Collection $appItems): ?APPItem
    {
        if ($appItems->isEmpty()) {
            return null;
        }

        $normalizedDescription = $this->normalizeItemText($description);
        if ($normalizedDescription === '') {
            return null;
        }

        $exact = $appItems->first(function (APPItem $item) use ($normalizedDescription): bool {
            return $this->normalizeItemText((string) $item->name) === $normalizedDescription;
        });

        if ($exact) {
            return $exact;
        }

        return $appItems->first(function (APPItem $item) use ($description): bool {
            $normalizedItemName = $this->normalizeItemText((string) $item->name);
            $normalizedDescription = $this->normalizeItemText($description);

            return str_contains($normalizedItemName, $normalizedDescription)
                || str_contains($normalizedDescription, $normalizedItemName);
        });
    }

    private function parseQuantityAndUnit(string $value): array
    {
        if ($value === '') {
            return [0, 'pcs'];
        }

        if (preg_match('/^(\d+)\s*(.*)$/', $value, $matches) === 1) {
            $quantity = (int) $matches[1];
            $unit = trim((string) ($matches[2] ?? ''));

            return [$quantity, $unit !== '' ? $unit : 'pcs'];
        }

        return [0, 'pcs'];
    }

    /**
     * Parse amount from CSV (removes commas, spaces, and currency symbols)
     */
    private function parseAmount(string $amount): float
    {
        $cleaned = preg_replace('/[^0-9.]/', '', $amount);

        return $cleaned !== '' ? (float) $cleaned : 0.0;
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/[^0-9]/', '', $code) ?? '';
    }

    private function normalizeAccountCodeForMatch(string $code): string
    {
        $parts = preg_split('/[^0-9]+/', $code) ?: [];
        $filtered = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));

        if ($filtered === []) {
            return '';
        }

        $normalizedParts = array_map(static fn(string $part): string => (string) ((int) $part), $filtered);

        return implode('-', $normalizedParts);
    }

    private function resolveAccountId(array $metadata): ?int
    {
        if ($this->ppmpCategory?->account_id) {
            return (int) $this->ppmpCategory->account_id;
        }

        $accountCode = trim((string) ($metadata['account_code'] ?? $metadata['ppmp_category_code'] ?? ''));
        $accountName = trim((string) ($metadata['account_name'] ?? $metadata['ppmp_category_name'] ?? ''));

        $accounts = Account::query()->get(['id', 'code', 'name']);

        if ($accountCode !== '') {
            $normalizedTargetCode = $this->normalizeAccountCodeForMatch($accountCode);

            $matchedByCode = $accounts->first(function (Account $account) use ($normalizedTargetCode): bool {
                return $this->normalizeAccountCodeForMatch((string) $account->code) === $normalizedTargetCode;
            });

            if ($matchedByCode) {
                return (int) $matchedByCode->id;
            }
        }

        if ($accountName !== '') {
            $normalizedTargetName = $this->normalizeItemText($accountName);

            $matchedByName = $accounts->first(function (Account $account) use ($normalizedTargetName): bool {
                return $this->normalizeItemText((string) $account->name) === $normalizedTargetName;
            });

            if ($matchedByName) {
                return (int) $matchedByName->id;
            }
        }

        return null;
    }

    private function normalizeItemText(string $text): string
    {
        $normalized = mb_strtolower(trim($text));
        $normalized = str_replace(['’', '“', '”', "\t", "\r", "\n"], ['\'', '"', '"', ' ', ' ', ' '], $normalized);
        $normalized = preg_replace('/[^\pL\pN\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    public function getItemsCreated(): int
    {
        return $this->itemsCreated;
    }

    public function getItemsMatchPPMP(): bool
    {
        return $this->itemsMatchPPMP;
    }

    public function getMatchingResults(): array
    {
        return $this->matchingResults;
    }

    public function getRequestingOfficerName(): ?string
    {
        return $this->requestingOfficerName;
    }

    public function getRequestingOfficerTitle(): ?string
    {
        return $this->requestingOfficerTitle;
    }
}
