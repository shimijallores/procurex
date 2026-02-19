<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Purchase Request – {{ $pr->pr_no ?? 'Draft' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 10mm;
            position: relative;
        }

        /* ── Header ── */
        .header {
            text-align: center;
            border: 1px solid #000;
            border-bottom: none;
            padding: 4px 2px 2px;
            position: relative;
        }

        .header .title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header .gov-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header {
            font-size: 11pt;
            font-weight: bold;
            margin-top: 2px;
        }

        .agu-code {
            font-weight: normal;
        }

        .header .page-no {
            position: absolute;
            top: 4px;
            right: 6px;
            font-size: 8pt;
        }

        /* ── Info rows ── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .info-table td {
            padding: 3px 5px;
            font-size: 9pt;
            vertical-align: top;
        }

        .info-table .label {
            white-space: nowrap;
            font-weight: normal;
        }

        .info-table .underlined {
            border-bottom: 1px solid #000;
            min-width: 140px;
            display: inline-block;
        }

        .info-row-border td {
            border-bottom: 1px solid #000;
        }

        /* ── Items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            border-top: none;
        }

        .items-table th {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            background: #fff;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8.5pt;
            vertical-align: top;
        }

        .items-table .col-no {
            width: 6%;
            text-align: center;
        }

        .items-table .col-stock {
            width: 7%;
            text-align: center;
        }

        .items-table .col-unit {
            width: 9%;
            text-align: center;
        }

        .items-table .col-desc {
            width: 42%;
        }

        .items-table .col-qty {
            width: 7%;
            text-align: center;
        }

        .items-table .col-ucost {
            width: 14%;
            text-align: right;
        }

        .items-table .col-tcost {
            width: 15%;
            text-align: right;
        }

        .items-table .total-row td {
            font-weight: bold;
            border-top: 2px solid #000;
        }

        .items-table .total-label {
            text-align: right;
            padding-right: 6px;
        }

        /* ── Purpose row ── */
        .purpose-row {
            border: 1px solid #000;
            border-top: none;
            padding: 4px 6px;
            font-size: 9pt;
            min-height: 28px;
        }

        /* ── Signature area ── */
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            border-top: none;
        }

        .signature-table td {
            padding: 3px 8px;
            font-size: 9pt;
            vertical-align: top;
            width: 50%;
        }

        .signature-table .sig-header {
            font-weight: normal;
            border-bottom: none;
            padding-bottom: 0;
        }

        .signature-table .sig-line {
            border-bottom: 1px solid #000;
            height: 28px;
            min-width: 140px;
        }

        .signature-table .sig-name {
            font-weight: bold;
            text-align: center;
        }

        .signature-table .sig-desig {
            text-align: center;
        }

        .sig-divider {
            border-left: 1px solid #000;
        }

        .sig-block {
            padding: 4px 8px 6px;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ══ HEADER ══ --}}
        <div class="header">
            <div class="page-no">Page 1 of 1</div>
            <div class="title">Purchase Request ✓</div>
            <div class="gov-name">Provincial Government of Batangas</div>
            <div class="agu-code">
                LGU
            </div>
        </div>

        {{-- ══ DEPARTMENT / SAI ROW ══ --}}
        <table class="info-table">
            <tr class="info-row-border">
                <td style="width:14%;" class="label">Department</td>
                <td style="width:36%;">
                    <span class="underlined">{{ $pr->office?->name ?? '' }}</span>
                </td>
                <td style="width:14%; border-left:1px solid #000;" class="label">PR No.</td>
                <td style="width:18%;">
                    <span class="underlined">{{ $pr->pr_no ?? '' }}</span>
                </td>
                <td style="width:4%; border-left:1px solid #000;" class="label">Date:</td>
                <td style="width:14%;">
                    {{ $pr->pr_date ? \Carbon\Carbon::parse($pr->pr_date)->format('m/d/Y') : '' }}
                </td>
            </tr>
            <tr>
                <td class="label">Section</td>
                <td>
                    <span class="underlined" style="min-width:140px;">&nbsp;</span>
                </td>
                <td style="border-left:1px solid #000;" class="label">SAI No.</td>
                <td>{{ $pr->sai_no ?? '' }}</td>
                <td style="border-left:1px solid #000;" class="label">Date:</td>
                <td>
                    {{ $pr->sai_date ? \Carbon\Carbon::parse($pr->sai_date)->format('m/d/Y') : '' }}
                </td>
            </tr>
        </table>

        {{-- ══ ITEMS TABLE ══ --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-no">Item<br>No.</th>
                    <th class="col-stock">Stock<br>No.</th>
                    <th class="col-unit">Unit of<br>Issue</th>
                    <th class="col-desc">Item Description</th>
                    <th class="col-qty">Qty.</th>
                    <th class="col-ucost">Unit Cost</th>
                    <th class="col-tcost">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @php
                $items = $pr->items ?? collect();
                $totalRows = 20; // fixed row count like the form
                $filledRows = count($items);
                $emptyRows = max(0, $totalRows - $filledRows);
                @endphp

                @foreach($items as $i => $item)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-stock"></td>
                    <td class="col-unit">{{ $item->unit ?? $item->emanating_item?->unit ?? '' }}</td>
                    <td class="col-desc">{{ $item->item_name ?? $item->emanating_item?->ppmp_item?->name ?? '' }}</td>
                    <td class="col-qty">{{ $item->quantity }}</td>
                    <td class="col-ucost">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="col-tcost">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach

                @for($r = 0; $r < $emptyRows; $r++)
                    <tr>
                    <td class="col-no" style="height:18px;">&nbsp;</td>
                    <td class="col-stock"></td>
                    <td class="col-unit"></td>
                    <td class="col-desc"></td>
                    <td class="col-qty"></td>
                    <td class="col-ucost"></td>
                    <td class="col-tcost"></td>
                    </tr>
                    @endfor

                    {{-- TOTAL row --}}
                    <tr class="total-row">
                        <td colspan="6" class="total-label">TOTAL-P</td>
                        <td class="col-tcost" style="font-weight:bold;">
                            {{ number_format($pr->total_amount ?? 0, 2) }}
                        </td>
                    </tr>
            </tbody>
        </table>

        {{-- ══ PURPOSE / REMARKS ══ --}}
        <div class="purpose-row">
            <strong>Purpose/Remarks:</strong>
            {{ $pr->purpose ?? '' }}
            @if($pr->remarks)
            &nbsp;–&nbsp;{{ $pr->remarks }}
            @endif
        </div>

        {{-- ══ SIGNATURE AREA ══ --}}
        <table class="signature-table">
            <thead>
                <tr>
                    <th style="width:20%; border-right:1px solid #000; padding:4px; font-weight:normal; font-size:9pt;">&nbsp;</th>
                    <th style="width:40%; border-right:1px solid #000; text-align:center; padding:4px; font-weight:normal; font-size:9pt;">Requested by</th>
                    <th style="width:40%; text-align:center; padding:4px; font-weight:normal; font-size:9pt;">Approved by</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="width:20%; border-right:1px solid #000; padding:4px; font-size:8pt; font-weight:bold;">Signature</td>
                    <td style="width:40%; border-right:1px solid #000; padding:4px; vertical-align:bottom;">
                        <div style="border-bottom:1px solid #000; height:24px;">&nbsp;</div>
                    </td>
                    <td style="width:40%; padding:4px; vertical-align:bottom;">
                        <div style="border-bottom:1px solid #000; height:24px;">&nbsp;</div>
                    </td>
                </tr>
                <tr>
                    <td style="width:20%; border-right:1px solid #000; border-top:1px solid #000; padding:4px; font-size:8pt; font-weight:bold;">Printed Name</td>
                    <td style="width:40%; border-right:1px solid #000; border-top:1px solid #000; padding:4px;">
                        <div style="border-bottom:1px solid #000; height:16px; text-align:center; font-size:8pt;">&nbsp;</div>
                    </td>
                    <td style="width:40%; border-top:1px solid #000; padding:4px;">
                        <div style="border-bottom:1px solid #000; height:16px; text-align:center; font-size:8pt;">{{ $approvedBy ?? 'VILMA SANTOS-RECTO' }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="width:20%; border-right:1px solid #000; border-top:1px solid #000; padding:4px; font-size:8pt; font-weight:bold;">Designation</td>
                    <td style="width:40%; border-right:1px solid #000; border-top:1px solid #000; padding:4px;">
                        <div style="border-bottom:1px solid #000; height:16px; text-align:center; font-size:8pt;">&nbsp;</div>
                    </td>
                    <td style="width:40%; border-top:1px solid #000; padding:4px;">
                        <div style="border-bottom:1px solid #000; height:16px; text-align:center; font-size:8pt;">{{ $approvedByDesig ?? 'Governor' }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

    </div>
</body>

</html>