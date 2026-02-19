<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PO - {{ $purchaseOrder->po_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 11px;
            line-height: 1.25;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 4mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .form {
            border: 1px solid #000;
        }

        .form td,
        .form th {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: top;
        }

        .no-border {
            border: 0 !important;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: 700;
        }

        .u {
            text-decoration: underline;
            font-weight: 700;
        }

        .h-top {
            height: 18px;
        }

        .label {
            width: 16%;
            white-space: nowrap;
        }

        .colon {
            width: 2%;
            text-align: center;
            white-space: nowrap;
        }

        .left-value {
            width: 48%;
        }

        .right-label {
            width: 18%;
            white-space: nowrap;
        }

        .right-value {
            width: 16%;
            white-space: nowrap;
        }

        .row-sir {
            height: 26px;
        }

        .row-mid {
            height: 22px;
        }

        .items th {
            text-align: center;
            font-weight: 700;
        }

        .items .item-no {
            width: 7%;
        }

        .items .unit {
            width: 8%;
        }

        .items .qty {
            width: 9%;
        }

        .items .desc {
            width: 37%;
        }

        .items .unit-cost {
            width: 19%;
        }

        .items .amount {
            width: 20%;
        }

        .item-row td {
            height: 40px;
        }

        .item-row td.desc-cell {
            line-height: 1.3;
        }

        .total-row td {
            font-weight: 700;
        }

        .words-row {
            height: 22px;
        }

        .penalty {
            height: 34px;
            font-weight: 700;
        }

        .sign-block {
            height: 110px;
        }

        .sig-line {
            display: inline-block;
            min-width: 190px;
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
        }

        .sig-line-sm {
            display: inline-block;
            min-width: 140px;
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
        }

        .tiny {
            font-size: 10px;
        }

        .no-border {
            border: 0 !important;
        }
    </style>
</head>

<body>
    @php
    $winnerSupplierName = strtoupper((string) ($winnerSupplier?->name ?? 'SUPPLIER'));
    $winnerAddress = (string) ($winnerSupplier?->address ?? '');
    $items = collect($purchaseOrder->items ?? [])->values();

    $displayItems = $items->take(8);
    $blankRows = max(0, 8 - $displayItems->count());
    @endphp

    <div class="page">
        <table class="form">
            <tr class="h-top">
                <td class="center bold" colspan="8" style="font-size: 16px; letter-spacing: 0.5px;">PURCHASE ORDER</td>
            </tr>

            <tr>
                <td class="label bold no-border">Supplier</td>
                <td class="bold no-border">:</td>
                <td class="left-value no-border" colspan="4"><span class="u">{{ $winnerSupplierName }}</span></td>
                <td class="right-label bold no-border">P.O. No.</td>
                <td class="right-value right no-border"><span class="u">{{ $purchaseOrder->po_no }}</span></td>
            </tr>

            <tr>
                <td class="label bold no-border">Address</td>
                <td class="colon bold no-border">:</td>
                <td class="left-value no-border"><span class="u">{{ $winnerAddress }}</span></td>
                <td class="right-label no-border"></td>
                <td class="colon no-border"></td>
                <td class="right-value no-border"></td>
                <td class="right-label bold no-border">Date</td>
                <td class="right-value right no-border"><span class="u">{{ optional($purchaseOrder->po_date)->format('m/d/Y') }}</span></td>
            </tr>

            <tr>
                <td class="label no-border"></td>
                <td class="colon no-border"></td>
                <td class="left-value no-border"></td>
                <td class="right-label no-border"></td>
                <td class="colon no-border"></td>
                <td class="right-value no-border"></td>
                <td class="right-label bold no-border">Mode of Procurement</td>
                <td class="right-value right no-border"><span class="u">{{ $purchaseOrder->mode_of_procurement }}</span></td>
            </tr>

            <tr>
                <td class="label no-border"></td>
                <td class="colon no-border"></td>
                <td class="left-value no-border"></td>
                <td class="right-label no-border"></td>
                <td class="colon no-border"></td>
                <td class="right-value no-border"></td>
                <td class="right-label bold no-border">P.R. No/s.</td>
                <td class="right-value right no-border"><span class="u">{{ $rfq?->purchaseRequest?->pr_no ?? '—' }}</span></td>
            </tr>

            <tr class="row-sir">
                <td class="bold">Sir/Madam:</td>
                <td colspan="7">Please furnish this office the following articles subject to the terms and conditions contained herein:</td>
            </tr>

            <tr class="row-mid">
                <td class="label bold no-border">Place of Delivery</td>
                <td class="colon bold no-border">:</td>
                <td class="left-value no-border"><span class="u">{{ strtoupper((string) ($purchaseOrder->place_of_delivery ?? '')) }}</span></td>
                <td class="right-label bold no-border" colspan="2">Delivery Term:</td>
                <td colspan="3" class="no-border"><span class="u">within {{ (int) ($purchaseOrder->delivery_term_days ?? 0) }} calendar days upon receipt hereof</span></td>
            </tr>

            <tr class="row-mid">
                <td class="label bold no-border">Date of Delivery</td>
                <td class="colon bold no-border">:</td>
                <td class="left-value no-border"><span class="u">within {{ (int) ($purchaseOrder->delivery_term_days ?? 0) }} calendar days upon receipt of this Purchase Order</span></td>
                <td class="right-label bold no-border" colspan="2">Payment Term :</td>
                <td colspan="3" class="no-border"><span class="u">{{ $purchaseOrder->payment_term }}</span></td>
            </tr>

            <tr>
                <td colspan="8" style="padding: 0;">
                    <table class="items" style="border-collapse: collapse; width: 100%;">
                        <thead>
                            <tr>
                                <th class="item-no">ITEM NO.</th>
                                <th class="unit">UNIT</th>
                                <th class="qty">QTY</th>
                                <th class="desc">DESCRIPTION</th>
                                <th class="unit-cost">UNIT COST</th>
                                <th class="amount">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($displayItems as $index => $item)
                            <tr class="item-row">
                                <td class="center">{{ $index + 1 }}</td>
                                <td class="center">{{ $item->rfqItem?->purchaseRequestItem?->unit ?? '' }}</td>
                                <td class="center">{{ (int) $item->quantity_snapshot }}</td>
                                <td class="desc-cell">{{ $item->rfqItem?->purchaseRequestItem?->item_name ?? '' }}</td>
                                <td class="right">{{ number_format((float) $item->unit_cost_snapshot, 2) }}</td>
                                <td class="right">{{ number_format((float) $item->amount_snapshot, 2) }}</td>
                            </tr>
                            @endforeach

                            @for($i = 0; $i < $blankRows; $i++)
                                <tr class="item-row">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="desc-cell"></td>
                                <td></td>
                                <td></td>
            </tr>
            @endfor

            <tr class="total-row">
                <td colspan="5" class="right">TOTAL (Php)</td>
                <td class="right">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
            </tr>
            </tbody>
        </table>
        </td>
        </tr>

        <tr class="words-row">
            <td colspan="8" style="padding: 12px 4px;" class="bold">
                (Total Amount in Words)
                <span class="u">{{ $purchaseOrder->total_amount_words ?: '—' }}</span>
            </td>
        </tr>

        <tr class="penalty">
            <td colspan="8 no-border">
                In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of one (1) percent
                for every day of delay shall be imposed.
            </td>
        </tr>

        <tr class="sign-block">
            <td colspan="4" style="vertical-align: top; padding-top: 20px;">
                <div class="bold">Conforme:</div>
                <div style="margin-top: 42px;" class="center">
                    <span class="sig-line">(Signature over printed name)</span>
                </div>
                <div style="margin-top: 14px;" class="center">
                    <span class="sig-line-sm">Date</span>
                </div>
            </td>
            <td colspan="4" style="vertical-align: top; padding-top: 20px;">
                <div class="center" style="margin-top: 12px;">Very truly yours,</div>
                <div class="center" style="margin-top: 30px;">
                    <span class="sig-line">VILMA SANTOS - RECTO</span>
                </div>
                <div class="center">Governor</div>
            </td>
        </tr>

        <tr>
            <td colspan="8" class="bold tiny">(In case of Negotiated Purchase pursuant to Section 369 (a) of RA 7160, this portion must be accomplished.)</td>
        </tr>

        <tr>
            <td colspan="8 no-border">
                <span class="bold">Approved per Sangguniang Resolution No:</span>
                <span style="display: inline-block; border-bottom: 1px solid #000; width: 62%; margin-left: 6px;">&nbsp;</span>
            </td>
        </tr>

        <tr>
            <td colspan="5 no-border">
                <span class="bold">Certified Correct:</span>
                <span style="display: inline-block; border-bottom: 1px solid #000; width: 52%; margin-left: 6px;">&nbsp;</span>
                <div class="center bold" style="margin-top: 2px;">Secretary to the Sanggunian</div>
            </td>
            <td colspan="3 no-border">
                <span class="bold">Date:</span>
                <span style="display: inline-block; border-bottom: 1px solid #000; width: 72%; margin-left: 6px;">&nbsp;</span>
            </td>
        </tr>
        </table>
    </div>
</body>

</html>