<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\RFQ;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RFQExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    public function __construct(private readonly RFQ $rfq) {}

    public function array(): array
    {
        $rows = [];

        $rows[] = ['REQUEST FOR QUOTATION (RFQ)'];
        $rows[] = ['Small Value Procurement (SVP)'];
        $rows[] = [''];
        $rows[] = ['SVP No.:', $this->rfq->svp_no];
        $rows[] = ['PR No.:', $this->rfq->purchaseRequest?->pr_no ?? ''];
        $rows[] = ['Date:', ''];
        $rows[] = ['Company Name:', ''];
        $rows[] = ['Address:', ''];
        $rows[] = ['Contact Details:', ''];
        $rows[] = [''];
        $rows[] = ['PROJECT NAME:', $this->rfq->project_name];
        $rows[] = ['APPROVED BUDGET FOR THE CONTRACT (ABC):', 'Php '.number_format((float) $this->rfq->abc_amount, 2)];
        $rows[] = [''];
        $rows[] = ['ITEM NO.', 'ITEM & DESCRIPTION', 'QTY', 'UNIT', 'PR PRICE', 'UNIT PRICE', 'TOTAL AMOUNT'];

        $counter = 0;
        $grandTotal = 0;

        foreach ($this->rfq->items as $item) {
            $counter++;
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

            $grandTotal += $prPrice * (int) ($item->quantity ?? 0);
        }

        $rows[] = ['', '', '', '', '', 'GRAND TOTAL:', number_format($grandTotal, 2)];
        $rows[] = [''];
        $rows[] = ['Total Amount in Words:', '', '', '', '', '', ''];
        $rows[] = ['', '', '', '', '', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            11 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $worksheet = $afterSheet->sheet->getDelegate();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();

                $headerRow = 14;
                $headerRange = sprintf('A%d:%s%d', $headerRow, $highestColumn, $headerRow);
                $headerStyle = $worksheet->getStyle($headerRange);
                $headerStyle->getFont()->setBold(true);
                $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFD9E2F3'));

                $dataStartRow = $headerRow + 1;
                $dataEndRow = $highestRow;
                $dataRange = sprintf('A%d:%s%d', $dataStartRow, $highestColumn, $dataEndRow);
                $worksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $worksheet->mergeCells('A1:G1');
                $worksheet->mergeCells('A2:G2');
                $worksheet->mergeCells('A11:B11');
                $worksheet->mergeCells('A12:B12');

                $worksheet->getColumnDimension('A')->setWidth(12);
                $worksheet->getColumnDimension('B')->setWidth(40);
                $worksheet->getColumnDimension('C')->setWidth(10);
                $worksheet->getColumnDimension('D')->setWidth(10);
                $worksheet->getColumnDimension('E')->setWidth(16);
                $worksheet->getColumnDimension('F')->setWidth(16);
                $worksheet->getColumnDimension('G')->setWidth(18);

                $worksheet->getRowDimension(1)->setRowHeight(22);
                $worksheet->getRowDimension(14)->setRowHeight(22);
            },
        ];
    }
}
