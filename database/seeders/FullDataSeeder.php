<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AOQ;
use App\Models\Canvas;
use App\Models\CanvasItem;
use App\Models\CanvasItemSelection;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\RFQ;
use App\Models\RFQItem;
use App\Models\RFQSupplier;
use App\Models\RFQSupplierItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class FullDataSeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = base_path('database/seeders/data/sample-data.json');

        if (! is_file($dataPath)) {
            $this->command?->warn('No sample-data.json found at database/seeders/data/. Run php artisan data:export first.');

            return;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);

        // ── 1. CANVASSES ──────────────────────────────────────────────
        foreach ($data['canvasses'] ?? [] as $canvasData) {
            $emanating = \App\Models\Emanating::where('emanating_no', $canvasData['emanating_no'])->first();

            if (! $emanating) {
                continue;
            }

            $user = \App\Models\User::where('email', $canvasData['created_by_email'])->first();

            $canvas = Canvas::create([
                'emanating_id' => $emanating->id,
                'created_by' => $user?->id,
                'status' => $canvasData['status'],
                'return_reason' => $canvasData['return_reason'],
                'total_amount' => $canvasData['total_amount'],
                'completed_at' => $canvasData['completed_at'],
            ]);

            foreach ($canvasData['items'] as $ciData) {
                $ei = \App\Models\EmanatingItem::where('emanating_id', $emanating->id)
                    ->where('name', $ciData['emanating_item_name'])
                    ->first();

                $canvasItem = CanvasItem::create([
                    'canvas_id' => $canvas->id,
                    'emanating_item_id' => $ei?->id,
                    'computed_price' => $ciData['computed_price'],
                ]);

                foreach ($ciData['selections'] as $selData) {
                    $mli = $this->findMasterListItem(
                        $selData['mli_supplier_name'],
                        $selData['mli_category_name'],
                        $selData['mli_item_name'],
                        $selData['mli_unit'],
                    );

                    CanvasItemSelection::create([
                        'canvas_item_id' => $canvasItem->id,
                        'master_list_item_id' => $mli?->id,
                        'quantity' => $selData['quantity'],
                        'unit_price' => $selData['unit_price'],
                        'subtotal' => $selData['subtotal'],
                    ]);
                }
            }
        }

        // ── 2. PURCHASE REQUESTS ──────────────────────────────────────
        foreach ($data['purchase_requests'] ?? [] as $prData) {
            $emanating = \App\Models\Emanating::where('emanating_no', $prData['emanating_no'])->first();

            if (! $emanating) {
                continue;
            }

            $office = \App\Models\Office::where('code', $prData['office_code'])->first();
            $fund = null;

            if ($prData['fund_office_code']) {
                $off = \App\Models\Office::where('code', $prData['fund_office_code'])->first();
                $pCode = $prData['fund_project_code'] ? \App\Models\ProjectCode::where('code', $prData['fund_project_code'])->first() : null;
                $fund = \App\Models\Fund::where('office_id', $off?->id)
                    ->where('project_code_id', $pCode?->id)
                    ->where('name', $prData['fund_name'])
                    ->first();
            }

            $pr = PurchaseRequest::create([
                'emanating_id' => $emanating->id,
                'office_id' => $office?->id,
                'fund_id' => $fund?->id,
                'pr_no' => $prData['pr_no'],
                'pr_date' => $prData['pr_date'],
                'sai_no' => $prData['sai_no'],
                'sai_date' => $prData['sai_date'],
                'requested_by_name' => $prData['requested_by_name'],
                'requested_by_designation' => $prData['requested_by_designation'],
                'purpose' => $prData['purpose'],
                'total_amount' => $prData['total_amount'],
                'status' => $prData['status'],
                'remarks' => $prData['remarks'],
            ]);

            foreach ($prData['items'] as $priData) {
                $ei = \App\Models\EmanatingItem::where('emanating_id', $emanating->id)
                    ->where('name', $priData['emanating_item_name'])
                    ->first();

                $matrixAccount = $priData['matrix_account_code']
                    ? \App\Models\Account::where('code', $priData['matrix_account_code'])->first()
                    : null;

                $matrixPrAdmin = $priData['matrix_pr_admin_user_email']
                    ? \App\Models\User::where('email', $priData['matrix_pr_admin_user_email'])->first()
                    : null;

                $matrixBudgetAdmin = $priData['matrix_budgeting_admin_user_email']
                    ? \App\Models\User::where('email', $priData['matrix_budgeting_admin_user_email'])->first()
                    : null;

                PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'emanating_item_id' => $ei?->id,
                    'quantity' => $priData['quantity'],
                    'unit_cost' => $priData['unit_cost'],
                    'line_total' => $priData['line_total'],
                    'vat_applicable' => $priData['vat_applicable'],
                    'vat_rate' => $priData['vat_rate'],
                    'remarks' => $priData['remarks'],
                    'matrix_amount_below_1m' => $priData['matrix_amount_below_1m'],
                    'matrix_amount_above_1m' => $priData['matrix_amount_above_1m'],
                    'matrix_new_amount' => $priData['matrix_new_amount'],
                    'matrix_account_id' => $matrixAccount?->id,
                    'matrix_pr_admin_user_id' => $matrixPrAdmin?->id,
                    'matrix_budgeting_admin_user_id' => $matrixBudgetAdmin?->id,
                    'matrix_date_release' => $priData['matrix_date_release'],
                    'matrix_new_date_release' => $priData['matrix_new_date_release'],
                    'matrix_remarks' => $priData['matrix_remarks'],
                ]);
            }
        }

        // ── 3. RFQs ───────────────────────────────────────────────────
        foreach ($data['rfqs'] ?? [] as $rfqData) {
            $emanating = \App\Models\Emanating::where('emanating_no', $rfqData['pr_emanating_no'])->first();

            if (! $emanating) {
                continue;
            }

            $pr = PurchaseRequest::where('emanating_id', $emanating->id)
                ->where('pr_no', $rfqData['pr_no'])
                ->first();

            if (! $pr) {
                continue;
            }

            $rfq = RFQ::create([
                'pr_id' => $pr->id,
                'svp_no' => $rfqData['svp_no'],
                'rfq_date' => $rfqData['rfq_date'],
                'submission_deadline' => $rfqData['submission_deadline'],
                'project_name' => $rfqData['project_name'],
                'abc_amount' => $rfqData['abc_amount'],
                'remarks' => $rfqData['remarks'],
            ]);

            foreach ($rfqData['items'] as $riData) {
                $prItem = null;

                if ($riData['pr_emanating_no'] && $riData['pr_item_emanating_name']) {
                    $ei = \App\Models\EmanatingItem::where('emanating_id', $emanating->id)
                        ->where('name', $riData['pr_item_emanating_name'])
                        ->first();

                    $prItem = PurchaseRequestItem::where('purchase_request_id', $pr->id)
                        ->where('emanating_item_id', $ei?->id)
                        ->first();
                }

                RFQItem::create([
                    'rfq_id' => $rfq->id,
                    'pr_item_id' => $prItem?->id,
                    'item_name' => $riData['item_name'],
                    'unit' => $riData['unit'],
                    'quantity' => $riData['quantity'],
                ]);
            }

            foreach ($rfqData['suppliers'] as $rsData) {
                $supplier = Supplier::where('name', $rsData['supplier_name'])->first();

                if (! $supplier) {
                    continue;
                }

                $rfqSupplier = RFQSupplier::create([
                    'rfq_id' => $rfq->id,
                    'supplier_id' => $supplier->id,
                    'is_late' => $rsData['is_late'],
                    'submitted_at' => $rsData['submitted_at'],
                    'remarks' => $rsData['remarks'],
                ]);

                foreach ($rsData['supplier_items'] as $rsiData) {
                    $rfqItem = RFQItem::where('rfq_id', $rfq->id)
                        ->where('item_name', $rsiData['rfq_item_name'])
                        ->first();

                    RFQSupplierItem::create([
                        'rfq_supplier_id' => $rfqSupplier->id,
                        'rfq_item_id' => $rfqItem?->id,
                        'unit_price' => $rsiData['unit_price'],
                    ]);
                }
            }
        }

        // ── 4. AOQs ───────────────────────────────────────────────────
        foreach ($data['aoqs'] ?? [] as $aoqData) {
            $rfq = RFQ::where('svp_no', $aoqData['rfq_svp_no'])->first();

            if (! $rfq) {
                continue;
            }

            $winner = $aoqData['winner_supplier_name']
                ? Supplier::where('name', $aoqData['winner_supplier_name'])->first()
                : null;

            AOQ::create([
                'rfq_id' => $rfq->id,
                'aoq_date' => $aoqData['aoq_date'],
                'winner_supplier_id' => $winner?->id,
            ]);
        }
    }

    private function findMasterListItem(
        ?string $supplierName,
        ?string $categoryName,
        ?string $itemName,
        ?string $unit,
    ): ?MasterListItem {
        if (! $supplierName) {
            return null;
        }

        $supplier = Supplier::where('name', $supplierName)->first();

        if (! $supplier) {
            return null;
        }

        $category = $categoryName
            ? MasterListCategory::where('name', $categoryName)->first()
            : null;

        $query = MasterListItem::where('supplier_id', $supplier->id)
            ->where('master_list_category_id', $category?->id)
            ->where('item_name', $itemName ?? '');

        if ($unit) {
            $query->where('unit', $unit);
        }

        return $query->first();
    }
}
