<?php

declare(strict_types=1);

namespace App\Actions\WordDocuments;

use App\Models\AOQ;
use App\Models\BACResolution;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class BuildBacResolutionWordDocument
{
    public function handle(BACResolution $bacResolution): string
    {
        $bacResolution->load([
            'aoqs.rfq.purchaseRequest.office',
            'aoqs.rfq.items.purchaseRequestItem',
            'aoqs.rfq.suppliers.supplier',
            'aoqs.rfq.suppliers.supplierItems.rfqItem',
            'aoqs.winnerSupplier',
            'aoq.rfq.purchaseRequest.office',
            'aoq.rfq.items',
            'aoq.rfq.suppliers.supplier',
            'aoq.rfq.suppliers.supplierItems.rfqItem',
        ]);

        $resolutionYear = optional($bacResolution->resolution_date)->format('Y') ?? now()->year;

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
            'lineHeight' => 240,
        ]);

        // Header with logos
        $sealPath = public_path('images/batangas-seal.png');
        $bagongPath = public_path('images/bagong-pilipinas.png');

        $headerTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 30,
        ]);
        $headerTable->addRow();

        $leftCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
        if (is_file($sealPath)) {
            $leftCell->addImage($sealPath, ['width' => 60, 'height' => 60, 'alignment' => 'center']);
        }

        $centerCell = $headerTable->addCell(7200, ['borderSize' => 0, 'valign' => 'center']);
        $centerCell->addText('REPUBLIC OF THE PHILIPPINES', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $centerCell->addText('PROVINCIAL GOVERNMENT OF BATANGAS', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER]);
        $centerCell->addText('Capitol Site, Kumintang Ibaba, Batangas City 4200', ['size' => 10], ['alignment' => Jc::CENTER]);
        $centerCell->addText('BIDS and AWARDS COMMITTEE', ['bold' => true, 'size' => 16, 'underline' => 'single'], ['alignment' => Jc::CENTER]);

        $rightCell = $headerTable->addCell(1800, ['borderSize' => 0, 'valign' => 'center']);
        if (is_file($bagongPath)) {
            $rightCell->addImage($bagongPath, ['width' => 78, 'height' => 60, 'alignment' => 'center']);
        }

        $section->addTextBreak();

        // Resolution number
        $section->addText(sprintf('RESOLUTION NO. %s, SERIES OF %s', $bacResolution->resolution_no, $resolutionYear), ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        // Resolution main title
        $section->addText(
            'RESOLUTION RECOMMENDING THE AWARD OF CONTRACT TO THE SUPPLIERS WITH THE LOWEST/SINGLE CALCULATED RESPONSIVE QUOTATIONS, THROUGH SMALL VALUE PROCUREMENT (TWO HUNDRED THOUSAND PESOS AND BELOW)',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::BOTH]
        );
        $section->addTextBreak();

        // Batch AOQs
        $batchAoqs = $bacResolution->aoqs;
        if ($batchAoqs->isEmpty() && $bacResolution->aoq) {
            $batchAoqs = collect([$bacResolution->aoq]);
        }

        $section->addText('WHEREAS, the Provincial Government of Batangas, through its Bids and Awards Committee (BAC), is in need of suppliers for the following:', [], ['alignment' => Jc::BOTH]);

        $summaryTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        $summaryTable->addRow();
        $summaryTable->addCell(2000)->addText('OFFICE', ['bold' => true], ['alignment' => Jc::CENTER]);
        $summaryTable->addCell(6000)->addText('NAME OF PROJECT', ['bold' => true], ['alignment' => Jc::CENTER]);
        $summaryTable->addCell(3000)->addText('APPROVED BUDGET FOR THE CONTRACT (ABC)', ['bold' => true], ['alignment' => Jc::CENTER]);

        foreach ($batchAoqs as $aoq) {
            $summaryTable->addRow();
            $summaryTable->addCell(2000)->addText(strtoupper((string) ($aoq->rfq?->purchaseRequest?->office?->name ?? 'OFFICE')));
            $summaryTable->addCell(6000)->addText((string) ($aoq->rfq?->project_name ?? 'PROJECT'));
            $summaryTable->addCell(3000)->addText(number_format((float) ($aoq->rfq?->abc_amount ?? 0), 2));
        }

        $this->addWhereasClauses($section, $resolutionYear);

        // Abstract of Quotation sections
        $abstracts = $batchAoqs->map(function (AOQ $aoq): array {
            $rfq = $aoq->rfq;
            if (! $rfq) {
                return ['svp_no' => 'N/A', 'rfq_date' => null, 'project_name' => 'PROJECT', 'suppliers' => collect(), 'items' => collect(), 'abc_total' => 0.0];
            }

            $supplierTotals = collect($this->calculateSupplierTotals($aoq)['supplier_totals'] ?? []);
            $rankedSuppliers = $supplierTotals->values()->map(function (array $row, int $index): array {
                $rank = $index + 1;
                $rankLabel = $rank === 1 ? '1ST' : ($rank === 2 ? '2ND' : ($rank === 3 ? '3RD' : $rank.'TH'));

                return [
                    'supplier_id' => (int) $row['supplier_id'],
                    'supplier_name' => strtoupper((string) ($row['supplier_name'] ?? 'N/A')),
                    'total_amount' => (float) ($row['total_amount'] ?? 0),
                    'rank_label' => $rankLabel,
                ];
            });

            $items = collect($rfq?->items ?? [])->map(function ($item) use ($rfq, $rankedSuppliers): array {
                $quantity = (float) ($item->quantity ?? 0);
                $approvedBudget = $quantity * (float) ($item->purchaseRequestItem?->unit_cost ?? 0);
                $supplierColumns = $rankedSuppliers->map(function (array $supplier) use ($rfq, $item, $quantity): array {
                    $entry = collect($rfq?->suppliers ?? [])->firstWhere('supplier_id', $supplier['supplier_id']);
                    $supplierItem = $entry?->supplierItems?->firstWhere('rfq_item_id', $item->id);
                    $unitCost = $supplierItem?->unit_price;
                    $lineTotal = $unitCost !== null ? ((float) $unitCost * $quantity) : null;

                    return ['unit_cost' => $unitCost !== null ? (float) $unitCost : null, 'total_amount' => $lineTotal];
                })->values();

                return ['quantity' => $quantity, 'unit' => (string) ($item->unit ?? ''), 'particulars' => (string) ($item->item_name ?? ''), 'approved_budget' => $approvedBudget, 'supplier_columns' => $supplierColumns];
            })->values();

            return ['svp_no' => (string) ($rfq?->svp_no ?? 'N/A'), 'rfq_date' => $rfq?->rfq_date, 'project_name' => (string) ($rfq?->project_name ?? 'PROJECT'), 'suppliers' => $rankedSuppliers, 'items' => $items, 'abc_total' => (float) ($rfq?->abc_amount ?? 0)];
        })->values();

        foreach ($abstracts as $abstract) {
            $section->addTextBreak();
            $section->addText('ABSTRACT OF QUOTATION', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
            $section->addText('DATE OF RFQ: '.($abstract['rfq_date'] ? \Carbon\Carbon::parse($abstract['rfq_date'])->format('F d, Y') : '__________'), ['bold' => true]);
            $section->addText(sprintf('SVP NO. %s - %s', $abstract['svp_no'], $abstract['project_name']), ['bold' => true]);

            $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);

            $headerRow = $table->addRow();
            $headerRow->addCell(800)->addText('QTY', ['bold' => true], ['alignment' => Jc::CENTER]);
            $headerRow->addCell(800)->addText('UNIT', ['bold' => true], ['alignment' => Jc::CENTER]);
            $headerRow->addCell(2800)->addText('PARTICULARS', ['bold' => true], ['alignment' => Jc::CENTER]);
            $headerRow->addCell(1400)->addText('ABC', ['bold' => true], ['alignment' => Jc::CENTER]);
            foreach ($abstract['suppliers'] as $supplier) {
                $headerRow->addCell(1400)->addText(sprintf('%s (%s)', $supplier['supplier_name'], $supplier['rank_label']), ['bold' => true], ['alignment' => Jc::CENTER]);
                $headerRow->addCell(1400)->addText('TOTAL AMOUNT', ['bold' => true], ['alignment' => Jc::CENTER]);
            }

            foreach ($abstract['items'] as $item) {
                $row = $table->addRow();
                $row->addCell(800)->addText((string) ((int) ($item['quantity'] ?? 0)));
                $row->addCell(800)->addText($item['unit'] ?? '');
                $row->addCell(2800)->addText($item['particulars'] ?? '');
                $row->addCell(1400)->addText(number_format((float) ($item['approved_budget'] ?? 0), 2));
                foreach ($item['supplier_columns'] ?? [] as $col) {
                    $row->addCell(1400)->addText($col['unit_cost'] !== null ? number_format((float) $col['unit_cost'], 2) : '');
                    $row->addCell(1400)->addText($col['total_amount'] !== null ? number_format((float) $col['total_amount'], 2) : '');
                }
            }

            $totalRow = $table->addRow();
            $totalRow->addCell(800)->addText('');
            $totalRow->addCell(800)->addText('');
            $totalRow->addCell(2800)->addText('TOTAL', ['bold' => true], ['alignment' => Jc::RIGHT]);
            $totalRow->addCell(1400)->addText(number_format((float) ($abstract['abc_total'] ?? 0), 2));
            foreach ($abstract['suppliers'] as $supplier) {
                $totalRow->addCell(1400)->addText('');
                $totalRow->addCell(1400)->addText(number_format((float) ($supplier['total_amount'] ?? 0), 2), ['bold' => true]);
            }
        }

        $section->addTextBreak();
        $section->addText('WHEREAS, upon careful examination, validation and verification of all the documents submitted by the suppliers with the Lowest Calculated Quotation, their price quotations have been found to be responsive;', [], ['alignment' => Jc::BOTH]);
        $section->addTextBreak();

        $section->addText(
            'NOW, THEREFORE, we, the members of the Bids and Awards Committee of the Provincial Government of Batangas, acknowledging the submitted Abstract of Quotation by the GSO, RESOLVE, as it is hereby RESOLVED, to recommend to the HON. GOVERNOR VILMA SANTOS-RECTO, the approval of the aforesaid Abstract of Quotation, as conducted and submitted by the GSO Team of Canvassers, pursuant to Sections 34.1 and 34.2 of the Implementing Rules and Regulations of RA No. 12009; to declare the abovementioned suppliers as those with the Lowest Calculated Responsive Quotations for the purchase and delivery of various goods of the Provincial Government of Batangas, and to recommend the awarding of their respective Contracts, after passing the criteria set forth by the procurement law and the rules of the BAC;',
            [],
            ['alignment' => Jc::BOTH]
        );

        $section->addTextBreak();
        $section->addText(sprintf('UNANIMOUSLY APPROVED, this ____ day of _________________________ %s.', $resolutionYear), ['bold' => true], ['alignment' => Jc::CENTER]);
        $section->addText('CERTIFIED TO BE DULY ATTESTED AND APPROVED:', ['bold' => true], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();

        // Signatory grid
        $signTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $signTable->addRow();
        $signTable->addCell(3000)->addText('MR. NOEL R. ROCAFORT', ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addCell(3000)->addText('MR. PEDRITO MARTIN M. DIJAN, JR.', ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addRow();
        $signTable->addCell(3000)->addText('Member', '', ['alignment' => Jc::CENTER]);
        $signTable->addCell(3000)->addText('Member', '', ['alignment' => Jc::CENTER]);

        $signTable->addRow();
        $signTable->addCell(3000)->addText('ENGR. NERIO L. RONQUILLO, JR.', ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addCell(3000)->addText('ATTY. LOUIE MARK M. DALAWAMPU', ['bold' => true], ['alignment' => Jc::CENTER]);
        $signTable->addRow();
        $signTable->addCell(3000)->addText('Member', '', ['alignment' => Jc::CENTER]);
        $signTable->addCell(3000)->addText('Member', '', ['alignment' => Jc::CENTER]);

        $section->addTextBreak();
        $section->addText('ATTY. JOEL L. MONTEALTO', ['bold' => true], ['alignment' => Jc::CENTER]);
        $section->addText('Chairperson', '', ['alignment' => Jc::CENTER]);
        $section->addTextBreak();
        $section->addText('Approved by:', '', ['alignment' => Jc::CENTER]);
        $section->addText('VILMA SANTOS - RECTO', ['bold' => true], ['alignment' => Jc::CENTER]);
        $section->addText('Governor', '', ['alignment' => Jc::CENTER]);

        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return $tempFile;
    }

    private function addWhereasClauses($section, string $resolutionYear): void
    {
        $clauses = [
            'Rule IV (Mode of Procurement) of Republic Act (RA) No. 12009 or the New Government Procurement Act states that the Procuring Entity may, in order to promote economy and efficiency, resort to aforesaid method of procurement and shall ensure that it is the most advantageous price for the government;',
            'Rule IV, Section 26 of R.A. No. 12009 likewise provides for Small Value Procurement (SVP) as a mode of procurement, consistent with the Fit-for-Purpose procurement approach;',
            'under Section 34.1 of the Implementing Rules and Regulations (IRR) of RA No. 12009, Small Value Procurement (SVP) is a mode of procurement whereby the Procuring Entity requests for the submission of at least three (3) price quotations for Goods not available in the PS-DBM, Infrastructure Projects, and Consulting Services;',
            'under Section 34.3 b) of the same IRR, except for those with ABCs equal to Two Hundred Thousand Pesos (P200,000.00) and below which shall not require posting, RFQ or Request for Proposal (RFP) shall be posted for a period of three (3) calendar days on the PhilGEPS website, website of the Procuring Entity, if available, and at any conspicuous place reserved for this purpose in the premises of the Procuring Entity;',
            'the receipt of one (1) quotation is sufficient to proceed with the evaluation of bidders: provided, that, the amount involved does not exceed Two Million Pesos (P2,000,000.00), as detailed in the table contained in Section 34.2, and subject to the periodic review of the threshold amount and adjustments as may be deemed appropriate by the GPPB;',
            'the General Services Office (GSO) invited prospective suppliers to furnish their quotations on the items abovementioned;',
            sprintf('on the deadline for the submission of Request for Quotation (RFQ) Forms last __________________, %s, the GSO prepared the hereunder Abstract of Quotation from all the interested bidders within the prescribed period of posting, to wit:', $resolutionYear),
        ];

        foreach ($clauses as $clause) {
            $section->addTextBreak();
            $section->addText('WHEREAS, '.$clause, [], ['alignment' => Jc::BOTH]);
        }
    }
}
