<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchaseRequestMatrixExport implements FromArray, WithHeadings
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
}
