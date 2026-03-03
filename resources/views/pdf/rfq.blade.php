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

        .top-right-meta {
            width: 100%;
            text-align: right;
            font-size: 10pt;
            line-height: 1.3;
            margin-bottom: 8px;
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
    $rows = $rfq->items ?? collect();
    $minRows = 15;
    $emptyRows = max(0, $minRows - $rows->count());
    @endphp

    <div class="page">
        <div class="top-right-meta">
            <div><strong>PR No.:</strong> {{ $rfq->purchaseRequest?->pr_no }}</div>
            <div><strong>SVP No.:</strong> {{ $rfq->svp_no }}</div>
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
                    <div class="mt-1">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
                    <div class="mt-2 subtitle">Bids and Awards Committee</div>
                    <div class="mt-3 title">Request for Quotation (RFQ)</div>
                    <div class="subtitle">Small Value Procurement (SVP)</div>
                </td>
                <td class="header-logo-cell">
                    @if ($bagongLogo)
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                    @endif
                </td>
            </tr>
        </table>

        <div class="mt-4">
            <div class="line-row">
                <div class="line-label">Date:</div>
                <div class="line-value"></div>
            </div>
            <div class="line-row">
                <div class="line-label">Company Name:</div>
                <div class="line-value"></div>
            </div>
            <div class="line-row">
                <div class="line-label">Address:</div>
                <div class="line-value"></div>
            </div>
            <div class="line-row">
                <div class="line-label">Contact Details:</div>
                <div class="line-value"></div>
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
                @foreach ($rows as $index => $rfqItem)
                <tr>
                    <td class="col-item-no">{{ $index + 1 }}</td>
                    <td class="col-desc">{{ $rfqItem->item_name }}</td>
                    <td class="col-qty">{{ (int) $rfqItem->quantity }}</td>
                    <td class="col-unit">{{ $rfqItem->unit }}</td>
                    <td class="col-unit-price"></td>
                    <td class="col-total"></td>
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
                        <td class="col-total"></td>
                    </tr>
            </tbody>
        </table>

        <div class="footer-line mt-5">
            <div class="footer-label">Total Amount in Words:</div>
            <div class="footer-value"></div>
        </div>
    </div>
</body>

</html>