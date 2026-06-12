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

        $this->info("Exported to {$path}");

        return self::SUCCESS;
    }

    private function copyUploadedFiles(): void
    {
        $storage = Storage::disk('public');
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
            if (! $storage->exists($path)) {
                continue;
            }

            $dest = $filesDir.'/'.$path;
            $destDir = dirname($dest);

            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            file_put_contents($dest, $storage->get($path));
        }

        $count = $paths->count();
        $this->info("Copied {$count} uploaded file(s) to database/seeders/files/");
    }

    private function exportApps(): array
    {
        return APP::with('APPCategories.APPItems')->get()->map(function (APP $app): array {
            return [
                'office_code' => $app->office?->code,
                'fiscal_year' => $app->fiscal_year,
                'uploaded_file' => $app->uploaded_file,
                'categories' => $app->APPCategories->map(fn (APPCategory $cat): array => [
                    'account_code' => $cat->account?->code,
                    'early_procurement' => $cat->early_procurement,
                    'mode_of_procurement' => $cat->mode_of_procurement,
                    'schedule_from_month' => $cat->schedule_from_month,
                    'schedule_to_month' => $cat->schedule_to_month,
                    'source_of_fund' => $cat->source_of_fund,
                    'estimated_budget' => (float) $cat->estimated_budget,
                    'mooe_amount' => (float) $cat->mooe_amount,
                    'co_amount' => (float) $cat->co_amount,
                    'remarks' => $cat->remarks,
                    'items' => $cat->APPItems->map(fn (APPItem $item): array => [
                        'name' => $item->name,
                        'estimated_budget' => (float) $item->estimated_budget,
                        'mooe_amount' => (float) $item->mooe_amount,
                        'co_amount' => (float) $item->co_amount,
                        'remarks' => $item->remarks,
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
                        'items' => $project->workProgram->items->map(fn (WorkProgramItem $i): array => [
                            'item_name' => $i->item_name,
                            'quantity' => (float) $i->quantity,
                            'unit' => $i->unit,
                            'amount' => (float) $i->amount,
                            'row_order' => $i->row_order,
                        ])->all(),
                    ] : null,
                    'project_brief' => $project->projectBrief ? [
                        'file_url' => $project->projectBrief->file_url,
                        'items' => $project->projectBrief->items->map(fn (ProjectBriefItem $i): array => [
                            'item_name' => $i->item_name,
                            'quantity' => (float) $i->quantity,
                            'unit' => $i->unit,
                            'amount' => (float) $i->amount,
                            'row_order' => $i->row_order,
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
                    'categories' => $ppmp->categories->map(fn (PPMPCategory $cat): array => [
                        'account_code' => $cat->account?->code,
                        'estimated_budget' => (float) $cat->estimated_budget,
                        'remaining_budget' => (float) $cat->remaining_budget,
                        'items' => $cat->items->map(fn (PPMPItem $item): array => [
                            'name' => $item->name,
                            'quantity' => $item->quantity,
                            'unit' => $item->unit,
                            'estimated_budget' => (float) $item->estimated_budget,
                            'remaining_budget' => (float) $item->remaining_budget,
                            'mode_of_procurement' => $item->mode_of_procurement,
                            'months' => $item->months->map(fn (PPMPItemMonth $m): array => [
                                'month' => $m->month,
                                'planned_quantity' => $m->planned_quantity,
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
                    'items' => $emanating->emanatingItems->map(fn (EmanatingItem $ei): array => [
                        'ppmp_item_name' => $ei->ppmpItem?->name,
                        'name' => $ei->name,
                        'quantity' => $ei->quantity,
                        'unit' => $ei->unit,
                        'total_price' => (float) $ei->total_price,
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
                'items' => $canvas->canvasItems->map(fn (CanvasItem $ci): array => [
                    'emanating_item_name' => $ci->emanatingItem?->name,
                    'computed_price' => (float) $ci->computed_price,
                    'selections' => $ci->selections->map(fn (CanvasItemSelection $sel): array => [
                        'mli_supplier_name' => $sel->masterListItem?->supplier?->name,
                        'mli_category_name' => $sel->masterListItem?->masterListCategory?->name,
                        'mli_item_name' => $sel->masterListItem?->item_name,
                        'mli_unit' => $sel->masterListItem?->unit,
                        'quantity' => (float) $sel->quantity,
                        'unit_price' => (float) $sel->unit_price,
                        'subtotal' => (float) $sel->subtotal,
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
        ])->get()->map(function (PurchaseRequest $pr): array {
            return [
                'emanating_no' => $pr->emanating?->emanating_no,
                'office_code' => $pr->office?->code,
                'fund_office_code' => $pr->fund?->office?->code,
                'fund_project_code' => $pr->fund?->projectCode?->code,
                'fund_name' => $pr->fund?->name,
                'pr_no' => $pr->pr_no,
                'pr_date' => $pr->pr_date,
                'sai_no' => $pr->sai_no,
                'sai_date' => $pr->sai_date,
                'requested_by_name' => $pr->requested_by_name,
                'requested_by_designation' => $pr->requested_by_designation,
                'purpose' => $pr->purpose,
                'total_amount' => (float) $pr->total_amount,
                'status' => $pr->status,
                'remarks' => $pr->remarks,
                'items' => $pr->items->map(fn (PurchaseRequestItem $pri): array => [
                    'emanating_item_name' => $pri->emanatingItem?->name,
                    'quantity' => $pri->quantity,
                    'unit_cost' => (float) $pri->unit_cost,
                    'line_total' => (float) $pri->line_total,
                    'vat_applicable' => $pri->vat_applicable,
                    'vat_rate' => (float) $pri->vat_rate,
                    'remarks' => $pri->remarks,
                    'matrix_amount_below_1m' => (float) $pri->matrix_amount_below_1m,
                    'matrix_amount_above_1m' => (float) $pri->matrix_amount_above_1m,
                    'matrix_new_amount' => (float) $pri->matrix_new_amount,
                    'matrix_account_code' => $pri->matrixAccount?->code,
                    'matrix_pr_admin_user_email' => $pri->matrixPrAdminUser?->email,
                    'matrix_budgeting_admin_user_email' => $pri->matrixBudgetingAdminUser?->email,
                    'matrix_date_release' => $pri->matrix_date_release,
                    'matrix_new_date_release' => $pri->matrix_new_date_release,
                    'matrix_remarks' => $pri->matrix_remarks,
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
                'items' => $rfq->items->map(fn (RFQItem $ri): array => [
                    'pr_emanating_no' => $ri->purchaseRequestItem?->emanatingItem?->emanating?->emanating_no,
                    'pr_item_emanating_name' => $ri->purchaseRequestItem?->emanatingItem?->name,
                    'item_name' => $ri->item_name,
                    'unit' => $ri->unit,
                    'quantity' => $ri->quantity,
                ])->all(),
                'suppliers' => $rfq->suppliers->map(fn (RFQSupplier $rs): array => [
                    'supplier_name' => $rs->supplier?->name,
                    'is_late' => $rs->is_late,
                    'submitted_at' => $rs->submitted_at,
                    'remarks' => $rs->remarks,
                    'supplier_items' => $rs->supplierItems->map(fn (RFQSupplierItem $rsi): array => [
                        'rfq_item_name' => $rsi->rfqItem?->item_name,
                        'unit_price' => (float) $rsi->unit_price,
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
