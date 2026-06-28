<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Xls;

class PrExcelImportService
{
    private const SKIP_SHEETS = ['offices', 'Sheet1', 'email', 'Sheet5'];

    private array $warnings = [];

    private array $created = [];

    private array $pendingMultiPage = [];

    public function __construct(private readonly DatabaseManager $databaseManager) {}

    public function import(UploadedFile $uploadedFile, int $adminId): array
    {
        $this->warnings = [];
        $this->created = [];
        $this->pendingMultiPage = [];

        $xls = new Xls;
        $xls->setReadDataOnly(true);

        $spreadsheet = $xls->load($uploadedFile->getRealPath());

        $sheetCount = $spreadsheet->getSheetCount();

        for ($sheetIndex = 0; $sheetIndex < $sheetCount; ++$sheetIndex) {
            $sheet = $spreadsheet->getSheet($sheetIndex);
            $sheetName = $sheet->getTitle();

            if (in_array($sheetName, self::SKIP_SHEETS, true)) {
                continue;
            }

            $rows = $sheet->toArray();
            $this->processSheet($rows, $sheetName, $adminId);
        }

        $spreadsheet->disconnectWorksheets();

        return [
            'created' => $this->created,
            'warnings' => $this->warnings,
        ];
    }

    private function processSheet(array $rows, string $sheetName, int $adminId): void
    {
        $numRows = count($rows);

        for ($i = 0; $i < $numRows; ++$i) {
            $colA = $this->cell($rows, $i, 0);

            if ($colA === 'PURCHASE REQUEST') {
                try {
                    $this->parseBlock($rows, $i, $sheetName, $adminId);
                } catch (\Throwable $e) {
                    $this->warnings[] = sprintf('Sheet "%s", row %d: %s', $sheetName, $i, $e->getMessage());
                }
            }
        }
    }

    private function parseBlock(array $rows, int $start, string $sheetName, int $adminId): void
    {
        // Row +3 — Page indicator
        $pageLabel = $this->cell($rows, $start + 3, 6);
        $pageNum = 1;
        $totalPages = 1;
        if ($pageLabel !== '' && preg_match('/Page\s+(\d+)\s+of\s+(\d+)/i', $pageLabel, $m)) {
            $pageNum = (int) $m[1];
            $totalPages = (int) $m[2];
        }

        // Row +4 — PR Header
        $department = $this->cell($rows, $start + 4, 2);
        $prNoRaw = $this->cell($rows, $start + 4, 4);
        $prDateRaw = $this->cell($rows, $start + 4, 6);

        $prNo = $this->parsePrNo($prNoRaw);
        $prDate = $this->parseDate($prDateRaw);

        // Row +39 — Grand Total (or Sub Total for multi-page)
        $this->cell($rows, $start + 39, 5);
        $grandTotal = $this->cell($rows, $start + 39, 6);

        // Row +40, +41 — Purpose / Remarks
        $remarksMain = $this->cell($rows, $start + 40, 0);
        $remarksCont = $this->cell($rows, $start + 41, 2);

        $remarks = $this->parseRemarks($remarksMain, $remarksCont);

        // Row +44 — Names
        $requesterName = $this->cell($rows, $start + 44, 3);
        $approverName = $this->cell($rows, $start + 44, 4);

        // Row +45 — Designations
        $requesterDesignation = $this->cell($rows, $start + 45, 3);
        $approverDesignation = $this->cell($rows, $start + 45, 4);

        // Line items — rows +9 to +38
        $items = $this->parseItems($rows, $start + 9, $start + 38);

        $totalAmount = is_numeric($grandTotal) ? (float) $grandTotal : 0.0;

        // If multi-page continuation (page > 1)
        $prRecord = [
            'department' => $department,
            'pr_no' => $prNo,
            'pr_date' => $prDate,
            'items' => $items,
            'total_amount' => $totalAmount,
            'remarks' => $remarks,
            'requester_name' => $requesterName,
            'requester_designation' => $requesterDesignation,
            'approver_name' => $approverName,
            'approver_designation' => $approverDesignation,
            'admin_id' => $adminId,
        ];

        if ($totalPages > 1) {
            $this->handleMultiPage($prRecord, $pageNum, $totalPages, $sheetName, $start);
        } else {
            $this->savePr($prRecord, $sheetName, $start);
        }
    }

    private function handleMultiPage(array $prRecord, int $pageNum, int $totalPages, string $sheetName, int $start): void
    {
        $key = $prRecord['pr_no'] ?? sprintf('unknown-%s-%d', $sheetName, $start);

        if ($pageNum === 1) {
            $this->pendingMultiPage[$key] = $prRecord;
        } elseif ($pageNum > 1 && isset($this->pendingMultiPage[$key])) {
            // Filter out "balance forwarded" rows
            $items = array_values(array_filter($prRecord['items'], function (array $item): bool {
                return stripos((string) ($item['description'] ?? ''), 'balance forwarded') === false;
            }));

            $this->pendingMultiPage[$key]['items'] = array_merge(
                $this->pendingMultiPage[$key]['items'],
                $items
            );
            $this->pendingMultiPage[$key]['total_amount'] = $prRecord['total_amount'];

            if ($pageNum === $totalPages) {
                $record = $this->pendingMultiPage[$key];
                unset($this->pendingMultiPage[$key]);
                $this->savePr($record, $sheetName, $start);
            }
        } else {
            // Unexpected: page > 1 but no pending record
            $this->savePr($prRecord, $sheetName, $start);
        }
    }

    private function parseItems(array $rows, int $from, int $to): array
    {
        $items = [];
        $itemCount = count($rows);

        for ($offset = $from; $offset <= $to && $offset < $itemCount; ++$offset) {
            $itemNoVal = $rows[$offset][0] ?? null;

            if (! is_numeric($itemNoVal) || (float) $itemNoVal <= 0) {
                continue;
            }

            $item = [
                'item_no' => (int) $itemNoVal,
                'unit' => $this->cell($rows, $offset, 2),
                'description' => $this->cell($rows, $offset, 3),
                'quantity' => $this->parseNumeric($rows, $offset, 4),
                'unit_cost' => $this->parseNumeric($rows, $offset, 5),
                'total_cost' => $this->parseNumeric($rows, $offset, 6),
            ];

            // Check for continuation rows (same offset range, no item number, has description text)
            $nextOffset = $offset + 1;
            while ($nextOffset <= $to && $nextOffset < $itemCount) {
                $nextItemNo = $rows[$nextOffset][0] ?? null;
                $nextDesc = $this->cell($rows, $nextOffset, 3);

                if (is_numeric($nextItemNo) && (float) $nextItemNo > 0) {
                    break;
                }

                if ($nextDesc === '') {
                    break;
                }

                $item['description'] = trim($item['description'].' '.$nextDesc);
                $offset = $nextOffset;
                ++$nextOffset;
            }

            $items[] = $item;
        }

        return $items;
    }

    private function savePr(array $record, string $sheetName, int $start): void
    {
        $prNo = $record['pr_no'] ?? null;

        // Skip PRs with invalid PR number format (must match MMYY-NNNN or MMYYNNNN)
        if ($prNo === null || ! preg_match('/^\d{4}-?\d{4}$/', $prNo)) {
            $this->warnings[] = sprintf('Sheet "%s", row %d: Skipping PR "%s" — invalid PR number format.', $sheetName, $start, $prNo ?? '(empty)');

            return;
        }

        // Skip PRs with no items
        $items = $record['items'] ?? [];

        if (count($items) === 0) {
            $this->warnings[] = sprintf('Sheet "%s", row %d: Skipping PR "%s" — no line items found.', $sheetName, $start, $prNo);

            return;
        }

        $department = trim($record['department'] ?? '');
        $office = $this->findOffice($department);

        if (!$office instanceof \App\Models\Office && $department !== '') {
            $this->warnings[] = sprintf('Sheet "%s", row %d: Office not found for "%s". PR %s will have no office assigned.', $sheetName, $start, $department, $prNo);
        }

        $prDate = $record['pr_date'];
        $fiscalYear = null;
        if ($prDate) {
            try {
                $fiscalYear = (int) Carbon::parse($prDate)->format('Y');
            } catch (\Throwable) {
                // ignore
            }
        }

        $this->databaseManager->beginTransaction();
        try {
            $pr = PurchaseRequest::create([
                'emanating_id' => null,
                'office_id' => $office?->id,
                'fund_id' => null,
                'pr_no' => $record['pr_no'],
                'pr_date' => $prDate,
                'fiscal_year' => $fiscalYear,
                'sai_no' => null,
                'sai_date' => null,
                'requested_by_name' => $record['requester_name'] !== '' ? $record['requester_name'] : null,
                'requested_by_designation' => $record['requester_designation'] !== '' ? $record['requester_designation'] : null,
                'purpose' => $record['remarks'] !== '' ? $record['remarks'] : null,
                'total_amount' => $record['total_amount'],
                'status' => 'approved',
                'remarks' => null,
            ]);

            foreach ($record['items'] as $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $totalCost = (float) ($item['total_cost'] ?? 0);

                if ($totalCost === 0.0 && $quantity > 0 && $unitCost > 0) {
                    $totalCost = $quantity * $unitCost;
                }

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'emanating_item_id' => null,
                    'item_name' => $item['description'] !== '' ? $item['description'] : null,
                    'unit' => $item['unit'] !== '' ? $item['unit'] : null,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'line_total' => round($totalCost, 2),
                    'vat_applicable' => false,
                    'vat_rate' => 0,
                    'remarks' => null,
                    'matrix_new_amount' => round($totalCost, 2),
                    'matrix_account_id' => null,
                    'matrix_pr_admin_user_id' => null,
                    'matrix_budgeting_admin_user_id' => null,
                ]);
            }

            $this->databaseManager->commit();
            $this->created[] = $pr->id;
        } catch (\Throwable $throwable) {
            $this->databaseManager->rollBack();
            throw $throwable;
        }
    }

    private function findOffice(string $department): ?Office
    {
        $trimmed = trim($department);

        if ($trimmed === '') {
            return null;
        }

        // 1. Exact match
        $office = Office::query()
            ->whereRaw('TRIM(name) = ?', [$trimmed])
            ->first();

        if ($office !== null) {
            return $office;
        }

        // 2. Case-insensitive exact match
        $office = Office::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($trimmed)])
            ->first();

        if ($office !== null) {
            return $office;
        }

        // 3. Fuzzy match name using similar_text
        $normalized = mb_strtolower(preg_replace('/\s+/', ' ', $trimmed));
        $offices = Office::all(['id', 'name', 'acronym']);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($offices as $office) {
            $officeNormalized = mb_strtolower(preg_replace('/\s+/', ' ', $office->name));
            similar_text($normalized, $officeNormalized, $score);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $office;
            }
        }

        if ($bestMatch !== null && $bestScore >= 65) {
            return $bestMatch;
        }

        // 3b. Normalized name match — strip boilerplate words and re-compare
        $boilerplate = ['/\boffice\b/', '/\bof\b/', '/\bthe\b/', '/\band\b/', '/\bfor\b/', '/\ba\b/', '/\ban\b/', '/\bbureau\b/', '/\bdepartment\b/'];
        $normalizedClean = preg_replace($boilerplate, '', $normalized);
        $normalizedClean = preg_replace('/[&]/', ' ', $normalizedClean);
        $normalizedClean = trim(preg_replace('/\s+/', ' ', $normalizedClean));
        $bestMatch = null;
        $bestScore = 0;

        foreach ($offices as $office) {
            $officeClean = mb_strtolower(preg_replace('/\s+/', ' ', $office->name));
            $officeClean = preg_replace($boilerplate, '', $officeClean);
            $officeClean = preg_replace('/[&]/', ' ', $officeClean);
            $officeClean = trim(preg_replace('/\s+/', ' ', $officeClean));

            similar_text($normalizedClean, $officeClean, $score);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $office;
            }
        }

        if ($bestMatch !== null && $bestScore >= 65) {
            return $bestMatch;
        }

        // 4. Fuzzy match acronym
        $normalizedStripped = preg_replace('/[^a-z0-9]/', '', $normalized);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($offices as $office) {
            if ($office->acronym === null || $office->acronym === '') {
                continue;
            }

            $acronymNormalized = mb_strtolower(trim($office->acronym));
            $acronymStripped = preg_replace('/[^a-z0-9]/', '', $acronymNormalized);

            // 4a. Exact normalized match
            if ($normalizedStripped === $acronymStripped) {
                return $office;
            }

            // 4b. Substring match (input contains acronym or acronym contains input)
            if (
                str_contains($normalizedStripped, $acronymStripped)
                || str_contains($acronymStripped, $normalizedStripped)
            ) {
                return $office;
            }

            // 4c. Fuzzy match
            similar_text($normalizedStripped, $acronymStripped, $score);

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $office;
            }
        }

        if ($bestMatch !== null && $bestScore >= 60) {
            return $bestMatch;
        }

        return null;
    }

    private function parsePrNo(string $raw): ?string
    {
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^PR\s*No\.?\s*/i', '', $cleaned);

        if ($cleaned === '') {
            return null;
        }

        if (preg_match('/\d{4}-?\d{4}/', $cleaned, $m)) {
            return $m[0];
        }

        return $cleaned;
    }

    private function parseDate(string $raw): ?string
    {
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^Date\s*:\s*/i', '', $cleaned);

        if ($cleaned === '') {
            return null;
        }

        $normalized = str_replace('/', '-', $cleaned);

        $formats = ['m-d-Y', 'm/d/Y', 'Y-m-d', 'd-m-Y'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $normalized);
                return $parsed->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($normalized)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseRemarks(string $main, string $cont): string
    {
        $main = trim($main);
        $main = preg_replace('/^Purpose\/Remarks\s*:\s*/i', '', $main);

        $cont = trim($cont);

        if ($main === '' && $cont === '') {
            return '';
        }

        return trim($main.' '.$cont);
    }

    private function cell(array $rows, int $row, int $col): string
    {
        if (! isset($rows[$row])) {
            return '';
        }

        $value = $rows[$row][$col] ?? null;

        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return trim((string) $value);
    }

    private function parseNumeric(array $rows, int $row, int $col): ?float
    {
        $value = $rows[$row][$col] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
