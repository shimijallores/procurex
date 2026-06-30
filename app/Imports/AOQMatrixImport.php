<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\RFQ;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AOQMatrixImport implements ToCollection, WithStartRow
{
    public array $parsedUnitPrices = [];

    protected RFQ $rfq;

    protected int $expectedSupplierCount;

    public function __construct(RFQ $rfq, int $expectedSupplierCount)
    {
        $this->rfq = $rfq;
        $this->expectedSupplierCount = $expectedSupplierCount;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows): void
    {
        $rfqItems = $this->rfq->items()->with('purchaseRequestItem')->get();
        $supplierCount = $this->expectedSupplierCount;

        $this->parsedUnitPrices = [];

        foreach ($rows as $rowIndex => $row) {
            $rowData = $row->toArray();

            if ($rowIndex >= $rfqItems->count()) {
                break;
            }

            $rfqItem = $rfqItems->get($rowIndex);

            if (! $rfqItem) {
                continue;
            }

            // Columns: 0=Item Name, 1=Qty, 2=Unit, 3=Expected Price, 4+=Supplier Prices
            for ($s = 0; $s < $supplierCount; $s++) {
                $colIndex = 4 + $s;
                $rawPrice = $rowData[$colIndex] ?? null;

                if ($rawPrice === null || $rawPrice === '' || (is_string($rawPrice) && trim($rawPrice) === '')) {
                    continue;
                }

                if (! isset($this->parsedUnitPrices[$s])) {
                    $this->parsedUnitPrices[$s] = [];
                }

                $this->parsedUnitPrices[$s][$rfqItem->id] = (float) $rawPrice;
            }
        }
    }
}
