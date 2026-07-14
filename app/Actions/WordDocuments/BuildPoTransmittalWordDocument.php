<?php

declare(strict_types=1);

namespace App\Actions\WordDocuments;

use App\Models\POTransmittal;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class BuildPoTransmittalWordDocument
{
    public function handle(POTransmittal $poTransmittal): string
    {
        $poTransmittal->load([
            'purchaseOrder.noa.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.aoq.winnerSupplier',
            'purchaseOrder.noa.bacResolution.aoq.rfq.purchaseRequest.office',
            'purchaseOrder.noa.bacResolution.aoq.winnerSupplier',
        ]);

        $relatedTransmittals = POTransmittal::query()
            ->where('purchase_order_id', $poTransmittal->purchase_order_id)
            ->get();

        $coaTransmittal =
            $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');
        $purchaseOrder = $poTransmittal->purchaseOrder;
        $noa = $purchaseOrder?->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
        $rfq = $aoq?->rfq;

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);
        $phpWord->addParagraphStyle('Normal', ['lineHeight' => 1.0]);

        $sealPath = public_path('images/batangas-seal.png');
        $bagongPath = public_path('images/bagong-pilipinas.png');

        $noBorder = [
            'borderTopSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 0,
            'borderLeftSize' => 0,
            'borderColor' => 'FFFFFF',
        ];

        $dataFont = ['size' => 10];
        $boldDataFont = ['bold' => true, 'size' => 10];
        $tableCellMargin = [
            'cellMarginTop' => 30,
            'cellMarginRight' => 30,
            'cellMarginBottom' => 30,
            'cellMarginLeft' => 30,
        ];

        $buildHeader = function ($section) use (
            $sealPath,
            $bagongPath,
            $noBorder,
        ): void {
            $headerTable = $section->addTable(
                array_merge($noBorder, ['cellMargin' => 30]),
            );
            $headerTable->addRow();

            $leftCell = $headerTable->addCell(
                1800,
                array_merge($noBorder, ['valign' => 'center']),
            );
            if (is_file($sealPath)) {
                $leftCell->addImage($sealPath, [
                    'width' => 58,
                    'height' => 58,
                    'alignment' => 'center',
                ]);
            }

            $centerCell = $headerTable->addCell(
                7200,
                array_merge($noBorder, ['valign' => 'center']),
            );
            $centerCell->addText(
                'Republic of the Philippines',
                ['bold' => true],
                ['alignment' => Jc::CENTER],
            );
            $centerCell->addText(
                'PROVINCIAL GOVERNMENT OF BATANGAS',
                ['bold' => true],
                ['alignment' => Jc::CENTER],
            );
            $centerCell->addText(
                'OFFICE OF THE GENERAL SERVICES',
                ['bold' => true],
                ['alignment' => Jc::CENTER],
            );
            $centerCell->addText(
                'Capitol Site, Batangas City',
                ['bold' => true],
                ['alignment' => Jc::CENTER],
            );

            $rightCell = $headerTable->addCell(
                1800,
                array_merge($noBorder, ['valign' => 'center']),
            );
            if (is_file($bagongPath)) {
                $rightCell->addImage($bagongPath, [
                    'width' => 74,
                    'height' => 58,
                    'alignment' => 'center',
                ]);
            }

            $section->addTextBreak();
        };

        // COA Page
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        $buildHeader($section);

        $coaHeaderLines = collect(
            preg_split(
                '/\r\n|\r|\n/',
                trim((string) ($coaTransmittal?->header_text ?? '')),
            ),
        )->filter()->reject(fn (string $line): bool => trim(mb_strtolower($line)) === "ma'am,");
        if ($coaHeaderLines->isNotEmpty()) {
            foreach ($coaHeaderLines as $coaHeaderLine) {
                $section->addText($coaHeaderLine, ['bold' => true]);
            }
        } else {
            $section->addText('MARIA VANESSA C. BRIONES - VEGAS');
            $section->addText("OIC \u{2013} SUPERVISING AUDITOR");
            $section->addText('COMMISSION ON AUDIT');
            $section->addText('Capitol Site, Batangas City');
        }

        $section->addTextBreak();
        $section->addText("Ma\u{2019}am,");
        $section->addText(
            'This is to respectfully transmit to your office the Purchase Order and supporting procurement documents for the project stated below, in compliance with COA Circular No. 2009-001 and related audit requirements.',
            [],
            ['alignment' => Jc::BOTH],
        );
        $section->addTextBreak();
        $section->addTextBreak();

        $table = $section->addTable(
            array_merge(
                ['borderSize' => 6, 'borderColor' => '000000'],
                $tableCellMargin,
            ),
        );
        $table->addRow();
        $table->addCell(1400)->addText('PROJECT NO.', $boldDataFont, [
            'alignment' => Jc::CENTER,
        ]);
        $table
            ->addCell(1400)
            ->addText('PO No.', $boldDataFont, ['alignment' => Jc::CENTER]);
        $table
            ->addCell(1400)
            ->addText('Date', $boldDataFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1800)->addText('Mode of Procurement', $boldDataFont, [
            'alignment' => Jc::CENTER,
        ]);
        $table->addCell(2000)->addText('NAME OF SUPPLIER', $boldDataFont, [
            'alignment' => Jc::CENTER,
        ]);
        $table->addCell(3000)->addText('NAME OF PROJECT', $boldDataFont, [
            'alignment' => Jc::CENTER,
        ]);
        $table->addCell(1400)->addText('CONTRACT AMOUNT', $boldDataFont, [
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow();
        $table
            ->addCell(1400)
            ->addText('N/A', $dataFont, ['alignment' => Jc::CENTER]);
        $table
            ->addCell(1400)
            ->addText($purchaseOrder?->po_no ?? '', $dataFont, [
                'alignment' => Jc::CENTER,
            ]);
        $table
            ->addCell(1400)
            ->addText(
                optional($purchaseOrder?->po_date)->format('m/d/Y') ?? '',
                $dataFont,
                ['alignment' => Jc::CENTER],
            );
        $table
            ->addCell(1800)
            ->addText(
                strtoupper(
                    (string) ($purchaseOrder?->mode_of_procurement ?? ''),
                ),
                $dataFont,
                ['alignment' => Jc::CENTER],
            );
        $table
            ->addCell(2000)
            ->addText(
                strtoupper(
                    (string) ($aoq?->winnerSupplier?->name ?? "\u{2014}"),
                ),
                $dataFont,
                ['alignment' => Jc::CENTER],
            );
        $table
            ->addCell(3000)
            ->addText(
                $rfq?->project_name ??
                    ($noa?->bacResolution?->project_name ?? "\u{2014}"),
                $dataFont,
            );
        $table
            ->addCell(1400)
            ->addText(
                number_format((float) ($purchaseOrder?->total_amount ?? 0), 2),
                $dataFont,
                ['alignment' => Jc::CENTER],
            );

        $section->addTextBreak();
        $section->addText('Thank you very much.');
        $section->addTextBreak();
        $section->addText('Very truly yours,');
        $section->addTextBreak();
        $section->addText(
            strtoupper(
                (string) ($coaTransmittal?->signatory_name ??
                    'NOEL R. ROCAFORT'),
            ),
            ['bold' => true],
        );
        $section->addText(
            strtoupper(
                (string) ($coaTransmittal?->signatory_title ??
                    'PGDH \u{2013} GSO'),
            ),
        );

        // OPG Page
        if ($opgTransmittal) {
            $section->addPageBreak();

            $buildHeader($section);

            $opgHeaderLines = collect(
                preg_split(
                    '/\r\n|\r|\n/',
                    trim((string) ($opgTransmittal?->header_text ?? '')),
                ),
            )->filter()->reject(fn (string $line): bool => trim(mb_strtolower($line)) === "ma'am,");
            if ($opgHeaderLines->isNotEmpty()) {
                foreach ($opgHeaderLines as $opgHeaderLine) {
                    $section->addText($opgHeaderLine, ['bold' => true]);
                }
            } else {
                $section->addText('HON. VILMA SANTOS - RECTO', ['bold' => true]);
                $section->addText('Governor');
                $section->addText('Province of Batangas');
                $section->addText('Capitol Site, Batangas City');
            }

            $section->addTextBreak();
            $section->addText("Ma\u{2019}am,");
            $section->addText(
                'This is to respectfully transmit to your office the Purchase Order and related procurement documents for the project below:',
                [],
                ['alignment' => Jc::BOTH],
            );
            $section->addTextBreak();

            $opgTable = $section->addTable(
                array_merge(
                    ['borderSize' => 6, 'borderColor' => '000000'],
                    $tableCellMargin,
                ),
            );
            $opgTable->addRow();
            $opgTable->addCell(2000)->addText('PROJECT NO.', $boldDataFont, [
                'alignment' => Jc::CENTER,
            ]);
            $opgTable
                ->addCell(3000)
                ->addText('NAME OF SUPPLIER', $boldDataFont, [
                    'alignment' => Jc::CENTER,
                ]);
            $opgTable
                ->addCell(5000)
                ->addText('NAME OF PROJECT', $boldDataFont, [
                    'alignment' => Jc::CENTER,
                ]);
            $opgTable
                ->addCell(2000)
                ->addText('CONTRACT AMOUNT', $boldDataFont, [
                    'alignment' => Jc::CENTER,
                ]);

            $opgTable->addRow();
            $opgTable
                ->addCell(2000)
                ->addText('N/A', $dataFont, ['alignment' => Jc::CENTER]);
            $opgTable
                ->addCell(3000)
                ->addText(
                    strtoupper(
                        (string) ($aoq?->winnerSupplier?->name ?? "\u{2014}"),
                    ),
                    $dataFont,
                );
            $opgTable
                ->addCell(5000)
                ->addText(
                    $rfq?->project_name ??
                        ($noa?->bacResolution?->project_name ?? "\u{2014}"),
                    $dataFont,
                );
            $opgTable
                ->addCell(2000)
                ->addText(
                    number_format(
                        (float) ($purchaseOrder?->total_amount ?? 0),
                        2,
                    ),
                    $dataFont,
                    ['alignment' => Jc::CENTER],
                );

            $section->addTextBreak();
            $section->addText('Thank you very much.');
            $section->addTextBreak();
            $section->addText('Very truly yours,');
            $section->addTextBreak();
            $section->addText(
                strtoupper(
                    (string) ($opgTransmittal?->signatory_name ??
                        'NOEL R. ROCAFORT'),
                ),
                ['bold' => true],
            );
            $section->addText(
                strtoupper(
                    (string) ($opgTransmittal?->signatory_title ??
                        'PGDH \u{2013} GSO'),
                ),
            );
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }
}
