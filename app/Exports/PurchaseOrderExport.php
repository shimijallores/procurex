<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\NumberToWords;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromArray;
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

class PurchaseOrderExport implements FromArray, WithEvents, WithStyles
{
    private const EMPTY_ROWS = 10;

    private const TOTAL_COLS = 6;

    private ?Supplier $winnerSupplier = null;

    public function __construct(
        private readonly PurchaseOrder $purchaseOrder,
        ?Supplier $winnerSupplier = null,
    ) {
        $this->winnerSupplier = $winnerSupplier;
    }

    public function array(): array
    {
        $po = $this->purchaseOrder;
        $supplierName = strtoupper((string) ($this->winnerSupplier?->name ?? 'SUPPLIER'));
        $supplierAddress = (string) ($this->winnerSupplier?->address ?? '');
        $deliveryDays = (int) ($po->delivery_term_days ?? 0);
        $deliveryText = $deliveryDays > 0
            ? 'within '.$deliveryDays.' calendar days upon receipt of this Purchase Order'
            : '________________________';
        $deliveryTermShort = $deliveryDays > 0
            ? 'within '.$deliveryDays.' calendar days upon receipt hereof'
            : '________________________';
        $poAmount = (float) ($po->total_amount ?? 0);
        $poAmountWords = NumberToWords::convert($poAmount);
        $prNo = $po->noa?->aoq?->rfq?->purchaseRequest?->pr_no ?? '—';
        $poDate = $po->po_date ? $po->po_date->format('m/d/Y') : '';
        $modeOfProc = $po->mode_of_procurement ?? '';
        $placeOfDelivery = strtoupper((string) ($po->place_of_delivery ?? ''));
        $paymentTerm = $po->payment_term ?: '________________________';

        $rows = [];

        // Row 1: Logo area
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Row 2: Title
        $rows[] = $this->colspan('PURCHASE ORDER', self::TOTAL_COLS);

        // Row 3: LGU
        $rows[] = $this->colspan('LGU', self::TOTAL_COLS);

        // Row 4: blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Row 5-8: Info grid (no borders)
        $rows[] = ['Supplier:', $supplierName, '', '', 'P.O. No.:', $po->po_no];
        $rows[] = ['Address:', $supplierAddress !== '' ? $supplierAddress : '________________________', '', '', 'Date:', $poDate];
        $rows[] = ['', '', '', '', 'Mode of Procurement:', $modeOfProc];
        $rows[] = ['', '', '', '', 'P.R. No/s.:', $prNo];

        // Row 9: Instruction line
        $rows[] = ['Sir/Madam:', 'Please furnish this office the following articles subject to the terms and conditions contained herein:'];

        // Row 10: blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Row 11-12: Delivery/Payment terms block
        $rows[] = ['Place of Delivery:', $placeOfDelivery, '', 'Delivery Term:', $deliveryTermShort, ''];
        $rows[] = ['Date of Delivery:', $deliveryText, '', 'Payment Term:', $paymentTerm, ''];

        // Row 13: blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Row 14: Table header
        $rows[] = ['ITEM NO.', 'UNIT', 'QTY', 'DESCRIPTION', 'UNIT COST', 'AMOUNT'];

        // Data rows
        $counter = 0;
        $total = 0.0;

        foreach ($po->items as $item) {
            ++$counter;
            $quantity = (int) ($item->quantity_snapshot ?? 0);
            $unitCost = (float) ($item->unit_cost_snapshot ?? 0);
            $amount = (float) ($item->amount_snapshot ?? 0);
            $total += $amount;
            $description = $item->rfqItem?->purchaseRequestItem?->item_name ?? '';

            $rows[] = [
                (string) $counter,
                $item->rfqItem?->purchaseRequestItem?->unit ?? '',
                $quantity,
                $description,
                $unitCost,
                $amount,
            ];
        }

        // Blank padding rows
        $dataRows = $counter;
        $padding = max(0, self::EMPTY_ROWS - $dataRows);
        for ($i = 0; $i < $padding; ++$i) {
            $rows[] = $this->colspan('', self::TOTAL_COLS);
        }

        // Grand Total row
        $totalRow = $this->colspan('', self::TOTAL_COLS);
        $totalRow[0] = 'TOTAL (Php)';
        $totalRow[5] = $total;
        $rows[] = $totalRow;

        // Blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Amount in Words row
        $rows[] = ['Total Amount in Words:', $poAmountWords, '', '', '', ''];

        // Blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Penalty clause
        $rows[] = $this->colspan(
            'In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of one (1) percent for every day of delay shall be imposed.',
            self::TOTAL_COLS,
        );

        // Blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Signature block
        $rows[] = ['Conforme:', '', '', 'Very truly yours,', '', ''];
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['(Signature over printed name)', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['Date', '', '', 'VILMA SANTOS - RECTO', '', ''];
        $rows[] = ['', '', '', 'Governor', '', ''];

        // Blank
        $rows[] = $this->colspan('', self::TOTAL_COLS);

        // Negotiated Purchase footer block
        $rows[] = $this->colspan(
            '(In case of Negotiated Purchase pursuant to Section 369 (a) of RA 7160, this portion must be accomplished.)',
            self::TOTAL_COLS,
        );
        $rows[] = $this->colspan('Approved per Sangguniang Resolution No.: ________________________________________', self::TOTAL_COLS);
        $rows[] = ['Certified Correct', '', '', 'Date', '', ''];
        $rows[] = ['', 'Secretary to the Sanggunian', '', '', '', ''];

        return $rows;
    }

    /** @return array<int, string> */
    private function colspan(string $value, int $cols): array
    {
        $row = array_fill(0, $cols, '');
        $row[0] = $value;

        return $row;
    }

    /** @return array<int|string, array<string, mixed>> */
    public function styles(Worksheet $sheet): array
    {
        return [
            2 => [
                'font' => ['bold' => true, 'size' => 22],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $worksheet = $afterSheet->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(self::TOTAL_COLS);
                $highestRow = $worksheet->getHighestRow();

                // ── Page setup ───────────────────────────────────────────
                $worksheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $worksheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
                $worksheet->getPageMargins()->setTop(0.5);
                $worksheet->getPageMargins()->setRight(0.5);
                $worksheet->getPageMargins()->setBottom(0.5);
                $worksheet->getPageMargins()->setLeft(0.5);

                // ── Column widths — 10% wider ───────────────────────────
                $worksheet->getColumnDimension('A')->setWidth(11);
                $worksheet->getColumnDimension('B')->setWidth(13);
                $worksheet->getColumnDimension('C')->setWidth(7);
                $worksheet->getColumnDimension('D')->setWidth(35);
                $worksheet->getColumnDimension('E')->setWidth(15);
                $worksheet->getColumnDimension('F')->setWidth(15);

                // ── Logo (before merge) ───────────────────────────────────
                $sealPath = public_path('images/batangas-seal.png');
                if (is_file($sealPath)) {
                    $drawing = new Drawing;
                    $drawing->setName('Provincial Seal');
                    $drawing->setPath($sealPath);
                    $drawing->setCoordinates('D1');
                    $drawing->setOffsetX(84);
                    $drawing->setOffsetY(5);
                    $drawing->setResizeProportional(true);
                    $drawing->setWidth(70);
                    $drawing->setHeight(70);
                    $drawing->setWorksheet($worksheet);
                }

                // ── Header merges ────────────────────────────────────────
                $worksheet->mergeCells(sprintf('A1:%s1', $lastCol));
                $worksheet->mergeCells(sprintf('A2:%s2', $lastCol));
                $worksheet->mergeCells(sprintf('A3:%s3', $lastCol));
                $worksheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getRowDimension(1)->setRowHeight(55);

                // ── Info grid (rows 5-8) — no borders, style values ─────
                foreach ([5, 6, 7, 8] as $infoRow) {
                    $worksheet->mergeCells(sprintf('B%d:D%d', $infoRow, $infoRow));

                    // Column A — label (bold)
                    if (($worksheet->getCell('A'.$infoRow)->getValue() ?? '') !== '') {
                        $worksheet->getStyle('A'.$infoRow)->getFont()->setBold(true);
                    }

                    // Column B (merged B-D) — value (bold + underline)
                    if (($worksheet->getCell('B'.$infoRow)->getValue() ?? '') !== '') {
                        $worksheet->getStyle('B'.$infoRow)->getFont()->setBold(true);
                        $worksheet->getStyle('B'.$infoRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }

                    // Column E — label (bold)
                    if (($worksheet->getCell('E'.$infoRow)->getValue() ?? '') !== '') {
                        $worksheet->getStyle('E'.$infoRow)->getFont()->setBold(true);
                        $worksheet->getStyle('E'.$infoRow)->getAlignment()->setWrapText(true);
                    }

                    // Column F — value (bold + underline)
                    if (($worksheet->getCell('F'.$infoRow)->getValue() ?? '') !== '') {
                        $worksheet->getStyle('F'.$infoRow)->getFont()->setBold(true);
                        $worksheet->getStyle('F'.$infoRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }
                }

                // ── Instruction row (row 9) — top+bottom border ─────────
                $worksheet->getStyle('A9')->getFont()->setBold(true);
                $worksheet->mergeCells(sprintf('B9:%s9', $lastCol));
                $worksheet->getStyle('A9')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $worksheet->getStyle('B9')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $worksheet->getStyle('B9')->getAlignment()->setWrapText(true);
                $worksheet->getStyle(sprintf('A9:%s9', $lastCol))->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getStyle(sprintf('A9:%s9', $lastCol))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                // ── Delivery/Payment terms (rows 11-12) ────────────────
                foreach ([11, 12] as $termRow) {
                    $worksheet->getStyle('A'.$termRow)->getFont()->setBold(true);
                    $worksheet->getStyle('D'.$termRow)->getFont()->setBold(true);
                    $worksheet->getStyle('B'.$termRow)->getFont()->setBold(true);
                    $worksheet->getStyle('B'.$termRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    $worksheet->getStyle('E'.$termRow)->getFont()->setBold(true);
                    $worksheet->getStyle('E'.$termRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    $worksheet->mergeCells(sprintf('B%d:C%d', $termRow, $termRow));
                    $worksheet->mergeCells(sprintf('E%d:F%d', $termRow, $termRow));
                    $worksheet->getStyle(sprintf('A%d:%s%d', $termRow, $lastCol, $termRow))
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    for ($c = 1; $c <= self::TOTAL_COLS; ++$c) {
                        $col = Coordinate::stringFromColumnIndex($c);
                        $worksheet->getStyle($col.$termRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                        $worksheet->getStyle($col.$termRow)->getAlignment()->setWrapText(true);
                    }
                }

                // ── Table header (row 14) — bold, centered, bordered ────
                $hRow = 14;
                $hRange = sprintf('A%d:%s%d', $hRow, $lastCol, $hRow);
                $worksheet->getStyle($hRange)->getFont()->setBold(true);
                $worksheet->getStyle($hRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle($hRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $worksheet->getRowDimension($hRow)->setRowHeight(20);

                // ── Data range ───────────────────────────────────────────
                $dStart = $hRow + 1; // row 15
                $gtRow = $this->findRowStartingWith($worksheet, $dStart, $highestRow, 'TOTAL (Php)');
                $tableEnd = ($gtRow !== null) ? $gtRow : $highestRow;

                // Apply borders to table only (header through grand total)
                $worksheet->getStyle(sprintf('A%d:%s%d', $hRow, $lastCol, $tableEnd))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Number format for financial columns
                $worksheet->getStyle(sprintf('E%d:F%d', $dStart, $tableEnd))
                    ->getNumberFormat()->setFormatCode('#,##0.00');

                // Wrap text and alignment
                for ($row = $dStart; $row <= $tableEnd; ++$row) {
                    $worksheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('A'.$row)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('B'.$row)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('C'.$row)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $worksheet->getStyle('D'.$row)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $worksheet->getStyle('E'.$row)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $worksheet->getStyle('F'.$row)->getAlignment()->setWrapText(true);
                }

                // ── Grand Total row ─────────────────────────────────────
                if ($gtRow !== null) {
                    $worksheet->mergeCells(sprintf('A%d:E%d', $gtRow, $gtRow));
                    $worksheet->getStyle('A'.$gtRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $gtRow, $lastCol, $gtRow))
                        ->getFont()->setBold(true);
                }

                // ── Amount in Words row ─────────────────────────────────
                $awRow = $this->findRowStartingWith($worksheet, $dStart, $highestRow, 'Total Amount in Words');
                if ($awRow !== null) {
                    // Read value from B BEFORE merge (merge clears other cells)
                    $awValue = $worksheet->getCell('B'.$awRow)->getValue() ?? '';
                    // Merge A-F for the full row
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow));
                    // Use RichText for label + bold+underline value
                    $richText = new RichText;
                    $richText->createText('Total Amount in Words: ');
                    if ($awValue !== '') {
                        $valRun = $richText->createTextRun((string) $awValue);
                        $valRun->getFont()->setBold(true);
                        $valRun->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }

                    $worksheet->getCell('A'.$awRow)->setValue($richText);
                    $worksheet->getStyle('A'.$awRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $worksheet->getStyle('A'.$awRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $worksheet->getRowDimension($awRow)->setRowHeight(25);
                    // Top and bottom border
                    $worksheet->getStyle(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                }

                // ── Penalty clause row ──────────────────────────────────
                $penRow = $this->findRowStartingWith($worksheet, $dStart, $highestRow, 'In case of failure');
                if ($penRow !== null) {
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow));
                    $worksheet->getStyle('A'.$penRow)->getAlignment()->setWrapText(true);
                    $worksheet->getStyle('A'.$penRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('A'.$penRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    $worksheet->getRowDimension($penRow)->setRowHeight(35);
                    // Top and bottom border
                    $worksheet->getStyle(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                }

                // ── Signature block ─────────────────────────────────────
                $confRow = $this->findRowExact($worksheet, $dStart, $highestRow, 'Conforme:');
                if ($confRow !== null) {
                    $sigEnd = $confRow + 5;

                    // Clear all inner cell borders first
                    for ($r = $confRow; $r <= $sigEnd; ++$r) {
                        $worksheet->getStyle(sprintf('A%d:%s%d', $r, $lastCol, $r))
                            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                    }

                    // Merges
                    $worksheet->mergeCells(sprintf('A%d:C%d', $confRow, $confRow));     // Conforme row left
                    $worksheet->mergeCells(sprintf('D%d:%s%d', $confRow, $lastCol, $confRow)); // Conforme row right
                    $worksheet->mergeCells(sprintf('A%d:C%d', $confRow + 2, $confRow + 2)); // (Signature over printed name) left
                    $worksheet->mergeCells(sprintf('D%d:%s%d', $confRow + 2, $lastCol, $confRow + 2)); // empty right
                    $worksheet->mergeCells(sprintf('A%d:C%d', $confRow + 4, $confRow + 4)); // Date left
                    $worksheet->mergeCells(sprintf('D%d:%s%d', $confRow + 4, $lastCol, $confRow + 4)); // VILMA SANTOS - RECTO right
                    $worksheet->mergeCells(sprintf('D%d:%s%d', $confRow + 5, $lastCol, $confRow + 5)); // Governor right

                    // Apply outer border on the full block — top only on first row
                    $worksheet->getStyle(sprintf('A%d:%s%d', $confRow, $lastCol, $confRow))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $sigEnd, $lastCol, $sigEnd))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                    // Style — center Conforme and Very Truly Yours
                    $worksheet->getStyle('A'.$confRow)->getFont()->setBold(true);
                    $worksheet->getStyle('A'.$confRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('D'.$confRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Top borders on signature name rows
                    $worksheet->getStyle(sprintf('A%d:%s%d', $confRow + 2, $lastCol, $confRow + 2))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $confRow + 4, $lastCol, $confRow + 4))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    // Style labels
                    $worksheet->getStyle('A'.($confRow + 2))->getFont()->setSize(8);
                    $worksheet->getStyle('A'.($confRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('A'.($confRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('D'.($confRow + 4))->getFont()->setBold(true);
                    $worksheet->getStyle('D'.($confRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $worksheet->getStyle('D'.($confRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── Negotiated Purchase footer block ─────────────────────
                $negRow = $this->findRowStartingWith($worksheet, $dStart, $highestRow, '(In case of Negotiated');
                if ($negRow !== null) {
                    // Clear borders for negotiated section
                    for ($r = $negRow; $r <= $highestRow; ++$r) {
                        $worksheet->getStyle(sprintf('A%d:%s%d', $r, $lastCol, $r))
                            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                    }

                    // Merge full width for all negotiated rows
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $negRow, $lastCol, $negRow));
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $negRow + 1, $lastCol, $negRow + 1));

                    // (In case of Negotiated Purchase...) — italic small
                    $worksheet->getStyle('A'.$negRow)->getFont()->setSize(8);
                    $worksheet->getStyle('A'.$negRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Approved per Sangguniang Resolution No. — bold
                    $worksheet->getStyle('A'.($negRow + 1))->getFont()->setBold(true);
                    $worksheet->getStyle('A'.($negRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // Apply outer border on negotiated section
                    $worksheet->getStyle(sprintf('A%d:%s%d', $negRow, $lastCol, $negRow))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:%s%d', $negRow + 3, $lastCol, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('A%d:A%d', $negRow, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('%s%d:%s%d', $lastCol, $negRow, $lastCol, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                    // Certified Correct / Date row
                    $certRow = $negRow + 2;
                    $worksheet->mergeCells(sprintf('A%d:C%d', $certRow, $certRow));
                    $worksheet->mergeCells(sprintf('D%d:%s%d', $certRow, $lastCol, $certRow));
                    $worksheet->getStyle('A'.$certRow)->getFont()->setBold(true);
                    $worksheet->getStyle('D'.$certRow)->getFont()->setBold(true);
                    $worksheet->getStyle('D'.$certRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $worksheet->getStyle('A'.$certRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $worksheet->getStyle(sprintf('D%d:%s%d', $certRow, $lastCol, $certRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                    // Secretary to the Sanggunian
                    $secRow = $certRow + 1;
                    $worksheet->mergeCells(sprintf('B%d:C%d', $secRow, $secRow));
                    $worksheet->getStyle('B'.$secRow)->getFont()->setSize(8);
                    $worksheet->getStyle('B'.$secRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── Outer border around entire document ─────────────────
                $worksheet->getStyle(sprintf('A1:%s%d', $lastCol, $highestRow))
                    ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

                // ── Top border on info grid (row 5) ─────────────────────
                $worksheet->getStyle(sprintf('A5:%s5', $lastCol))
                    ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                // ── Page numbers ─────────────────────────────────────────
                $worksheet->getHeaderFooter()->setOddFooter('&R &P of &N');
            },
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────

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
