<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\WorkProgram;
use App\Models\WorkProgramItem;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WorkProgramImport
{
    public function import(WorkProgram $workProgram, ?int $_emanatingId = null): Collection
    {
        if (! $workProgram->file_url) {
            return collect();
        }

        $absolutePath = Storage::disk('public')->path($workProgram->file_url);

        if (! is_file($absolutePath)) {
            return $workProgram->items()->orderBy('row_order')->get();
        }

        $parsedRows = $this->parseWorkProgramDocxRows($absolutePath);

        if ($parsedRows === []) {
            return $workProgram->items()->orderBy('row_order')->get();
        }

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

        return $workProgram->items()->orderBy('row_order')->get();
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWorkProgramDocxRows(string $absolutePath): array
    {
        if (class_exists('PhpOffice\\PhpWord\\IOFactory')) {
            $parsedUsingPhpWord = $this->parseWorkProgramWithPhpWord($absolutePath);

            if ($parsedUsingPhpWord !== []) {
                return $parsedUsingPhpWord;
            }
        }

        return $this->parseWorkProgramWithDocumentXml($absolutePath);
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

                $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('phpword-table-%d', $tableIndex));

                if ($parsedRows !== []) {
                    $collectedRows = $parsedRows;
                    break 2;
                }
            }
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
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            return [];
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xmlContent) || $xmlContent === '') {
            return [];
        }

        $dom = new DOMDocument;
        $loaded = @$dom->loadXML($xmlContent);

        if (! $loaded) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = $xpath->query('//w:tbl');

        if (! $tables) {
            return [];
        }

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

            $parsedRows = $this->extractRowsFromThreeColumnTable($rows, sprintf('document-xml-table-%d', $tableIndex + 1));

            if ($parsedRows !== []) {
                return $parsedRows;
            }
        }

        return [];
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function extractRowsFromThreeColumnTable(array $rows, string $source): array
    {
        $headerIndex = null;
        $headerOffset = 0;
        $minimumDataStartIndex = 4;

        foreach ($rows as $index => $row) {
            $upperRow = array_map(
                fn ($cell): string => strtoupper(trim((string) $cell)),
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
            return [];
        }

        $nameColumnIndex = $headerOffset;
        $quantityUnitColumnIndex = $headerOffset + 1;
        $amountColumnIndex = $headerOffset + 2;
        $startIndex = max($headerIndex + 1, $minimumDataStartIndex);

        $expectedCells = $amountColumnIndex + 1;

        foreach ($rows as &$row) {
            if (count($row) < $expectedCells) {
                $diff = $expectedCells - count($row);
                $row = array_merge(array_fill(0, $diff, ''), $row);
            }
        }

        unset($row);

        $items = [];

        for ($i = $startIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $itemName = trim((string) ($row[$nameColumnIndex] ?? ''));
            $quantityUnitRaw = trim((string) ($row[$quantityUnitColumnIndex] ?? ''));
            $amountText = trim((string) ($row[$amountColumnIndex] ?? ''));

            if (str_contains(strtoupper($quantityUnitRaw), 'GRAND TOTAL')) {
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
