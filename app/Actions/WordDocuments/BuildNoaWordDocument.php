<?php

declare(strict_types=1);

namespace App\Actions\WordDocuments;

use App\Helpers\NumberToWords;
use App\Models\NOA;
use App\Models\Supplier;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class BuildNoaWordDocument
{
    public function handle(NOA $noa, string $outputPath): void
    {
        $noa->load([
            "aoq.rfq.purchaseRequest.office",
            "aoq.rfq.suppliers.supplierItems.rfqItem",
            "aoq.winnerSupplier",
            "bacResolution.aoq.rfq.purchaseRequest.office",
            "bacResolution.aoq.winnerSupplier",
        ]);

        $resolution = $noa->bacResolution;
        $aoq = $noa->aoq ?? $resolution?->aoq;
        $rfq = $aoq?->rfq;
        $supplierName =
            (string) ($aoq?->winnerSupplier?->name ??
                ($resolution?->winner_supplier_name ?? ""));
        $addressedSupplier = null;

        if ($supplierName !== "") {
            $addressedSupplier = Supplier::query()
                ->where("name", $supplierName)
                ->first();
        }

        $calculatedSupplierCount = 0;
        foreach ($rfq?->suppliers ?? collect() as $entry) {
            if (!$entry->submitted_at) {
                continue;
            }

            $hasAtLeastOnePrice = false;
            foreach ($entry->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price !== null) {
                    $hasAtLeastOnePrice = true;
                    break;
                }
            }

            if ($hasAtLeastOnePrice) {
                $calculatedSupplierCount++;
            }
        }

        $calculationLabel = strtoupper(
            $calculatedSupplierCount <= 1
                ? "Single Calculated and Responsive Quotation"
                : "Lowest Calculated and Responsive Quotation",
        );

        $recipientTitle = trim((string) ($noa->recipient_title ?? ""));
        if ($recipientTitle === "" && $addressedSupplier) {
            $recipientName = trim((string) ($noa->recipient_name ?? ""));
            if (
                $recipientName !== "" &&
                strcasecmp(
                    $recipientName,
                    (string) $addressedSupplier->proprietor,
                ) === 0
            ) {
                $recipientTitle = "Proprietor";
            } elseif (
                $recipientName !== "" &&
                strcasecmp(
                    $recipientName,
                    (string) $addressedSupplier->authorized_representative,
                ) === 0
            ) {
                $recipientTitle = "Authorized Representative";
            } elseif (
                $recipientName !== "" &&
                strcasecmp(
                    $recipientName,
                    (string) $addressedSupplier->owner,
                ) === 0
            ) {
                $recipientTitle = "Owner";
            }
        }

        if ($recipientTitle === "") {
            $recipientTitle = "Proprietor / Authorized Representative / Owner";
        }

        $resolutionNo = $resolution?->resolution_no;
        if (!$resolutionNo && $aoq?->batch) {
            $resolutionNo = $aoq->batch->generateResolutionNo($aoq);
        }

        $resolutionSeries = $resolution?->resolution_date
            ? $resolution->resolution_date->format("Y")
            : ($noa->noa_date
                ? $noa->noa_date->format("Y")
                : date("Y"));

        $recipientRaw =
            $noa->recipient_name ?:
            ($addressedSupplier?->proprietor ?:
            ($addressedSupplier?->authorized_representative ?:
            ($addressedSupplier?->owner ?:
            ($aoq?->winnerSupplier?->contact_person ?:
            "AUTHORIZED REPRESENTATIVE"))));
        $recipientName = strtoupper((string) $recipientRaw);
        $winnerSupplierName = strtoupper(
            (string) ($resolution?->winner_supplier_name ??
                ($aoq?->winnerSupplier?->name ?? "SUPPLIER")),
        );
        $recipientAddress =
            $addressedSupplier?->address ??
            ($aoq?->winnerSupplier?->address ?? "Batangas");

        $projectName =
            (string) ($rfq?->project_name ?: $resolution?->project_name);
        $amount =
            (float) ($noa->winner_amount ?: $resolution?->winner_amount ?? 0);
        $amountFmt = number_format($amount, 2);
        $amountWords = NumberToWords::convert($amount, "centavos");

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName("Arial");
        $phpWord->setDefaultFontSize(11);
        $phpWord->addParagraphStyle("Normal", ["lineHeight" => 1.0]);

        $section = $phpWord->addSection([
            "pageSizeW" => 11906,
            "pageSizeH" => 16838,
            "marginTop" => 720,
            "marginBottom" => 720,
            "marginLeft" => 720,
            "marginRight" => 720,
        ]);

        // Header with logos — all borders explicitly set to 0
        $sealPath = public_path("images/batangas-seal.png");
        $bagongPath = public_path("images/bagong-pilipinas.png");

        $noBorder = [
            "borderTopSize" => 0,
            "borderRightSize" => 0,
            "borderBottomSize" => 0,
            "borderLeftSize" => 0,
            "borderColor" => "FFFFFF",
        ];

        $headerTable = $section->addTable(
            array_merge($noBorder, ["cellMargin" => 30]),
        );
        $headerTable->addRow();

        // Left cell: Batangas Seal
        $leftCell = $headerTable->addCell(
            1800,
            array_merge($noBorder, ["valign" => "center"]),
        );
        if (is_file($sealPath)) {
            $leftCell->addImage($sealPath, [
                "width" => 58,
                "height" => 58,
                "alignment" => "center",
            ]);
        }

        // Center cell: Government header text
        $centerCell = $headerTable->addCell(
            7200,
            array_merge($noBorder, ["valign" => "center"]),
        );
        $centerCell->addText(
            "Republic of the Philippines",
            ["size" => 11],
            ["alignment" => Jc::CENTER],
        );
        $centerCell->addText(
            "Province of Batangas",
            ["size" => 11],
            ["alignment" => Jc::CENTER],
        );
        $centerCell->addText(
            "OFFICE OF THE PROVINCIAL GOVERNOR",
            ["bold" => true, "size" => 13],
            ["alignment" => Jc::CENTER],
        );
        $centerCell->addText(
            "Capitol Building, Batangas City 4200",
            ["size" => 10],
            ["alignment" => Jc::CENTER],
        );

        // Right cell: Bagong Pilipinas logo
        $rightCell = $headerTable->addCell(
            1800,
            array_merge($noBorder, ["valign" => "center"]),
        );
        if (is_file($bagongPath)) {
            $rightCell->addImage($bagongPath, [
                "width" => 74,
                "height" => 58,
                "alignment" => "center",
            ]);
        }

        $section->addTextBreak();

        // NOA number
        $section->addText("NOA No. " . $noa->noa_no, ["bold" => true]);

        // Title
        $section->addText(
            "NOTICE OF AWARD",
            ["bold" => true],
            ["alignment" => Jc::CENTER],
        );

        // Date
        $section->addText(
            optional($noa->noa_date)->format("F d, Y"),
            [],
            ["spaceAfter" => 40],
        );

        // Recipient block
        $section->addText(
            $winnerSupplierName,
            ["bold" => true],
            ["spaceAfter" => 40],
        );
        $section->addText(
            $recipientName,
            ["bold" => true],
            ["spaceAfter" => 40],
        );
        $section->addText($recipientTitle, [], ["spaceAfter" => 40]);
        $section->addText($recipientAddress, [], ["spaceAfter" => 40]);

        // Body text with mixed bold
        $_nameParts = explode(" ", $recipientName);
        $_surname = count($_nameParts) > 1 ? end($_nameParts) : $_nameParts[0];

        $section->addText(
            "Dear Ms/Mr " . $_surname . ",",
            [],
            ["spaceAfter" => 40],
        );
        $section->addTextBreak();

        $bodyRun = $section->addTextRun([
            "alignment" => Jc::BOTH,
            "lineHeight" => 1.0,
        ]);
        $bodyRun->addText(
            "We would like to inform you that your company was declared as the supplier with ",
        );
        $bodyRun->addText($calculationLabel, ["bold" => true]);
        if ($resolutionNo) {
            $bodyRun->addText(
                sprintf(
                    ", through Resolution No. %s, Series %s",
                    $resolutionNo,
                    $resolutionSeries,
                ),
                ["bold" => true],
            );
        }
        $bodyRun->addText(
            ", after passing all the terms, conditions and/or specifications needed by the Procuring Entity as stipulated in the Request for Quotation, dated ",
        );
        $bodyRun->addText(optional($rfq?->rfq_date)->format("F d, Y"), [
            "bold" => true,
        ]);
        $bodyRun->addText(
            ". Thus, you are hereby AWARDED of the project, as follows:",
        );

        // Table — full width with cell padding
        $table = $section->addTable([
            "borderSize" => 6,
            "borderColor" => "000000",
            "width" => 100,
            "cellMarginTop" => 50,
            "cellMarginRight" => 80,
            "cellMarginBottom" => 50,
            "cellMarginLeft" => 80,
        ]);
        $table->addRow();
        $table
            ->addCell(5233)
            ->addText(
                "Name of Project",
                ["bold" => true],
                ["alignment" => Jc::CENTER],
            );
        $table
            ->addCell(5233)
            ->addText(
                "Contract Price in Words in Figures",
                ["bold" => true],
                ["alignment" => Jc::CENTER],
            );

        $table->addRow();
        $table
            ->addCell(5233)
            ->addText(
                $projectName .
                    ($rfq?->purchaseRequest?->office?->name
                        ? sprintf(
                            " for use in %s.",
                            $rfq->purchaseRequest->office->name,
                        )
                        : ""),
            );
        $table
            ->addCell(5233)
            ->addText($amountWords . " Only\n(Php {$amountFmt})", [
                "bold" => true,
            ]);

        $section->addTextBreak();

        // Closing paragraph
        $section->addText(
            "In this regard, you are required to formally enter into the Purchase Order for the above project, within a period of seven (7) days, from the receipt of this Notice of Award. Failure to comply with this agreement shall be sufficient ground for cancellation of this award.",
            [],
            ["alignment" => Jc::BOTH],
        );
        $section->addTextBreak();

        // Signature block
        $section->addText("Very truly yours,");
        $section->addTextBreak();
        $section->addText("VILMA SANTOS-RECTO", ["bold" => true]);
        $section->addText("Governor");
        $section->addTextBreak();

        // Conforme block
        $section->addText("CONFORME:", ["bold" => true]);
        $section->addText("_______________________");
        $section->addText($recipientName);
        $section->addText($winnerSupplierName, ["bold" => true]);
        $section->addText("Date: __________________");

        $writer = IOFactory::createWriter($phpWord, "Word2007");
        $writer->save($outputPath);
    }
}
