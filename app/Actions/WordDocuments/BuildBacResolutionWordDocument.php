<?php

declare(strict_types=1);

namespace App\Actions\WordDocuments;

use App\Models\AOQ;
use App\Models\BACResolution;
use App\Models\RFQ;
use Carbon\Carbon;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
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

        $resolutionYear =
            optional($bacResolution->resolution_date)->format('Y') ??
            now()->year;

        Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->addParagraphStyle('Normal', ['lineHeight' => 1.0]);

        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Page {PAGE} of {NUMPAGES}',
            ['size' => 9],
            ['alignment' => Jc::CENTER],
        );

        // Header with logos — all borders explicitly set to 0
        $sealPath = public_path('images/batangas-seal.png');
        $bagongPath = public_path('images/bagong-pilipinas.png');

        $noBorder = [
            'borderTopSize' => 0,
            'borderRightSize' => 0,
            'borderBottomSize' => 0,
            'borderLeftSize' => 0,
            'borderColor' => 'FFFFFF',
        ];

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
                'width' => 60,
                'height' => 60,
                'alignment' => 'center',
            ]);
        }

        $centerCell = $headerTable->addCell(
            7200,
            array_merge($noBorder, ['valign' => 'center']),
        );
        $centerCell->addText(
            'REPUBLIC OF THE PHILIPPINES',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER],
        );
        $centerCell->addText(
            'PROVINCIAL GOVERNMENT OF BATANGAS',
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER],
        );
        $centerCell->addText(
            'Capitol Site, Kumintang Ibaba, Batangas City 4200',
            ['size' => 9],
            ['alignment' => Jc::CENTER],
        );
        $centerCell->addText(
            'BIDS and AWARDS COMMITTEE',
            ['bold' => true, 'size' => 14, 'underline' => 'single'],
            ['alignment' => Jc::CENTER],
        );

        $rightCell = $headerTable->addCell(
            1800,
            array_merge($noBorder, ['valign' => 'center']),
        );
        if (is_file($bagongPath)) {
            $rightCell->addImage($bagongPath, [
                'width' => 78,
                'height' => 60,
                'alignment' => 'center',
            ]);
        }

        $section->addTextBreak();

        // Resolution number
        $section->addText(
            sprintf(
                'RESOLUTION NO. %s, SERIES OF %s',
                $bacResolution->resolution_no,
                $resolutionYear,
            ),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER],
        );
        $section->addTextBreak();

        // Resolution main title
        $section->addText(
            'RESOLUTION RECOMMENDING THE AWARD OF CONTRACT TO THE SUPPLIERS WITH THE LOWEST/SINGLE CALCULATED RESPONSIVE QUOTATIONS, THROUGH SMALL VALUE PROCUREMENT (TWO HUNDRED THOUSAND PESOS AND BELOW)',
            ['bold' => true, 'size' => 10],
            ['alignment' => Jc::CENTER],
        );
        $section->addTextBreak();

        // Batch AOQs
        $batchAoqs = $bacResolution->aoqs;
        if ($batchAoqs->isEmpty() && $bacResolution->aoq) {
            $batchAoqs = collect([$bacResolution->aoq]);
        }

        $tableCellMargin = [
            'cellMarginTop' => 40,
            'cellMarginRight' => 50,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 50,
        ];
        $dataFont = ['size' => 9];
        $boldDataFont = ['bold' => true, 'size' => 9];

        // First WHEREAS
        $section->addText(
            '       WHEREAS, the Provincial Government of Batangas, through its Bids and Awards Committee (BAC), is in need of suppliers for the following:',
            [],
            ['alignment' => Jc::BOTH, 'bold' => true],
        );

        $summaryTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMarginTop' => 40,
            'cellMarginRight' => 50,
            'cellMarginBottom' => 40,
            'cellMarginLeft' => 50,
        ]);
        $summaryTable->addRow();
        $summaryTable
            ->addCell(2000)
            ->addText(
                'OFFICE',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );
        $summaryTable
            ->addCell(6000)
            ->addText(
                'NAME OF PROJECT',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );
        $summaryTable
            ->addCell(3000)
            ->addText(
                'APPROVED BUDGET FOR THE CONTRACT (ABC)',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );

        foreach ($batchAoqs as $aoq) {
            $summaryTable->addRow();
            $summaryTable
                ->addCell(2000)
                ->addText(
                    strtoupper(
                        (string) ($aoq->rfq?->purchaseRequest?->office?->name ??
                            'OFFICE'),
                    ),
                    $dataFont,
                    ['alignment' => Jc::CENTER],
                );
            $summaryTable
                ->addCell(6000)
                ->addText(
                    (string) ($aoq->rfq?->project_name ?? 'PROJECT'),
                    $dataFont,
                );
            $summaryTable
                ->addCell(3000)
                ->addText(
                    number_format((float) ($aoq->rfq?->abc_amount ?? 0), 2),
                    $dataFont,
                    ['alignment' => Jc::CENTER],
                );
        }

        $this->addWhereasClauses($section, $resolutionYear);

        // Abstract of Quotation sections
        $abstracts = $batchAoqs
            ->map(function (AOQ $aoq): array {
                $rfq = $aoq->rfq;
                if (! $rfq) {
                    return [
                        'svp_no' => 'N/A',
                        'rfq_date' => null,
                        'project_name' => 'PROJECT',
                        'suppliers' => collect(),
                        'items' => collect(),
                        'abc_total' => 0.0,
                    ];
                }

                $supplierTotals = collect(
                    $this->calculateSupplierTotals($aoq->rfq)[
                        'supplier_totals'
                    ] ?? [],
                );
                $rankedSuppliers = $supplierTotals
                    ->values()
                    ->map(function (array $row, int $index): array {
                        $rank = $index + 1;
                        $rankLabel =
                            $rank === 1
                                ? '1ST'
                                : ($rank === 2
                                    ? '2ND'
                                    : ($rank === 3
                                        ? '3RD'
                                        : $rank.'TH'));

                        return [
                            'supplier_id' => (int) $row['supplier_id'],
                            'supplier_name' => strtoupper(
                                (string) ($row['supplier_name'] ?? 'N/A'),
                            ),
                            'total_amount' => (float) ($row['total_amount'] ?? 0),
                            'rank_label' => $rankLabel,
                        ];
                    });

                $items = collect($rfq?->items ?? [])
                    ->map(function ($item) use ($rfq, $rankedSuppliers): array {
                        $quantity = (float) ($item->quantity ?? 0);
                        $approvedBudget =
                            $quantity *
                            (float) ($item->purchaseRequestItem?->unit_cost ??
                                0);
                        $supplierColumns = $rankedSuppliers
                            ->map(function (array $supplier) use (
                                $rfq,
                                $item,
                                $quantity,
                            ): array {
                                $entry = collect(
                                    $rfq?->suppliers ?? [],
                                )->firstWhere(
                                    'supplier_id',
                                    $supplier['supplier_id'],
                                );
                                $supplierItem = $entry?->supplierItems?->firstWhere(
                                    'rfq_item_id',
                                    $item->id,
                                );
                                $unitCost = $supplierItem?->unit_price;
                                $lineTotal =
                                    $unitCost !== null
                                        ? (float) $unitCost * $quantity
                                        : null;

                                return [
                                    'unit_cost' => $unitCost !== null
                                            ? (float) $unitCost
                                            : null,
                                    'total_amount' => $lineTotal,
                                ];
                            })
                            ->values();

                        return [
                            'quantity' => $quantity,
                            'unit' => (string) ($item->unit ?? ''),
                            'particulars' => (string) ($item->item_name ?? ''),
                            'approved_budget' => $approvedBudget,
                            'supplier_columns' => $supplierColumns,
                        ];
                    })
                    ->values();

                return [
                    'svp_no' => (string) ($rfq?->svp_no ?? 'N/A'),
                    'rfq_date' => $rfq?->rfq_date,
                    'project_name' => (string) ($rfq?->project_name ?? 'PROJECT'),
                    'suppliers' => $rankedSuppliers,
                    'items' => $items,
                    'abc_total' => (float) ($rfq?->abc_amount ?? 0),
                ];
            })
            ->values();

        foreach ($abstracts as $abstract) {
            $supplierCount = count($abstract['suppliers']);
            if ($supplierCount < 1) {
                $supplierCount = 1;
            }

            $fixedWidth = 3800;
            $supplierWidth = max(
                1500,
                (int) ((10466 - $fixedWidth) / $supplierCount),
            );
            $subColWidth = (int) ($supplierWidth / 2);

            $section->addTextBreak();
            $section->addText(
                'ABSTRACT OF QUOTATION',
                ['bold' => true, 'size' => 12],
                ['alignment' => Jc::CENTER],
            );
            $section->addText(
                'DATE OF RFQ: '.
                    ($abstract['rfq_date']
                        ? Carbon::parse($abstract['rfq_date'])->format(
                            'F d, Y',
                        )
                        : '__________'),
                ['bold' => true],
            );
            $section->addText(
                sprintf(
                    'SVP NO. %s - %s',
                    $abstract['svp_no'],
                    $abstract['project_name'],
                ),
                ['bold' => true],
            );

            $table = $section->addTable(
                array_merge(
                    ['borderSize' => 6, 'borderColor' => '000000'],
                    $tableCellMargin,
                ),
            );

            // Row 1: Supplier names spanning 2 columns each
            $headerRow1 = $table->addRow();
            $headerRow1
                ->addCell(500, ['gridSpan' => 1, 'valign' => 'center'])
                ->addText('QTY', $boldDataFont, ['alignment' => Jc::CENTER]);
            $headerRow1
                ->addCell(500, ['gridSpan' => 1, 'valign' => 'center'])
                ->addText('UNIT', $boldDataFont, ['alignment' => Jc::CENTER]);
            $headerRow1
                ->addCell(1800, ['gridSpan' => 1, 'valign' => 'center'])
                ->addText('PARTICULARS', $boldDataFont, [
                    'alignment' => Jc::CENTER,
                ]);
            $headerRow1
                ->addCell(1000, ['gridSpan' => 1, 'valign' => 'center'])
                ->addText('APPROVED BUDGET FOR THE CONTRACT', $boldDataFont, [
                    'alignment' => Jc::CENTER,
                ]);
            foreach ($abstract['suppliers'] as $supplier) {
                $supplierName = $supplier['supplier_name'];
                $label =
                    mb_strlen($supplierName) > 20
                        ? substr($supplierName, 0, 18).'...'
                        : $supplierName;
                $headerRow1
                    ->addCell($supplierWidth, [
                        'gridSpan' => 2,
                        'valign' => 'center',
                    ])
                    ->addText(
                        sprintf('%s (%s)', $label, $supplier['rank_label']),
                        $boldDataFont,
                        ['alignment' => Jc::CENTER],
                    );
            }

            // Row 2: UNIT COST / TOTAL AMOUNT sub-headers
            $headerRow2 = $table->addRow();
            $headerRow2->addCell(500, ['gridSpan' => 1, 'valign' => 'center']);
            $headerRow2->addCell(500, ['gridSpan' => 1, 'valign' => 'center']);
            $headerRow2->addCell(1800, ['gridSpan' => 1, 'valign' => 'center']);
            $headerRow2->addCell(1000, ['gridSpan' => 1, 'valign' => 'center']);
            for ($s = 0; $s < $supplierCount; ++$s) {
                $headerRow2
                    ->addCell($subColWidth, ['valign' => 'center'])
                    ->addText('UNIT COST', $boldDataFont, [
                        'alignment' => Jc::CENTER,
                    ]);
                $headerRow2
                    ->addCell($subColWidth, ['valign' => 'center'])
                    ->addText('TOTAL AMOUNT', $boldDataFont, [
                        'alignment' => Jc::CENTER,
                    ]);
            }

            foreach ($abstract['items'] as $item) {
                $row = $table->addRow();
                $row->addCell(500)->addText(
                    (string) ((int) ($item['quantity'] ?? 0)),
                    $dataFont,
                    ['alignment' => Jc::CENTER],
                );
                $row->addCell(500)->addText($item['unit'] ?? '', $dataFont, [
                    'alignment' => Jc::CENTER,
                ]);
                $row->addCell(1800)->addText(
                    $item['particulars'] ?? '',
                    $dataFont,
                );
                $row->addCell(1000)->addText(
                    number_format((float) ($item['approved_budget'] ?? 0), 2),
                    $dataFont,
                    ['alignment' => Jc::RIGHT],
                );
                foreach ($item['supplier_columns'] ?? [] as $col) {
                    $row->addCell($subColWidth)->addText(
                        $col['unit_cost'] !== null
                            ? number_format((float) $col['unit_cost'], 2)
                            : '',
                        $dataFont,
                        ['alignment' => Jc::RIGHT],
                    );
                    $row->addCell($subColWidth)->addText(
                        $col['total_amount'] !== null
                            ? number_format((float) $col['total_amount'], 2)
                            : '',
                        $dataFont,
                        ['alignment' => Jc::RIGHT],
                    );
                }
            }

            $totalRow = $table->addRow();
            $totalRow->addCell(500);
            $totalRow->addCell(500);
            $totalRow
                ->addCell(1800)
                ->addText('TOTAL', $boldDataFont, ['alignment' => Jc::RIGHT]);
            $totalRow
                ->addCell(1000)
                ->addText(
                    number_format((float) ($abstract['abc_total'] ?? 0), 2),
                    $dataFont,
                    ['alignment' => Jc::RIGHT],
                );
            foreach ($abstract['suppliers'] as $supplier) {
                $totalRow->addCell($subColWidth)->addText('', $dataFont);
                $totalRow
                    ->addCell($subColWidth)
                    ->addText(
                        number_format(
                            (float) ($supplier['total_amount'] ?? 0),
                            2,
                        ),
                        $boldDataFont,
                        ['alignment' => Jc::RIGHT],
                    );
            }
        }

        $section->addTextBreak(0);
        $section->addText(
            '       WHEREAS, upon careful examination, validation and verification of all the documents submitted by the suppliers with the Lowest Calculated Quotation, their price quotations have been found to be responsive;',
            [],
            ['alignment' => Jc::BOTH, 'spaceBefore' => 60],
        );
        $section->addTextBreak(0);

        $section->addText(
            'NOW, THEREFORE, we, the members of the Bids and Awards Committee of the Provincial Government of Batangas, acknowledging the submitted Abstract of Quotation by the GSO, RESOLVE, as it is hereby RESOLVED, to recommend to the HON. GOVERNOR VILMA SANTOS-RECTO, the approval of the aforesaid Abstract of Quotation, as conducted and submitted by the GSO Team of Canvassers, pursuant to Sections 34.1 and 34.2 of the Implementing Rules and Regulations of RA No. 12009; to declare the abovementioned suppliers as those with the Lowest Calculated Responsive Quotations for the purchase and delivery of various goods of the Provincial Government of Batangas, and to recommend the awarding of their respective Contracts, after passing the criteria set forth by the procurement law and the rules of the BAC;',
            [],
            ['alignment' => Jc::BOTH],
        );

        $section->addTextBreak(0);
        $section->addText(
            sprintf(
                'UNANIMOUSLY APPROVED, this ____ day of _________________________ %s.',
                $resolutionYear,
            ),
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $section->addText(
            'CERTIFIED TO BE DULY ATTESTED AND APPROVED:',
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $section->addTextBreak(0);

        // Signatory grid — space above names, name + Member together
        $signTable = $section->addTable([
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);

        // Spacer row for signatures above first pair of names
        $signTable->addRow(800);
        $signTable->addCell(5233);
        $signTable->addCell(5233);

        // Names row
        $signTable->addRow();
        $signTable
            ->addCell(5233)
            ->addText(
                'MR. NOEL R. ROCAFORT',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );
        $signTable
            ->addCell(5233)
            ->addText(
                'MR. PEDRITO MARTIN M. DIJAN, JR.',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );

        // Role row
        $signTable->addRow();
        $signTable
            ->addCell(5233)
            ->addText('Member', '', ['alignment' => Jc::CENTER]);
        $signTable
            ->addCell(5233)
            ->addText('Member', '', ['alignment' => Jc::CENTER]);

        // Spacer row for signatures above second pair of names
        $signTable->addRow(800);
        $signTable->addCell(5233);
        $signTable->addCell(5233);

        // Names row
        $signTable->addRow();
        $signTable
            ->addCell(5233)
            ->addText(
                'ENGR. NERIO L. RONQUILLO, JR.',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );
        $signTable
            ->addCell(5233)
            ->addText(
                'ATTY. LOUIE MARK M. DALAWAMPU',
                ['bold' => true, 'size' => 9],
                ['alignment' => Jc::CENTER],
            );

        // Role row
        $signTable->addRow();
        $signTable
            ->addCell(5233)
            ->addText('Member', '', ['alignment' => Jc::CENTER]);
        $signTable
            ->addCell(5233)
            ->addText('Member', '', ['alignment' => Jc::CENTER]);

        $section->addTextBreak();
        $section->addText(
            'ATTY. JOEL L. MONTEALTO',
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $section->addText('Chairperson', '', ['alignment' => Jc::CENTER]);
        $section->addTextBreak();
        $section->addText('Approved by:', '', ['alignment' => Jc::CENTER]);
        $section->addText(
            'VILMA SANTOS - RECTO',
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
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
            sprintf(
                'on the deadline for the submission of Request for Quotation (RFQ) Forms last __________________, %s, the GSO prepared the hereunder Abstract of Quotation from all the interested bidders within the prescribed period of posting, to wit:',
                $resolutionYear,
            ),
        ];

        foreach ($clauses as $clause) {
            $run = $section->addTextRun([
                'alignment' => Jc::BOTH,
                'spaceBefore' => 60,
            ]);
            $run->addText('       WHEREAS, ', ['bold' => true]);
            $run->addText($clause);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function calculateSupplierTotals(RFQ $rfq): array
    {
        $rfq->loadMissing([
            'items.purchaseRequestItem',
            'suppliers.supplier',
            'suppliers.supplierItems.rfqItem.purchaseRequestItem',
        ]);

        $supplierTotals = [];

        foreach ($rfq->suppliers as $rfqSupplier) {
            $total = 0.0;
            $hasAtLeastOnePrice = false;

            foreach ($rfqSupplier->supplierItems as $supplierItem) {
                if ($supplierItem->unit_price === null) {
                    continue;
                }

                $quantity = (float) ($supplierItem->rfqItem?->quantity ?? 0);
                $lineTotal = $quantity * (float) $supplierItem->unit_price;
                $total += $lineTotal;
                $hasAtLeastOnePrice = true;
            }

            if (! $hasAtLeastOnePrice) {
                continue;
            }

            $supplierTotals[] = [
                'rfq_supplier_id' => $rfqSupplier->id,
                'supplier_id' => $rfqSupplier->supplier_id,
                'supplier_name' => $rfqSupplier->supplier?->name,
                'total_amount' => round($total, 2),
            ];
        }

        usort(
            $supplierTotals,
            fn (array $left, array $right): int => $left['total_amount'] <=>
                $right['total_amount'],
        );

        $count = count($supplierTotals);
        $winner = $count > 0 ? $supplierTotals[0] : null;
        $calculationMode =
            $count >= 2 ? 'lowest_calculated' : 'single_calculated';

        return [
            'supplier_totals' => $supplierTotals,
            'calculated_supplier_count' => $count,
            'calculation_mode' => $calculationMode,
            'winner_supplier_id' => $winner['supplier_id'] ?? null,
            'winner_total_amount' => $winner['total_amount'] ?? null,
        ];
    }
}
