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

class SvpMatrixExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return [
            '#',
            'OFFICE',
            'PO NO.',
            'MODE OF PROCUREMENT',
            'PR NO.',
            'ABC',
            'SUPPLIER',
            'PARTICULARS',
            'AMOUNT',
            'RFQ',
            'ABSTRACT',
            'RESOLUTION',
            'NOA & PO',
            'TRANSMITTAL FORM',
            'ADMIN',
            'FRONTDESK',
            'REMARKS',
        ];
    }

    public function array(): array
    {
        return collect($this->rows)
            ->map(function (array $row): array {
                return [
                    $row['row_no'] ?? '',
                    $row['office'] ?? '',
                    $row['po_no'] ?? '',
                    $row['mode_of_procurement'] ?? '',
                    $row['pr_no'] ?? '',
                    $row['abc'] ?? '',
                    $row['supplier'] ?? '',
                    $row['particulars'] ?? '',
                    $row['amount'] ?? '',
                    $row['rfq'] ?? '',
                    $row['abstract'] ?? '',
                    $row['resolution'] ?? '',
                    $row['noa_po'] ?? '',
                    $row['transmittal_form'] ?? '',
                    $row['admin'] ?? '',
                    $row['frontdesk'] ?? '',
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
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = sprintf('A1:%s%d', $highestColumn, $highestRow);

                $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A2');
                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}
