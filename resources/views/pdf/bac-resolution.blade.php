<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BAC Resolution - {{ $resolution->resolution_no }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 12mm 14mm;
        }

        .page-break {
            page-break-after: always;
        }

        .text-center {
            text-align: center;
        }

        .underlined {
            text-decoration: underline;
            font-weight: bold;
        }

        .top-right-meta {
            width: 100%;
            text-align: right;
            font-size: 10pt;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .header-layout {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-layout td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }

        .header-left {
            width: 18%;
            text-align: center;
        }

        .header-mid {
            width: 64%;
            text-align: center;
        }

        .header-right {
            width: 18%;
            text-align: center;
        }

        .logo-seal {
            width: 74px;
            height: 74px;
            object-fit: contain;
        }

        .logo-bagong {
            width: 86px;
            height: 74px;
            object-fit: contain;
        }

        .gov-title {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.25;
        }

        .gov-sub {
            font-size: 10.5pt;
            line-height: 1.3;
        }

        .bac-title {
            margin-top: 12px;
            font-size: 15pt;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .resolution-no {
            margin-top: 38px;
            font-size: 12.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .main-resolution {
            margin-top: 36px;
            text-align: center;
            line-height: 1.45;
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .whereas {
            margin-top: 22px;
            text-align: justify;
            line-height: 1.5;
            text-indent: 36px;
        }

        .whereas strong {
            font-weight: 700;
        }

        .bidders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10.5pt;
        }

        .bidders-table th,
        .bidders-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .bidders-table th {
            text-align: center;
            font-weight: 700;
        }

        .bidders-table td:last-child,
        .bidders-table td:nth-child(2) {
            text-align: center;
        }

        .resolved {
            margin-top: 16px;
            text-align: justify;
            line-height: 1.5;
            text-indent: 36px;
        }

        .resolved-date {
            margin-top: 14px;
            text-align: justify;
            line-height: 1.5;
            text-indent: 36px;
        }

        .members {
            margin-top: 42px;
        }

        .member-grid {
            width: 100%;
        }

        .member-row {
            width: 100%;
            margin-top: 28px;
        }

        .member-cell {
            display: inline-block;
            width: 49%;
            text-align: center;
            vertical-align: top;
        }

        .member-name {
            font-weight: 700;
            text-transform: uppercase;
        }

        .member-role {
            margin-top: 2px;
        }

        .chair-wrap {
            margin-top: 30px;
            text-align: center;
        }

        .approved-wrap {
            margin-top: 70px;
            text-align: center;
        }

        .approved-label {
            margin-bottom: 24px;
        }

        .governor-name {
            font-weight: 700;
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
    $bagongLogo = $imagePath(['bagong-pilipinas.png']);

    $officeName = strtoupper((string) ($rfq?->purchaseRequest?->office?->name ?? 'PROVINCIAL OFFICE'));
    $projectName = strtoupper((string) ($resolution->project_name ?? 'PROJECT'));
    $winnerName = strtoupper((string) ($resolution->winner_supplier_name ?? 'WINNING SUPPLIER'));
    $winnerAmount = (float) ($resolution->winner_amount ?? 0);
    $winnerAmountFmt = number_format($winnerAmount, 2);
    $abcAmount = (float) ($rfq?->abc_amount ?? 0);
    $abcAmountFmt = number_format($abcAmount, 2);

    $amountToWords = static function (float $amount): string {
    $whole = (int) floor($amount);
    $fraction = (int) round(($amount - $whole) * 100);

    if (! class_exists(\NumberFormatter::class)) {
    return 'AMOUNT IN WORDS';
    }

    $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
    $wholeWords = strtoupper((string) $formatter->format($whole));
    $words = $wholeWords . ' PESOS';

    if ($fraction > 0) {
    $words .= ' AND ' . strtoupper((string) $formatter->format($fraction)) . ' CENTAVOS';
    }

    return $words;
    };

    $winnerAmountWords = $amountToWords($winnerAmount);
    $abcAmountWords = $amountToWords($abcAmount);
    $calculationLabel = (string) ($resolution->calculation_label ?: 'Lowest Calculated');

    $meetingDate = $resolution->meeting_date ?? $resolution->resolution_date;
    @endphp

    <div class="page">
        <div class="top-right-meta">
            <div><strong>PR No.:</strong> {{ $rfq?->purchaseRequest?->pr_no }}</div>
            <div><strong>SVP No.:</strong> {{ $rfq?->svp_no }}</div>
        </div>

        <table class="header-layout">
            <tr>
                <td class="header-left">
                    @if ($sealLogo)
                    <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal">
                    @endif
                </td>
                <td class="header-mid">
                    <div class="gov-title">REPUBLIC OF THE PHILIPPINES</div>
                    <div class="gov-title">PROVINCIAL GOVERNMENT OF BATANGAS</div>
                    <div class="gov-sub">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
                    <div class="bac-title">BIDS and AWARDS COMMITTEE</div>
                </td>
                <td class="header-right">
                    @if ($bagongLogo)
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                    @endif
                </td>
            </tr>
        </table>

        <div class="text-center resolution-no">
            RESOLUTION NO. {{ strtoupper((string) ($resolution->resolution_no ?? 'BAC-____')) }}, Series of {{ optional($resolution->resolution_date)->format('Y') ?? now()->year }}
        </div>

        <div class="main-resolution">
            RESOLUTION RECOMMENDING THE AWARD OF CONTRACT TO
            <span class="underlined">{{ $winnerName }}</span>
            FOR THE PURCHASE OF
            <span class="underlined">{{ $projectName }}</span>
            FOR USE OF
            <span class="underlined">{{ $officeName }}</span>
            IN THE AMOUNT OF
            <span class="underlined">{{ $winnerAmountWords }} ONLY (P {{ $winnerAmountFmt }})</span>
            THROUGH SMALL VALUE PROCUREMENT
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the Provincial Government of Batangas, through its Bids and Awards Committee (BAC), is in need of a supplier for the
            <span class="underlined">{{ strtoupper((string) ($resolution->project_name ?? 'PROJECT')) }}</span>,
            with an Approved Budget for the Contract (ABC) in the amount of
            <span class="underlined">{{ $abcAmountWords }} (P{{ $abcAmountFmt }})</span>;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> Rule IV of the Implementing Rules and Regulations (IRR) of Republic Act No. 12009 provides the various Modes of Procurement consistent with the fit-for-purpose procurement approach;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> under Section 34.1 of the Implementing Rules and Regulations (IRR) of RA No. 12009, Small Value Procurement (SVP) is a mode of procurement whereby the Procuring Entity requests for the submission of at least three (3) price quotations for Goods not available in the PS-DBM, Infrastructure Projects, and Consulting Services;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the receipt of one (1) quotation is sufficient to proceed with the evaluation of bidders:
            <em>provided</em>, that, the amount involved does not exceed Two Million Pesos (P2,000,000.00);
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> under Section 34.3 (b) of the same IRR, except for those with ABCs equal to Two Hundred Thousand Pesos (P200,000.00) and below which shall not require posting, RFQ or Request for Proposal (RFP) shall be posted for a period of three (3) calendar days on the PhilGEPS website, website of the Procuring Entity, if available, and at any conspicuous place reserved for this purpose in the premises of the Procuring Entity;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the Request for Quotation (RFQ) was prepared together with the Terms and Conditions of the Procuring Entity and sent out to the prospective suppliers;
        </div>
    </div>

    <div class="page">
        <div class="whereas" style="margin-top: 0;">
            <strong>WHEREAS,</strong> on the deadline for the submission of RFQ, the suppliers submitted their bids, which were all found to be substantially compliant, as follows:
        </div>

        <table class="bidders-table">
            <thead>
                <tr>
                    <th style="width: 62%;">Name of Prospective Bidders</th>
                    <th style="width: 20%;">Quotation (in PhP)</th>
                    <th style="width: 18%;">Rank/Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($bidderRows ?? collect()) as $row)
                <tr>
                    <td>{{ $row['supplier_name'] }}</td>
                    <td>P {{ number_format((float) $row['amount'], 2) }}</td>
                    <td>{{ $row['rank_label'] }}</td>
                </tr>
                @empty
                <tr>
                    <td>N/A</td>
                    <td>P 0.00</td>
                    <td>—</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="whereas">
            <strong>WHEREAS,</strong> upon careful examination, validation and verification of all the documents submitted by the supplier with the {{ $calculationLabel }} Quotation, its price quotation has been found to be responsive;
        </div>

        <div class="resolved">
            <strong>NOW, THEREFORE,</strong> We, the members of the Bids and Awards Committee of the Provincial Government of Batangas, <strong>RESOLVE</strong>, as it is hereby <strong>RESOLVED</strong>, to recommend to HON. GOVERNOR VILMA SANTOS-RECTO, to resort to Small Value Procurement and award the contract for
            <span class="underlined">{{ strtoupper((string) ($resolution->project_name ?? 'PROJECT')) }}</span>
            in the amount of
            <span class="underlined">{{ $winnerAmountWords }} (P {{ $winnerAmountFmt }})</span>
            to
            <span class="underlined">{{ $winnerName }}</span>
            as the supplier with the {{ $calculationLabel }} and Responsive Quotation;
        </div>

        <div class="resolved-date">
            <strong>RESOLVED,</strong> at the Batangas Provincial BAC Office, Capitol Compound, Batangas City, this
            <span class="underlined">{{ optional($meetingDate)->format('jS') ?? '___' }}</span>
            day of
            <span class="underlined">{{ optional($meetingDate)->format('F Y') ?? '___________' }}</span>.
        </div>

        <div class="members">
            <div class="member-row">
                <div class="member-cell">
                    <div class="member-name">{{ strtoupper((string) ($resolution->signatory_member_one ?: 'BAC MEMBER')) }}</div>
                    <div class="member-role">Member</div>
                </div>
                <div class="member-cell">
                    <div class="member-name">{{ strtoupper((string) ($resolution->signatory_member_two ?: 'BAC MEMBER')) }}</div>
                    <div class="member-role">Member</div>
                </div>
            </div>

            <div class="member-row">
                <div class="member-cell">
                    <div class="member-name">{{ strtoupper((string) ($resolution->signatory_member_three ?: 'BAC MEMBER')) }}</div>
                    <div class="member-role">Member</div>
                </div>
                <div class="member-cell">
                    <div class="member-name">ATTY. LOUIE MARK M. DALAWAMPU</div>
                    <div class="member-role">Member</div>
                </div>
            </div>

            <div class="chair-wrap">
                <div class="member-name">{{ strtoupper((string) ($resolution->signatory_chairperson ?: 'BAC CHAIRPERSON')) }}</div>
                <div class="member-role">Chairperson</div>
            </div>

            <div class="approved-wrap">
                <div class="approved-label">Approved by:</div>
                <div class="governor-name">VILMA SANTOS - RECTO</div>
                <div>Governor</div>
            </div>
        </div>
    </div>
</body>

</html>