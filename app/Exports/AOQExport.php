<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\AOQ;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AOQExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
    /**
     * @param  array<string, mixed>  $calculation
     */
    public function __construct(
        private readonly AOQ $aoq,
        private readonly array $calculation,
    ) {}

    public function array(): array
    {
        $rfq = $this->aoq->rfq;
        $supplierTotals = collect($this->calculation['supplier_totals'] ?? [])->take(3)->values();
        $rfqSuppliers = $rfq?->suppliers ?? collect();

        $rows = [];

        $rows[] = ['ABSTRACT OF QUOTATION'];
        $rows[] = ['Small Value Procurement'];
        $rows[] = [''];
        $rows[] = ['Project Name:', $rfq?->project_name ?? ''];
        $rows[] = ['Date:', \Carbon\Carbon::parse($this->aoq->aoq_date)->format('m/d/y')];
        $rows[] = [''];
        $supplierTotals->count();

        $headerRow1 = ['QTY', 'UNIT', 'PARTICULARS', 'APPROVED BUDGET FOR THE CONTRACT'];
        foreach ($supplierTotals as $supplier) {
            $headerRow1[] = $supplier['supplier_name'] ?? '';
            $headerRow1[] = '';
        }

        $headerRow2 = ['', '', '', ''];
        foreach ($supplierTotals as $supplier) {
            $headerRow2[] = 'UNIT COST';
            $headerRow2[] = 'TOTAL AMOUNT';
        }

        $rows[] = $headerRow1;
        $rows[] = $headerRow2;

        $abcGrandTotal = 0;

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
                number_format($abcLineTotal, 2),
            ];

            foreach ($supplierTotals as $supplierTotal) {
                $entry = $rfqSuppliers->firstWhere('supplier_id', $supplierTotal['supplier_id']);
                $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $rfqItem->id);
                $unitPrice = $supplierItem?->unit_price;
                $lineTotal = $unitPrice !== null ? ((float) $unitPrice * $quantity) : null;

                $row[] = $unitPrice !== null ? number_format((float) $unitPrice, 2) : '';
                $row[] = $lineTotal !== null ? number_format($lineTotal, 2) : '';
            }

            $rows[] = $row;
        }

        $totalRow = ['', '', 'GRAND TOTAL - P', number_format($abcGrandTotal, 2)];
        foreach ($supplierTotals as $supplierTotal) {
            $totalRow[] = '';
            $totalRow[] = number_format((float) ($supplierTotal['total_amount'] ?? 0), 2);
        }

        $rows[] = $totalRow;
        $rows[] = [''];

        $winnerName = $this->aoq->winnerSupplier?->name ?? 'N/A';
        $modeLabel = ((int) ($this->calculation['calculated_supplier_count'] ?? 0)) >= 2
            ? 'Lowest Calculated'
            : 'Single Calculated';

        $rows[] = [
            sprintf('After careful scrutiny, the quotation is recommended to be awarded to %s for being the supplier with the %s and Responsive Quotation.', $winnerName, $modeLabel),
        ];
        $rows[] = [''];
        $rows[] = ['APPROVED:'];
        $rows[] = [''];
        $rows[] = ['NOEL R. ROCAFORT', 'PEDRITO MARTIN M. DIJAN, JR.', 'ENGR. NERIO L. RONQUILLO, JR.', 'ATTY. LOUIE MARK M. DALAWAMPU'];
        $rows[] = ['BAC Member', 'BAC Member', 'BAC Member', 'BAC Member'];
        $rows[] = [''];
        $rows[] = ['ATTY. JOEL L. MONTEALTO', '', '', ''];
        $rows[] = ['BAC Chairperson', '', '', ''];

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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $worksheet = $afterSheet->sheet->getDelegate();
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();

                $titleMergeCol = Coordinate::stringFromColumnIndex($highestColumn);
                $worksheet->mergeCells(sprintf('A1:%s1', $titleMergeCol));
                $worksheet->mergeCells(sprintf('A2:%s2', $titleMergeCol));

                $headerRow = 7;
                $headerEndRow = $headerRow + 1;
                $headerRange = sprintf('A%d:%s%d', $headerRow, $highestColumn, $headerEndRow);
                $headerStyle = $worksheet->getStyle($headerRange);
                $headerStyle->getFont()->setBold(true);
                $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFD9E2F3'));

                $supplierTotals = collect($this->calculation['supplier_totals'] ?? [])->take(3)->values();
                $supplierCount = $supplierTotals->count();
                if ($supplierCount > 0) {
                    $startCol = 5;
                    foreach ($supplierTotals as $supplierTotal) {
                        $colLetter1 = Coordinate::stringFromColumnIndex($startCol);
                        $colLetter2 = Coordinate::stringFromColumnIndex($startCol + 1);
                        $worksheet->mergeCells(sprintf('%s7:%s7', $colLetter1, $colLetter2));
                        $startCol += 2;
                    }
                }

                $dataStartRow = $headerRow + 2;
                $dataEndRow = $highestRow;
                $dataRange = sprintf('A%d:%s%d', $dataStartRow, $highestColumn, $dataEndRow);
                $worksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $winnerRow = $dataEndRow - 9;
                if ($winnerRow > 0) {
                    $worksheet->mergeCells(sprintf('A%d:%s%d', $winnerRow, $highestColumn, $winnerRow));
                    $worksheet->getStyle('A'.$winnerRow)->getAlignment()->setWrapText(true);
                }

                $worksheet->getRowDimension($headerRow)->setRowHeight(22);
            },
        ];
    }
}
