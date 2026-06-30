<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Office;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use XMLReader;
use ZipArchive;

class PrExcelImportService
{
    private const SKIP_SHEETS = ['offices', 'Sheet1', 'email', 'Sheet5'];

    private const MAX_EMPTY_ROWS = 100;

    private array $warnings = [];

    private array $created = [];

    private array $pendingMultiPage = [];

    private int $draftImportCount = 0;

    public function __construct(private readonly DatabaseManager $databaseManager) {}

    public function import(UploadedFile $uploadedFile, int $adminId): array
    {
        $this->warnings = [];
        $this->created = [];
        $this->pendingMultiPage = [];
        $this->draftImportCount = 0;

        $path = $uploadedFile->getRealPath();
        $ext = strtolower($uploadedFile->getClientOriginalExtension());

        if ($ext === 'xlsx') {
            $this->importXlsxDirect($path, $adminId);
        } else {
            $this->importXlsWithPhpSpreadsheet($path, $adminId);
        }

        if ($this->draftImportCount > 0) {
            array_unshift($this->warnings, sprintf(
                '%d PR(s) were imported as draft due to incomplete PR number format.',
                $this->draftImportCount,
            ));
        }

        return [
            'created' => $this->created,
            'warnings' => $this->warnings,
        ];
    }

    private function importXlsWithPhpSpreadsheet(string $path, int $adminId): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell($column, $row, $worksheetName = ''): bool
            {
                return Coordinate::columnIndexFromString($column) <= 7;
            }
        });

        $spreadsheet = $reader->load($path);

        for ($i = 0; $i < $spreadsheet->getSheetCount(); $i++) {
            $sheet = $spreadsheet->getSheet($i);
            $name = $sheet->getTitle();

            if (in_array($name, self::SKIP_SHEETS, true)) {
                continue;
            }

            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cellIter = $row->getCellIterator();
                $cellIter->setIterateOnlyExistingCells(true);
                $rowData = array_fill(0, 7, '');
                foreach ($cellIter as $cell) {
                    $colIdx = Coordinate::columnIndexFromString($cell->getColumn()) - 1;
                    if ($colIdx < 7) {
                        $rowData[$colIdx] = $cell->getFormattedValue();
                    }
                }

                $rows[] = $rowData;
            }

            $this->processSheetRows($rows, $name, $adminId);
        }

        $spreadsheet->disconnectWorksheets();
    }

    private function importXlsxDirect(string $path, int $adminId): void
    {
        $zipArchive = new ZipArchive;
        if ($zipArchive->open($path) !== true) {
            throw new \RuntimeException('Cannot open XLSX archive');
        }

        try {
            $sharedStrings = $this->readXlsxSharedStrings($zipArchive);
            $sheetMap = $this->readXlsxSheetMap($zipArchive);

            foreach ($sheetMap as $sheetName => $sheetFile) {
                if (in_array($sheetName, self::SKIP_SHEETS, true)) {
                    continue;
                }

                $rows = $this->readXlsxSheetRows($zipArchive, $sheetFile, $sharedStrings);
                $this->processSheetRows($rows, $sheetName, $adminId);
            }
        } finally {
            $zipArchive->close();
        }
    }

    private function readXlsxSharedStrings(ZipArchive $zipArchive): array
    {
        $content = $zipArchive->getFromName('xl/sharedStrings.xml');
        if ($content === false) {
            return [];
        }

        $strings = [];
        $xmlReader = new XMLReader;
        $xmlReader->xml($content);

        $text = '';
        $inSi = false;
        $inT = false;

        while ($xmlReader->read()) {
            switch ($xmlReader->nodeType) {
                case XMLReader::ELEMENT:
                    if ($xmlReader->name === 'si') {
                        $text = '';
                        $inSi = true;
                    } elseif ($inSi && $xmlReader->name === 't') {
                        $inT = true;
                    }

                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    if ($inT) {
                        $text .= $xmlReader->value;
                    }

                    break;
                case XMLReader::END_ELEMENT:
                    if ($xmlReader->name === 't') {
                        $inT = false;
                    } elseif ($xmlReader->name === 'si') {
                        $strings[] = $text;
                        $inSi = false;
                    }

                    break;
            }
        }

        $xmlReader->close();

        return $strings;
    }

    private function readXlsxSheetMap(ZipArchive $zipArchive): array
    {
        $rels = [];
        $content = $zipArchive->getFromName('xl/_rels/workbook.xml.rels');
        if ($content !== false) {
            $reader = new XMLReader;
            $reader->xml($content);
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Relationship') {
                    $id = $reader->getAttribute('Id');
                    $target = $reader->getAttribute('Target');
                    if ($id !== null && $target !== null) {
                        $rels[$id] = $target;
                    }
                }
            }

            $reader->close();
        }

        $sheets = [];
        $content = $zipArchive->getFromName('xl/workbook.xml');
        if ($content === false) {
            return $sheets;
        }

        $reader = new XMLReader;
        $reader->xml($content);
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'sheet') {
                $name = $reader->getAttribute('name');
                $rId = $reader->getAttribute('r:id');
                if ($name !== null && $rId !== null && isset($rels[$rId])) {
                    $rel = $rels[$rId];
                    if (str_starts_with($rel, './')) {
                        $rel = substr($rel, 2);
                    }

                    $sheets[$name] = 'xl/'.$rel;
                }
            }
        }

        $reader->close();

        return $sheets;
    }

    private function readXlsxSheetRows(ZipArchive $zipArchive, string $sheetFile, array $sharedStrings): array
    {
        $content = $zipArchive->getFromName($sheetFile);
        if ($content === false) {
            return [];
        }

        $sparse = [];
        $xmlReader = new XMLReader;
        $xmlReader->xml($content);

        $inRow = false;
        $inCell = false;
        $inIs = false;
        $inV = false;
        $currentRowIndex = 0;
        $currentCellRef = '';
        $currentCellType = '';
        $currentText = '';

        $setCellValue = function (array &$rowData, string $ref, string $type, string $value, array $sharedStrings): void {
            $col = preg_replace('/\d/', '', $ref);
            $colIdx = Coordinate::columnIndexFromString($col) - 1;
            if ($colIdx >= 7) {
                return;
            }

            if ($type === 's') {
                $rowData[$colIdx] = $sharedStrings[(int) $value] ?? '';
            } elseif ($type === 'b') {
                $rowData[$colIdx] = $value === '1' ? 'TRUE' : 'FALSE';
            } elseif (is_numeric($value)) {
                $rowData[$colIdx] = $value;
            } else {
                $rowData[$colIdx] = $value;
            }
        };

        while ($xmlReader->read()) {
            switch ($xmlReader->nodeType) {
                case XMLReader::ELEMENT:
                    if ($xmlReader->name === 'row') {
                        $currentRowIndex = (int) ($xmlReader->getAttribute('r') ?? 0);
                        $inRow = true;
                    } elseif ($inRow && $xmlReader->name === 'c') {
                        $currentCellRef = $xmlReader->getAttribute('r') ?? '';
                        $currentCellType = $xmlReader->getAttribute('t') ?? '';
                        $currentText = '';
                        $inCell = true;
                    } elseif ($inCell && $xmlReader->name === 'is') {
                        $inIs = true;
                    } elseif ($inCell && ($xmlReader->name === 'v' || $xmlReader->name === 't')) {
                        $inV = true;
                    }

                    break;

                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    if ($inV) {
                        $currentText .= $xmlReader->value;
                    }

                    break;

                case XMLReader::END_ELEMENT:
                    if ($xmlReader->name === 'v' || $xmlReader->name === 't') {
                        $inV = false;
                    } elseif ($xmlReader->name === 'is') {
                        $inIs = false;
                    } elseif ($xmlReader->name === 'c') {
                        if (! isset($sparse[$currentRowIndex])) {
                            $sparse[$currentRowIndex] = array_fill(0, 7, '');
                        }

                        $setCellValue($sparse[$currentRowIndex], $currentCellRef, $currentCellType, $currentText, $sharedStrings);
                        $inCell = false;
                    } elseif ($xmlReader->name === 'row') {
                        $inRow = false;
                    }

                    break;
            }
        }

        $xmlReader->close();

        if ($sparse === []) {
            return [];
        }

        $minRow = min(array_keys($sparse));
        $maxRow = max(array_keys($sparse));
        $dense = [];

        for ($r = $minRow; $r <= $maxRow; $r++) {
            $dense[] = $sparse[$r] ?? array_fill(0, 7, '');
        }

        return $dense;
    }

    private function processSheetRows(array $rows, string $sheetName, int $adminId): void
    {
        $numRows = count($rows);
        $consecutiveEmpty = 0;

        for ($i = 0; $i < $numRows; $i++) {
            $colA = $rows[$i][0] ?? '';

            if ($colA === 'PURCHASE REQUEST' || str_starts_with($colA, 'PROVINCIAL GOVERNMENT OF')) {
                $consecutiveEmpty = 0;

                try {
                    $this->parseBlock($rows, $i, $sheetName, $adminId);
                } catch (\Throwable $e) {
                    $this->warnings[] = sprintf('Sheet "%s", row %d: %s', $sheetName, $i, $e->getMessage());
                }
            } else {
                $rowEmpty = true;
                for ($c = 0; $c < 7; $c++) {
                    if (($rows[$i][$c] ?? '') !== '') {
                        $rowEmpty = false;
                        break;
                    }
                }

                if ($rowEmpty) {
                    $consecutiveEmpty++;
                    if ($consecutiveEmpty >= self::MAX_EMPTY_ROWS) {
                        break;
                    }
                } else {
                    $consecutiveEmpty = 0;
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

        // Fallback: if not found in column C, try column A and strip "Department" prefix
        if ($department === '') {
            $department = preg_replace('/^Department\s+/i', '', $this->cell($rows, $start + 4, 0));
        }

        $prNoRaw = $this->cell($rows, $start + 4, 4);
        $prDateRaw = $this->cell($rows, $start + 4, 6);

        $prNo = $this->parsePrNo($prNoRaw);
        $prDate = $this->parseDate($prDateRaw);

        // Line items — rows +9 to +38
        $parsedItems = $this->parseItems($rows, $start + 9, $start + 38);
        $items = $parsedItems['items'];

        // Purpose / Remarks — find "Purpose/Remarks" label dynamically, fallback to fixed +40
        $purposeRow = $this->findLabelRow($rows, $start + 9, $start + 55, 0, '/purpose\s*\//i');
        if ($purposeRow === null) {
            $purposeRow = $start + 40;
        }

        $remarksMain = $this->cell($rows, $purposeRow, 0);
        $remarksCont = $this->cell($rows, $purposeRow + 1, 2);

        $remarks = $this->parseRemarks($remarksMain, $remarksCont);

        // Names — 4 rows after Purpose/Remarks, fallback to fixed +44
        $namesRow = $purposeRow !== $start + 40 ? $purposeRow + 4 : $start + 44;
        $requesterName = $this->cell($rows, $namesRow, 3);
        $approverName = $this->cell($rows, $namesRow, 4);

        // Designations — 1 row after names, fallback to fixed +45
        $designationsRow = $purposeRow !== $start + 40 ? $purposeRow + 5 : $start + 45;
        $requesterDesignation = $this->cell($rows, $designationsRow, 3);
        $approverDesignation = $this->cell($rows, $designationsRow, 4);

        // Grand Total — find dynamically instead of hardcoded offset
        $grandTotal = $this->findGrandTotal($rows, $start + 9, $designationsRow + 5);

        $grandTotal = preg_replace('/[^0-9.\-]/', '', $grandTotal);

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
        $lastUsedRow = $from - 1;

        for ($offset = $from; $offset <= $to && $offset < $itemCount; $offset++) {
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
                $nextOffset++;
            }

            $items[] = $item;
            $lastUsedRow = $offset;
        }

        return ['items' => $items, 'lastRow' => $lastUsedRow];
    }

    private function savePr(array $record, string $sheetName, int $start): void
    {
        $prNo = $record['pr_no'] ?? null;

        $isProperFormat = $prNo !== null && preg_match('/^\d{4}-?\d{4}$/', $prNo);
        $isDraftImport = ! $isProperFormat && $prNo !== null && strlen($prNo) >= 4 && ! preg_match('/^0+$/', $prNo);

        // Skip PRs with empty or meaningless PR numbers
        if ($prNo === null || (! $isProperFormat && ! $isDraftImport)) {
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

        if (! $office instanceof \App\Models\Office && $department !== '') {
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

        // Skip if PR number already exists
        if (PurchaseRequest::where('pr_no', $prNo)->exists()) {
            $this->warnings[] = sprintf('Sheet "%s", row %d: Skipping PR "%s" — PR number already exists.', $sheetName, $start, $prNo);

            return;
        }

        if ($isDraftImport) {
            $this->draftImportCount++;
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
                'status' => $isProperFormat ? 'approved' : 'draft',
                'remarks' => null,
                'is_imported' => true,
            ]);

            $totalFromItems = 0;

            foreach ($record['items'] as $item) {
                $quantity = (int) ($item['quantity'] ?? 0);
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $totalCost = (float) ($item['total_cost'] ?? 0);

                if ($unitCost > 0 && $quantity > 0 && $totalCost === 0.0) {
                    $totalCost = $quantity * $unitCost;
                }

                if ($totalCost > 0 && $quantity > 0 && $unitCost === 0.0) {
                    $unitCost = $totalCost / $quantity;
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

                $totalFromItems += round($totalCost, 2);
            }

            $pr->update(['total_amount' => round($totalFromItems, 2)]);

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

        // Normalize spacing around dash
        $cleaned = preg_replace('/\s*-\s*/', '-', $cleaned);
        $cleaned = preg_replace('/\s+/', '', $cleaned);

        // Remove any remaining non-digit, non-dash characters
        // (handles Unicode whitespace, non-breaking spaces, etc.)
        $cleaned = preg_replace('/[^\d-]/', '', $cleaned);

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

        // Excel serial date number
        if (is_numeric($cleaned)) {
            $num = (float) $cleaned;
            if ($num >= 40000 && $num < 65000) {
                try {
                    return Carbon::createFromFormat('Y-m-d', '1899-12-30')
                        ->addDays((int) $num)
                        ->toDateString();
                } catch (\Throwable) {
                    return null;
                }
            }
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

    private function findGrandTotal(array $rows, int $from, int $to): string
    {
        $itemCount = count($rows);

        for ($offset = $from; $offset <= $to && $offset < $itemCount; $offset++) {
            $colA = $this->cell($rows, $offset, 0);

            if (preg_match('/grand\s*total/i', $colA)) {
                $value = $this->grandTotalValue($rows, $offset);

                if ($value !== null) {
                    return $value;
                }
            }
        }

        // Fallback: scan any cell in the range for a grand-total-like value
        for ($offset = $from; $offset <= $to && $offset < $itemCount; $offset++) {
            for ($col = 0; $col <= 6; $col++) {
                $cell = $this->cell($rows, $offset, $col);

                if (preg_match('/grand\s*total/i', $cell) || preg_match('/^total\s+amount/i', $cell)) {
                    $value = $this->grandTotalValue($rows, $offset);

                    if ($value !== null) {
                        return $value;
                    }

                    break;
                }
            }
        }

        return '0';
    }

    private function grandTotalValue(array $rows, int $offset): ?string
    {
        $candidates = [5, 6, 1, 2, 3, 4, 0];

        foreach ($candidates as $candidate) {
            $raw = $this->cell($rows, $offset, $candidate);

            if ($raw === '') {
                continue;
            }

            $cleaned = preg_replace('/[^0-9.\-]/', '', $raw);

            if ($cleaned !== '' && is_numeric($cleaned) && (float) $cleaned !== 0.0) {
                return $cleaned;
            }
        }

        return null;
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

    private function findLabelRow(array $rows, int $from, int $to, int $col, string $pattern): ?int
    {
        $itemCount = count($rows);

        for ($offset = $from; $offset <= $to && $offset < $itemCount; $offset++) {
            $value = $this->cell($rows, $offset, $col);

            if (preg_match($pattern, $value)) {
                return $offset;
            }
        }

        return null;
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

        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9.\-]/', '', $value);

            if ($cleaned !== '' && is_numeric($cleaned)) {
                return (float) $cleaned;
            }
        }

        return null;
    }
}
