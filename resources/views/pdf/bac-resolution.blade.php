<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BAC Resolution - {{ $resolution->resolution_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 1in;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
            min-height: auto;
            padding: 0;
        }

        .header-layout {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .header-layout td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .header-left,
        .header-right {
            width: 18%;
            text-align: center;
        }

        .header-mid {
            width: 64%;
            text-align: center;
        }

        .logo-seal {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .logo-bagong {
            width: 78px;
            height: 60px;
            object-fit: contain;
        }

        .gov-title {
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .gov-sub {
            font-size: 10pt;
            line-height: 1.2;
        }

        .bac-title {
            margin-top: 6px;
            font-size: 16pt;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .resolution-no {
            margin-top: 14px;
            text-align: center;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .resolution-main {
            margin-top: 12px;
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
        }

        .whereas {
            margin-top: 9px;
            text-align: justify;
            line-height: 1.35;
            text-indent: 26px;
        }

        .whereas strong,
        .resolved strong {
            font-weight: 700;
        }

        .section-title {
            margin-top: 12px;
            text-align: center;
            font-size: 16pt;
            font-weight: 700;
            text-decoration: underline;
            text-transform: uppercase;
        }

        .meta-line {
            margin-top: 6px;
            font-size: 11pt;
        }

        .meta-line strong {
            font-weight: 700;
        }

        .block-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 10pt;
        }

        .block-table th,
        .block-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            vertical-align: top;
        }

        .block-table th {
            text-align: center;
            font-weight: 700;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .abstract-wrap {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .resolved {
            margin-top: 10px;
            text-align: justify;
            line-height: 1.4;
            text-indent: 26px;
        }

        .approval-line {
            margin-top: 18px;
            text-align: center;
            font-weight: 700;
        }

        .certification {
            margin-top: 6px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .signatory-grid {
            width: 100%;
            margin-top: 14px;
        }

        .signatory-row {
            width: 100%;
            margin-top: 16px;
        }

        .signatory-cell {
            display: inline-block;
            width: 49%;
            text-align: center;
            vertical-align: top;
        }

        .name {
            font-weight: 700;
            text-transform: uppercase;
        }

        .role {
            margin-top: 2px;
        }

        .chair {
            margin-top: 20px;
            text-align: center;
        }

        .approved {
            margin-top: 14px;
            text-align: center;
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

    $resolutionYear = optional($resolution->resolution_date)->format('Y') ?? now()->year;
    $meetingDate = $resolution->meeting_date ?? $resolution->resolution_date;
    @endphp

    <div class="page">
        <table class="header-layout">
            <tr>
                <td class="header-left">
                    @if ($sealLogo)
                    <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal" />
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
                    <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong" />
                    @endif
                </td>
            </tr>
        </table>

        <div class="resolution-no">
            RESOLUTION NO. BAC - SVP - B200K - ___, Series of {{ $resolutionYear }}
        </div>

        <div class="resolution-main">
            RESOLUTION RECOMMENDING THE AWARD OF CONTRACT TO THE SUPPLIERS WITH THE
            LOWEST/SINGLE CALCULATED RESPONSIVE QUOTATIONS, THROUGH SMALL VALUE PROCUREMENT
            (TWO HUNDRED THOUSAND PESOS AND BELOW)
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the Provincial Government of Batangas, through its Bids and Awards Committee (BAC), is in need of suppliers for the following:
        </div>

        <table class="block-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th style="width: 18%;">OFFICE</th>
                    <th style="width: 56%;">NAME OF PROJECT</th>
                    <th style="width: 26%;">APPROVED BUDGET FOR THE CONTRACT (ABC)</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($summaryRows ?? collect()) as $row)
                <tr>
                    <td class="text-center">{{ strtoupper((string) ($row['office_name'] ?? 'OFFICE')) }}</td>
                    <td>{{ $row['project_name'] ?? 'PROJECT' }}</td>
                    <td class="text-center">{{ number_format((float) ($row['abc_amount'] ?? 0), 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="whereas">
            <strong>WHEREAS,</strong> Rule IV (Mode of Procurement) of Republic Act (RA) No. 12009 or the New Government Procurement Act states that the Procuring Entity may, in order to promote economy and efficiency, resort to aforesaid method of procurement and shall ensure that it is the most advantageous price for the government;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> Rule IV, Section 26 of R.A. No. 12009 likewise provides for Small Value Procurement (SVP) as a mode of procurement, consistent with the Fit-for-Purpose procurement approach;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> under Section 34.1 of the Implementing Rules and Regulations (IRR) of RA No. 12009, Small Value Procurement (SVP) is a mode of procurement whereby the Procuring Entity requests for the submission of at least three (3) price quotations for Goods not available in the PS-DBM, Infrastructure Projects, and Consulting Services;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> under Section 34.3 b) of the same IRR, except for those with ABCs equal to Two Hundred Thousand Pesos (P200,000.00) and below which shall not require posting, RFQ or Request for Proposal (RFP) shall be posted for a period of three (3) calendar days on the PhilGEPS website, website of the Procuring Entity, if available, and at any conspicuous place reserved for this purpose in the premises of the Procuring Entity;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the receipt of one (1) quotation is sufficient to proceed with the evaluation of bidders: provided, that, the amount involved does not exceed Two Million Pesos (P2,000,000.00), as detailed in the table contained in Section 34.2, and subject to the periodic review of the threshold amount and adjustments as may be deemed appropriate by the GPPB;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> the General Services Office (GSO) invited prospective suppliers to furnish their quotations on the items abovementioned;
        </div>

        <div class="whereas">
            <strong>WHEREAS,</strong> on the deadline for the submission of Request for Quotation (RFQ) Forms last __________________, {{ $resolutionYear }}, the GSO prepared the hereunder Abstract of Quotation from all the interested bidders within the prescribed period of posting, to wit:
        </div>

        @foreach(($abstracts ?? collect()) as $abstract)
        <div class="abstract-wrap">
            <div class="section-title">ABSTRACT OF QUOTATION</div>
            <div class="meta-line">
                <strong>DATE OF RFQ:</strong>
                <strong>{{ $abstract['rfq_date'] ? \Carbon\Carbon::parse($abstract['rfq_date'])->format('F d, Y') : '__________' }}</strong>
            </div>

            <table class="block-table">
                <tbody>
                    <tr>
                        <td style="width: 18%; font-weight: 700;">SVP NO. {{ $abstract['svp_no'] ?? 'N/A' }}</td>
                        <td>{{ $abstract['project_name'] ?? 'PROJECT' }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="block-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 5%;">QTY</th>
                        <th rowspan="2" style="width: 6%;">UNIT</th>
                        <th rowspan="2" style="width: 27%;">PARTICULARS</th>
                        <th rowspan="2" style="width: 11%;">APPROVED BUDGET FOR THE CONTRACT</th>
                        @foreach(($abstract['suppliers'] ?? collect()) as $supplier)
                        <th colspan="2">
                            {{ $supplier['supplier_name'] }} ({{ $supplier['rank_label'] }})
                        </th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach(($abstract['suppliers'] ?? collect()) as $supplier)
                        <th>UNIT COST</th>
                        <th>TOTAL AMOUNT</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(($abstract['items'] ?? collect()) as $item)
                    <tr>
                        <td class="text-center">{{ (int) ($item['quantity'] ?? 0) }}</td>
                        <td class="text-center">{{ $item['unit'] ?? '' }}</td>
                        <td>{{ $item['particulars'] ?? '' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['approved_budget'] ?? 0), 2) }}</td>
                        @foreach(($item['supplier_columns'] ?? collect()) as $column)
                        <td class="text-right">{{ $column['unit_cost'] !== null ? number_format((float) $column['unit_cost'], 2) : '' }}</td>
                        <td class="text-right">{{ $column['total_amount'] !== null ? number_format((float) $column['total_amount'], 2) : '' }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>{{ number_format((float) ($abstract['abc_total'] ?? 0), 2) }}</strong></td>
                        @foreach(($abstract['suppliers'] ?? collect()) as $supplier)
                        <td></td>
                        <td class="text-right"><strong>{{ number_format((float) ($supplier['total_amount'] ?? 0), 2) }}</strong></td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
        @endforeach

        <div class="whereas">
            <strong>WHEREAS,</strong> upon careful examination, validation and verification of all the documents submitted by the suppliers with the Lowest Calculated Quotation, their price quotations have been found to be responsive;
        </div>

        <div class="resolved">
            <strong>NOW, THEREFORE,</strong> we, the members of the Bids and Awards Committee of the Provincial Government of Batangas, acknowledging the submitted Abstract of Quotation by the GSO, <strong>RESOLVE</strong>, as it is hereby <strong>RESOLVED</strong>, to recommend to the HON. GOVERNOR VILMA SANTOS-RECTO, the approval of the aforesaid Abstract of Quotation, as conducted and submitted by the GSO Team of Canvassers, pursuant to Sections 34.1 and 34.2 of the Implementing Rules and Regulations of RA No. 12009; to declare the abovementioned suppliers as those with the Lowest Calculated Responsive Quotations for the purchase and delivery of various goods of the Provincial Government of Batangas, and to recommend the awarding of their respective Contracts, after passing the criteria set forth by the procurement law and the rules of the BAC;
        </div>

        <div class="approval-line">
            UNANIMOUSLY APPROVED, this ____ day of _________________________ {{ $resolutionYear }}.
        </div>

        <div class="certification">CERTIFIED TO BE DULY ATTESTED AND APPROVED:</div>

        <div class="signatory-grid">
            <div class="signatory-row">
                <div class="signatory-cell">
                    <div class="name">MR. NOEL R. ROCAFORT</div>
                    <div class="role">Member</div>
                </div>
                <div class="signatory-cell">
                    <div class="name">MR. PEDRITO MARTIN M. DIJAN, JR.</div>
                    <div class="role">Member</div>
                </div>
            </div>

            <div class="signatory-row">
                <div class="signatory-cell">
                    <div class="name">ENGR. NERIO L. RONQUILLO, JR.</div>
                    <div class="role">Member</div>
                </div>
                <div class="signatory-cell">
                    <div class="name">ATTY. LOUIE MARK M. DALAWAMPU</div>
                    <div class="role">Member</div>
                </div>
            </div>

            <div class="chair">
                <div class="name">ATTY. JOEL L. MONTEALTO</div>
                <div class="role">Chairperson</div>
            </div>

            <div class="approved">
                <div>Approved by:</div>
                <div class="name" style="margin-top: 10px;">VILMA SANTOS - RECTO</div>
                <div class="role">Governor</div>
            </div>
        </div>
    </div>
</body>

</html>