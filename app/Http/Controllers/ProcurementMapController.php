<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\APP;
use App\Models\Emanating;
use App\Models\Fund;
use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementMapController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedOfficeId = $request->filled('office_id') ? (int) $request->input('office_id') : null;
        $selectedFiscalYear = trim((string) $request->input('fiscal_year', ''));
        $selectedStatus = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $emanatings = Emanating::query()
            ->with([
                'fund.office',
                'fund.project.workProgram',
                'fund.project.projectProposal',
                'fund.project.projectBrief',
                'ppmp',
                'canvasses',
                'purchaseRequest.office',
                'purchaseRequest.rfq.aoq.bacResolution.noa.purchaseOrder.poTransmittals',
                'purchaseRequest.rfq.aoq.bacResolution.noa.purchaseOrder.acceptanceInspection',
                'purchaseRequest.rfq.aoq.bacResolution.noa.purchaseOrder.coaInspection',
            ])
            ->when($selectedOfficeId !== null, function (Builder $query) use ($selectedOfficeId): void {
                $query->whereHas('fund', function (Builder $fundQuery) use ($selectedOfficeId): void {
                    $fundQuery->where('office_id', $selectedOfficeId);
                });
            })
            ->when($selectedFiscalYear !== '', function (Builder $query) use ($selectedFiscalYear): void {
                $query->where('fiscal_year', (int) $selectedFiscalYear);
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('emanating_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('pr_no', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('fund', function (Builder $fundQuery) use ($search): void {
                            $fundQuery
                                ->where('name', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('office', function (Builder $officeQuery) use ($search): void {
                                    $officeQuery->where('name', 'like', sprintf('%%%s%%', $search));
                                });
                        })
                        ->orWhereHas('purchaseRequest', function (Builder $purchaseRequestQuery) use ($search): void {
                            $purchaseRequestQuery
                                ->where('pr_no', 'like', sprintf('%%%s%%', $search))
                                ->orWhereHas('rfq', function (Builder $rfqQuery) use ($search): void {
                                    $rfqQuery
                                        ->where('svp_no', 'like', sprintf('%%%s%%', $search))
                                        ->orWhere('project_name', 'like', sprintf('%%%s%%', $search));
                                });
                        });
                });
            })
            ->latest('id')
            ->get();

        $filteredEmanatings = $selectedStatus !== ''
            ? $emanatings->filter(fn (Emanating $emanating): bool => $this->deriveOverallStatus($emanating) === $selectedStatus)->values()
            : $emanatings;

        $apps = APP::query()
            ->with('office')
            ->when($selectedOfficeId !== null, fn (Builder $query) => $query->where('office_id', $selectedOfficeId))
            ->when($selectedFiscalYear !== '', fn (Builder $query) => $query->where('fiscal_year', (int) $selectedFiscalYear))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('fiscal_year', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('uploaded_file', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('office', function (Builder $officeQuery) use ($search): void {
                            $officeQuery->where('name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->get()
            ->keyBy(fn (APP $app): string => sprintf('%d-%d', (int) $app->office_id, (int) $app->fiscal_year));

        $funds = Fund::query()
            ->with('office')
            ->when($selectedOfficeId !== null, fn (Builder $query) => $query->where('office_id', $selectedOfficeId))
            ->when($selectedFiscalYear !== '', fn (Builder $query) => $query->where('fiscal_year', (int) $selectedFiscalYear))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('type', 'like', sprintf('%%%s%%', $search))
                        ->orWhere('fiscal_year', 'like', sprintf('%%%s%%', $search))
                        ->orWhereHas('office', function (Builder $officeQuery) use ($search): void {
                            $officeQuery->where('name', 'like', sprintf('%%%s%%', $search));
                        });
                });
            })
            ->latest('id')
            ->get();

        $graph = $this->buildGraph($filteredEmanatings, $apps, $funds, $selectedStatus);

        $emanatingFiscalYears = Emanating::query()
            ->whereNotNull('fiscal_year')
            ->distinct()
            ->pluck('fiscal_year');

        $appFiscalYears = APP::query()
            ->whereNotNull('fiscal_year')
            ->distinct()
            ->pluck('fiscal_year');

        $fundFiscalYears = Fund::query()
            ->whereNotNull('fiscal_year')
            ->distinct()
            ->pluck('fiscal_year');

        $availableFiscalYears = $emanatingFiscalYears
            ->merge($appFiscalYears)
            ->merge($fundFiscalYears)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (int $year): array => [(string) $year => (string) $year]);

        return Inertia::render('ProcurementMap/Index', [
            'graph' => $graph,
            'offices' => Office::query()->orderBy('name')->get(['id', 'name']),
            'fiscalYears' => $availableFiscalYears,
            'filters' => [
                'search' => $search,
                'office_id' => $selectedOfficeId,
                'fiscal_year' => $selectedFiscalYear,
                'status' => $selectedStatus,
            ],
            'stats' => [
                'total_flows' => $filteredEmanatings->count(),
                'pending' => $filteredEmanatings->filter(fn (Emanating $emanating): bool => $this->deriveOverallStatus($emanating) === 'pending')->count(),
                'ongoing' => $filteredEmanatings->filter(fn (Emanating $emanating): bool => $this->deriveOverallStatus($emanating) === 'ongoing')->count(),
                'completed' => $filteredEmanatings->filter(fn (Emanating $emanating): bool => $this->deriveOverallStatus($emanating) === 'completed')->count(),
            ],
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'ongoing', 'label' => 'Ongoing'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ]);
    }

    private function buildGraph($emanatings, $apps, $funds, string $selectedStatus = ''): array
    {
        $nodes = [];
        $edges = [];
        $representedAppKeys = [];
        $representedFundIds = [];
        $officeNodeIds = [];
        $yearNodeIds = [];
        $appNodeIds = [];
        $edgeIds = [];
        $officeIndexes = [];
        $officeYearCounts = [];
        $yearColumns = [];
        $officeYearMinColumns = [];
        $officeYearMaxColumns = [];
        $nextOfficeIndex = 0;
        $officeColumnGap = 6;
        $baseX = 120;
        $columnSpacing = 250;

        $appendUniqueEdge = function (string $source, string $target) use (&$edgeIds, &$edges): void {
            $edgeId = sprintf('%s->%s', $source, $target);

            if (isset($edgeIds[$edgeId])) {
                return;
            }

            $edgeIds[$edgeId] = true;
            $this->appendEdge($edges, $source, $target);
        };

        $resolveYearColumn = function (string $officeKey, string $yearKey) use (&$officeIndexes, &$officeYearCounts, &$yearColumns, &$nextOfficeIndex, &$officeYearMinColumns, &$officeYearMaxColumns, $officeColumnGap): int {
            if (! isset($officeIndexes[$officeKey])) {
                $officeIndexes[$officeKey] = $nextOfficeIndex;
                $nextOfficeIndex++;
                $officeYearCounts[$officeKey] = 0;
            }

            $officeColumnStart = $officeIndexes[$officeKey] * $officeColumnGap;

            if (! isset($yearColumns[$yearKey])) {
                $yearColumns[$yearKey] = $officeColumnStart + $officeYearCounts[$officeKey];
                $officeYearCounts[$officeKey]++;
            }

            $currentYearColumn = $yearColumns[$yearKey];
            $officeYearMinColumns[$officeKey] = isset($officeYearMinColumns[$officeKey])
                ? min($officeYearMinColumns[$officeKey], $currentYearColumn)
                : $currentYearColumn;
            $officeYearMaxColumns[$officeKey] = isset($officeYearMaxColumns[$officeKey])
                ? max($officeYearMaxColumns[$officeKey], $currentYearColumn)
                : $currentYearColumn;

            return $yearColumns[$yearKey];
        };

        foreach ($emanatings as $emanating) {
            $purchaseRequest = $emanating->purchaseRequest;
            $rfq = $purchaseRequest?->rfq;
            $aoq = $rfq?->aoq;
            $resolution = $aoq?->bacResolution;
            $noa = $resolution?->noa;
            $purchaseOrder = $noa?->purchaseOrder;

            $office = $emanating->fund?->office ?? $purchaseRequest?->office;
            $fiscalYear = (int) ($emanating->fiscal_year ?: ($purchaseRequest?->pr_date?->format('Y') ?? now()->year));
            $appLookupKey = $office ? sprintf('%d-%d', (int) $office->id, $fiscalYear) : null;
            $app = $appLookupKey ? $apps->get($appLookupKey) : null;
            if ($appLookupKey !== null) {
                $representedAppKeys[] = $appLookupKey;
            }

            $project = $emanating->fund?->project;
            $workProgram = $project?->workProgram;
            $projectProposal = $project?->projectProposal;
            $projectBrief = $project?->projectBrief;
            $canvassing = $emanating->canvasses->sortByDesc('created_at')->first();
            $poTransmittal = $purchaseOrder?->poTransmittals?->sortByDesc('transmittal_date')->first();
            $acceptanceInspection = $purchaseOrder?->acceptanceInspection;
            $coaInspection = $purchaseOrder?->coaInspection;

            $officeKey = $office ? (string) $office->id : sprintf('unknown-%d', $emanating->id);
            $yearKey = sprintf('%s-%d', $officeKey, $fiscalYear);
            $yearColumn = $resolveYearColumn($officeKey, $yearKey);
            $officeColumn = $officeIndexes[$officeKey] * $officeColumnGap;

            if (! isset($officeNodeIds[$officeKey])) {
                $officeNodeIds[$officeKey] = sprintf('office-%s', $officeKey);

                $nodes[] = $this->makeNode(
                    $officeNodeIds[$officeKey],
                    0,
                    $officeColumn,
                    $office?->name ?? 'Office Not Set',
                    'Office',
                    'completed',
                    ['office' => $office?->name],
                    $office ? route('offices.show', $office->id) : null,
                );
            }

            $officeNodeId = $officeNodeIds[$officeKey];

            if (! isset($yearNodeIds[$yearKey])) {
                $yearNodeIds[$yearKey] = sprintf('year-%s', $yearKey);

                $nodes[] = $this->makeNode(
                    $yearNodeIds[$yearKey],
                    1,
                    $yearColumn,
                    (string) $fiscalYear,
                    'Fiscal Year',
                    'completed',
                    ['fiscal_year' => $fiscalYear],
                    null,
                );
            }

            $yearNodeId = $yearNodeIds[$yearKey];

            if (! isset($appNodeIds[$yearKey])) {
                $appNodeIds[$yearKey] = sprintf('app-%s', $yearKey);

                $nodes[] = $this->makeNode(
                    $appNodeIds[$yearKey],
                    2,
                    $yearColumn,
                    $app ? sprintf('APP FY %d', (int) $app->fiscal_year) : 'APP Pending',
                    'APP',
                    $app ? 'completed' : 'pending',
                    [
                        'office' => $office?->name,
                        'fiscal_year' => $fiscalYear,
                    ],
                    $app ? route('apps.show', $app->id) : null,
                );
            }

            $appNodeId = $appNodeIds[$yearKey];
            $fundNodeId = sprintf('fund-%d-%d', $emanating->id, (int) ($emanating->fund?->id ?? 0));
            if ($emanating->fund?->id !== null) {
                $representedFundIds[] = (int) $emanating->fund->id;
            }
            $ppmpNodeId = sprintf('ppmp-%d-%s', $emanating->id, (string) ($emanating->ppmp?->id ?? 'none'));
            $workProgramNodeId = sprintf('work-program-%d-%s', $emanating->id, (string) ($workProgram?->id ?? 'none'));
            $projectProposalNodeId = sprintf('project-proposal-%d-%s', $emanating->id, (string) ($projectProposal?->id ?? 'none'));
            $projectBriefNodeId = sprintf('project-brief-%d-%s', $emanating->id, (string) ($projectBrief?->id ?? 'none'));
            $emanatingNodeId = sprintf('emanating-%d', $emanating->id);
            $canvassingNodeId = sprintf('canvassing-%d-%s', $emanating->id, (string) ($canvassing?->id ?? 'none'));

            $nodes[] = $this->makeNode(
                $fundNodeId,
                3,
                $yearColumn,
                $emanating->fund?->name ?? 'Fund Not Set',
                'Fund',
                $emanating->fund ? 'completed' : 'pending',
                [
                    'fund' => $emanating->fund?->name,
                    'office' => $office?->name,
                ],
                $emanating->fund ? route('funds.show', $emanating->fund->id) : null,
            );

            $nodes[] = $this->makeNode(
                $ppmpNodeId,
                4,
                $yearColumn,
                $emanating->ppmp ? sprintf('PPMP FY %d', (int) $emanating->ppmp->fiscal_year) : 'PPMP Pending',
                'PPMP',
                $emanating->ppmp ? 'completed' : 'pending',
                [
                    'fiscal_year' => $emanating->ppmp?->fiscal_year,
                    'project_code' => $emanating->ppmp?->projectCode?->code,
                ],
                $emanating->ppmp ? route('ppmps.show', $emanating->ppmp->id) : null,
            );

            $nodes[] = $this->makeNode(
                $workProgramNodeId,
                5,
                $yearColumn,
                $workProgram ? 'Work Program' : 'Work Program Pending',
                'Work Program',
                $workProgram ? 'completed' : 'pending',
                [
                    'project' => $project?->name,
                ],
                null,
            );

            $nodes[] = $this->makeNode(
                $projectProposalNodeId,
                6,
                $yearColumn,
                $projectProposal ? 'Project Proposal' : 'Project Proposal Pending',
                'Project Proposal',
                $projectProposal ? 'completed' : 'pending',
                [
                    'project' => $project?->name,
                ],
                null,
            );

            $nodes[] = $this->makeNode(
                $projectBriefNodeId,
                7,
                $yearColumn,
                $projectBrief ? 'Project Brief' : 'Project Brief Pending',
                'Project Brief',
                $projectBrief ? 'completed' : 'pending',
                [
                    'project' => $project?->name,
                ],
                null,
            );

            $nodes[] = $this->makeNode(
                $emanatingNodeId,
                8,
                $yearColumn,
                $emanating->emanating_no ?? sprintf('Emanating #%d', $emanating->id),
                'Emanating',
                $this->normalizeStatus((string) ($emanating->status ?? 'ongoing')),
                [
                    'emanating_no' => $emanating->emanating_no,
                    'charged_to_code' => $emanating->charged_to_code,
                    'status' => $emanating->status,
                ],
                route('emanatings.show', $emanating->id),
            );

            $nodes[] = $this->makeNode(
                $canvassingNodeId,
                9,
                $yearColumn,
                $canvassing ? sprintf('Canvassing #%d', $canvassing->id) : 'Canvassing Pending',
                'Canvassing',
                $canvassing ? $this->normalizeStatus((string) ($canvassing->status ?? 'ongoing')) : 'pending',
                [
                    'status' => $canvassing?->status,
                    'total_amount' => $canvassing?->total_amount,
                ],
                $canvassing ? route('canvasses.show', $canvassing->id) : null,
            );

            $appendUniqueEdge($officeNodeId, $yearNodeId);
            $appendUniqueEdge($yearNodeId, $appNodeId);
            $appendUniqueEdge($appNodeId, $fundNodeId);
            $appendUniqueEdge($fundNodeId, $ppmpNodeId);
            $appendUniqueEdge($ppmpNodeId, $workProgramNodeId);
            $appendUniqueEdge($workProgramNodeId, $projectProposalNodeId);
            $appendUniqueEdge($projectProposalNodeId, $projectBriefNodeId);
            $appendUniqueEdge($projectBriefNodeId, $emanatingNodeId);
            $appendUniqueEdge($emanatingNodeId, $canvassingNodeId);

            $previousNodeId = $canvassingNodeId;

            if ($purchaseRequest) {
                $prNodeId = sprintf('pr-%d', $purchaseRequest->id);
                $nodes[] = $this->makeNode(
                    $prNodeId,
                    10,
                    $yearColumn,
                    $purchaseRequest->pr_no ?: sprintf('PR #%d', $purchaseRequest->id),
                    'Purchase Request',
                    $this->normalizeStatus((string) ($purchaseRequest->status ?? 'ongoing')),
                    [
                        'pr_no' => $purchaseRequest->pr_no,
                        'pr_date' => $purchaseRequest->pr_date?->toDateString(),
                        'status' => $purchaseRequest->status,
                    ],
                    route('purchase-requests.show', $purchaseRequest->id),
                );
                $appendUniqueEdge($previousNodeId, $prNodeId);
                $previousNodeId = $prNodeId;
            }

            if ($rfq) {
                $rfqNodeId = sprintf('rfq-%d', $rfq->id);
                $nodes[] = $this->makeNode(
                    $rfqNodeId,
                    11,
                    $yearColumn,
                    $rfq->svp_no ?: sprintf('RFQ #%d', $rfq->id),
                    'RFQ',
                    $aoq ? 'completed' : 'ongoing',
                    [
                        'svp_no' => $rfq->svp_no,
                        'rfq_date' => $rfq->rfq_date?->toDateString(),
                    ],
                    route('rfqs.show', $rfq->id),
                );
                $appendUniqueEdge($previousNodeId, $rfqNodeId);
                $previousNodeId = $rfqNodeId;
            }

            if ($aoq) {
                $aoqNodeId = sprintf('aoq-%d', $aoq->id);
                $nodes[] = $this->makeNode(
                    $aoqNodeId,
                    12,
                    $yearColumn,
                    sprintf('AOQ #%d', $aoq->id),
                    'AOQ',
                    $resolution ? 'completed' : 'ongoing',
                    [
                        'aoq_date' => $aoq->aoq_date?->toDateString(),
                    ],
                    route('aoqs.show', $aoq->id),
                );
                $appendUniqueEdge($previousNodeId, $aoqNodeId);
                $previousNodeId = $aoqNodeId;
            }

            if ($resolution) {
                $resolutionNodeId = sprintf('bac-resolution-%d', $resolution->id);
                $nodes[] = $this->makeNode(
                    $resolutionNodeId,
                    13,
                    $yearColumn,
                    $resolution->resolution_no ?: sprintf('Resolution #%d', $resolution->id),
                    'BAC Resolution',
                    $noa ? 'completed' : 'ongoing',
                    [
                        'resolution_no' => $resolution->resolution_no,
                        'resolution_date' => $resolution->resolution_date?->toDateString(),
                    ],
                    route('bac-resolutions.show', $resolution->id),
                );
                $appendUniqueEdge($previousNodeId, $resolutionNodeId);
                $previousNodeId = $resolutionNodeId;
            }

            if ($noa) {
                $noaNodeId = sprintf('noa-%d', $noa->id);
                $nodes[] = $this->makeNode(
                    $noaNodeId,
                    14,
                    $yearColumn,
                    $noa->noa_no ?: sprintf('NOA #%d', $noa->id),
                    'Notice of Award',
                    $purchaseOrder ? 'completed' : 'ongoing',
                    [
                        'noa_no' => $noa->noa_no,
                        'noa_date' => $noa->noa_date?->toDateString(),
                    ],
                    route('noas.show', $noa->id),
                );
                $appendUniqueEdge($previousNodeId, $noaNodeId);
                $previousNodeId = $noaNodeId;
            }

            if ($purchaseOrder) {
                $poNodeId = sprintf('po-%d', $purchaseOrder->id);
                $nodes[] = $this->makeNode(
                    $poNodeId,
                    15,
                    $yearColumn,
                    $purchaseOrder->po_no ?: sprintf('PO #%d', $purchaseOrder->id),
                    'Purchase Order',
                    ($acceptanceInspection || $coaInspection) ? 'completed' : 'ongoing',
                    [
                        'po_no' => $purchaseOrder->po_no,
                        'po_date' => $purchaseOrder->po_date?->toDateString(),
                    ],
                    route('purchase-orders.show', $purchaseOrder->id),
                );
                $appendUniqueEdge($previousNodeId, $poNodeId);
                $previousNodeId = $poNodeId;
            }

            if ($poTransmittal) {
                $transmittalNodeId = sprintf('po-transmittal-%d', $poTransmittal->id);
                $nodes[] = $this->makeNode(
                    $transmittalNodeId,
                    16,
                    $yearColumn,
                    $poTransmittal->transmittal_no ?: sprintf('PO Transmittal #%d', $poTransmittal->id),
                    'PO Transmittal',
                    'completed',
                    [
                        'type' => strtoupper((string) $poTransmittal->type),
                        'transmittal_date' => $poTransmittal->transmittal_date?->toDateString(),
                    ],
                    route('po-transmittals.show', $poTransmittal->id),
                );
                $appendUniqueEdge($previousNodeId, $transmittalNodeId);
                $previousNodeId = $transmittalNodeId;
            }

            if ($acceptanceInspection) {
                $acceptanceNodeId = sprintf('acceptance-inspection-%d', $acceptanceInspection->id);
                $acceptanceCompleted = (bool) $acceptanceInspection->inspection_status_ok
                    && $acceptanceInspection->acceptance_status === 'complete';

                $nodes[] = $this->makeNode(
                    $acceptanceNodeId,
                    17,
                    $yearColumn,
                    sprintf('Acceptance #%d', $acceptanceInspection->id),
                    'Acceptance Inspection',
                    $acceptanceCompleted ? 'completed' : 'ongoing',
                    [
                        'date_received' => $acceptanceInspection->acceptance_date_received?->toDateString(),
                        'date_inspected' => $acceptanceInspection->inspection_date_inspected?->toDateString(),
                        'result' => $acceptanceInspection->inspection_status_ok ? 'OK' : 'Pending/Not OK',
                    ],
                    route('acceptance-inspections.show', $acceptanceInspection->id),
                );
                $appendUniqueEdge($previousNodeId, $acceptanceNodeId);
                $previousNodeId = $acceptanceNodeId;
            }

            if ($coaInspection) {
                $coaInspectionNodeId = sprintf('coa-inspection-%d', $coaInspection->id);
                $nodes[] = $this->makeNode(
                    $coaInspectionNodeId,
                    18,
                    $yearColumn,
                    sprintf('COA Inspection #%d', $coaInspection->id),
                    'COA Inspection',
                    'completed',
                    [
                        'signatory_name' => $coaInspection->signatory_name,
                        'signatory_title' => $coaInspection->signatory_title,
                    ],
                    route('coa-inspections.show', $coaInspection->id),
                );
                $appendUniqueEdge($previousNodeId, $coaInspectionNodeId);
            }
        }

        $shouldRenderAppOnlyRows = $selectedStatus === '' || $selectedStatus === 'pending';

        if ($shouldRenderAppOnlyRows) {
            foreach ($apps as $appKey => $app) {
                if (in_array((string) $appKey, $representedAppKeys, true)) {
                    continue;
                }

                $office = $app->office;
                if (! $office) {
                    continue;
                }

                $fiscalYear = (int) $app->fiscal_year;
                $officeKey = (string) $office->id;
                $yearKey = sprintf('%s-%d', $officeKey, $fiscalYear);
                $yearColumn = $resolveYearColumn($officeKey, $yearKey);
                $officeColumn = $officeIndexes[$officeKey] * $officeColumnGap;

                if (! isset($officeNodeIds[$officeKey])) {
                    $officeNodeIds[$officeKey] = sprintf('office-%s', $officeKey);

                    $nodes[] = $this->makeNode(
                        $officeNodeIds[$officeKey],
                        0,
                        $officeColumn,
                        $office->name,
                        'Office',
                        'completed',
                        ['office' => $office->name],
                        route('offices.show', $office->id),
                    );
                }

                $officeNodeId = $officeNodeIds[$officeKey];

                if (! isset($yearNodeIds[$yearKey])) {
                    $yearNodeIds[$yearKey] = sprintf('year-%s', $yearKey);

                    $nodes[] = $this->makeNode(
                        $yearNodeIds[$yearKey],
                        1,
                        $yearColumn,
                        (string) $fiscalYear,
                        'Fiscal Year',
                        'completed',
                        ['fiscal_year' => $fiscalYear],
                        null,
                    );
                }

                $yearNodeId = $yearNodeIds[$yearKey];

                if (! isset($appNodeIds[$yearKey])) {
                    $appNodeIds[$yearKey] = sprintf('app-%s', $yearKey);

                    $nodes[] = $this->makeNode(
                        $appNodeIds[$yearKey],
                        2,
                        $yearColumn,
                        sprintf('APP FY %d', $fiscalYear),
                        'APP',
                        'completed',
                        [
                            'office' => $office->name,
                            'fiscal_year' => $fiscalYear,
                        ],
                        route('apps.show', $app->id),
                    );
                }

                $appNodeId = $appNodeIds[$yearKey];

                $appendUniqueEdge($officeNodeId, $yearNodeId);
                $appendUniqueEdge($yearNodeId, $appNodeId);
            }

            foreach ($funds as $fund) {
                if (in_array((int) $fund->id, $representedFundIds, true)) {
                    continue;
                }

                $office = $fund->office;
                if (! $office) {
                    continue;
                }

                $fiscalYear = (int) $fund->fiscal_year;
                $officeKey = (string) $office->id;
                $yearKey = sprintf('%s-%d', $officeKey, $fiscalYear);
                $yearColumn = $resolveYearColumn($officeKey, $yearKey);
                $officeColumn = $officeIndexes[$officeKey] * $officeColumnGap;

                if (! isset($officeNodeIds[$officeKey])) {
                    $officeNodeIds[$officeKey] = sprintf('office-%s', $officeKey);

                    $nodes[] = $this->makeNode(
                        $officeNodeIds[$officeKey],
                        0,
                        $officeColumn,
                        $office->name,
                        'Office',
                        'completed',
                        ['office' => $office->name],
                        route('offices.show', $office->id),
                    );
                }

                $officeNodeId = $officeNodeIds[$officeKey];

                if (! isset($yearNodeIds[$yearKey])) {
                    $yearNodeIds[$yearKey] = sprintf('year-%s', $yearKey);

                    $nodes[] = $this->makeNode(
                        $yearNodeIds[$yearKey],
                        1,
                        $yearColumn,
                        (string) $fiscalYear,
                        'Fiscal Year',
                        'completed',
                        ['fiscal_year' => $fiscalYear],
                        null,
                    );
                }

                $yearNodeId = $yearNodeIds[$yearKey];

                $app = $apps->get(sprintf('%d-%d', (int) $office->id, $fiscalYear));

                if (! isset($appNodeIds[$yearKey])) {
                    $appNodeIds[$yearKey] = sprintf('app-%s', $yearKey);

                    $nodes[] = $this->makeNode(
                        $appNodeIds[$yearKey],
                        2,
                        $yearColumn,
                        $app ? sprintf('APP FY %d', (int) $app->fiscal_year) : 'APP Pending',
                        'APP',
                        $app ? 'completed' : 'pending',
                        [
                            'office' => $office->name,
                            'fiscal_year' => $fiscalYear,
                        ],
                        $app ? route('apps.show', $app->id) : null,
                    );
                }

                $appNodeId = $appNodeIds[$yearKey];
                $fundNodeId = sprintf('fund-only-%d', (int) $fund->id);

                $nodes[] = $this->makeNode(
                    $fundNodeId,
                    3,
                    $yearColumn,
                    $fund->name,
                    'Fund',
                    'pending',
                    [
                        'fund' => $fund->name,
                        'type' => $fund->type,
                        'fiscal_year' => $fund->fiscal_year,
                        'office' => $office->name,
                    ],
                    route('funds.show', $fund->id),
                );

                $appendUniqueEdge($officeNodeId, $yearNodeId);
                $appendUniqueEdge($yearNodeId, $appNodeId);
                $appendUniqueEdge($appNodeId, $fundNodeId);
            }
        }

        $officeCenterXByNodeId = [];
        foreach ($officeNodeIds as $officeKey => $officeNodeId) {
            $minColumn = $officeYearMinColumns[$officeKey] ?? null;
            $maxColumn = $officeYearMaxColumns[$officeKey] ?? null;

            if ($minColumn === null || $maxColumn === null) {
                continue;
            }

            $officeCenterXByNodeId[$officeNodeId] = $baseX + ((($minColumn + $maxColumn) / 2) * $columnSpacing);
        }

        foreach ($nodes as &$node) {
            $nodeId = (string) ($node['id'] ?? '');
            if (! isset($officeCenterXByNodeId[$nodeId])) {
                continue;
            }

            $node['position']['x'] = $officeCenterXByNodeId[$nodeId];
        }
        unset($node);

        // Final layout guard: keep every node from overlapping with any previously placed node.
        $placedNodeBoxes = [];
        foreach ($nodes as &$node) {
            $category = strtolower(trim((string) ($node['data']['category'] ?? '')));
            $width = $category === 'office' ? 220.0 : 190.0;
            $height = $category === 'office' ? 220.0 : 44.0;
            $x = (float) ($node['position']['x'] ?? 0.0);
            $y = (float) ($node['position']['y'] ?? 0.0);
            $attempts = 0;

            while ($attempts < 40) {
                $collides = false;

                foreach ($placedNodeBoxes as $box) {
                    $deltaX = abs(($x + ($width / 2)) - ($box['x'] + ($box['width'] / 2)));
                    $deltaY = abs(($y + ($height / 2)) - ($box['y'] + ($box['height'] / 2)));
                    $allowedX = (($width + $box['width']) / 2) + 14;
                    $allowedY = (($height + $box['height']) / 2) + 14;

                    if ($deltaX < $allowedX && $deltaY < $allowedY) {
                        $collides = true;
                        break;
                    }
                }

                if (! $collides) {
                    break;
                }

                $x += 140;
                $attempts++;
            }

            $node['position']['x'] = $x;
            $placedNodeBoxes[] = [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
            ];
        }
        unset($node);

        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }

    private function makeNode(
        string $id,
        int $stage,
        int $laneColumn,
        string $label,
        string $category,
        string $status,
        array $meta,
        ?string $href,
    ): array {
        $lane = max(0, $laneColumn);
        $isOfficeNode = strtolower(trim($category)) === 'office';

        return [
            'id' => $id,
            'position' => [
                'x' => 120 + ($lane * 250),
                'y' => ($isOfficeNode ? -30 : 80) + ($stage * 170),
            ],
            'data' => [
                'label' => $label,
                'category' => $category,
                'status' => $status,
                'meta' => $meta,
                'href' => $href,
            ],
        ];
    }

    private function appendEdge(array &$edges, string $source, string $target): void
    {
        $edges[] = [
            'id' => sprintf('%s->%s', $source, $target),
            'source' => $source,
            'target' => $target,
            'type' => 'smoothstep',
            'animated' => false,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        if ($normalized === '') {
            return 'ongoing';
        }

        if (in_array($normalized, ['complete', 'completed', 'approved', 'finalized', 'done', 'ok'], true)) {
            return 'completed';
        }

        if (in_array($normalized, ['pending', 'draft', 'returned', 'rejected'], true)) {
            return 'pending';
        }

        return 'ongoing';
    }

    private function deriveOverallStatus(Emanating $emanating): string
    {
        $purchaseOrder = $emanating->purchaseRequest?->rfq?->aoq?->bacResolution?->noa?->purchaseOrder;

        if ($purchaseOrder?->coaInspection || $purchaseOrder?->acceptanceInspection) {
            return 'completed';
        }

        if ($emanating->purchaseRequest?->rfq || $emanating->canvasses->isNotEmpty()) {
            return 'ongoing';
        }

        return $this->normalizeStatus((string) ($emanating->status ?? 'pending'));
    }
}
