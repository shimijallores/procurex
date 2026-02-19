<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PO Transmittal - OPG</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 15px;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 16mm 12mm 14mm;
        }

        .header {
            width: 100%;
            margin-bottom: 26px;
        }

        .header td {
            vertical-align: top;
        }

        .logo-cell {
            width: 18%;
            text-align: center;
        }

        .head-cell {
            width: 64%;
            text-align: center;
            line-height: 1.35;
            font-weight: 700;
        }

        .seal {
            width: 70px;
            height: 70px;
            border: 2px solid #000;
            border-radius: 50%;
            margin: 0 auto;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bagong {
            margin-top: 10px;
            font-style: italic;
            font-weight: 700;
        }

        .recipient {
            line-height: 1.35;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .body {
            margin-top: 14px;
            line-height: 1.45;
            font-weight: 700;
        }

        .table-wrap {
            margin-top: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px 8px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
        }

        .project-cell {
            line-height: 1.35;
            text-align: justify;
        }

        .amount {
            text-align: center;
            font-weight: 700;
        }

        .footer {
            margin-top: 32px;
            font-weight: 700;
            line-height: 1.4;
        }

        .sign {
            margin-top: 34px;
            font-weight: 700;
            line-height: 1.4;
        }

        .normal-weight {
            font-weight: 400 !important;
        }
    </style>
</head>

<body>
    @php
    $headerLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($poTransmittal->header_text ?? ''))))->filter();
    $supplier = strtoupper((string) ($winnerSupplier?->name ?? '—'));
    $projectName = (string) ($resolution?->project_name ?? '—');
    @endphp

    <div class="page">
        <table class="header">
            <tr>
                <td class="logo-cell">
                    <div class="seal">BATANGAS<br>SEAL</div>
                </td>
                <td class="head-cell">
                    Republic of the Philippines<br>
                    PROVINCE OF BATANGAS<br>
                    OFFICE OF THE GENERAL SERVICES<br>
                    Capitol Site, Batangas City
                </td>
                <td class="logo-cell">
                    <div class="seal" style="border:0;">&nbsp;</div>
                    <div class="bagong">BAGONG PILIPINAS</div>
                </td>
            </tr>
        </table>

        <div class="recipient normal-weight">
            @forelse($headerLines as $line)
            <div>{{ $line }}</div>
            @empty
            <div style="font-weight: bold !important;">HON. VILMA SANTOS - RECTO</div>
            <div>Governor</div>
            <div>Province of Batangas</div>
            <div>Capitol Site, Batangas City</div>
            <div style="margin-top: 10px;">Ma’am,</div>
            @endforelse
        </div>

        <div class="body normal-weight">
            This is to respectfully transmit to your good office the Purchase Order of the project:
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 13%;">PROJECT NO.</th>
                        <th style="width: 26%;">NAME OF SUPPLIER</th>
                        <th style="width: 38%;">NAME OF PROJECT</th>
                        <th style="width: 23%;">CONTRACT AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center">N/A</td>
                        <td style="text-align:center">{{ $supplier }}</td>
                        <td class="project-cell">{{ $projectName }}</td>
                        <td class="amount normal-weight">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer normal-weight">Thank you very much.</div>

        <div class="sign">
            <span class="normal-weight">Very truly yours,</span><br><br><br>
            {{ strtoupper((string) ($poTransmittal->signatory_name ?: 'NOEL R. ROCAFORT')) }}<br>
            {{ strtoupper((string) ($poTransmittal->signatory_title ?: 'PGDH – GSO')) }}
        </div>
    </div>
</body>

</html>