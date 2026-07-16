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
        $rows[] = ['Certified Correct', '', '', '', 'Date:', ''];
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['Secretary to the Sanggunian', '', '', '', '', ''];

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
            AfterSheet::class => function (AfterSheet $e): void {
                $ws = $e->sheet->getDelegate();
                $lastCol = Coordinate::stringFromColumnIndex(self::TOTAL_COLS);
                $highestRow = $ws->getHighestRow();

                // ── Page setup ───────────────────────────────────────────
                $ws->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $ws->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LETTER);
                $ws->getPageMargins()->setTop(0.5);
                $ws->getPageMargins()->setRight(0.5);
                $ws->getPageMargins()->setBottom(0.5);
                $ws->getPageMargins()->setLeft(0.5);

                // ── Column widths — 10% wider ───────────────────────────
                $ws->getColumnDimension('A')->setWidth(11);
                $ws->getColumnDimension('B')->setWidth(13);
                $ws->getColumnDimension('C')->setWidth(7);
                $ws->getColumnDimension('D')->setWidth(35);
                $ws->getColumnDimension('E')->setWidth(15);
                $ws->getColumnDimension('F')->setWidth(15);

                // ── Logo (before merge) ───────────────────────────────────
                $sealPath = public_path('images/batangas-seal.png');
                if (is_file($sealPath)) {
                    $seal = new Drawing;
                    $seal->setName('Provincial Seal');
                    $seal->setPath($sealPath);
                    $seal->setCoordinates('D1');
                    $seal->setOffsetX(84);
                    $seal->setOffsetY(5);
                    $seal->setResizeProportional(true);
                    $seal->setWidth(70);
                    $seal->setHeight(70);
                    $seal->setWorksheet($ws);
                }

                // ── Header merges ────────────────────────────────────────
                $ws->mergeCells(sprintf('A1:%s1', $lastCol));
                $ws->mergeCells(sprintf('A2:%s2', $lastCol));
                $ws->mergeCells(sprintf('A3:%s3', $lastCol));
                $ws->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $ws->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $ws->getRowDimension(1)->setRowHeight(55);

                // ── Info grid (rows 5-8) — no borders, style values ─────
                foreach ([5, 6, 7, 8] as $infoRow) {
                    $ws->mergeCells(sprintf('B%d:D%d', $infoRow, $infoRow));

                    // Column A — label (bold)
                    if (($ws->getCell('A'.$infoRow)->getValue() ?? '') !== '') {
                        $ws->getStyle('A'.$infoRow)->getFont()->setBold(true);
                    }
                    // Column B (merged B-D) — value (bold + underline)
                    if (($ws->getCell('B'.$infoRow)->getValue() ?? '') !== '') {
                        $ws->getStyle('B'.$infoRow)->getFont()->setBold(true);
                        $ws->getStyle('B'.$infoRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }
                    // Column E — label (bold)
                    if (($ws->getCell('E'.$infoRow)->getValue() ?? '') !== '') {
                        $ws->getStyle('E'.$infoRow)->getFont()->setBold(true);
                        $ws->getStyle('E'.$infoRow)->getAlignment()->setWrapText(true);
                    }
                    // Column F — value (bold + underline)
                    if (($ws->getCell('F'.$infoRow)->getValue() ?? '') !== '') {
                        $ws->getStyle('F'.$infoRow)->getFont()->setBold(true);
                        $ws->getStyle('F'.$infoRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }
                }

                // ── Instruction row (row 9) — top+bottom border ─────────
                $ws->getStyle('A9')->getFont()->setBold(true);
                $ws->mergeCells(sprintf('B9:%s9', $lastCol));
                $ws->getStyle('A9')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('B9')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $ws->getStyle('B9')->getAlignment()->setWrapText(true);
                $ws->getStyle(sprintf('A9:%s9', $lastCol))->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $ws->getStyle(sprintf('A9:%s9', $lastCol))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                // ── Delivery/Payment terms (rows 11-12) ────────────────
                foreach ([11, 12] as $termRow) {
                    $ws->getStyle('A'.$termRow)->getFont()->setBold(true);
                    $ws->getStyle('D'.$termRow)->getFont()->setBold(true);
                    $ws->getStyle('B'.$termRow)->getFont()->setBold(true);
                    $ws->getStyle('B'.$termRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    $ws->getStyle('E'.$termRow)->getFont()->setBold(true);
                    $ws->getStyle('E'.$termRow)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    $ws->mergeCells(sprintf('B%d:C%d', $termRow, $termRow));
                    $ws->mergeCells(sprintf('E%d:F%d', $termRow, $termRow));
                    $ws->getStyle(sprintf('A%d:%s%d', $termRow, $lastCol, $termRow))
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    for ($c = 1; $c <= self::TOTAL_COLS; ++$c) {
                        $col = Coordinate::stringFromColumnIndex($c);
                        $ws->getStyle($col.$termRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                        $ws->getStyle($col.$termRow)->getAlignment()->setWrapText(true);
                    }
                }

                // ── Table header (row 14) — bold, centered, bordered ────
                $hRow = 14;
                $hRange = sprintf('A%d:%s%d', $hRow, $lastCol, $hRow);
                $ws->getStyle($hRange)->getFont()->setBold(true);
                $ws->getStyle($hRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $ws->getStyle($hRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $ws->getRowDimension($hRow)->setRowHeight(20);

                // ── Data range ───────────────────────────────────────────
                $dStart = $hRow + 1; // row 15
                $gtRow = $this->findRowStartingWith($ws, $dStart, $highestRow, 'TOTAL (Php)');
                $tableEnd = ($gtRow !== null) ? $gtRow : $highestRow;

                // Apply borders to table only (header through grand total)
                $ws->getStyle(sprintf('A%d:%s%d', $hRow, $lastCol, $tableEnd))
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Number format for financial columns
                $ws->getStyle(sprintf('E%d:F%d', $dStart, $tableEnd))
                    ->getNumberFormat()->setFormatCode('#,##0.00');

                // Wrap text and alignment
                for ($row = $dStart; $row <= $tableEnd; ++$row) {
                    $ws->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('A'.$row)->getAlignment()->setWrapText(true);
                    $ws->getStyle('B'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('B'.$row)->getAlignment()->setWrapText(true);
                    $ws->getStyle('C'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('C'.$row)->getAlignment()->setWrapText(true);
                    $ws->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $ws->getStyle('D'.$row)->getAlignment()->setWrapText(true);
                    $ws->getStyle('E'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $ws->getStyle('E'.$row)->getAlignment()->setWrapText(true);
                    $ws->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $ws->getStyle('F'.$row)->getAlignment()->setWrapText(true);
                }

                // ── Grand Total row ─────────────────────────────────────
                if ($gtRow !== null) {
                    $ws->mergeCells(sprintf('A%d:E%d', $gtRow, $gtRow));
                    $ws->getStyle('A'.$gtRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $ws->getStyle(sprintf('A%d:%s%d', $gtRow, $lastCol, $gtRow))
                        ->getFont()->setBold(true);
                }

                // ── Amount in Words row ─────────────────────────────────
                $awRow = $this->findRowStartingWith($ws, $dStart, $highestRow, 'Total Amount in Words');
                if ($awRow !== null) {
                    // Read value from B BEFORE merge (merge clears other cells)
                    $awValue = $ws->getCell('B'.$awRow)->getValue() ?? '';
                    // Merge A-F for the full row
                    $ws->mergeCells(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow));
                    // Use RichText for label + bold+underline value
                    $rt = new RichText;
                    $rt->createText('Total Amount in Words: ');
                    if ($awValue !== '') {
                        $valRun = $rt->createTextRun((string) $awValue);
                        $valRun->getFont()->setBold(true);
                        $valRun->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
                    }
                    $ws->getCell('A'.$awRow)->setValue($rt);
                    $ws->getStyle('A'.$awRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $ws->getStyle('A'.$awRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $ws->getRowDimension($awRow)->setRowHeight(25);
                    // Top and bottom border
                    $ws->getStyle(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:%s%d', $awRow, $lastCol, $awRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                }

                // ── Penalty clause row ──────────────────────────────────
                $penRow = $this->findRowStartingWith($ws, $dStart, $highestRow, 'In case of failure');
                if ($penRow !== null) {
                    $ws->mergeCells(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow));
                    $ws->getStyle('A'.$penRow)->getAlignment()->setWrapText(true);
                    $ws->getStyle('A'.$penRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('A'.$penRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    $ws->getRowDimension($penRow)->setRowHeight(35);
                    // Top and bottom border
                    $ws->getStyle(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow))
                        ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:%s%d', $penRow, $lastCol, $penRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                }

                // ── Signature block ─────────────────────────────────────
                $confRow = $this->findRowExact($ws, $dStart, $highestRow, 'Conforme:');
                if ($confRow !== null) {
                    $sigEnd = $confRow + 5;

                    // Clear all inner cell borders first
                    for ($r = $confRow; $r <= $sigEnd; ++$r) {
                        $ws->getStyle(sprintf('A%d:%s%d', $r, $lastCol, $r))
                            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                    }

                    // Merges
                    $ws->mergeCells(sprintf('A%d:C%d', $confRow, $confRow));     // Conforme row left
                    $ws->mergeCells(sprintf('D%d:%s%d', $confRow, $lastCol, $confRow)); // Conforme row right
                    $ws->mergeCells(sprintf('A%d:C%d', $confRow + 2, $confRow + 2)); // (Signature over printed name) left
                    $ws->mergeCells(sprintf('D%d:%s%d', $confRow + 2, $lastCol, $confRow + 2)); // empty right
                    $ws->mergeCells(sprintf('A%d:C%d', $confRow + 4, $confRow + 4)); // Date left
                    $ws->mergeCells(sprintf('D%d:%s%d', $confRow + 4, $lastCol, $confRow + 4)); // VILMA SANTOS - RECTO right
                    $ws->mergeCells(sprintf('D%d:%s%d', $confRow + 5, $lastCol, $confRow + 5)); // Governor right

                    // Apply outer border on the full block
                    $ws->getStyle(sprintf('A%d:%s%d', $confRow, $lastCol, $confRow))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:%s%d', $sigEnd, $lastCol, $sigEnd))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:A%d', $confRow, $sigEnd))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('%s%d:%s%d', $lastCol, $confRow, $lastCol, $sigEnd))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                    // Vertical divider at column C
                    $ws->getStyle(sprintf('C%d:C%d', $confRow, $sigEnd))
                        ->getBorders()->getRight()->setBorderStyle(Border::BORDER_THIN);

                    // Signature lines (bottom border on the signature row)
                    $ws->getStyle('A'.($confRow + 4))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('D%d:%s%d', $confRow + 4, $lastCol, $confRow + 4))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                    // Style labels
                    $ws->getStyle('A'.$confRow)->getFont()->setBold(true); // Conforme
                    $ws->getStyle('A'.($confRow + 2))->getFont()->setSize(8); // caption size
                    $ws->getStyle('A'.($confRow + 2))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('A'.($confRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('D'.($confRow + 4))->getFont()->setBold(true); // VILMA
                    $ws->getStyle('D'.($confRow + 4))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('D'.($confRow + 5))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── Negotiated Purchase footer block ─────────────────────
                $negRow = $this->findRowStartingWith($ws, $dStart, $highestRow, '(In case of Negotiated');
                if ($negRow !== null) {
                    // Clear borders for negotiated section
                    for ($r = $negRow; $r <= $highestRow; ++$r) {
                        $ws->getStyle(sprintf('A%d:%s%d', $r, $lastCol, $r))
                            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
                    }

                    // Merge full width for all negotiated rows
                    $ws->mergeCells(sprintf('A%d:%s%d', $negRow, $lastCol, $negRow));
                    $ws->mergeCells(sprintf('A%d:%s%d', $negRow + 1, $lastCol, $negRow + 1));

                    // (In case of Negotiated Purchase...) — italic small
                    $ws->getStyle('A'.$negRow)->getFont()->setSize(8);
                    $ws->getStyle('A'.$negRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Approved per Sangguniang Resolution No. — bold
                    $ws->getStyle('A'.($negRow + 1))->getFont()->setBold(true);
                    $ws->getStyle('A'.($negRow + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // Apply outer border on negotiated section
                    $ws->getStyle(sprintf('A%d:%s%d', $negRow, $lastCol, $negRow))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:%s%d', $negRow + 3, $lastCol, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('A%d:A%d', $negRow, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('%s%d:%s%d', $lastCol, $negRow, $lastCol, $negRow + 3))
                        ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

                    // Certified Correct / Date row
                    $certRow = $negRow + 2;
                    $ws->mergeCells(sprintf('A%d:C%d', $certRow, $certRow));
                    $ws->mergeCells(sprintf('D%d:%s%d', $certRow, $lastCol, $certRow));
                    $ws->getStyle('A'.$certRow)->getFont()->setBold(true);
                    $ws->getStyle('D'.$certRow)->getFont()->setBold(true);
                    $ws->getStyle('A'.$certRow)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                    $ws->getStyle(sprintf('D%d:%s%d', $certRow, $lastCol, $certRow))
                        ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

                    // Secretary to the Sanggunian
                    $secRow = $certRow + 1;
                    $ws->mergeCells(sprintf('A%d:C%d', $secRow, $secRow));
                    $ws->getStyle('A'.$secRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $ws->getStyle('A'.$secRow)->getFont()->setSize(8);
                }

                // ── Outer border around entire document ─────────────────
                $ws->getStyle(sprintf('A1:%s%d', $lastCol, $highestRow))
                    ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_MEDIUM);

                // ── Top border on info grid (row 5) ─────────────────────
                $ws->getStyle(sprintf('A5:%s5', $lastCol))
                    ->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                // ── Page numbers ─────────────────────────────────────────
                $ws->getHeaderFooter()->setOddFooter('&R &P of &N');
            },
        ];
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    /** Find the first row in [$from, $to] whose column-A value starts with $prefix. */
    private function findRowStartingWith(Worksheet $ws, int $from, int $to, string $prefix): ?int
    {
        for ($row = $from; $row <= $to; ++$row) {
            $val = $ws->getCell('A'.$row)->getValue();
            if (is_string($val) && str_starts_with($val, $prefix)) {
                return $row;
            }
        }

        return null;
    }

    /** Find the first row in [$from, $to] whose column-A value exactly equals $value. */
    private function findRowExact(Worksheet $ws, int $from, int $to, string $value): ?int
    {
        for ($row = $from; $row <= $to; ++$row) {
            $val = $ws->getCell('A'.$row)->getValue();
            if (is_string($val) && $val === $value) {
                return $row;
            }
        }

        return null;
    }
}
