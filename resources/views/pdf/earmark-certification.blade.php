<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Earmark Certification – {{ $earmark->earmark_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Times New Roman, serif;
            font-size: 11pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 25mm 20mm 25mm;
            position: relative;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            margin-bottom: 30px;
        }

        .header-logo {
            width: 72px;
            height: 72px;
            flex-shrink: 0;
        }

        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-logo-placeholder {
            width: 72px;
            height: 72px;
            border: 2px solid #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7pt;
            text-align: center;
            color: #555;
            flex-shrink: 0;
        }

        .header-text {
            text-align: center;
            flex: 1;
        }

        .header-text .republic {
            font-size: 10pt;
            margin-bottom: 1px;
        }

        .header-text .province {
            font-size: 10pt;
            margin-bottom: 1px;
        }

        .header-text .office {
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .header-text .address {
            font-size: 9pt;
        }

        /* ── Divider ── */
        .divider-outer {
            border-top: 3px solid #000;
            border-bottom: 1.5px solid #000;
            height: 6px;
            margin-bottom: 40px;
        }

        /* ── Title ── */
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 8px;
            text-transform: uppercase;
            margin-bottom: 36px;
        }

        /* ── Body ── */
        .body-text {
            font-size: 11pt;
            line-height: 2;
            text-align: justify;
            margin-bottom: 24px;
            text-indent: 60px;
        }

        .body-text .amount-words {
            font-style: italic;
            font-weight: bold;
            text-decoration: underline;
        }

        .body-text .earmark-ref {
            font-weight: bold;
            text-decoration: underline;
        }

        .body-text .project-ref {
            font-style: italic;
            text-decoration: underline;
        }

        .body-text .office-ref {
            text-transform: uppercase;
            font-style: italic;
            text-decoration: underline;
        }

        /* ── Issued line ── */
        .issued-line {
            font-size: 11pt;
            line-height: 1.8;
            margin-bottom: 50px;
            text-indent: 60px;
        }

        /* ── Signature ── */
        .signature-block {
            margin-left: 60%;
            text-align: center;
        }

        .signature-block .sig-name {
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .signature-block .sig-desig {
            font-size: 10pt;
        }
    </style>
</head>

<body>
    <div class="page">

        {{-- ══ HEADER ══ --}}
        <div class="header">
            <div class="header-logo-placeholder">BATANGAS<br>SEAL</div>

            <div class="header-text">
                <div class="republic">Republic of the Philippines</div>
                <div class="province">Province of Batangas</div>
                <div class="office">Office of the Provincial Budget</div>
                <div class="address">Capitol Building, Batangas City 4200</div>
            </div>

            <div class="header-logo-placeholder">BAGONG<br>PILIPINAS</div>
        </div>

        {{-- ══ DOUBLE LINE DIVIDER ══ --}}
        <div class="divider-outer"></div>

        {{-- ══ TITLE ══ --}}
        <div class="title">Certification</div>

        {{-- ══ BODY ══ --}}
        @php
        $pr = $earmark->purchaseRequest;
        $office = $pr?->office;
        $fund = $earmark->fund ?? $pr?->fund;
        $project = $pr?->emanating?->project;
        $amount = (float) $earmark->certified_amount;
        $amountFmt = number_format($amount, 2);
        $fiscalYear = $earmark->earmark_date ? \Carbon\Carbon::parse($earmark->earmark_date)->year : now()->year;

        // Convert amount to words using PHP's NumberFormatter (intl extension)
        if (class_exists(\NumberFormatter::class)) {
        $fmt = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $words = ucfirst($fmt->format($amount));
        // Capitalise "Pesos" and "Centavos" parts
        $wholePart = (int) $amount;
        $centsPart = (int) round(($amount - $wholePart) * 100);
        $wholeWords = ucwords($fmt->format($wholePart));
        $amountWords = $wholeWords . ' Pesos';
        if ($centsPart > 0) {
        $amountWords .= ' and ' . ucwords($fmt->format($centsPart)) . ' Centavos';
        }
        } else {
        $amountWords = 'Amount in Words';
        }
        @endphp

        <div class="body-text">
            This is to certify that there is an available allotment of
            <span class="amount-words">{{ $amountWords }}</span>
            <span style="font-weight:bold;">(Php{{ $amountFmt }})</span>
            @if($earmark->expense_class)
            for <span style="text-decoration: underline;">{{ $earmark->expense_class }}</span>
            @endif
            with <span class="earmark-ref">Earmark No. {{ $earmark->earmark_no }}</span>
            dated {{ \Carbon\Carbon::parse($earmark->earmark_date)->format('F d, Y') }}
            @if($project)
            under the <span class="project-ref">{{ strtoupper($project->name ?? '') }}</span>
            @endif
            @if($office)
            of the <span class="office-ref">{{ strtoupper($office->name ?? '') }}</span>
            @endif
            embodied in the FY {{ $fiscalYear }} General Fund Annual Budget approved by the
            Sangguniang Panlalawigan under
            @if($earmark->resolution_no || $earmark->ordinance_no)
            Resolution No. {{ $earmark->resolution_no ?? '___' }}/Appropriation Ordinance No. {{ $earmark->ordinance_no ?? '___' }}
            @if($earmark->ordinance_date)
            dated {{ \Carbon\Carbon::parse($earmark->ordinance_date)->format('F j, Y') }}
            @endif
            @else
            the applicable Resolution/Appropriation Ordinance
            @endif
            .
        </div>

        @if($earmark->remarks)
        <div class="body-text" style="text-indent:0; font-size:10pt; font-style:italic; color:#333;">
            Remarks: {{ $earmark->remarks }}
        </div>
        @endif

        {{-- ══ ISSUED LINE ══ --}}
        <div class="issued-line">
            Issued this {{ \Carbon\Carbon::parse($earmark->earmark_date)->format('l, F j, Y') }} at Batangas City.
        </div>

        {{-- ══ SIGNATURE ══ --}}
        <div class="signature-block">
            <div style="margin-bottom: 4px;">&nbsp;</div>
            <div style="margin-bottom:4px; font-style:italic; font-size:10pt;">(Sgd.)</div>
            <div class="sig-name">{{ $certifiedBy ?? 'VICTORIA B. CULIAT' }}</div>
            <div class="sig-desig">{{ $certifiedByDesig ?? 'Provincial Budget Officer' }}</div>
        </div>

    </div>
</body>

</html>