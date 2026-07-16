<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\RFQ;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RFQExport implements FromArray, WithEvents, WithStyles
{
    private int $headerRow;

    public function __construct(private readonly RFQ $rfq)
    {
        $projectLabel = 'PROJECT NAME: ';
        $projectName = $this->rfq->project_name ?? '';
        $fullText = $projectLabel.$projectName;
        $projectLineCount = substr_count(wordwrap($fullText, 80, "\n", true), "\n") + 1;
        // 20 fixed rows + project name lines + 3 (empty + ABC + empty) + 1 (header)
        $this->headerRow = 20 + $projectLineCount + 3 + 1;
    }

    public function array(): array
    {
        $rows = [];

        // Row 1-2: PR/SVP numbers (top right)
        $rows[] = [
            '',
            '',
            '',
            '',
            '',
            'PR No.:',
            $this->rfq->purchaseRequest?->pr_no ?? '',
        ];
        $rows[] = ['', '', '', '', '', 'SVP No.:', $this->rfq->svp_no];
        $rows[] = [''];

        // Government header
        $rows[] = ['REPUBLIC OF THE PHILIPPINES'];
        $rows[] = ['PROVINCIAL GOVERNMENT OF BATANGAS'];
        $rows[] = ['Capitol Site, Kumintang Ibaba, Batangas City 4200'];
        $rows[] = ['Bids and Awards Committee'];
        $rows[] = [''];

        // Title
        $rows[] = ['REQUEST FOR QUOTATION (RFQ)'];
        $rows[] = ['Small Value Procurement (SVP)'];
        $rows[] = [''];

        // Input fields — label and underline in column A
        $rows[] = ['Date: ________________________________________________'];
        $rows[] = [
            'Company Name: ________________________________________________',
        ];
        $rows[] = ['Address: ________________________________________________'];
        $rows[] = [
            'Contact Details: ________________________________________________',
        ];
        $rows[] = [''];

        // Body text
        $rows[] = [
            'The Provincial Government of Batangas, through its Bids and Awards Committee (BAC), invites ',
        ];
        $rows[] = [
            'suppliers to submit pricequotations for the procurement of the item/s described below, taking',
        ];
        $rows[] = ['into consideration the stated Terms and Conditions.'];
        $rows[] = [''];

        // Project name — bold label, normal name, wordwrap into rows
        $projectLabel = 'PROJECT NAME: ';
        $projectName = $this->rfq->project_name ?? '';
        $fullText = $projectLabel.$projectName;
        $wrapped = wordwrap($fullText, 80, "\n", true);
        foreach (explode("\n", $wrapped) as $line) {
            $rows[] = [$line];
        }

        $rows[] = [''];

        // ABC bold
        $rows[] = [
            'APPROVED BUDGET FOR THE CONTRACT (ABC): Php '.
            number_format((float) $this->rfq->abc_amount, 2),
        ];
        $rows[] = [''];

        // Table header
        $rows[] = [
            'NO.',
            'ITEM & DESCRIPTION',
            'QTY',
            'UNIT',
            'PR PRICE',
            'UNIT PRICE',
            'TOTAL AMOUNT',
        ];

        $counter = 0;
        foreach ($this->rfq->items as $item) {
            ++$counter;
            $prPrice = (float) ($item->purchaseRequestItem?->unit_cost ?? 0);
            $rows[] = [
                $counter,
                $item->item_name ?? '',
                (int) $item->quantity,
                $item->unit ?? '',
                number_format($prPrice, 2),
                '',
                '',
            ];
        }

        // Empty rows
        for ($i = $counter; $i < 18; ++$i) {
            $rows[] = ['', '', '', '', '', '', ''];
        }

        // Grand total row
        $rows[] = ['GRAND TOTAL:'];
        $rows[] = ['', '', '', '', '', '', ''];

        $rows[] = [
            'Total Amount in Words: _____________________________________________________________',
        ];
        $rows[] = ['', '', '', '', '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            4 => [
                'font' => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            5 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            7 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            8 => [
                'font' => ['size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            9 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            10 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            12 => ['font' => ['bold' => true]],
            13 => ['font' => ['bold' => true]],
            14 => ['font' => ['bold' => true]],
            15 => ['font' => ['bold' => true]],
            20 => ['font' => ['bold' => true]],
            21 => ['font' => ['bold' => true]],
            22 => ['font' => ['bold' => true]],
            23 => ['font' => ['bold' => true]],
            24 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $worksheet = $afterSheet->sheet->getDelegate();

                // Merge cells for government header (centered)
                $worksheet->mergeCells('A4:G4');
                $worksheet->mergeCells('A5:G5');
                $worksheet->mergeCells('A6:G6');
                $worksheet->mergeCells('A7:G7');

                // Merge cells for title
                $worksheet->mergeCells('A9:G9');
                $worksheet->mergeCells('A10:G10');

                // Merge project name row (row 21) for wordwrap
                $worksheet->mergeCells('A21:G21');
                $worksheet->getStyle('A21')->getAlignment()->setWrapText(true);

                // Right-align PR/SVP numbers (NOT bold)
                $worksheet
                    ->getStyle('F1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $worksheet
                    ->getStyle('F2')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Table header row — font size 10, bold, centered
                $headerRow = $this->headerRow;
                $headerRange = sprintf('A%d:G%d', $headerRow, $headerRow);
                $headerStyle = $worksheet->getStyle($headerRange);
                $headerStyle->getFont()->setBold(true)->setSize(10);
                $headerStyle
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Borders for table area (header + data)
                $dataEndRow = $worksheet->getHighestRow();
                $dataRange = sprintf('A%d:G%d', $headerRow, $dataEndRow);
                $worksheet
                    ->getStyle($dataRange)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Explicit right border on TOTAL AMOUNT header (G26)
                $worksheet
                    ->getStyle('G' . $headerRow)
                    ->getBorders()
                    ->getRight()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Center all columns except B, left-align B with wrap text
                // Also set font size 10 on all data rows
                $dataStartRow = $headerRow + 1;
                for ($col = 1; $col <= 7; ++$col) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    // Header column
                    $headerCellRange = sprintf('%s%d', $colLetter, $headerRow);
                    // Data column
                    $dataCellRange = sprintf('%s%d:%s%d', $colLetter, $dataStartRow, $colLetter, $dataEndRow);
                    if ($colLetter === 'B') {
                        $worksheet
                            ->getStyle($dataCellRange)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                            ->setVertical(Alignment::VERTICAL_CENTER)
                            ->setWrapText(true);
                    } else {
                        $worksheet
                            ->getStyle($dataCellRange)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    }
                }

                // Set font size 10 on all content rows (data only, not header)
                $worksheet
                    ->getStyle(sprintf('A%d:G%d', $dataStartRow, $dataEndRow))
                    ->getFont()
                    ->setSize(10);

                // Format PR Price column (E) as currency
                $worksheet
                    ->getStyle(sprintf('E%d:E%d', $headerRow, $dataEndRow))
                    ->getNumberFormat()
                    ->setFormatCode(
                        NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                    );

                // Find GRAND TOTAL row — merge A to F, put text on right, bold
                for ($row = $headerRow; $row <= $dataEndRow; ++$row) {
                    if (
                        $worksheet->getCell('A' . $row)->getValue() ===
                        'GRAND TOTAL:'
                    ) {
                        $worksheet->mergeCells(sprintf('A%d:F%d', $row, $row));
                        $worksheet
                            ->getStyle('A' . $row)
                            ->getFont()
                            ->setBold(true);
                        $worksheet
                            ->getStyle('A' . $row)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        break;
                    }
                }

                // Find "Total Amount in Words" row — remove borders, left-align, top-align
                for ($row = $headerRow; $row <= $dataEndRow; ++$row) {
                    if (
                        str_starts_with(
                            (string) $worksheet->getCell('A' . $row)->getValue(),
                            'Total Amount in Words',
                        )
                    ) {
                        // Remove borders from the spacer row (one row above)
                        if ($row > $headerRow) {
                            $spacerRange = 'A'.($row - 1).':G'.($row - 1);
                            $worksheet
                                ->getStyle($spacerRange)
                                ->getBorders()
                                ->getAllBorders()
                                ->setBorderStyle(Border::BORDER_NONE);
                        }

                        $noBorderRange = sprintf('A%d:G%d', $row, $row);
                        $worksheet
                            ->getStyle($noBorderRange)
                            ->getBorders()
                            ->getAllBorders()
                            ->setBorderStyle(Border::BORDER_NONE);
                        $worksheet
                            ->getStyle('A' . $row)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                            ->setVertical(Alignment::VERTICAL_TOP);
                        break;
                    }
                }

                // Column widths — compressed to fit A4 portrait (~174mm total)
                $worksheet->getColumnDimension('A')->setWidth(6);
                $worksheet->getColumnDimension('B')->setWidth(30);
                $worksheet->getColumnDimension('C')->setWidth(7);
                $worksheet->getColumnDimension('D')->setWidth(7);
                $worksheet->getColumnDimension('E')->setWidth(12);
                $worksheet->getColumnDimension('F')->setWidth(12);
                $worksheet->getColumnDimension('G')->setWidth(14);
                // Print setup — A4 portrait, fit-to-width, narrow margins
                $worksheet
                    ->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                $worksheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
                $worksheet->getPageSetup()->setFitToPage(true);
                $worksheet->getPageSetup()->setFitToWidth(1);
                $worksheet->getPageSetup()->setFitToHeight(0);
                $worksheet->getPageMargins()->setLeft(0.75);
                $worksheet->getPageMargins()->setRight(0.75);
                $worksheet->getPageMargins()->setTop(0.75);
                $worksheet->getPageMargins()->setBottom(0.75);
                // Page numbering footer — "Page 1 of 2"
                $worksheet
                    ->getHeaderFooter()
                    ->setOddFooter('&CPage &P of &N');

                // Row heights
                $worksheet->getRowDimension(1)->setRowHeight(18);
                $worksheet->getRowDimension(2)->setRowHeight(18);

                // Add logos
                $sealPath = public_path('images/batangas-seal.png');
                if (is_file($sealPath)) {
                    $seal = new Drawing;
                    $seal->setName('Batangas Seal');
                    $seal->setPath($sealPath);
                    $seal->setCoordinates('B3');
                    $seal->setWorksheet($worksheet);
                    $seal->setResizeProportional(true);
                    $seal->setWidth(60);
                    $seal->setHeight(60);
                    $seal->setOffsetX(50);
                    $seal->setOffsetY(30);
                }

                $bagongPath = public_path('images/bagong-pilipinas.png');
                if (is_file($bagongPath)) {
                    $bagong = new Drawing;
                    $bagong->setName('Bagong Pilipinas');
                    $bagong->setPath($bagongPath);
                    $bagong->setCoordinates('F3');
                    $bagong->setWorksheet($worksheet);
                    $bagong->setResizeProportional(true);
                    $bagong->setWidth(90);
                    $bagong->setHeight(70);
                    $bagong->setOffsetX(30);
                    $bagong->setOffsetY(8);
                }
            },
        ];
    }
}
