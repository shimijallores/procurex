<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseRequestMatrixExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return [
            'CONTROL NO. / EMANATING NO.',
            'OFFICES / HOSPITALS',
            'ITEM DESCRIPTION',
            'PR NO.',
            'PR DATE',
            'AMOUNT BELOW 1M',
            'AMOUNT ABOVE 1M',
            'NEW AMOUNT',
            'ACCOUNT / CHARGED TO',
            'PERSON IN CHARGE (PR SECTION)',
            'PERSON IN CHARGE (BUDGETING)',
            'DATE RELEASE',
            'NEW DATE RELEASE',
            'REMARKS',
        ];
    }

    public function array(): array
    {
        return collect($this->rows)
            ->map(function (array $row): array {
                return [
                    $row['control_no'] ?? '',
                    $row['office_name'] ?? '',
                    $row['item_description'] ?? '',
                    $row['pr_no'] ?? '',
                    $row['pr_date'] ?? '',
                    $row['amount_below_1m'] ?? '',
                    $row['amount_above_1m'] ?? '',
                    $row['new_amount'] ?? '',
                    $row['account_name'] ?? '',
                    $row['pr_admin_name'] ?? '',
                    $row['budgeting_admin_name'] ?? '',
                    $row['date_release'] ?? '',
                    $row['new_date_release'] ?? '',
                    $row['remarks'] ?? '',
                ];
            })
            ->values()
            ->all();
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
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
                $range = sprintf('A1:%s%d', $highestColumn, $highestRow);

                $worksheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $worksheet->freezePane('A2');
                $worksheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
