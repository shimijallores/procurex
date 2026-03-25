<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PO Transmittal - COA</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            font-size: 12pt;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 14mm 12mm;
        }

        .header {
            width: 100%;
            margin-bottom: 26px;
        }

        .header td {
            vertical-align: top;
            border: none;
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

        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-wrap th,
        .table-wrap td {
            border: 1px solid #000;
            padding: 8px 8px;
            vertical-align: middle;
        }

        .table-wrap th {
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

    $headerLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($poTransmittal->header_text ?? ''))))->filter();
    $supplier = strtoupper((string) ($winnerSupplier?->name ?? '—'));
    $projectName = (string) ($resolution?->project_name ?? '—');
    $coaCircular = $poTransmittal->coa_circular_no ?: '2009-001';
    @endphp

    <div class="page">
        <table class="header">
            <tr>
                <td class="logo-cell">
                    @if ($sealLogo)
                    <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal">
                    @endif
                </td>
                <td class="head-cell">
                    Republic of the Philippines<br>
                    PROVINCIAL GOVERNMENT OF BATANGAS<br>
                    OFFICE OF THE GENERAL SERVICES<br>
                    Capitol Site, Batangas City
                </td>
                <td class="logo-cell">
                    @if ($bagongLogo)
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                    @endif
                </td>
            </tr>
        </table>

        <div class="recipient">
            @forelse($headerLines as $line)
            <div>{{ $line }}</div>
            @empty
            <div class="normal-weight">MARIA VANESSA C. BRIONES - VEGAS</div>
            <div class="normal-weight">OIC – SUPERVISING AUDITOR</div>
            <div class="normal-weight">COMMISSION ON AUDIT</div>
            <div class="normal-weight">Capitol Site, Batangas City</div>
            @endforelse
        </div>

        <div class="body normal-weight">
            <div style="margin-top: 10px; margin-bottom: 10px;">Ma’am,</div>

            This is to respectfully transmit to your office the Purchase Order and supporting procurement documents for the project stated below,
            in compliance with COA Circular No. {{ $coaCircular }} and related audit requirements.
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">PROJECT NO.</th>
                        <th style="width: 12%;">PO No.</th>
                        <th style="width: 13%;">Date</th>
                        <th style="width: 14%;">Mode of Procurement</th>
                        <th style="width: 16%;">NAME OF SUPPLIER</th>
                        <th style="width: 25%;">NAME OF PROJECT</th>
                        <th style="width: 10%;">CONTRACT AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="text-align:center">N/A</td>
                        <td style="text-align:center">{{ $purchaseOrder->po_no }}</td>
                        <td style="text-align:center">{{ optional($purchaseOrder->po_date)->format('m/d/Y') }}</td>
                        <td style="text-align:center;">{{ strtoupper((string) $purchaseOrder->mode_of_procurement) }}</td>
                        <td style="text-align:center;">{{ $supplier }}</td>
                        <td class="project-cell">{{ $projectName }}</td>
                        <td class="amount normal-weight">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer normal-weight">Thank you very much.</div>

        <div class="sign">
            <span class="normal-weight">Very truly yours,</span>
            <br><br><br>
            {{ strtoupper((string) ($poTransmittal->signatory_name ?: 'NOEL R. ROCAFORT')) }}<br>
            {{ strtoupper((string) ($poTransmittal->signatory_title ?: 'PGDH – GSO')) }}
        </div>
    </div>
</body>

</html>