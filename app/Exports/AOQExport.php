<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AOQ;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AOQExport implements FromArray, WithDrawings, WithEvents, WithStyles
{
    private const BAC_MEMBERS = [
        'NOEL R. ROCAFORT',
        'PEDRITO MARTIN M. DIJAN, JR.',
        'ENGR. NERIO L. RONQUILLO, JR.',
        'ATTY. LOUIE MARK M. DALAWAMPU',
    ];

    private const BAC_CHAIRPERSON = 'ATTY. JOEL L. MONTEALTO';

    private int $totalCols;

    /**
     * @param  array<string, mixed>  $calculation
     */
    public function __construct(
        private readonly AOQ $aoq,
        private readonly array $calculation,
    ) {
        $supplierCount = min(count($this->calculation['supplier_totals'] ?? []), 3);
        $this->totalCols = 4 + ($supplierCount * 2);
    }

    public function array(): array
    {
        $rfq = $this->aoq->rfq;
        $supplierTotals = collect($this->calculation['supplier_totals'] ?? [])->take(3)->values();
        $rfqSuppliers = $rfq?->suppliers ?? collect();
        $totalCols = $this->totalCols;

        $rows = [];

        // --- Header block (rows 1-6) ---
        $rows[] = $this->padFirst('REPUBLIC OF THE PHILIPPINES', $totalCols);
        $rows[] = $this->padFirst('PROVINCIAL GOVERNMENT OF BATANGAS', $totalCols);
        $rows[] = $this->padFirst('Capitol Site, Kumintang Ibaba, Batangas City 4200', $totalCols);
        $rows[] = $this->padFirst('Bids and Awards Committee', $totalCols);
        $rows[] = $this->padFirst('ABSTRACT OF QUOTATION', $totalCols);
        $rows[] = $this->padFirst('Small Value Procurement', $totalCols);

        // Row 7: blank
        $rows[] = $this->emptyRow($totalCols);

        // Row 8: Project Name (wrapped in registerEvents)
        $rows[] = $this->padFirst('Project Name: '.($rfq?->project_name ?? ''), $totalCols);

        // Row 9: Date
        $rows[] = $this->padFirst('Date: '.Carbon::parse($this->aoq->aoq_date)->format('m/d/y'), $totalCols);

        // Row 10: blank
        $rows[] = $this->emptyRow($totalCols);

        // Row 11: Table Header Row 1
        $headerRow1 = ['QTY', 'UNIT', 'PARTICULARS', 'APPROVED BUDGET FOR THE CONTRACT'];
        foreach ($supplierTotals as $supplier) {
            $headerRow1[] = $supplier['supplier_name'] ?? '';
            $headerRow1[] = '';
        }

        $rows[] = $this->pad($headerRow1, $totalCols);

        // Row 12: Table Header Row 2
        $headerRow2 = ['', '', '', ''];
        foreach ($supplierTotals as $supplier) {
            $headerRow2[] = 'UNIT COST';
            $headerRow2[] = 'TOTAL AMOUNT';
        }

        $rows[] = $this->pad($headerRow2, $totalCols);

        // --- Data rows ---
        $abcGrandTotal = 0.0;

        foreach ($rfq?->items ?? collect() as $rfqItem) {
            $prItem = $rfqItem->purchaseRequestItem;
            $quantity = (float) ($rfqItem->quantity ?? 0);
            $unitCost = (float) ($prItem?->unit_cost ?? 0);
            $abcLineTotal = $quantity * $unitCost;
            $abcGrandTotal += $abcLineTotal;

            $row = [
                (int) $quantity,
                $rfqItem->unit ?? '',
                $rfqItem->item_name ?? '',
                $abcLineTotal,
            ];

            foreach ($supplierTotals as $supplierTotal) {
                $entry = $rfqSuppliers->firstWhere('supplier_id', $supplierTotal['supplier_id']);
                $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $rfqItem->id);
                $unitPrice = $supplierItem?->unit_price;
                $lineTotal = $unitPrice !== null ? ((float) $unitPrice * $quantity) : null;

                $row[] = $unitPrice !== null ? (float) $unitPrice : '';
                $row[] = $lineTotal !== null ? $lineTotal : '';
            }

            $rows[] = $this->pad($row, $totalCols);
        }

        // Blank template rows (pad to at least 13 total table rows)
        $dataRowCount = $rfq?->items?->count() ?? 0;
        $blankRows = max(0, 13 - $dataRowCount);
        for ($i = 0; $i < $blankRows; ++$i) {
            $rows[] = $this->emptyRow($totalCols);
        }

        // Grand Total row
        $grandTotalRow = $this->emptyRow($totalCols);
        $grandTotalRow[0] = 'GRAND TOTAL - P';
        $grandTotalRow[3] = $abcGrandTotal;
        $col = 4;
        foreach ($supplierTotals as $supplierTotal) {
            $grandTotalRow[$col] = (float) ($supplierTotal['total_amount'] ?? 0);
            $grandTotalRow[$col + 1] = '';
            $col += 2;
        }

        $rows[] = $grandTotalRow;

        // Blank
        $rows[] = $this->emptyRow($totalCols);

        // Recommendation paragraph (placeholder — replaced with RichText in registerEvents)
        $winnerName = $this->aoq->winnerSupplier?->name ?? 'N/A';
        $modeLabel = ((int) ($this->calculation['calculated_supplier_count'] ?? 0)) >= 2
            ? 'Lowest Calculated'
            : 'Single Calculated';
        $rows[] = $this->padFirst(
            sprintf(
                'After our careful scrutiny and deliberation of the submitted quotation of the supplier as reflected in this Abstract of Quotation, we strongly recommend the quotation to be given to %s for being the supplier with the %s and Responsive Quotation which is advantageous to the Provincial Government of Batangas.',
                $winnerName,
                $modeLabel,
            ),
            $totalCols,
        );

        // Blank
        $rows[] = $this->emptyRow($totalCols);

        // APPROVED:
        $rows[] = $this->padFirst('APPROVED:', $totalCols);

        // Blank
        $rows[] = $this->emptyRow($totalCols);

        // BAC Member names — spread across columns
        $memberZones = $this->signatureZones($totalCols, count(self::BAC_MEMBERS));
        $bacNamesRow = $this->emptyRow($totalCols);
        foreach (self::BAC_MEMBERS as $i => $name) {
            $bacNamesRow[$memberZones[$i]['start'] - 1] = $name;
        }

        $rows[] = $bacNamesRow;

        // BAC Member titles
        $bacTitlesRow = $this->emptyRow($totalCols);
        foreach (array_keys(self::BAC_MEMBERS) as $i) {
            $bacTitlesRow[$memberZones[$i]['start'] - 1] = 'BAC Member';
            // suppress unused variable warning
        }

        $rows[] = $bacTitlesRow;

        // Blank
        $rows[] = $this->emptyRow($totalCols);

        // BAC Chairperson name (centered via merge in registerEvents)
        $chairRow = $this->padFirst(self::BAC_CHAIRPERSON, $totalCols);
        $rows[] = $chairRow;

        // BAC Chairperson title
        $chairTitleRow = $this->padFirst('BAC Chairperson', $totalCols);
        $rows[] = $chairTitleRow;

        return $rows;
    }

    // ─── Drawing (logos) ──────────────────────────────────────────────

    /** @return array<int, Drawing> */
    public function drawings(): array
    {
        $lastCol = Coordinate::stringFromColumnIndex($this->totalCols);

        $seal = new Drawing;
        $seal->setName('Provincial Seal');
        $seal->setPath(public_path('images/batangas-seal.png'));
        $seal->setCoordinates('A1');
        $seal->setOffsetX(5);
        $seal->setOffsetY(5);
        $seal->setResizeProportional(true);
        $seal->setWidth(80);

        $bagong = new Drawing;
        $bagong->setName('Bagong Pilipinas');
        $bagong->setPath(public_path('images/bagong-pilipinas.png'));
        $bagong->setCoordinates($lastCol.'1');
        $bagong->setOffsetX(5);
        $bagong->setOffsetY(5);
        $bagong->setResizeProportional(true);
        $bagong->setWidth(80);

        return [$seal, $bagong];
    }

    // ─── Styles (basic row-level — complex formatting in registerEvents) ─

    /** @return array<int|array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            4 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    // ─── AfterSheet event (all formatting) ─────────────────────────────

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $worksheet = $afterSheet->sheet->getDelegate();
                $totalCols = $this->totalCols;
                $lastCol = Coordinate::stringFromColumnIndex($totalCols);
                $highestRow = $worksheet->getHighestRow();

                $supplierTotals = collect($this->calculation['supplier_totals'] ?? [])->take(3)->values();
                $supplierCount = $supplierTotals->count();

                // ── Page setup (redundant safety) ──────────────────────
                $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                $worksheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
                $worksheet->getPageMargins()->setTop(0.5);
                $worksheet->getPageMargins()->setRight(0.5);
                $worksheet->getPageMargins()->setBottom(0.5);
                $worksheet->getPageMargins()->setLeft(0.5);

                // ── Column widths (dynamic based on supplier count) ───
                // Available width ~98 chars (landscape letter, 0.5" margins).
                // Column widths — scale supplier cols wider when fewer suppliers
                $supplierColWidth = match (true) {
                    $supplierCount <= 1 => 26,
                    $supplierCount <= 2 => 17,
                    default => 11,
                };
                $particularsWidth = match (true) {
                    $supplierCount <= 1 => 42,
                    $supplierCount <= 2 => 38,
                    default => 29,
                };
                $abcWidth = match (true) {
                    $supplierCount <= 1 => 16,
                    $supplierCount <= 2 => 14,
                    default => 13,
                };
                $worksheet->getColumnDimension('A')->setWidth(6);
                $worksheet->getColumnDimension('B')->setWidth(8);
                $worksheet->getColumnDimension('C')->setWidth($particularsWidth);
                $worksheet->getColumnDimension('D')->setWidth($abcWidth);
                for ($c = 5; $c <= $totalCols; ++$c) {
                    $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth($supplierColWidth);
                }

                // ── Header block merges (rows 1-6) ────────────────────
                for ($row = 1; $row <= 6; ++$row) {
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $row, $lastCol, $row));
                }

                // ── Project info merges (rows 8-9) ─────────────────────
                $worksheet->mergeCells(sprintf('A8:%s8', $lastCol));
                $worksheet->mergeCells(sprintf('A9:%s9', $lastCol));
                $worksheet->getStyle('A8')->getAlignment()->setWrapText(true);
                $worksheet->getRowDimension(8)->setRowHeight(30);

                // ── Table header merges ────────────────────────────────
                $hRow1 = 11;
                $hRow2 = 12;

                // Vertical merges for fixed columns A-D
                for ($c = 1; $c <= 4; ++$c) {
                    $col = Coordinate::stringFromColumnIndex($c);
                    $worksheet->mergeCells(sprintf('%s%d:%s%d', $col, $hRow1, $col, $hRow2));
                }

                // Horizontal merges for supplier name headers (row 11)
                $sc = 5;
                for ($s = 0; $s < $supplierCount; ++$s) {
                    $c1 = Coordinate::stringFromColumnIndex($sc);
                    $c2 = Coordinate::stringFromColumnIndex($sc + 1);
                    $worksheet->mergeCells(sprintf('%s%d:%s%d', $c1, $hRow1, $c2, $hRow1));
                    $sc += 2;
                }

                // Header styling
                $hRange = sprintf('A%d:%s%d', $hRow1, $lastCol, $hRow2);
                $hStyle = $worksheet->getStyle($hRange);
                $hStyle->getFont()->setBold(true);
                $hStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $hStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $hStyle->getAlignment()->setWrapText(true);
                $hStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getRowDimension($hRow1)->setRowHeight(35);

                // ── Data range ─────────────────────────────────────────
                $dStart = $hRow2 + 1; // row 13
                $dEnd = $highestRow;
                $dRange = sprintf('A%d:%s%d', $dStart, $lastCol, $dEnd);

                // Borders on entire table
                $worksheet->getStyle($dRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Number format for financial columns (D onwards)
                for ($c = 4; $c <= $totalCols; ++$c) {
                    $col = Coordinate::stringFromColumnIndex($c);
                    $worksheet->getStyle(sprintf('%s%d:%s%d', $col, $dStart, $col, $dEnd))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }

                // Cell alignment for data rows
                for ($row = $dStart; $row <= $dEnd; ++$row) {
                    // QTY — centered
                    $worksheet->getStyle('A'.$row)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // UNIT — centered
                    $worksheet->getStyle('B'.$row)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // Financial columns — right-aligned
                    for ($c = 4; $c <= $totalCols; ++$c) {
                        $worksheet->getStyle(Coordinate::stringFromColumnIndex($c).$row)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                // ── Grand Total row ────────────────────────────────────
                $gtRow = $this->findRowStartingWith($worksheet, $dStart, $dEnd, 'GRAND TOTAL');
                if ($gtRow !== null) {
                    // Merge A-C for label
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $gtRow, Coordinate::stringFromColumnIndex(3), $gtRow));
                    $worksheet->getStyle('A'.$gtRow)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Bold entire row
                    $worksheet->getStyle(sprintf('A%d:%s%d', $gtRow, $lastCol, $gtRow))
                        ->getFont()
                        ->setBold(true);

                    // Merge each supplier's total across 2 columns
                    $mc = 5;
                    for ($s = 0; $s < $supplierCount; ++$s) {
                        $c1 = Coordinate::stringFromColumnIndex($mc);
                        $c2 = Coordinate::stringFromColumnIndex($mc + 1);
                        $worksheet->mergeCells(sprintf('%s%d:%s%d', $c1, $gtRow, $c2, $gtRow));
                        $mc += 2;
                    }
                }

                // ── Clear borders below Grand Total (signatures are borderless) ──
                if ($gtRow !== null) {
                    $borderClearStart = $gtRow + 1;
                    if ($borderClearStart <= $dEnd) {
                        $clearRange = sprintf('A%d:%s%d', $borderClearStart, $lastCol, $dEnd);
                        $worksheet->getStyle($clearRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                    }
                }

                // ── Recommendation paragraph (RichText) ────────────────
                $recRow = $this->findRowStartingWith($worksheet, $dStart, $dEnd, 'After our careful scrutiny');
                if ($recRow !== null) {
                    $winnerName = $this->aoq->winnerSupplier?->name ?? 'N/A';
                    $modeLabel = ((int) ($this->calculation['calculated_supplier_count'] ?? 0)) >= 2
                        ? 'Lowest Calculated'
                        : 'Single Calculated';

                    $richText = new RichText;
                    $richText->createText('After our careful scrutiny and deliberation of the submitted quotation of the supplier as reflected in this Abstract of Quotation, we strongly recommend the quotation to be given to ');

                    $bold = $richText->createTextRun($winnerName);
                    $bold->getFont()->setBold(true);

                    $richText->createText(' for being the supplier with the ');

                    $boldUnderline = $richText->createTextRun($modeLabel.' and Responsive Quotation');
                    $boldUnderline->getFont()->setBold(true);
                    $boldUnderline->getFont()->setUnderline(Font::UNDERLINE_SINGLE);

                    $richText->createText(' which is advantageous to the Provincial Government of Batangas.');

                    $worksheet->mergeCells(sprintf('A%d:%s%d', $recRow, $lastCol, $recRow));
                    $worksheet->getCell('A'.$recRow)->setValue($richText);
                    $worksheet->getStyle('A'.$recRow)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('A'.$recRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $worksheet->getStyle('A'.$recRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $worksheet->getRowDimension($recRow)->setRowHeight(80);
                }

                // ── APPROVED: row ──────────────────────────────────────
                $apRow = $this->findRowExact($worksheet, $dStart, $dEnd, 'APPROVED:');
                if ($apRow !== null) {
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $apRow, $lastCol, $apRow));
                    $worksheet->getStyle('A'.$apRow)->getFont()->setBold(true);
                    $worksheet->getStyle('A'.$apRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── BAC Member signature block ─────────────────────────
                $memberZones = $this->signatureZones($totalCols, count(self::BAC_MEMBERS));
                $bacRow = $this->findRowExact($worksheet, $dStart, $dEnd, self::BAC_MEMBERS[0]);
                if ($bacRow !== null) {
                    // Name row — merge zones, bold + underline, centered
                    foreach ($memberZones as $zone) {
                        $c1 = Coordinate::stringFromColumnIndex($zone['start']);
                        $c2 = Coordinate::stringFromColumnIndex($zone['end']);
                        $worksheet->mergeCells(sprintf('%s%d:%s%d', $c1, $bacRow, $c2, $bacRow));
                        $style = $worksheet->getStyle($c1.$bacRow);
                        $style->getFont()->setBold(true);
                        $style->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Title row (next line)
                    $bacTitleRow = $bacRow + 1;
                    foreach ($memberZones as $memberZone) {
                        $c1 = Coordinate::stringFromColumnIndex($memberZone['start']);
                        $c2 = Coordinate::stringFromColumnIndex($memberZone['end']);
                        $worksheet->mergeCells(sprintf('%s%d:%s%d', $c1, $bacTitleRow, $c2, $bacTitleRow));
                        $style = $worksheet->getStyle($c1.$bacTitleRow);
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $style->getFont()->setSize(9);
                    }
                }

                // ── BAC Chairperson signature block ────────────────────
                $chairRow = $this->findRowExact($worksheet, $dStart, $dEnd, self::BAC_CHAIRPERSON);
                if ($chairRow !== null) {
                    // Name — merge full width, bold + underline, centered
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $chairRow, $lastCol, $chairRow));
                    $style = $worksheet->getStyle('A'.$chairRow);
                    $style->getFont()->setBold(true);
                    $style->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Title (next line)
                    $chairTitleRow = $chairRow + 1;
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $chairTitleRow, $lastCol, $chairTitleRow));
                    $titleStyle = $worksheet->getStyle('A'.$chairTitleRow);
                    $titleStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $titleStyle->getFont()->setSize(9);
                }

                // ── Page numbers in footer ─────────────────────────────
                $worksheet->getHeaderFooter()->setOddFooter('&R &P of &N');
            },
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    /** @return array<int, mixed> */
    private function padFirst(string $value, int $cols): array
    {
        $row = array_fill(0, $cols, '');
        $row[0] = $value;

        return $row;
    }

    /** @return array<int, mixed> */
    private function emptyRow(int $cols): array
    {
        return array_fill(0, $cols, '');
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function pad(array $row, int $cols): array
    {
        while (count($row) < $cols) {
            $row[] = '';
        }

        return $row;
    }

    /**
     * Compute evenly-spaced column zones for signature blocks.
     *
     * @return list<array{start: int, end: int}>
     */
    private function signatureZones(int $totalCols, int $count): array
    {
        $zones = [];
        $per = (int) floor($totalCols / $count);
        $remainder = $totalCols % $count;
        $cursor = 1;

        for ($i = 0; $i < $count; ++$i) {
            // Distribute remainder to FIRST members for better spacing
            $zoneWidth = $per + ($i < $remainder ? 1 : 0);
            $start = $cursor;
            $end = $cursor + $zoneWidth - 1;
            $zones[] = ['start' => $start, 'end' => $end];
            $cursor = $end + 1;
        }

        return $zones;
    }

    /** Find the first row in [$from, $to] whose column-A value starts with $prefix. */
    private function findRowStartingWith(Worksheet $worksheet, int $from, int $to, string $prefix): ?int
    {
        for ($row = $from; $row <= $to; ++$row) {
            $val = $worksheet->getCell('A'.$row)->getValue();
            if (is_string($val) && str_starts_with($val, $prefix)) {
                return $row;
            }
        }

        return null;
    }

    /** Find the first row in [$from, $to] whose column-A value exactly equals $value. */
    private function findRowExact(Worksheet $worksheet, int $from, int $to, string $value): ?int
    {
        for ($row = $from; $row <= $to; ++$row) {
            $val = $worksheet->getCell('A'.$row)->getValue();
            if (is_string($val) && $val === $value) {
                return $row;
            }
        }

        return null;
    }
}
