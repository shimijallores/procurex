<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\NumberToWords;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
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

class PurchaseOrderExport implements FromArray, ShouldAutoSize, WithEvents, WithStyles
{
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

        $poAmount = (float) ($po->total_amount ?? 0);
        $poAmountWords = NumberToWords::convert($poAmount);

        $rows = [];

        $rows[] = ['PURCHASE ORDER'];
        $rows[] = ['LGU'];
        $rows[] = [''];
        $rows[] = ['Supplier:', $supplierName, '', '', 'P.O. No.:', $po->po_no];
        $rows[] = ['Address:', $supplierAddress !== '' ? $supplierAddress : '________________________', '', '', 'Date:', optional($po->po_date)->format('m/d/Y')];
        $rows[] = ['', '', '', '', 'Mode of Procurement:', $po->mode_of_procurement];
        $rows[] = ['', '', '', '', 'P.R. No/s.:', $po->noa?->aoq?->rfq?->purchaseRequest?->pr_no ?? '—'];

        $rows[] = ['Sir/Madam:', 'Please furnish this office the following articles subject to the terms and conditions contained herein:'];
        $rows[] = [''];
        $rows[] = ['Place of Delivery:', strtoupper((string) ($po->place_of_delivery ?? '')), '', '', 'Delivery Term:', $deliveryDays > 0 ? 'within '.$deliveryDays.' calendar days upon receipt hereof' : '________________________'];
        $rows[] = ['Date of Delivery:', $deliveryText, '', '', 'Payment Term:', $po->payment_term ?: '________________________'];
        $rows[] = [''];

        $rows[] = ['ITEM NO.', 'UNIT', 'QTY', 'DESCRIPTION', 'UNIT COST', 'AMOUNT'];

        $counter = 0;
        $total = 0;

        foreach ($po->items as $item) {
            $counter++;
            $quantity = (int) ($item->quantity_snapshot ?? 0);
            $unitCost = (float) ($item->unit_cost_snapshot ?? 0);
            $amount = (float) ($item->amount_snapshot ?? 0);
            $total += $amount;
            $description = $item->rfqItem?->purchaseRequestItem?->item_name ?? '';

            $rows[] = [
                $counter,
                $item->rfqItem?->purchaseRequestItem?->unit ?? '',
                $quantity,
                $description,
                number_format($unitCost, 2),
                number_format($amount, 2),
            ];
        }

        $rows[] = ['', '', '', '', 'TOTAL (Php)', number_format($total, 2)];
        $rows[] = [''];
        $rows[] = ['Total Amount in Words:', $poAmountWords];
        $rows[] = [''];
        $rows[] = ['In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of one (1) percent for every day of delay shall be imposed.'];
        $rows[] = [''];
        $rows[] = ['Conforme:', '', '', 'Very truly yours,', '', ''];
        $rows[] = ['(Signature over printed name)', '', '', '', '', ''];
        $rows[] = ['Date', '', '', 'VILMA SANTOS - RECTO', '', ''];
        $rows[] = ['', '', '', 'Governor', '', ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 24],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 16],
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

                $worksheet->mergeCells('A1:F1');
                $worksheet->mergeCells('A2:F2');
                $worksheet->mergeCells('B8:F8');

                $worksheet->getColumnDimension('A')->setWidth(20);
                $worksheet->getColumnDimension('B')->setWidth(12);
                $worksheet->getColumnDimension('C')->setWidth(8);
                $worksheet->getColumnDimension('D')->setWidth(45);
                $worksheet->getColumnDimension('E')->setWidth(18);
                $worksheet->getColumnDimension('F')->setWidth(18);

                $headerRow = 13;
                $headerRange = sprintf('A%d:F%d', $headerRow, $headerRow);
                $headerStyle = $worksheet->getStyle($headerRange);
                $headerStyle->getFont()->setBold(true);
                $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFD9E2F3'));

                $dataStartRow = $headerRow + 1;
                $dataEndRow = $highestRow;
                $dataRange = sprintf('A%d:F%d', $dataStartRow, $dataEndRow);
                $worksheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $worksheet->getRowDimension($headerRow)->setRowHeight(22);
            },
        ];
    }
}
