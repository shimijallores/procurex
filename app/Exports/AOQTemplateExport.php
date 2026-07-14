<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\RFQ;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AOQTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithStyles
{
    protected RFQ $rfq;

    protected int $supplierCount;

    /** @var array<int, string> */
    protected array $supplierNames;

    /**
     * @param  array<int, string>  $supplierNames
     */
    public function __construct(RFQ $rfq, int $supplierCount, array $supplierNames)
    {
        $this->rfq = $rfq;
        $this->supplierCount = $supplierCount;
        $this->supplierNames = $supplierNames;
    }

    /**
     * @return array<int, array<int, string|int|float|null>>
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->rfq->items as $item) {
            $row = [
                $item->item_name ?? $item->purchaseRequestItem?->item_name ?? '—',
                (int) ($item->quantity ?? 0),
                $item->unit ?? $item->purchaseRequestItem?->unit ?? '—',
                (float) ($item->purchaseRequestItem?->unit_cost ?? 0),
            ];

            for ($i = 0; $i < $this->supplierCount; ++$i) {
                $row[] = null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        $headings = ['Item Name', 'Qty', 'Unit', 'Expected Price'];

        for ($i = 0; $i < $this->supplierCount; ++$i) {
            $headings[] = ($this->supplierNames[$i] ?? 'Supplier '.($i + 1)).' Unit Price';
        }

        return $headings;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = chr(65 + 3 + $this->supplierCount); // A + 4 headings + supplier columns - 1

        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
            'A:'.$lastColumn => [
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    /**
     * @return array<int, \Maatwebsite\Excel\Events\AfterSheet>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $afterSheet): void {
                $sheet = $afterSheet->sheet;
                $lastColumn = chr(65 + 3 + $this->supplierCount);
                $lastRow = $sheet->getHighestRow();

                $range = 'A1:'.$lastColumn.$lastRow;

                $sheet->getStyle($range)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle($range)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1:'.$lastColumn.'1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFE8F0FE');

                $sheet->getStyle('D2:D'.$lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $priceColumns = [];
                for ($i = 0; $i < $this->supplierCount; ++$i) {
                    $col = chr(69 + $i); // E, F, G, ...
                    $priceColumns[] = $col;
                }

                foreach ($priceColumns as $priceColumn) {
                    if ($priceColumn <= $lastColumn) {
                        $sheet->getStyle($priceColumn.'2:'.$priceColumn.$lastRow)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }

                $sheet->getColumnDimension('A')->setWidth(40);
            },
        ];
    }
}
