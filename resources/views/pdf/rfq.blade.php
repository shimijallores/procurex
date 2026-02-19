<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RFQ - {{ $rfq->svp_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
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

        .mt-5 {
            margin-top: 20px;
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

        .line-row {
            display: flex;
            gap: 8px;
            align-items: baseline;
            margin-top: 4px;
        }

        .line-label {
            min-width: 120px;
            font-weight: bold;
        }

        .line-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding: 0 2px;
        }

        .body-copy {
            margin-top: 16px;
            line-height: 1.4;
            text-align: justify;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            border: 1px solid #000;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
            font-size: 9pt;
        }

        .table th {
            text-align: center;
            font-weight: bold;
        }

        .col-item-no {
            width: 10%;
            text-align: center;
        }

        .col-desc {
            width: 48%;
        }

        .col-qty {
            width: 8%;
            text-align: center;
        }

        .col-unit {
            width: 10%;
            text-align: center;
        }

        .col-unit-price {
            width: 12%;
            text-align: right;
        }

        .col-total {
            width: 12%;
            text-align: right;
        }

        .total-row td {
            font-weight: bold;
        }

        .footer-line {
            margin-top: 10px;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .footer-label {
            font-weight: bold;
            min-width: 160px;
        }

        .footer-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 18px;
            padding: 2px;
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="text-center">
            <div class="header-title">Republic of the Philippines</div>
            <div class="header-title">Provincial Government of Batangas</div>
            <div class="mt-1">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
            <div class="mt-2 subtitle">Bids and Awards Committee</div>
            <div class="mt-3 title">Request for Quotation (RFQ)</div>
            <div class="subtitle">Small Value Procurement (SVP)</div>
            <div class="mt-1">SVP No.: <strong>{{ $rfq->svp_no }}</strong></div>
        </div>

        <div class="mt-4">
            <div class="line-row">
                <div class="line-label">Date:</div>
                <div class="line-value">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('m/d/Y') }}</div>
            </div>
            <div class="line-row">
                <div class="line-label">Company Name:</div>
                <div class="line-value">{{ $supplierEntry->supplier?->name }}</div>
            </div>
            <div class="line-row">
                <div class="line-label">Address:</div>
                <div class="line-value">{{ $supplierEntry->supplier?->address }}</div>
            </div>
            <div class="line-row">
                <div class="line-label">Contact Details:</div>
                <div class="line-value">{{ $supplierEntry->supplier?->contact_number }}</div>
            </div>
        </div>

        <div class="body-copy">
            The Provincial Government of Batangas, through its Bids and Awards Committee (BAC), invites suppliers to submit
            price quotations for the procurement of the item/s described below, taking into consideration the stated
            Procurement Terms and Conditions.
        </div>

        <div class="mt-4">
            <strong>PROJECT NAME:</strong>
            <span style="text-decoration: underline;">{{ $rfq->project_name }}</span>
        </div>

        <div class="mt-3">
            <strong>APPROVED BUDGET FOR THE CONTRACT (ABC):</strong>
            Php <strong>{{ number_format((float) $rfq->abc_amount, 2) }}</strong>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th class="col-item-no">ITEM NO.</th>
                    <th class="col-desc">ITEM & DESCRIPTION</th>
                    <th class="col-qty">QTY</th>
                    <th class="col-unit">UNIT</th>
                    <th class="col-unit-price">UNIT PRICE</th>
                    <th class="col-total">TOTAL AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @php
                $supplierItems = $supplierEntry->supplierItems ?? collect();
                $minRows = 15;
                $emptyRows = max(0, $minRows - $supplierItems->count());
                @endphp

                @foreach ($supplierItems as $index => $supplierItem)
                @php
                $prItem = $supplierItem->rfqItem?->purchaseRequestItem;
                $quantity = (int) ($prItem?->quantity ?? 0);
                $itemName = $prItem?->item_name ?? $prItem?->emanatingItem?->ppmpItem?->name ?? '';
                $unit = $prItem?->unit ?? $prItem?->emanatingItem?->unit ?? '';
                $unitPrice = $supplierItem->unit_price;
                $lineTotal = $unitPrice !== null ? ((float) $unitPrice * $quantity) : null;
                @endphp
                <tr>
                    <td class="col-item-no">{{ $index + 1 }}</td>
                    <td class="col-desc">{{ $itemName }}</td>
                    <td class="col-qty">{{ $quantity }}</td>
                    <td class="col-unit">{{ $unit }}</td>
                    <td class="col-unit-price">{{ $unitPrice !== null ? number_format((float) $unitPrice, 2) : '' }}</td>
                    <td class="col-total">{{ $lineTotal !== null ? number_format($lineTotal, 2) : '' }}</td>
                </tr>
                @endforeach

                @for ($i = 0; $i < $emptyRows; $i++)
                    <tr>
                    <td class="col-item-no" style="height: 20px;">&nbsp;</td>
                    <td class="col-desc"></td>
                    <td class="col-qty"></td>
                    <td class="col-unit"></td>
                    <td class="col-unit-price"></td>
                    <td class="col-total"></td>
                    </tr>
                    @endfor

                    <tr class="total-row">
                        <td colspan="5" class="text-right">TOTAL AMOUNT:</td>
                        <td class="col-total">{{ $totalQuotedAmount > 0 ? number_format((float) $totalQuotedAmount, 2) : '' }}</td>
                    </tr>
            </tbody>
        </table>

        <div class="footer-line">
            <div class="footer-label">Total Amount in Words:</div>
            <div class="footer-value">{{ $totalQuotedAmount > 0 ? $totalAmountInWords : '' }}</div>
        </div>
    </div>
</body>

</html>