<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\ProjectBrief;
use App\Models\ProjectBriefItem;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProjectBriefImport
{
    public function import(ProjectBrief $projectBrief, ?int $_emanatingId = null): Collection
    {
        if (! $projectBrief->file_url) {
            return collect();
        }

        $absolutePath = Storage::disk('public')->path($projectBrief->file_url);

        if (! is_file($absolutePath)) {
            return $projectBrief->items()->orderBy('row_order')->get();
        }

        $parsedRows = $this->parseProjectBriefDocxRows($absolutePath);

        if ($parsedRows === []) {
            return $projectBrief->items()->orderBy('row_order')->get();
        }

        $projectBrief->items()->delete();

        foreach ($parsedRows as $index => $row) {
            ProjectBriefItem::create([
                'project_brief_id' => $projectBrief->id,
                'item_name' => $row['item_name'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'] ?: null,
                'amount' => $row['amount'],
                'row_order' => $index + 1,
            ]);
        }

        return $projectBrief->items()->orderBy('row_order')->get();
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseProjectBriefDocxRows(string $absolutePath): array
    {
        return $this->parseWithDocumentXml($absolutePath);
    }

    /**
     * @return array<int, array{item_name:string, quantity:float|null, unit:string, amount:float|null}>
     */
    private function parseWithDocumentXml(string $absolutePath): array
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

        foreach ($tables as $table) {
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

            $parsedRows = $this->extractRowsFromFourColumnTable($rows);

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
    private function extractRowsFromFourColumnTable(array $rows): array
    {
        $headerIndex = null;
        $headerOffset = 0;

        foreach ($rows as $index => $row) {
            $upperRow = array_map(
                fn ($cell): string => strtoupper(trim((string) $cell)),
                $row,
            );

            for ($offset = 0; $offset <= max(0, count($upperRow) - 4); $offset++) {
                $colA = (string) ($upperRow[$offset] ?? '');
                $colB = (string) ($upperRow[$offset + 1] ?? '');
                $colC = (string) ($upperRow[$offset + 2] ?? '');
                $colD = (string) ($upperRow[$offset + 3] ?? '');

                if (
                    str_contains($colA, 'ACCOUNT NAME')
                    && str_contains($colB, 'DESCRIPTION')
                    && str_contains($colC, 'QUANTITY')
                    && str_contains($colD, 'AMOUNT')
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

        $descriptionColumnIndex = $headerOffset + 1;
        $quantityColumnIndex = $headerOffset + 2;
        $amountColumnIndex = $headerOffset + 3;

        $items = [];

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $itemName = trim((string) ($row[$descriptionColumnIndex] ?? ''));
            $quantityUnitRaw = trim((string) ($row[$quantityColumnIndex] ?? ''));
            $amountText = trim((string) ($row[$amountColumnIndex] ?? ''));

            $combinedRow = strtoupper(trim(implode(' ', $row)));
            if (str_contains($combinedRow, 'GRAND TOTAL')) {
                continue;
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

        if (! is_string($normalized) || $normalized === '' || ! is_numeric($normalized)) {
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
