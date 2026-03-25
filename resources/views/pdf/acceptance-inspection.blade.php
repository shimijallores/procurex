<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Acceptance & Inspection Report</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 11pt;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 8mm 10mm;
        }

        .title {
            text-align: center;
            font-size: 23px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.05;
        }

        .title-sub {
            text-align: center;
            font-size: 17px;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .title-sub-small {
            text-align: center;
            font-size: 13px;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 6px;
        }

        .meta-table,
        .items-table,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td,
        .meta-table th,
        .items-table td,
        .items-table th,
        .footer-table td,
        .footer-table th {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: top;
        }

        .meta-label {
            width: 12%;
            white-space: nowrap;
        }

        .meta-value {
            font-weight: 700;
        }

        .items-header th {
            text-align: center;
            font-weight: 700;
        }

        .center {
            text-align: center;
        }

        .footer-title {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .line {
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px solid #000;
            height: 14px;
            vertical-align: bottom;
        }

        .checkbox {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 1px solid #000;
            margin-right: 6px;
            vertical-align: middle;
            text-align: center;
            line-height: 14px;
            font-size: 12px;
        }

        .signature-line {
            width: 75%;
            border-top: 1px solid #000;
            margin: 26px auto 0;
            text-align: center;
            padding-top: 3px;
        }
    </style>
</head>

<body>
    @php
    $supplier = strtoupper((string) ($winnerSupplier?->name ?? '—'));
    $officeName = strtoupper((string) ($office?->name ?? '—'));

    $itemRows = collect($purchaseOrder?->items ?? []);
    $minimumRows = 28;
    $extraRows = max(0, $minimumRows - $itemRows->count());

    $isComplete = ($acceptanceInspection->acceptance_status ?? null) === 'complete';
    $isPartial = ($acceptanceInspection->acceptance_status ?? null) === 'partial';
    $inspectionStatus = $acceptanceInspection->inspection_status_ok;
    $isInspectedOk = $inspectionStatus === true;
    $findingsText = $inspectionStatus === null
    ? ''
    : ($acceptanceInspection->inspection_findings_text ?: 'Inspected, verified and found Ok as to quantity and specifications');
    @endphp

    <div class="page">
        <div class="title">Acceptance & Inspection Report</div>
        <div class="title-sub">Provincial Government of Batangas</div>
        <div class="title-sub-small">LGU</div>

        <table class="meta-table">
            <tr>
                <td class="meta-label">Supplier:</td>
                <td class="meta-value" style="width: 32%;">{{ $supplier }}</td>
                <td class="meta-label">Air No:</td>
                <td class="meta-value" style="width: 16%;">{{ $acceptanceInspection->air_no ?: '' }}</td>
            </tr>
            <tr>
                <td class="meta-label">P.O. No.</td>
                <td class="meta-value">{{ $purchaseOrder->po_no }}</td>
                <td class="meta-label">Invoice No:</td>
                <td class="meta-value">{{ $acceptanceInspection->invoice_no ?: '' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Date:</td>
                <td class="meta-value">{{ optional($purchaseOrder->po_date)->format('m/d/Y') }}</td>
                <td class="meta-label">Date:</td>
                <td class="meta-value">{{ optional($acceptanceInspection->inspection_date_inspected)->format('m/d/Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Requisitioning Office:</td>
                <td class="meta-value" colspan="3">{{ $officeName }}</td>
            </tr>
        </table>

        <table class="items-table" style="margin-top: 3px;">
            <thead class="items-header">
                <tr>
                    <th style="width: 8%;">Item No.</th>
                    <th style="width: 12%;">Unit</th>
                    <th>Description</th>
                    <th style="width: 18%;">Quantity</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($itemRows as $index => $item)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $item->rfqItem?->purchaseRequestItem?->unit ?? '—' }}</td>
                    <td>{{ $item->rfqItem?->purchaseRequestItem?->item_name ?? '—' }}</td>
                    <td class="center">{{ $item->quantity_snapshot }}</td>
                </tr>
                @endforeach

                @for ($i = 0; $i < $extraRows; $i++)
                    <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    </tr>
                    @endfor
            </tbody>
        </table>

        <table class="footer-table" style="margin-top: 3px;">
            <tr>
                <th style="width: 52%;" class="footer-title">Acceptance</th>
                <th style="width: 48%;" class="footer-title">Inspection</th>
            </tr>
            <tr>
                <td style="height: 120px;">
                    Date Received: <span class="line">{{ optional($acceptanceInspection->acceptance_date_received)->format('m/d/Y') }}</span>
                    <div style="margin-top: 16px;">
                        <div style="margin-bottom: 8px;">
                            <span class="checkbox">{{ $isComplete ? '✓' : '' }}</span>
                            Complete
                        </div>
                        <div>
                            <span class="checkbox">{{ $isPartial ? '✓' : '' }}</span>
                            Partial
                        </div>
                    </div>

                    <div class="signature-line">
                        {{ strtoupper((string) ($acceptanceInspection->property_officer_name ?: '')) }}
                        <div>{{ $acceptanceInspection->property_officer_title ?: 'Property Officer' }}</div>
                    </div>
                </td>
                <td style="height: 120px;">
                    Date Inspected: <span class="line">{{ optional($acceptanceInspection->inspection_date_inspected)->format('m/d/Y') }}</span>
                    <div style="margin-top: 16px; line-height: 1.35;">
                        <span class="checkbox">{{ $isInspectedOk ? '✓' : '' }}</span>
                        {{ $findingsText }}
                    </div>

                    <div class="signature-line">
                        {{ strtoupper((string) ($acceptanceInspection->inspection_officer_name ?: '')) }}
                        <div>{{ $acceptanceInspection->inspection_officer_title ?: 'Inspection Officer/Committee Officer' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>