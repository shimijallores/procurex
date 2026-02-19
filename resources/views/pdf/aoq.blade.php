<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AOQ - {{ $rfq->svp_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 8mm;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-2 {
            margin-top: 8px;
        }

        .mt-3 {
            margin-top: 12px;
        }

        .mt-4 {
            margin-top: 16px;
        }

        .header-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 11pt;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .winner-line {
            margin-top: 10px;
            line-height: 1.4;
        }

        .signature {
            margin-top: 20px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="text-center">
            <div class="header-title">Republic of the Philippines</div>
            <div class="header-title">Provincial Government of Batangas</div>
            <div class="mt-1">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
            <div class="mt-1 subtitle">Bids and Awards Committee</div>
            <div class="title mt-2">Abstract of Quotation</div>
            <div class="subtitle">Small Value Procurement</div>
        </div>

        <div class="mt-3"><strong>Project Name:</strong> {{ $rfq->project_name }}</div>
        <div class="mt-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($aoq->aoq_date)->format('F d, Y') }}</div>
        <div class="mt-1"><strong>SVP No.:</strong> {{ $rfq->svp_no }}</div>

        @php
        $supplierTotals = collect($calculation['supplier_totals'] ?? [])->take(3)->values();
        $rfqSuppliers = $rfq->suppliers ?? collect();
        @endphp

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width:5%">QTY</th>
                    <th rowspan="2" style="width:8%">UNIT</th>
                    <th rowspan="2" style="width:30%">PARTICULARS</th>
                    <th rowspan="2" style="width:10%">APPROVED BUDGET FOR THE CONTRACT</th>
                    @foreach($supplierTotals as $supplier)
                    <th colspan="2" style="width:15%">{{ $supplier['supplier_name'] }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($supplierTotals as $supplier)
                    <th>UNIT COST</th>
                    <th>TOTAL AMOUNT</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach(($rfq->items ?? collect()) as $rfqItem)
                @php
                $prItem = $rfqItem->purchaseRequestItem;
                @endphp
                <tr>
                    <td>{{ (int) ($prItem?->quantity ?? 0) }}</td>
                    <td>{{ $prItem?->unit ?? $prItem?->emanatingItem?->unit ?? '' }}</td>
                    <td>{{ $prItem?->item_name ?? $prItem?->emanatingItem?->ppmpItem?->name ?? '' }}</td>
                    <td class="text-right">{{ number_format((float) ($prItem?->line_total ?? 0), 2) }}</td>

                    @foreach($supplierTotals as $supplier)
                    @php
                    $entry = $rfqSuppliers->firstWhere('supplier_id', $supplier['supplier_id']);
                    $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $rfqItem->id);
                    $unitPrice = $supplierItem?->unit_price;
                    $lineTotal = $unitPrice !== null ? ((float) $unitPrice * (float) ($prItem?->quantity ?? 0)) : null;
                    @endphp
                    <td class="text-right">{{ $unitPrice !== null ? number_format((float) $unitPrice, 2) : '' }}</td>
                    <td class="text-right">{{ $lineTotal !== null ? number_format((float) $lineTotal, 2) : '' }}</td>
                    @endforeach
                </tr>
                @endforeach

                <tr>
                    <td colspan="3" class="text-right"><strong>GRAND TOTAL - P</strong></td>
                    <td class="text-right"><strong>{{ number_format((float) ($rfq->abc_amount ?? 0), 2) }}</strong></td>
                    @foreach($supplierTotals as $supplier)
                    <td></td>
                    <td class="text-right"><strong>{{ number_format((float) $supplier['total_amount'], 2) }}</strong></td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <div class="winner-line">
            After our careful scrutiny and deliberation of the submitted quotation of the supplier as reflected in this Abstract of Quotation,
            we strongly recommend the quotation to be given to
            <strong style="text-decoration: underline">{{ $aoq->winnerSupplier?->name ?? 'N/A' }}</strong>
            for being the supplier with the
            <strong style="text-decoration: underline">{{ ($calculation['calculation_mode'] ?? 'single_calculated') === 'single_calculated' ? 'Single Calculated' : 'Lowest Calculated' }} and Responsive Quotation</strong>
            which is advantageous to the Provincial Government of Batangas.
        </div>

        <div class="signature">APPROVED:</div>
    </div>
</body>

</html>