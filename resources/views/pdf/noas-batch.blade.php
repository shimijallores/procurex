<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NOAs - Batch {{ $batch->batch_no }}</title>
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
            padding: 12mm 14mm;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .header-cell {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 18%;
            text-align: left;
        }

        .header-mid {
            width: 64%;
            text-align: center;
        }

        .header-right {
            width: 18%;
            text-align: right;
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

        .gov-line {
            text-transform: uppercase;
            font-weight: 600;
            line-height: 1.3;
        }

        .gov-sub {
            font-size: 10.5pt;
        }

        .gov-office {
            margin-top: 4px;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .divider {
            border-top: 3px solid #000;
            margin-top: 8px;
        }

        .noa-number {
            margin-top: 24px;
            font-weight: 700;
        }

        .title {
            margin-top: 18px;
            text-align: center;
            font-size: 12pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .block {
            margin-top: 22px;
            line-height: 1.45;
        }

        .recipient {
            margin-top: 16px;
            line-height: 1.35;
        }

        .body {
            margin-top: 18px;
            text-align: justify;
            line-height: 1.5;
        }

        .body b {
            font-weight: 700;
        }

        .table-wrap {
            margin-top: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            text-align: center;
            font-weight: 700;
        }

        .closing {
            margin-top: 14px;
            text-align: justify;
            line-height: 1.5;
        }

        .signature {
            margin-top: 26px;
            line-height: 1.35;
        }

        .governor {
            margin-top: 36px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .conforme {
            margin-top: 46px;
        }

        .underline {
            text-decoration: underline;
            font-weight: 700;
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

    @foreach ($noas as $noa)
        @php
            $resolution = $noa->bacResolution;
            $aoq = $noa->aoq ?? $resolution?->aoq;
            $rfq = $aoq?->rfq;
            $winnerSupplier = $aoq?->winnerSupplier;
            $supplierName = strtoupper((string) ($resolution?->winner_supplier_name ?? $winnerSupplier?->name ?? 'SUPPLIER'));
            $addressedSupplier = null;

            if ($supplierName !== '') {
                $addressedSupplier = \App\Models\Supplier::query()
                    ->where('name', $supplierName)
                    ->first();
            }

            $recipientRaw = $noa->recipient_name ?: ($addressedSupplier?->proprietor ?: ($addressedSupplier?->authorized_representative ?: ($addressedSupplier?->owner ?: ($winnerSupplier?->contact_person ?: 'AUTHORIZED REPRESENTATIVE'))));
            $recipientName = strtoupper((string) $recipientRaw);
            $recipientTitle = trim((string) ($noa->recipient_title ?? ''));

            if ($recipientTitle === '' && $addressedSupplier) {
                $name = trim((string) ($noa->recipient_name ?? ''));
                if ($name !== '' && strcasecmp($name, (string) $addressedSupplier->proprietor) === 0) {
                    $recipientTitle = 'Proprietor';
                } elseif ($name !== '' && strcasecmp($name, (string) $addressedSupplier->authorized_representative) === 0) {
                    $recipientTitle = 'Authorized Representative';
                } elseif ($name !== '' && strcasecmp($name, (string) $addressedSupplier->owner) === 0) {
                    $recipientTitle = 'Owner';
                }
            }

            if ($recipientTitle === '') {
                $recipientTitle = 'Proprietor / Authorized Representative / Owner';
            }

            $recipientAddress = $addressedSupplier?->address ?? $winnerSupplier?->address ?? 'Batangas';
            $calculationLabel = strtoupper((string) ($resolution?->calculation_label ?: 'LOWEST CALCULATED AND RESPONSIVE QUOTATION'));
            $projectName = (string) ($rfq?->project_name ?: $resolution?->project_name);
            $amount = (float) ($noa->winner_amount ?: ($resolution?->winner_amount ?? 0));
            $amountFmt = number_format($amount, 2);
            $amountWords = \App\Helpers\NumberToWords::convert($amount, 'centavos');
        @endphp
        <div class="page">
                <div class="header">
                    <div class="header-cell header-left">
                        @if ($sealLogo)
                        <img src="{{ $sealLogo }}" alt="Batangas Seal" class="logo-seal">
                        @endif
                    </div>
                    <div class="header-cell header-mid">
                        <div class="gov-line">Republic of the Philippines</div>
                        <div class="gov-line">Provincial Government of Batangas</div>
                        <div class="gov-office">Office of the Provincial Governor</div>
                        <div class="gov-sub">Capitol Site, Kumintang Ibaba, Batangas City 4200</div>
                    </div>
                    <div class="header-cell header-right">
                        @if ($bagongLogo)
                        <img src="{{ $bagongLogo }}" alt="Bagong Pilipinas" class="logo-bagong">
                        @endif
                    </div>
                </div>

                <div class="divider"></div>

                <div class="noa-number">NOA No. {{ $noa->noa_no }}</div>

                <div class="title">Notice of Award</div>

                <div class="block">{{ optional($noa->noa_date)->format('F d, Y') }}</div>

                <div class="recipient">
                    <div style="font-weight:700; text-transform: uppercase;">{{ $supplierName }}</div>
                    <div style="font-weight:700; text-transform: uppercase;">{{ $recipientName }}</div>
                    <div>{{ $recipientTitle }}</div>
                    <div>{{ $recipientAddress }}</div>
                </div>

                <div class="body">
                    Dear {{ explode(' ', $recipientName)[0] ?? 'Sir/Madam' }},<br><br>
                    We would like to inform you that your company was declared as the supplier with <b>{{ $calculationLabel }}</b>@if($resolution), through BAC Resolution No. <b>{{ $resolution->resolution_no }}</b> dated <b>{{ optional($resolution->resolution_date)->format('F d, Y') }}</b>@endif, after passing all terms, conditions, and specifications stipulated in the Request for Quotation dated <b>{{ optional($rfq?->rfq_date)->format('F d, Y') }}</b>. Thus, you are hereby AWARDED of the project, as follows:
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 62%;">Name of Project</th>
                                <th style="width: 38%;">Contract Price in Words in Figures</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    {{ $projectName }}
                                    @if($rfq?->purchaseRequest?->office?->name)
                                    for use in {{ $rfq->purchaseRequest->office->name }}.
                                    @endif
                                </td>
                                <td>
                                    <span class="underline">{{ $amountWords }}</span>
                                    (Php {{ $amountFmt }})
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="closing">
                    In this regard, you are required to formally enter into the Purchase Order for the above project, within a period of seven (7) days, from the receipt of this Notice of Award. Failure to comply with this agreement shall be sufficient ground for cancellation of this award.
                </div>

                <div class="signature">
                    Very truly yours,
                    <div class="governor">VILMA SANTOS-RECTO</div>
                    <div>Governor</div>
                </div>

                <div class="conforme">
                    <div style="font-weight:700;">CONFORME:</div>
                    <div style="margin-top: 28px;" class="underline">{{ $recipientName }}</div>
                    <div style="font-weight:700; text-transform:uppercase;">{{ $supplierName }}</div>
                    <div>Date: __________________</div>
                </div>
            </div>
    @endforeach
</body>
</html>
