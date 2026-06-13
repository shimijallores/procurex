<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AOQ;
use App\Models\APP;
use App\Models\APPCategory;
use App\Models\APPItem;
use App\Models\Canvas;
use App\Models\CanvasItem;
use App\Models\CanvasItemSelection;
use App\Models\Emanating;
use App\Models\EmanatingItem;
use App\Models\Fund;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\PPMPItemMonth;
use App\Models\ProjectBrief;
use App\Models\ProjectBriefItem;
use App\Models\ProjectProposal;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RFQ;
use App\Models\RFQItem;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use App\Models\WorkProgram;
use App\Models\WorkProgramItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportSeedData extends Command
{
    protected $signature = 'data:export {--path=database/seeders/data/sample-data.json}';

    protected $description = 'Export APP, Fund, PPMP, Emanating, Canvas, PR, RFQ, and AOQ data to a JSON file for re-seeding';

    public function handle(): int
    {
        $path = $this->option('path');

        $data = [
            'apps' => $this->exportApps(),
            'funds' => $this->exportFunds(),
            'ppmps' => $this->exportPpmps(),
            'emanatings' => $this->exportEmanatings(),
            'canvasses' => $this->exportCanvasses(),
            'purchase_requests' => $this->exportPurchaseRequests(),
            'rfqs' => $this->exportRfqs(),
            'aoqs' => $this->exportAoqs(),
        ];

        $fullPath = base_path($path);
        $dir = dirname($fullPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Copy uploaded files to seeders/files/
        $this->copyUploadedFiles();

        $this->info('Exported to ' . $path);

        return self::SUCCESS;
    }

    private function copyUploadedFiles(): void
    {
        $filesystem = Storage::disk('public');
        $filesDir = base_path('database/seeders/files');

        $paths = collect()
            ->merge(APP::pluck('uploaded_file'))
            ->merge(PPMP::pluck('xlsx_path'))
            ->merge(WorkProgram::pluck('file_url'))
            ->merge(ProjectBrief::pluck('file_url'))
            ->merge(ProjectProposal::pluck('file_url'))
            ->merge(Emanating::pluck('xlsx_path'))
            ->filter()
            ->unique();

        foreach ($paths as $path) {
            if (! $filesystem->exists($path)) {
                continue;
            }

            $dest = $filesDir.'/'.$path;
            $destDir = dirname($dest);

            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            file_put_contents($dest, $filesystem->get($path));
        }

        $count = $paths->count();
        $this->info(sprintf('Copied %d uploaded file(s) to database/seeders/files/', $count));
    }

    private function exportApps(): array
    {
        return APP::with('APPCategories.APPItems')->get()->map(function (APP $app): array {
            return [
                'office_code' => $app->office?->code,
                'fiscal_year' => $app->fiscal_year,
                'uploaded_file' => $app->uploaded_file,
                'categories' => $app->APPCategories->map(fn (APPCategory $appCategory): array => [
                    'account_code' => $appCategory->account?->code,
                    'early_procurement' => $appCategory->early_procurement,
                    'mode_of_procurement' => $appCategory->mode_of_procurement,
                    'schedule_from_month' => $appCategory->schedule_from_month,
                    'schedule_to_month' => $appCategory->schedule_to_month,
                    'source_of_fund' => $appCategory->source_of_fund,
                    'estimated_budget' => (float) $appCategory->estimated_budget,
                    'mooe_amount' => (float) $appCategory->mooe_amount,
                    'co_amount' => (float) $appCategory->co_amount,
                    'remarks' => $appCategory->remarks,
                    'items' => $appCategory->APPItems->map(fn (APPItem $appItem): array => [
                        'name' => $appItem->name,
                        'estimated_budget' => (float) $appItem->estimated_budget,
                        'mooe_amount' => (float) $appItem->mooe_amount,
                        'co_amount' => (float) $appItem->co_amount,
                        'remarks' => $appItem->remarks,
                    ])->all(),
                ])->all(),
            ];
        })->all();
    }

    private function exportFunds(): array
    {
        return Fund::with([
            'office',
            'projectCode',
            'project.workProgram.items',
            'project.projectBrief.items',
            'project.projectProposal',
        ])->get()->map(function (Fund $fund): array {
            $project = $fund->project;

            return [
                'office_code' => $fund->office?->code,
                'project_code' => $fund->projectCode?->code,
                'name' => $fund->name,
                'type' => $fund->type,
                'fiscal_year' => $fund->fiscal_year,
                'remarks' => $fund->remarks,
                'project' => $project ? [
                    'name' => $project->name,
                    'remarks' => $project->remarks,
                    'work_program' => $project->workProgram ? [
                        'file_url' => $project->workProgram->file_url,
                        'items' => $project->workProgram->items->map(fn (WorkProgramItem $workProgramItem): array => [
                            'item_name' => $workProgramItem->item_name,
                            'quantity' => (float) $workProgramItem->quantity,
                            'unit' => $workProgramItem->unit,
                            'amount' => (float) $workProgramItem->amount,
                            'row_order' => $workProgramItem->row_order,
                        ])->all(),
                    ] : null,
                    'project_brief' => $project->projectBrief ? [
                        'file_url' => $project->projectBrief->file_url,
                        'items' => $project->projectBrief->items->map(fn (ProjectBriefItem $projectBriefItem): array => [
                            'item_name' => $projectBriefItem->item_name,
                            'quantity' => (float) $projectBriefItem->quantity,
                            'unit' => $projectBriefItem->unit,
                            'amount' => (float) $projectBriefItem->amount,
                            'row_order' => $projectBriefItem->row_order,
                        ])->all(),
                    ] : null,
                    'project_proposal' => $project->projectProposal ? [
                        'file_url' => $project->projectProposal->file_url,
                    ] : null,
                ] : null,
            ];
        })->all();
    }

    private function exportPpmps(): array
    {
        return PPMP::with('office', 'projectCode', 'categories.account', 'categories.items.months')
            ->get()
            ->map(function (PPMP $ppmp): array {
                return [
                    'office_code' => $ppmp->office?->code,
                    'project_code' => $ppmp->projectCode?->code,
                    'fiscal_year' => $ppmp->fiscal_year,
                    'is_addendum' => $ppmp->is_addendum,
                    'remarks' => $ppmp->remarks,
                    'xlsx_path' => $ppmp->xlsx_path,
                    'budget_notices' => $ppmp->budget_notices,
                    'categories' => $ppmp->categories->map(fn (PPMPCategory $ppmpCategory): array => [
                        'account_code' => $ppmpCategory->account?->code,
                        'estimated_budget' => (float) $ppmpCategory->estimated_budget,
                        'remaining_budget' => (float) $ppmpCategory->remaining_budget,
                        'items' => $ppmpCategory->items->map(fn (PPMPItem $ppmpItem): array => [
                            'name' => $ppmpItem->name,
                            'quantity' => $ppmpItem->quantity,
                            'unit' => $ppmpItem->unit,
                            'estimated_budget' => (float) $ppmpItem->estimated_budget,
                            'remaining_budget' => (float) $ppmpItem->remaining_budget,
                            'mode_of_procurement' => $ppmpItem->mode_of_procurement,
                            'months' => $ppmpItem->months->map(fn (PPMPItemMonth $ppmpItemMonth): array => [
                                'month' => $ppmpItemMonth->month,
                                'planned_quantity' => $ppmpItemMonth->planned_quantity,
                            ])->all(),
                        ])->all(),
                    ])->all(),
                ];
            })->all();
    }

    private function exportEmanatings(): array
    {
        return Emanating::with('fund.office', 'fund.projectCode', 'ppmp', 'account', 'ppmpCategory.account', 'emanatingItems', 'project')
            ->get()
            ->map(function (Emanating $emanating): array {
                return [
                    'emanating_no' => $emanating->emanating_no,
                    'fund_office_code' => $emanating->fund?->office?->code,
                    'fund_project_code' => $emanating->fund?->projectCode?->code,
                    'fund_name' => $emanating->fund?->name,
                    'ppmp_office_code' => $emanating->ppmp?->office?->code,
                    'ppmp_project_code' => $emanating->ppmp?->projectCode?->code,
                    'ppmp_fiscal_year' => $emanating->ppmp?->fiscal_year,
                    'account_code' => $emanating->account?->code,
                    'ppmp_category_account_code' => $emanating->ppmpCategory?->account?->code,
                    'charged_to_code' => $emanating->charged_to_code,
                    'pr_no' => $emanating->pr_no,
                    'fiscal_year' => $emanating->fiscal_year,
                    'quarter' => $emanating->quarter,
                    'month' => $emanating->month,
                    'is_addendum' => $emanating->is_addendum,
                    'remarks' => $emanating->remarks,
                    'reimbursement' => $emanating->reimbursement,
                    'xlsx_path' => $emanating->xlsx_path,
                    'requesting_officer_name' => $emanating->requesting_officer_name,
                    'requesting_officer_title' => $emanating->requesting_officer_title,
                    'items_match_ppmp' => $emanating->items_match_ppmp,
                    'is_canvassed' => $emanating->is_canvassed,
                    'is_approved' => $emanating->is_approved,
                    'approved_at' => $emanating->approved_at,
                    'rejection_reason' => $emanating->rejection_reason,
                    'status' => $emanating->status,
                    'items' => $emanating->emanatingItems->map(fn (EmanatingItem $emanatingItem): array => [
                        'ppmp_item_name' => $emanatingItem->ppmpItem?->name,
                        'name' => $emanatingItem->name,
                        'quantity' => $emanatingItem->quantity,
                        'unit' => $emanatingItem->unit,
                        'total_price' => (float) $emanatingItem->total_price,
                    ])->all(),
                ];
            })->all();
    }

    private function exportCanvasses(): array
    {
        return Canvas::with([
            'emanating',
            'createdBy',
            'canvasItems.emanatingItem',
            'canvasItems.selections.masterListItem.masterListCategory',
            'canvasItems.selections.masterListItem.supplier',
        ])->get()->map(function (Canvas $canvas): array {
            return [
                'emanating_no' => $canvas->emanating?->emanating_no,
                'created_by_email' => $canvas->createdBy?->email,
                'status' => $canvas->status,
                'return_reason' => $canvas->return_reason,
                'total_amount' => (float) $canvas->total_amount,
                'completed_at' => $canvas->completed_at,
                'items' => $canvas->canvasItems->map(fn (CanvasItem $canvasItem): array => [
                    'emanating_item_name' => $canvasItem->emanatingItem?->name,
                    'computed_price' => (float) $canvasItem->computed_price,
                    'selections' => $canvasItem->selections->map(fn (CanvasItemSelection $canvasItemSelection): array => [
                        'mli_supplier_name' => $canvasItemSelection->masterListItem?->supplier?->name,
                        'mli_category_name' => $canvasItemSelection->masterListItem?->masterListCategory?->name,
                        'mli_item_name' => $canvasItemSelection->masterListItem?->item_name,
                        'mli_unit' => $canvasItemSelection->masterListItem?->unit,
                        'quantity' => (float) $canvasItemSelection->quantity,
                        'unit_price' => (float) $canvasItemSelection->unit_price,
                        'subtotal' => (float) $canvasItemSelection->subtotal,
                    ])->all(),
                ])->all(),
            ];
        })->all();
    }

    private function exportPurchaseRequests(): array
    {
        return PurchaseRequest::with([
            'emanating',
            'office',
            'fund',
            'items.emanatingItem',
        ])->get()->map(function (PurchaseRequest $purchaseRequest): array {
            return [
                'emanating_no' => $purchaseRequest->emanating?->emanating_no,
                'office_code' => $purchaseRequest->office?->code,
                'fund_office_code' => $purchaseRequest->fund?->office?->code,
                'fund_project_code' => $purchaseRequest->fund?->projectCode?->code,
                'fund_name' => $purchaseRequest->fund?->name,
                'pr_no' => $purchaseRequest->pr_no,
                'pr_date' => $purchaseRequest->pr_date,
                'sai_no' => $purchaseRequest->sai_no,
                'sai_date' => $purchaseRequest->sai_date,
                'requested_by_name' => $purchaseRequest->requested_by_name,
                'requested_by_designation' => $purchaseRequest->requested_by_designation,
                'purpose' => $purchaseRequest->purpose,
                'total_amount' => (float) $purchaseRequest->total_amount,
                'status' => $purchaseRequest->status,
                'remarks' => $purchaseRequest->remarks,
                'items' => $purchaseRequest->items->map(fn (PurchaseRequestItem $purchaseRequestItem): array => [
                    'emanating_item_name' => $purchaseRequestItem->emanatingItem?->name,
                    'quantity' => $purchaseRequestItem->quantity,
                    'unit_cost' => (float) $purchaseRequestItem->unit_cost,
                    'line_total' => (float) $purchaseRequestItem->line_total,
                    'vat_applicable' => $purchaseRequestItem->vat_applicable,
                    'vat_rate' => (float) $purchaseRequestItem->vat_rate,
                    'remarks' => $purchaseRequestItem->remarks,
                    'matrix_amount_below_1m' => (float) $purchaseRequestItem->matrix_amount_below_1m,
                    'matrix_amount_above_1m' => (float) $purchaseRequestItem->matrix_amount_above_1m,
                    'matrix_new_amount' => (float) $purchaseRequestItem->matrix_new_amount,
                    'matrix_account_code' => $purchaseRequestItem->matrixAccount?->code,
                    'matrix_pr_admin_user_email' => $purchaseRequestItem->matrixPrAdminUser?->email,
                    'matrix_budgeting_admin_user_email' => $purchaseRequestItem->matrixBudgetingAdminUser?->email,
                    'matrix_date_release' => $purchaseRequestItem->matrix_date_release,
                    'matrix_new_date_release' => $purchaseRequestItem->matrix_new_date_release,
                    'matrix_remarks' => $purchaseRequestItem->matrix_remarks,
                ])->all(),
            ];
        })->all();
    }

    private function exportRfqs(): array
    {
        return RFQ::with([
            'purchaseRequest.emanating',
            'items.purchaseRequestItem.emanatingItem',
            'suppliers.supplier',
            'suppliers.supplierItems',
        ])->get()->map(function (RFQ $rfq): array {
            return [
                'pr_emanating_no' => $rfq->purchaseRequest?->emanating?->emanating_no,
                'pr_no' => $rfq->purchaseRequest?->pr_no,
                'svp_no' => $rfq->svp_no,
                'rfq_date' => $rfq->rfq_date,
                'submission_deadline' => $rfq->submission_deadline,
                'project_name' => $rfq->project_name,
                'abc_amount' => (float) $rfq->abc_amount,
                'remarks' => $rfq->remarks,
                'items' => $rfq->items->map(fn (RFQItem $rfqItem): array => [
                    'pr_emanating_no' => $rfqItem->purchaseRequestItem?->emanatingItem?->emanating?->emanating_no,
                    'pr_item_emanating_name' => $rfqItem->purchaseRequestItem?->emanatingItem?->name,
                    'item_name' => $rfqItem->item_name,
                    'unit' => $rfqItem->unit,
                    'quantity' => $rfqItem->quantity,
                ])->all(),
                'suppliers' => $rfq->suppliers->map(fn (RFQSupplier $rfqSupplier): array => [
                    'supplier_name' => $rfqSupplier->supplier?->name,
                    'is_late' => $rfqSupplier->is_late,
                    'submitted_at' => $rfqSupplier->submitted_at,
                    'remarks' => $rfqSupplier->remarks,
                    'supplier_items' => $rfqSupplier->supplierItems->map(fn (RFQSupplierItem $rfqSupplierItem): array => [
                        'rfq_item_name' => $rfqSupplierItem->rfqItem?->item_name,
                        'unit_price' => (float) $rfqSupplierItem->unit_price,
                    ])->all(),
                ])->all(),
            ];
        })->all();
    }

    private function exportAoqs(): array
    {
        return AOQ::with('rfq', 'winnerSupplier')->get()->map(function (AOQ $aoq): array {
            return [
                'rfq_svp_no' => $aoq->rfq?->svp_no,
                'aoq_date' => $aoq->aoq_date,
                'winner_supplier_name' => $aoq->winnerSupplier?->name,
            ];
        })->all();
    }
}
