<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>COA Inspection - SVP and Bidding</title>
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
            padding: 14mm 14mm;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            text-align: center;
            line-height: 1.2;
            margin-bottom: 30px;
        }

        .seal {
            width: 72px;
            height: 72px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .agency-strong {
            font-weight: 700;
        }

        .recipient {
            margin-bottom: 20px;
            line-height: 1.35;
        }

        .recipient-name {
            font-weight: 700;
        }

        .body {
            line-height: 1.4;
            text-align: justify;
        }

        .doc-list {
            margin: 14px 0 0 46px;
            line-height: 1.35;
        }

        .footer {
            margin-top: 30px;
            line-height: 1.4;
        }

        .sign {
            margin-top: 30px;
            line-height: 1.35;
        }

        .sign-name {
            margin-top: 28px;
            font-weight: 700;
        }

        .sign-title {
            text-transform: uppercase;
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

    $supplier = (string) ($winnerSupplier?->name ?? '-');
    $officeName = (string) ($office?->name ?? 'office');
    $amount = number_format((float) ($purchaseOrder?->total_amount ?? 0), 2);

    $bodyLine = 'May we request one of your inspectors to check and verify the Purchase and Delivery of ' . $itemSummary . ' for use of the ' . $officeName . ' amounting ' . $amount . '.';

    $svpHeaderLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($coaInspection->svp_header_text ?? ''))))->filter();
    $biddingHeaderLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($coaInspection->bidding_header_text ?? ''))))->filter();

    $svpSalutation = $coaInspection->svp_salutation ?: "Ma'am:";
    $biddingSalutation = $coaInspection->bidding_salutation ?: 'Dear Governor Recto:';

    $signatoryName = strtoupper((string) ($coaInspection->signatory_name ?: 'NOEL R. ROCAFORT'));
    $signatoryTitle = strtoupper((string) ($coaInspection->signatory_title ?: 'PGDH-GSO'));

    $documents = [
    'Acceptance and Inspection Report',
    'Purchase Request',
    'Delivery Receipt',
    'Sales invoice',
    'Notice of Award',
    'Purchase Order',
    'Notice to Proceed',
    'Pictures',
    ];
    @endphp

    <div class="page page-break">
        <div class="header">
            @if ($sealLogo)
            <img src="{{ $sealLogo }}" alt="Batangas Seal" class="seal">
            @endif
            <div>Republic of the Philippines</div>
            <div>PROVINCE OF BATANGAS</div>
            <div class="agency-strong">OFFICE OF THE GENERAL SERVICES</div>
            <div>Capitol Site, Batangas City</div>
        </div>

        <div class="recipient">
            @forelse($svpHeaderLines as $index => $line)
            <div class="{{ $index === 0 ? 'recipient-name' : '' }}">{{ $line }}</div>
            @empty
            <div class="recipient-name">MARIA VANESSA C. BRIONES - VEGAS</div>
            <div>OIC - SUPERVISING AUDITOR</div>
            <div>COMMISSION ON AUDIT</div>
            <div>Capitol Site, Batangas City</div>
            @endforelse
        </div>

        <div class="body">
            <div style="margin-bottom: 14px;">{{ $svpSalutation }}</div>
            <div>{{ $bodyLine }}</div>

            <ol class="doc-list">
                @foreach ($documents as $document)
                <li>{{ $document }}</li>
                @endforeach
            </ol>
        </div>

        <div class="footer">Thank you very much.</div>

        <div class="sign">
            <div>Very truly yours,</div>
            <div class="sign-name">{{ $signatoryName }}</div>
            <div class="sign-title">{{ $signatoryTitle }}</div>
        </div>
    </div>

    <div class="page">
        <div class="header">
            @if ($sealLogo)
            <img src="{{ $sealLogo }}" alt="Batangas Seal" class="seal">
            @endif
            <div>Republic of the Philippines</div>
            <div>PROVINCE OF BATANGAS</div>
            <div class="agency-strong">OFFICE OF THE GENERAL SERVICES</div>
            <div>Capitol Site, Batangas City</div>
        </div>

        <div class="recipient">
            @forelse($biddingHeaderLines as $index => $line)
            <div class="{{ $index === 0 ? 'recipient-name' : '' }}">{{ $line }}</div>
            @empty
            <div class="recipient-name">HON. VILMA SANTOS - RECTO</div>
            <div>Governor</div>
            <div>Province of Batangas</div>
            <div>Capitol Site, Batangas City</div>
            @endforelse
        </div>

        <div class="body">
            <div style="margin-bottom: 14px;">{{ $biddingSalutation }}</div>
            <div>{{ $bodyLine }}</div>

            <ol class="doc-list">
                @foreach ($documents as $document)
                <li>{{ $document }}</li>
                @endforeach
            </ol>
        </div>

        <div class="footer">Thank you very much.</div>

        <div class="sign">
            <div>Very truly yours,</div>
            <div class="sign-name">{{ $signatoryName }}</div>
            <div class="sign-title">{{ $signatoryTitle }}</div>
        </div>
    </div>
</body>

</html>