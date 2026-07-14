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

        $coaTransmittal = $relatedTransmittals->firstWhere('type', 'coa') ?? $poTransmittal;
        $opgTransmittal = $relatedTransmittals->firstWhere('type', 'opg');
        $purchaseOrder = $poTransmittal->purchaseOrder;
        $noa = $purchaseOrder?->noa;
        $aoq = $noa?->aoq ?? $noa?->bacResolution?->aoq;
        $rfq = $aoq?->rfq;

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $sealPath = public_path('images/batangas-seal.png');
        $bagongPath = public_path('images/bagong-pilipinas.png');

        $buildHeader = function ($section) use ($sealPath, $bagongPath): void {
            $headerTable = $section->addTable([
                'borderSize' => 0,
                'borderColor' => 'FFFFFF',
                'cellMargin' => 30,
            ]);
            $headerTable->addRow();

            $leftCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
            if (is_file($sealPath)) {
                $leftCell->addImage($sealPath, ['width' => 58, 'height' => 58, 'alignment' => 'center']);
            }

            $centerCell = $headerTable->addCell(7200, ['borderSize' => 0, 'valign' => 'center']);
            $centerCell->addText('Republic of the Philippines', ['bold' => true], ['alignment' => Jc::CENTER]);
            $centerCell->addText('PROVINCIAL GOVERNMENT OF BATANGAS', ['bold' => true], ['alignment' => Jc::CENTER]);
            $centerCell->addText('OFFICE OF THE GENERAL SERVICES', ['bold' => true], ['alignment' => Jc::CENTER]);
            $centerCell->addText('Capitol Site, Batangas City', ['bold' => true], ['alignment' => Jc::CENTER]);

            $rightCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
            if (is_file($bagongPath)) {
                $rightCell->addImage($bagongPath, ['width' => 74, 'height' => 58, 'alignment' => 'center']);
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
            'lineHeight' => 240,
        ]);

        $buildHeader($section);

        $coaHeaderLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($coaTransmittal?->header_text ?? ''))))->filter();
        if ($coaHeaderLines->isNotEmpty()) {
            foreach ($coaHeaderLines as $line) {
                $section->addText($line, ['bold' => true]);
            }
        } else {
            $section->addText('MARIA VANESSA C. BRIONES - VEGAS');
            $section->addText('OIC \u{2013} SUPERVISING AUDITOR');
            $section->addText('COMMISSION ON AUDIT');
            $section->addText('Capitol Site, Batangas City');
        }

        $section->addTextBreak();
        $section->addText("Ma\u{2019}am,");
        $section->addText('This is to respectfully transmit to your office the Purchase Order and supporting procurement documents for the project stated below, in compliance with COA Circular No. 2009-001 and related audit requirements.', [], ['alignment' => Jc::BOTH]);
        $section->addTextBreak();

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        $table->addRow();
        $table->addCell(1400)->addText('PROJECT NO.', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1400)->addText('PO No.', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1400)->addText('Date', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1800)->addText('Mode of Procurement', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2000)->addText('NAME OF SUPPLIER', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(3000)->addText('NAME OF PROJECT', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1400)->addText('CONTRACT AMOUNT', ['bold' => true], ['alignment' => Jc::CENTER]);

        $table->addRow();
        $table->addCell(1400)->addText('N/A', '', ['alignment' => Jc::CENTER]);
        $table->addCell(1400)->addText($purchaseOrder?->po_no ?? '', '', ['alignment' => Jc::CENTER]);
        $table->addCell(1400)->addText(optional($purchaseOrder?->po_date)->format('m/d/Y') ?? '', '', ['alignment' => Jc::CENTER]);
        $table->addCell(1800)->addText(strtoupper((string) ($purchaseOrder?->mode_of_procurement ?? '')), '', ['alignment' => Jc::CENTER]);
        $table->addCell(2000)->addText(strtoupper((string) ($aoq?->winnerSupplier?->name ?? "\u{2014}")), '', ['alignment' => Jc::CENTER]);
        $table->addCell(3000)->addText($rfq?->project_name ?? $noa?->bacResolution?->project_name ?? "\u{2014}");
        $table->addCell(1400)->addText(number_format((float) ($purchaseOrder?->total_amount ?? 0), 2), '', ['alignment' => Jc::CENTER]);

        $section->addTextBreak();
        $section->addText('Thank you very much.');
        $section->addTextBreak(2);
        $section->addText('Very truly yours,');
        $section->addTextBreak(3);
        $section->addText(strtoupper((string) ($coaTransmittal?->signatory_name ?: 'NOEL R. ROCAFORT')), ['bold' => true]);
        $section->addText(strtoupper((string) ($coaTransmittal?->signatory_title ?: 'PGDH \u{2013} GSO')));

        // OPG Page
        if ($opgTransmittal) {
            $section->addPageBreak();

            $buildHeader($section);

            $opgHeaderLines = collect(preg_split('/\r\n|\r|\n/', trim((string) ($opgTransmittal?->header_text ?? ''))))->filter();
            if ($opgHeaderLines->isNotEmpty()) {
                foreach ($opgHeaderLines as $line) {
                    $section->addText($line, ['bold' => true]);
                }
            } else {
                $section->addText('HON. VILMA SANTOS - RECTO', ['bold' => true]);
                $section->addText('Governor');
                $section->addText('Province of Batangas');
                $section->addText('Capitol Site, Batangas City');
                $section->addTextBreak();
                $section->addText("Ma\u{2019}am,");
            }

            $section->addTextBreak();
            $section->addText('This is to respectfully transmit to your office the Purchase Order and related procurement documents for the project below:', [], ['alignment' => Jc::BOTH]);
            $section->addTextBreak();

            $opgTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            $opgTable->addRow();
            $opgTable->addCell(2000)->addText('PROJECT NO.', ['bold' => true], ['alignment' => Jc::CENTER]);
            $opgTable->addCell(3000)->addText('NAME OF SUPPLIER', ['bold' => true], ['alignment' => Jc::CENTER]);
            $opgTable->addCell(5000)->addText('NAME OF PROJECT', ['bold' => true], ['alignment' => Jc::CENTER]);
            $opgTable->addCell(2000)->addText('CONTRACT AMOUNT', ['bold' => true], ['alignment' => Jc::CENTER]);

            $opgTable->addRow();
            $opgTable->addCell(2000)->addText('N/A', '', ['alignment' => Jc::CENTER]);
            $opgTable->addCell(3000)->addText(strtoupper((string) ($aoq?->winnerSupplier?->name ?? "\u{2014}")));
            $opgTable->addCell(5000)->addText($rfq?->project_name ?? $noa?->bacResolution?->project_name ?? "\u{2014}");
            $opgTable->addCell(2000)->addText(number_format((float) ($purchaseOrder?->total_amount ?? 0), 2), '', ['alignment' => Jc::CENTER]);

            $section->addTextBreak();
            $section->addText('Thank you very much.');
            $section->addTextBreak(2);
            $section->addText('Very truly yours,');
            $section->addTextBreak(3);
            $section->addText(strtoupper((string) ($opgTransmittal?->signatory_name ?: 'NOEL R. ROCAFORT')), ['bold' => true]);
            $section->addText(strtoupper((string) ($opgTransmittal?->signatory_title ?: 'PGDH \u{2013} GSO')));
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }
}
