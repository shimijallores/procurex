<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AOQ - {{ $rfq->svp_no }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
            min-height: auto;
            padding: 0;
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
            margin-top: 0;
        }

        .header-layout td {
            border: none;
            vertical-align: middle;
            padding: 0;
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
                    <div class="mt-1 subtitle">Bids and Awards Committee</div>
                    <div class="title mt-2">Abstract of Quotation</div>
                    <div class="subtitle">Small Value Procurement</div>
                </td>
                <td class="header-logo-cell">
                    @if ($bagongLogo)
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                    @endif
                </td>
            </tr>
        </table>

        <div class="mt-3"><strong>Project Name:</strong> {{ $rfq->project_name }}</div>
        <div class="mt-1"><strong>Date:</strong> {{ \Carbon\Carbon::parse($aoq->aoq_date)->format('F d, Y') }}</div>

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
                $quantity = (float) ($rfqItem->quantity ?? 0);
                $abcLineTotal = $quantity * (float) ($prItem?->unit_cost ?? 0);
                @endphp
                <tr>
                    <td>{{ (int) $quantity }}</td>
                    <td>{{ $rfqItem->unit ?? '' }}</td>
                    <td>{{ $rfqItem->item_name ?? '' }}</td>
                    <td class="text-right">{{ number_format((float) $abcLineTotal, 2) }}</td>

                    @foreach($supplierTotals as $supplier)
                    @php
                    $entry = $rfqSuppliers->firstWhere('supplier_id', $supplier['supplier_id']);
                    $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $rfqItem->id);
                    $unitPrice = $supplierItem?->unit_price;
                    $lineTotal = $unitPrice !== null ? ((float) $unitPrice * $quantity) : null;
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
            <strong style="text-decoration: underline">{{ ((int) ($calculation['calculated_supplier_count'] ?? 0)) >= 2 ? 'Lowest Calculated' : 'Single Calculated' }} and Responsive Quotation</strong>
            which is advantageous to the Provincial Government of Batangas.
        </div>

        <div class="signature">APPROVED:</div>

        <table style="margin-top: 28px; border: none;">
            <tr>
                <td style="border: none; width: 25%; text-align: center; padding: 12px;">
                    <div style="font-weight: bold; text-decoration: underline;">NOEL R. ROCAFORT</div>
                    <div>BAC MEMBER</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; padding: 12px;">
                    <div style="font-weight: bold; text-decoration: underline;">PEDRITO MARTIN M. DIJAN, JR.</div>
                    <div>BAC MEMBER</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; padding: 12px;">
                    <div style="font-weight: bold; text-decoration: underline;">ENGR. NERIO L. RONQUILLO, JR.</div>
                    <div>BAC MEMBER</div>
                </td>
                <td style="border: none; width: 25%; text-align: center; padding: 12px;">
                    <div style="font-weight: bold; text-decoration: underline;">ATTY. LOUIE MARK M. DALAWAMPU</div>
                    <div>BAC MEMBER</div>
                </td>
            </tr>
            <tr>
                <td colspan="4" style="border: none; text-align: center; padding: 20px 12px 12px;">
                    <div style="font-weight: bold; text-decoration: underline;">ATTY. JOEL L. MONTEALTO</div>
                    <div>BAC CHAIRPERSON</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>