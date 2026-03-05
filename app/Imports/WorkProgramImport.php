<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\WorkProgram;
use App\Models\WorkProgramItem;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WorkProgramImport
{
    public function import(WorkProgram $workProgram, ?int $emanatingId = null): Collection
    {
        if (! $workProgram->file_url) {
            Log::info('WorkProgram parse skipped: missing file URL.', [
                'emanating_id' => $emanatingId,
                'work_program_id' => $workProgram->id,
                'file_url' => $workProgram->file_url,
            ]);

            return collect();
        }

        $absolutePath = Storage::disk('public')->path($workProgram->file_url);

        if (! is_file($absolutePath)) {
            $existingItems = $workProgram->items()->count();

            Log::warning('WorkProgram file not found on disk, using existing stored items.', [
                'emanating_id' => $emanatingId,
                'work_program_id' => $workProgram->id,
                'file_url' => $workProgram->file_url,
                'resolved_path' => $absolutePath,
                'existing_items_count' => $existingItems,
            ]);

            return $workProgram->items()->orderBy('row_order')->get();
        }

        Log::info('WorkProgram parse started.', [
            'emanating_id' => $emanatingId,
            'work_program_id' => $workProgram->id,
            'file_url' => $workProgram->file_url,
            'resolved_path' => $absolutePath,
        ]);

        $parsedRows = $this->parseWorkProgramDocxRows($absolutePath);

        if ($parsedRows === []) {
            $existingItems = $workProgram->items()->count();

            Log::warning('WorkProgram parsing produced zero rows, using existing stored items.', [
                'emanating_id' => $emanatingId,
                'work_program_id' => $workProgram->id,
                'resolved_path' => $absolutePath,
                'existing_items_count' => $existingItems,
            ]);

            return $workProgram->items()->orderBy('row_order')->get();
        }

        Log::info('WorkProgram parsed rows ready for persistence.', [
            'emanating_id' => $emanatingId,
            'work_program_id' => $workProgram->id,
            'parsed_rows_count' => count($parsedRows),
            'sample_rows' => array_slice($parsedRows, 0, 3),
        ]);

        $workProgram->items()->delete();

        foreach ($parsedRows as $index => $row) {
            WorkProgramItem::create([
                'work_program_id' => $workProgram->id,
                'item_name' => $row['item_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'] ?: null,
                'amount' => $row['amount'],
                'row_order' => $index + 1,
            ]);
        }

        Log::info('WorkProgram items persisted from parsed DOCX rows.', [
            'emanating_id' => $emanatingId,
            'work_program_id' => $workProgram->id,
            'inserted_items_count' => count($parsedRows),
        ]);

        return $workProgram->items()->orderBy('row_order')->get();
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramDocxRows(string $absolutePath): array
    {
        Log::info('WorkProgram parser entry.', [
            'resolved_path' => $absolutePath,
            'phpword_available' => class_exists('PhpOffice\\PhpWord\\IOFactory'),
        ]);

        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            $parsedUsingPhpWord = $this->parseWorkProgramWithPhpWord($absolutePath);

            if ($parsedUsingPhpWord !== []) {
                Log::info('WorkProgram parser succeeded via PhpWord.', [
                    'resolved_path' => $absolutePath,
                    'rows_count' => count($parsedUsingPhpWord),
                ]);

                return $parsedUsingPhpWord;
            }

            Log::warning('WorkProgram PhpWord parse returned zero rows; falling back to document.xml parser.', [
                'resolved_path' => $absolutePath,
            ]);
        }

        $xmlRows = $this->parseWorkProgramWithDocumentXml($absolutePath);

        Log::info('WorkProgram document.xml parser finished.', [
            'resolved_path' => $absolutePath,
            'rows_count' => count($xmlRows),
        ]);

        return $xmlRows;
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramWithPhpWord(string $absolutePath): array
    {
        $ioFactoryClass = 'PhpOffice\\PhpWord\\IOFactory';

        try {
            $phpWord = $ioFactoryClass::load($absolutePath);
        } catch (\Throwable $throwable) {
            Log::warning('WorkProgram PhpWord load failed.', [
                'resolved_path' => $absolutePath,
                'error' => $throwable->getMessage(),
            ]);

            return [];
        }

        $collectedRows = [];
        $tableIndex = 0;

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (! is_object($element) || ! method_exists($element, 'getRows') || ! is_a($element, 'PhpOffice\\PhpWord\\Element\\Table')) {
                    continue;
                }

                $tableIndex++;

                $rows = [];

                foreach ($element->getRows() as $row) {
                    if (! method_exists($row, 'getCells')) {
                        continue;
                    }

                    $cells = [];

                    foreach ($row->getCells() as $cell) {
                        $cells[] = trim($this->extractPhpWordCellText($cell));
                    }

                    if ($cells !== []) {
                        $rows[] = $cells;
                    }
                }

                Log::info('WorkProgram PhpWord table collected.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex,
                    'row_count' => count($rows),
                    'sample_rows' => array_slice($rows, 0, 3),
                ]);

                $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('phpword-table-%d', $tableIndex));

                if ($parsedRows !== []) {
                    Log::info('WorkProgram PhpWord table parsed successfully.', [
                        'resolved_path' => $absolutePath,
                        'table_index' => $tableIndex,
                        'parsed_rows_count' => count($parsedRows),
                    ]);

                    $collectedRows = $parsedRows;
                    break 2;
                }

                Log::warning('WorkProgram PhpWord table did not match expected 3-column structure.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex,
                ]);
            }
        }

        if ($collectedRows === []) {
            Log::warning('WorkProgram PhpWord parser found no matching table.', [
                'resolved_path' => $absolutePath,
                'tables_checked' => $tableIndex,
            ]);
        }

        return $collectedRows;
    }

    private function extractPhpWordCellText(object $cell): string
    {
        if (! method_exists($cell, 'getElements')) {
            return '';
        }

        $parts = [];

        foreach ($cell->getElements() as $element) {
            if (is_object($element) && method_exists($element, 'getText')) {
                $parts[] = (string) $element->getText();
                continue;
            }

            if (is_object($element) && method_exists($element, 'getElements')) {
                $inner = [];

                foreach ($element->getElements() as $innerElement) {
                    if (is_object($innerElement) && method_exists($innerElement, 'getText')) {
                        $inner[] = (string) $innerElement->getText();
                    }
                }

                $parts[] = implode(' ', $inner);
            }
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramWithDocumentXml(string $absolutePath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($absolutePath) !== true) {
            Log::warning('WorkProgram document.xml parser could not open DOCX zip.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xmlContent) || $xmlContent === '') {
            Log::warning('WorkProgram document.xml missing or empty in DOCX.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($xmlContent);

        if (! $loaded) {
            Log::warning('WorkProgram document.xml failed to load into DOMDocument.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xpath->query('//w:tbl');

        if (! $tables) {
            Log::warning('WorkProgram document.xml has no table nodes.', [
                'resolved_path' => $absolutePath,
            ]);

            return [];
        }

        Log::info('WorkProgram document.xml tables detected.', [
            'resolved_path' => $absolutePath,
            'table_count' => $tables->length,
        ]);

        foreach ($tables as $tableIndex => $table) {
            $rows = [];
            $rowNodes = $xpath->query('.//w:tr', $table);

            if (! $rowNodes) {
                continue;
            }

            foreach ($rowNodes as $rowNode) {
                $cells = [];
                $cellNodes = $xpath->query('./w:tc', $rowNode);

                if (! $cellNodes) {
                    continue;
                }

                foreach ($cellNodes as $cellNode) {
                    $textNodes = $xpath->query('.//w:t', $cellNode);
                    $parts = [];

                    if ($textNodes) {
                        foreach ($textNodes as $textNode) {
                            $parts[] = (string) $textNode->nodeValue;
                        }
                    }

                    $cells[] = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
                }

                if ($cells !== []) {
                    $rows[] = $cells;
                }
            }

            Log::info('WorkProgram document.xml table collected.', [
                'resolved_path' => $absolutePath,
                'table_index' => $tableIndex + 1,
                'row_count' => count($rows),
                'sample_rows' => array_slice($rows, 0, 3),
            ]);

            $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('document-xml-table-%d', $tableIndex + 1));

            if ($parsedRows !== []) {
                Log::info('WorkProgram document.xml table parsed successfully.', [
                    'resolved_path' => $absolutePath,
                    'table_index' => $tableIndex + 1,
                    'parsed_rows_count' => count($parsedRows),
                ]);

                return $parsedRows;
            }

            Log::warning('WorkProgram document.xml table did not match expected 3-column structure.', [
                'resolved_path' => $absolutePath,
                'table_index' => $tableIndex + 1,
            ]);
        }

        Log::warning('WorkProgram document.xml parser found no matching table.', [
            'resolved_path' => $absolutePath,
            'tables_checked' => $tables->length,
        ]);

        return [];
    }

    /**
     * @param array<int, array<int, string>> $rows
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function extractRowsFromThreeColumnTable(array $rows, string $source): array
    {
        $headerIndex = null;
        $headerOffset = 0;
        $minimumDataStartIndex = 4;

        foreach ($rows as $index => $row) {
            $upperRow = array_map(
                fn($cell): string => strtoupper(trim((string) $cell)),
                $row,
            );

            for ($offset = 0; $offset <= max(0, count($upperRow) - 3); $offset++) {
                $col1 = (string) ($upperRow[$offset] ?? '');
                $col2 = (string) ($upperRow[$offset + 1] ?? '');
                $col3 = (string) ($upperRow[$offset + 2] ?? '');

                if (
                    str_contains($col1, 'ACCOUNT NAME')
                    && str_contains($col2, 'ACCOUNT CODE')
                    && str_contains($col3, 'AMOUNT')
                ) {
                    $headerIndex = $index;
                    $headerOffset = $offset;
                    break 2;
                }
            }
        }

        if ($headerIndex === null) {
            Log::warning('WorkProgram header not found in table rows.', [
                'source' => $source,
                'rows_count' => count($rows),
                'sample_rows' => array_slice($rows, 0, 5),
            ]);

            return [];
        }

        $nameColumnIndex = $headerOffset;
        $quantityUnitColumnIndex = $headerOffset + 1;
        $amountColumnIndex = $headerOffset + 2;
        $startIndex = max($headerIndex + 1, $minimumDataStartIndex);

        Log::info('WorkProgram header located.', [
            'source' => $source,
            'header_index' => $headerIndex,
            'header_offset' => $headerOffset,
            'rows_count' => count($rows),
            'start_index' => $startIndex,
        ]);

        $items = [];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $itemName = trim((string) ($row[$nameColumnIndex] ?? ''));
            $quantityUnitRaw = trim((string) ($row[$quantityUnitColumnIndex] ?? ''));
            $amountText = trim((string) ($row[$amountColumnIndex] ?? ''));

            if (str_contains(strtoupper($quantityUnitRaw), 'GRAND TOTAL')) {
                Log::info('WorkProgram GRAND TOTAL marker reached; stopping row extraction.', [
                    'source' => $source,
                    'row_index' => $i,
                    'second_column_value' => $quantityUnitRaw,
                ]);

                break;
            }

            if ($itemName === '' && $quantityUnitRaw === '' && $amountText === '') {
                continue;
            }

            if ($itemName === '') {
                continue;
            }

            ['quantity' => $quantity, 'unit' => $unit] = $this->splitQuantityAndUnit($quantityUnitRaw);

            $items[] = [
                'item_name' => $itemName,
                'quantity' => $quantity,
                'unit' => $unit,
                'amount' => $this->parseCurrencyToFloat($amountText),
            ];
        }

        Log::info('WorkProgram table extraction completed.', [
            'source' => $source,
            'extracted_items_count' => count($items),
            'sample_items' => array_slice($items, 0, 5),
        ]);

        return $items;
    }

    private function parseCurrencyToFloat(string $value): ?float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', $value);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    /**
     * @return array{quantity:float|null, unit:string}
     */
    private function splitQuantityAndUnit(string $rawValue): array
    {
        $cleaned = trim(preg_replace('/\s+/u', ' ', $rawValue) ?? '');

        if ($cleaned === '') {
            return [
                'quantity' => null,
                'unit' => '',
            ];
        }

        $normalized = preg_replace('/\s*[-–—]\s*/u', ' ', $cleaned) ?? $cleaned;
        $normalized = preg_replace('/(?<=\d)\s+(?=\d)/u', '', $normalized) ?? $normalized;

        if (preg_match('/^([0-9][0-9,\s]*(?:\.[0-9]+)?)\s*(.*)$/u', $normalized, $matches) === 1) {
            $quantityRaw = isset($matches[1])
                ? (preg_replace('/[,\s]/u', '', (string) $matches[1]) ?? '')
                : '';
            $quantity = is_numeric($quantityRaw) ? (float) $quantityRaw : null;
            $unit = isset($matches[2]) ? trim((string) $matches[2]) : '';

            return [
                'quantity' => $quantity,
                'unit' => $unit,
            ];
        }

        return [
            'quantity' => null,
            'unit' => $cleaned,
        ];
    }
}
