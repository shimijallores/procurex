<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Orders - Batch {{ $batch->batch_no }}</title>
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
            line-height: 1.2;
        }

        .page {
            padding: 4mm 6mm 0;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .form {
            border: 1px solid #000;
            border-top: 2px solid #000;
            border-collapse: separate;
            border-spacing: 0;
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

        .small {
            font-size: 10px;
        }

        .tiny {
            font-size: 9px;
        }

        .logo-seal {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .header-title {
            font-size: 33px;
            line-height: 1;
            letter-spacing: 0.3px;
            margin-top: 3px;
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
            font-size: 10px;
        }

        .items .item-no {
            width: 8%;
        }

        .items .unit {
            width: 9%;
        }

        .items .qty {
            width: 10%;
        }

        .items .desc {
            width: 39%;
        }

        .items .unit-cost {
            width: 17%;
        }

        .items .amount {
            width: 17%;
        }

        .item-row td {
            height: 24px;
        }

        .item-row .desc-cell {
            line-height: 1.2;
        }

        .total-row td {
            font-weight: 700;
        }

        .words-row {
            height: 24px;
        }

        .penalty {
            text-align: center;
            line-height: 1.35;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }

        .sign-block {
            height: 110px;
        }

        .sig-line {
            display: inline-block;
            min-width: 220px;
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
        }

        .sig-line-sm {
            display: inline-block;
            min-width: 160px;
            border-top: 1px solid #000;
            padding-top: 2px;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
    $imagePath = static function (array $candidates): ?string {
    foreach ($candidates as $candidate) {
    $absolute = public_path('images/' . $candidate);

    if (! is_file($absolute)) {
    continue;
    }

    $mime = mime_content_type($absolute) ?: 'image/png';
    $content = file_get_contents($absolute);

    if ($content === false) {
    continue;
    }

    return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    return null;
    };

    $sealLogo = $imagePath(['batangas-seal.png']);
    @endphp

    @foreach ($purchaseOrders as $purchaseOrder)
        @php
            $noa = $purchaseOrder->noa;
            $resolution = $noa?->bacResolution;
            $aoq = $noa?->aoq ?? $resolution?->aoq;
            $rfq = $aoq?->rfq;
            $winnerSupplier = $aoq?->winnerSupplier;

            $supplierName = strtoupper((string) ($winnerSupplier?->name ?? 'SUPPLIER'));
            $supplierAddress = (string) ($winnerSupplier?->address ?? '');
            $projectName = (string) ($rfq?->project_name ?? $resolution?->project_name ?? '');

            $rows = collect($purchaseOrder->items ?? [])->values();
            $minRows = 15;
            $displayRows = $rows->take($minRows);
            $blankRows = max(0, $minRows - $displayRows->count());

            $deliveryDays = (int) ($purchaseOrder->delivery_term_days ?? 0);
            $deliveryText = $deliveryDays > 0
            ? 'within ' . $deliveryDays . ' calendar days upon receipt of this Purchase Order'
            : '________________________';

            $poAmount = (float) ($purchaseOrder->total_amount ?? 0);
            $poAmountWords = \App\Helpers\NumberToWords::convert($poAmount, 'centavos');
        @endphp
        <div class="page">
                <table class="form">
                    <tr>
                        <td colspan="8" class="center no-border" style="padding-top:6px; padding-bottom:2px;">
                            @if($sealLogo)
                            <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal">
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td colspan="8" class="center no-border bold" style="font-size:32px; line-height:1; padding-top:0; padding-bottom:0;">PURCHASE ORDER</td>
                    </tr>
                    <tr>
                        <td colspan="8" class="center no-border bold" style="font-size:21px; padding-top:0; padding-bottom:4px;">LGU</td>
                    </tr>

                    <tr>
                        <td style="width:16%;" class="no-border">Supplier</td>
                        <td style="width:2%;" class="center no-border">:</td>
                        <td style="width:48%;" class="no-border"><span class="u">{{ $supplierName }}</span></td>
                        <td style="width:18%;" class="no-border">P.O. No.</td>
                        <td style="width:2%;" class="center no-border">:</td>
                        <td style="width:14%;" class="right no-border"><span class="u">{{ $purchaseOrder->po_no }}</span></td>
                    </tr>

                    <tr>
                        <td class="no-border">Address</td>
                        <td class="center no-border">:</td>
                        <td class="no-border"><span class="u">{{ $supplierAddress !== '' ? $supplierAddress : '________________________' }}</span></td>
                        <td class="no-border">Date</td>
                        <td class="center no-border">:</td>
                        <td class="right no-border"><span class="u">{{ optional($purchaseOrder->po_date)->format('m/d/Y') }}</span></td>
                    </tr>

                    <tr>
                        <td class="no-border"></td>
                        <td class="center no-border"></td>
                        <td class="no-border"></td>
                        <td class="small no-border">Mode of Procurement</td>
                        <td class="center no-border">:</td>
                        <td class="right no-border"><span class="u">{{ $purchaseOrder->mode_of_procurement }}</span></td>
                    </tr>

                    <tr>
                        <td class="no-border"></td>
                        <td class="center no-border"></td>
                        <td class="no-border"></td>
                        <td class="no-border">P.R. No/s.</td>
                        <td class="center no-border">:</td>
                        <td class="right no-border"><span class="u">{{ $rfq?->purchaseRequest?->pr_no ?? '—' }}</span></td>
                    </tr>

                    <tr class="row-sir">
                        <td class="bold">Sir/Madam:</td>
                        <td colspan="5">Please furnish this office the following articles subject to the terms and conditions contained herein:</td>
                    </tr>

                    <tr class="row-mid">
                        <td class="no-border">Place of Delivery</td>
                        <td class="center no-border">:</td>
                        <td class="no-border"><span class="u">{{ strtoupper((string) ($purchaseOrder->place_of_delivery ?? '')) }}</span></td>
                        <td class="no-border">Delivery Term:</td>
                        <td colspan="2" class="right no-border"><span class="u">{{ $deliveryDays > 0 ? 'within ' . $deliveryDays . ' calendar days upon receipt hereof' : '________________________' }}</span></td>
                    </tr>

                    <tr class="row-mid">
                        <td class="no-border">Date of Delivery</td>
                        <td class="center no-border">:</td>
                        <td class="no-border"><span class="u">{{ $deliveryText }}</span></td>
                        <td class="no-border">Payment Term :</td>
                        <td colspan="2" class="right no-border"><span class="u">{{ $purchaseOrder->payment_term ?: '________________________' }}</span></td>
                    </tr>

                    <tr>
                        <td colspan="6" style="padding:0;">
                            <table class="items">
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
                                    @foreach($displayRows as $index => $item)
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
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td class="desc-cell">&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
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
                        <td colspan="6" style="padding:10px 4px;">
                            <span>(Total Amount in Words)</span>
                            <span class="u">{{ $poAmountWords }}</span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6" class="penalty">
                            In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of one (1) percent
                            for every day of delay shall be imposed.
                        </td>
                    </tr>

                    <tr class="sign-block">
                        <td colspan="3" style="vertical-align: top; padding-top: 18px;">
                            <div class="bold">Conforme:</div>
                            <div style="margin-top: 44px;" class="center">
                                <span class="sig-line">(Signature over printed name)</span>
                            </div>
                            <div style="margin-top: 18px;" class="center">
                                <span class="sig-line-sm">Date</span>
                            </div>
                        </td>
                        <td colspan="3" style="vertical-align: top; padding-top: 18px;" class="center">
                            <div style="margin-top: 18px;">Very truly yours,</div>
                            <div style="margin-top: 28px;"><span class="sig-line-sm">&nbsp;</span></div>
                            <div class="bold" style="margin-top: 6px;">VILMA SANTOS - RECTO</div>
                            <div>Governor</div>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6" class="small">
                            (In case of Negotiated Purchase pursuant to Section 369 (a) of RA 7160, this portion must be accomplished.)
                        </td>
                    </tr>

                    <tr>
                        <td colspan="6" class="no-border">
                            <span class="bold">Approved per Sangguniang Resolution No:</span>
                            <span style="display:inline-block; border-bottom:1px solid #000; width:58%; margin-left:6px;">&nbsp;</span>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" class="no-border">
                            <span class="bold">Certified Correct</span>
                            <span style="display:inline-block; border-bottom:1px solid #000; width:45%; margin-left:6px;">&nbsp;</span>
                            <div class="center" style="margin-top:2px;">Secretary to the Sanggunian</div>
                        </td>
                        <td colspan="2" class="no-border">
                            <span class="bold">Date:</span>
                            <span style="display:inline-block; border-bottom:1px solid #000; width:70%; margin-left:6px;">&nbsp;</span>
                        </td>
                    </tr>
                </table>
            </div>
    @endforeach
</body>
</html>
