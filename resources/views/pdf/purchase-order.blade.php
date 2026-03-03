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
            font-size: 10.5pt;
            color: #000;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
        }

        .top-right-meta {
            width: 100%;
            text-align: right;
            font-size: 10pt;
            line-height: 1.35;
            margin-bottom: 10px;
        }

        .header-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .header-layout td {
            vertical-align: middle;
        }

        .header-logo-cell {
            width: 18%;
            text-align: center;
        }

        .header-main {
            width: 64%;
            text-align: center;
        }

        .logo-seal {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .logo-bagong {
            width: 74px;
            height: 58px;
            object-fit: contain;
        }

        .header-title {
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.25;
        }

        .header-sub {
            margin-top: 4px;
            font-size: 10pt;
        }

        .doc-title {
            margin-top: 10px;
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .divider {
            border-top: 2px solid #000;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .details td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        .details .label {
            width: 17%;
            font-weight: 700;
            background: #f5f5f5;
        }

        .details .value {
            width: 33%;
        }

        .details .label-right {
            width: 17%;
            font-weight: 700;
            background: #f5f5f5;
        }

        .details .value-right {
            width: 33%;
        }

        .intro {
            margin-top: 8px;
            line-height: 1.45;
            text-align: justify;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .items th,
        .items td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        .items th {
            text-align: center;
            font-weight: 700;
        }

        .items .col-no {
            width: 7%;
            text-align: center;
        }

        .items .col-unit {
            width: 9%;
            text-align: center;
        }

        .items .col-qty {
            width: 9%;
            text-align: center;
        }

        .items .col-desc {
            width: 39%;
        }

        .items .col-unit-cost {
            width: 18%;
            text-align: right;
        }

        .items .col-amount {
            width: 18%;
            text-align: right;
        }

        .items .item-row td {
            min-height: 30px;
        }

        .items .total-row td {
            font-weight: 700;
        }

        .amount-words {
            margin-top: 8px;
            border: 1px solid #000;
            padding: 8px;
            font-size: 10.5pt;
        }

        .amount-words .label {
            font-weight: 700;
        }

        .penalty {
            margin-top: 10px;
            border: 1px solid #000;
            padding: 8px;
            line-height: 1.45;
            text-align: justify;
        }

        .signatures {
            width: 100%;
            margin-top: 22px;
        }

        .signatures td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 8px;
        }

        .sig-line {
            display: inline-block;
            min-width: 220px;
            border-top: 1px solid #000;
            padding-top: 4px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .sig-sub {
            margin-top: 2px;
            font-size: 10pt;
        }

        .mt-24 {
            margin-top: 24px;
        }

        .mt-32 {
            margin-top: 32px;
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
    $bagongLogo = $imagePath(['bagong-pilipinas.png']);

    $winnerSupplierName = strtoupper((string) ($winnerSupplier?->name ?? 'SUPPLIER'));
    $winnerAddress = (string) ($winnerSupplier?->address ?? '');
    $projectName = (string) ($rfq?->project_name ?? $resolution?->project_name ?? '—');

    $rows = collect($purchaseOrder->items ?? [])->values();
    $minRows = 10;
    $emptyRows = max(0, $minRows - $rows->count());
    @endphp

    <div class="page">
        <div class="top-right-meta">
            <div><strong>PR No.:</strong> {{ $rfq?->purchaseRequest?->pr_no ?? '—' }}</div>
            <div><strong>NOA No.:</strong> {{ $noa?->noa_no ?? '—' }}</div>
            <div><strong>BAC Resolution No.:</strong> {{ $resolution?->resolution_no ?? '—' }}</div>
        </div>

        <table class="header-layout">
            <tr>
                <td class="header-logo-cell">
                    @if ($sealLogo)
                    <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal">
                    @endif
                </td>
                <td class="header-main">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-title">Provincial Government of Batangas</div>
                    <div class="header-sub">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
                    <div class="doc-title">Purchase Order</div>
                </td>
                <td class="header-logo-cell">
                    @if ($bagongLogo)
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                    @endif
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="details">
            <tr>
                <td class="label">Supplier</td>
                <td class="value">{{ $winnerSupplierName }}</td>
                <td class="label-right">P.O. No.</td>
                <td class="value-right">{{ $purchaseOrder->po_no }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td class="value">{{ $winnerAddress !== '' ? $winnerAddress : '—' }}</td>
                <td class="label-right">P.O. Date</td>
                <td class="value-right">{{ optional($purchaseOrder->po_date)->format('F d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Mode of Procurement</td>
                <td class="value">{{ $purchaseOrder->mode_of_procurement }}</td>
                <td class="label-right">Delivery Term</td>
                <td class="value-right">
                    @if($purchaseOrder->delivery_term_days)
                    {{ (int) $purchaseOrder->delivery_term_days }} calendar days
                    @else
                    —
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Place of Delivery</td>
                <td class="value">{{ $purchaseOrder->place_of_delivery }}</td>
                <td class="label-right">Payment Term</td>
                <td class="value-right">{{ $purchaseOrder->payment_term ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Project</td>
                <td class="value" colspan="3">{{ $projectName }}</td>
            </tr>
        </table>

        <div class="intro">
            Please furnish this office the following item(s), subject to the terms and conditions stated herein and in the referenced procurement documents.
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th class="col-no">Item No.</th>
                    <th class="col-unit">Unit</th>
                    <th class="col-qty">Qty</th>
                    <th class="col-desc">Description</th>
                    <th class="col-unit-cost">Unit Cost (Php)</th>
                    <th class="col-amount">Amount (Php)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $index => $item)
                <tr class="item-row">
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-unit">{{ $item->rfqItem?->purchaseRequestItem?->unit ?? '' }}</td>
                    <td class="col-qty">{{ (int) $item->quantity_snapshot }}</td>
                    <td class="col-desc">{{ $item->rfqItem?->purchaseRequestItem?->item_name ?? '' }}</td>
                    <td class="col-unit-cost">{{ number_format((float) $item->unit_cost_snapshot, 2) }}</td>
                    <td class="col-amount">{{ number_format((float) $item->amount_snapshot, 2) }}</td>
                </tr>
                @endforeach

                @for($i = 0; $i < $emptyRows; $i++)
                    <tr class="item-row">
                    <td class="col-no">&nbsp;</td>
                    <td class="col-unit">&nbsp;</td>
                    <td class="col-qty">&nbsp;</td>
                    <td class="col-desc">&nbsp;</td>
                    <td class="col-unit-cost">&nbsp;</td>
                    <td class="col-amount">&nbsp;</td>
                    </tr>
                    @endfor

                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;">TOTAL (Php)</td>
                        <td class="col-amount">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                    </tr>
            </tbody>
        </table>

        <div class="amount-words">
            <span class="label">Total Amount in Words:</span>
            {{ $purchaseOrder->total_amount_words ?: '—' }}
        </div>

        <div class="penalty">
            In case of failure to make complete delivery within the period specified above, a penalty of one-tenth (1/10) of one percent (1%) for every day of delay shall be imposed on the undelivered portion.
        </div>

        <table class="signatures">
            <tr>
                <td>
                    <div>Conforme:</div>
                    <div class="mt-32">
                        <span class="sig-line">{{ $winnerSupplierName }}</span>
                    </div>
                    <div class="sig-sub">Supplier / Authorized Signatory</div>
                    <div class="mt-24">Date: ______________________</div>
                </td>
                <td>
                    <div>Very truly yours,</div>
                    <div class="mt-32">
                        <span class="sig-line">VILMA SANTOS-RECTO</span>
                    </div>
                    <div class="sig-sub">Governor</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>