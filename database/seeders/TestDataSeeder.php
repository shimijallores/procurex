<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use App\Models\APP;
use App\Models\APPCategory;
use App\Models\APPItem;
use App\Models\Emanating;
use App\Models\EmanatingItem;
use App\Models\Fund;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use App\Models\Office;
use App\Models\PPMP;
use App\Models\PPMPCategory;
use App\Models\PPMPItem;
use App\Models\PPMPItemMonth;
use App\Models\Project;
use App\Models\ProjectBrief;
use App\Models\ProjectBriefItem;
use App\Models\ProjectCode;
use App\Models\ProjectProposal;
use App\Models\Supplier;
use App\Models\WorkProgram;
use App\Models\WorkProgramItem;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $office = Office::query()->where('code', '8721')->first();
        $projectCode = ProjectCode::query()->where('code', '92402')->first();

        if (! $office || ! $projectCode) {
            return;
        }

        $fiscalYear = 2026;

        // ── 1. FUND ───────────────────────────────────────────────────
        $fund = Fund::updateOrCreate(
            [
                'office_id' => $office->id,
                'project_code_id' => $projectCode->id,
                'name' => 'Livestock Development Program of the Provincial Veterinary Office',
            ],
            [
                'type' => 'project',
                'fiscal_year' => $fiscalYear,
                'remarks' => 'Test data for Livestock Development and Animal Care Services',
            ]
        );

        $project = Project::updateOrCreate(
            ['fund_id' => $fund->id],
            ['name' => 'Livestock Development and Animal Care Services', 'remarks' => 'Project code 92402']
        );

        // Work Program
        $wp = WorkProgram::updateOrCreate(
            ['project_id' => $project->id],
            ['file_url' => 'documents/5. work-program-template.docx']
        );
        $wp->items()->delete();

        $wpItems = [
            ['item_name' => 'Vaccination Program', 'quantity' => 5000, 'unit' => 'dose', 'amount' => 500000, 'row_order' => 1],
            ['item_name' => 'Livestock Feed Distribution', 'quantity' => 1000, 'unit' => 'bag', 'amount' => 300000, 'row_order' => 2],
            ['item_name' => 'Veterinary Medicines', 'quantity' => 2000, 'unit' => 'vial', 'amount' => 400000, 'row_order' => 3],
            ['item_name' => 'Breeding Stock Acquisition', 'quantity' => 200, 'unit' => 'head', 'amount' => 600000, 'row_order' => 4],
        ];
        foreach ($wpItems as $wpi) {
            WorkProgramItem::create($wpi + ['work_program_id' => $wp->id]);
        }

        // Project Brief
        $pb = ProjectBrief::updateOrCreate(
            ['project_id' => $project->id],
            ['file_url' => 'documents/4. project-brief-template.docx']
        );
        $pb->items()->delete();

        $pbItems = [
            ['item_name' => 'Rabies Vaccine', 'quantity' => 2000, 'unit' => 'dose', 'amount' => 200000, 'row_order' => 1],
            ['item_name' => 'Hog Cholera Vaccine', 'quantity' => 1500, 'unit' => 'dose', 'amount' => 150000, 'row_order' => 2],
            ['item_name' => 'Poultry Feed Supplement', 'quantity' => 500, 'unit' => 'bag', 'amount' => 125000, 'row_order' => 3],
            ['item_name' => 'Breeding Chicks', 'quantity' => 100, 'unit' => 'head', 'amount' => 250000, 'row_order' => 4],
        ];
        foreach ($pbItems as $pbi) {
            ProjectBriefItem::create($pbi + ['project_brief_id' => $pb->id]);
        }

        // Project Proposal
        ProjectProposal::updateOrCreate(
            ['project_id' => $project->id],
            ['file_url' => 'documents/3. project-proposal-template.docx']
        );

        // ── 2. APP ────────────────────────────────────────────────────
        $app = APP::updateOrCreate(
            ['office_id' => $office->id, 'fiscal_year' => $fiscalYear],
            ['uploaded_file' => 'standard-template/app-template.xlsx']
        );

        $app->APPCategories()->delete();

        // APP Category 1: Animal/Zoological Supplies (account 30)
        $catAccount30 = Account::find(30);
        $appCat1 = APPCategory::create([
            'app_id' => $app->id,
            'account_id' => $catAccount30?->id,
            'early_procurement' => false,
            'mode_of_procurement' => 'small value',
            'schedule_from_month' => 1,
            'schedule_to_month' => 12,
            'source_of_fund' => 'Fund 101',
            'estimated_budget' => 450000,
            'mooe_amount' => 450000,
            'co_amount' => null,
            'remarks' => 'Veterinary biologicals and medicines',
        ]);

        $appCat1Items = [
            ['name' => 'Rabies Vaccine', 'estimated_budget' => 100000, 'mooe_amount' => 100000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Hog Cholera Vaccine', 'estimated_budget' => 80000, 'mooe_amount' => 80000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Newcastle Disease Vaccine', 'estimated_budget' => 70000, 'mooe_amount' => 70000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Veterinary Dewormer', 'estimated_budget' => 50000, 'mooe_amount' => 50000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Disinfectant Solution', 'estimated_budget' => 75000, 'mooe_amount' => 75000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Multivitamin for Livestock', 'estimated_budget' => 75000, 'mooe_amount' => 75000, 'co_amount' => null, 'remarks' => null],
        ];
        foreach ($appCat1Items as $item) {
            APPItem::create($item + ['app_category_id' => $appCat1->id]);
        }

        // APP Category 2: Agricultural and Marine Supplies (account 36)
        $catAccount36 = Account::find(36);
        $appCat2 = APPCategory::create([
            'app_id' => $app->id,
            'account_id' => $catAccount36?->id,
            'early_procurement' => false,
            'mode_of_procurement' => 'small value',
            'schedule_from_month' => 1,
            'schedule_to_month' => 12,
            'source_of_fund' => 'Fund 101',
            'estimated_budget' => 375000,
            'mooe_amount' => 375000,
            'co_amount' => null,
            'remarks' => 'Animal feed and supplements',
        ]);

        $appCat2Items = [
            ['name' => 'Poultry Feed Supplement', 'estimated_budget' => 125000, 'mooe_amount' => 125000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Swine Grower Feed', 'estimated_budget' => 100000, 'mooe_amount' => 100000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Mineral Salt Block', 'estimated_budget' => 75000, 'mooe_amount' => 75000, 'co_amount' => null, 'remarks' => null],
            ['name' => 'Fodder Seeds', 'estimated_budget' => 75000, 'mooe_amount' => 75000, 'co_amount' => null, 'remarks' => null],
        ];
        foreach ($appCat2Items as $item) {
            APPItem::create($item + ['app_category_id' => $appCat2->id]);
        }

        // APP Category 3: Breeding Stocks (account 103)
        $catAccount103 = Account::find(103);
        $appCat3 = APPCategory::create([
            'app_id' => $app->id,
            'account_id' => $catAccount103?->id,
            'early_procurement' => false,
            'mode_of_procurement' => 'small value',
            'schedule_from_month' => 3,
            'schedule_to_month' => 9,
            'source_of_fund' => 'Fund 101',
            'estimated_budget' => 600000,
            'mooe_amount' => null,
            'co_amount' => 600000,
            'remarks' => 'Livestock acquisition',
        ]);

        $appCat3Items = [
            ['name' => 'Breeding Chicks', 'estimated_budget' => 250000, 'mooe_amount' => null, 'co_amount' => 250000, 'remarks' => null],
            ['name' => 'Breeding Piglets', 'estimated_budget' => 200000, 'mooe_amount' => null, 'co_amount' => 200000, 'remarks' => null],
            ['name' => 'Breeding Goats', 'estimated_budget' => 150000, 'mooe_amount' => null, 'co_amount' => 150000, 'remarks' => null],
        ];
        foreach ($appCat3Items as $item) {
            APPItem::create($item + ['app_category_id' => $appCat3->id]);
        }

        // ── 3. PPMP ───────────────────────────────────────────────────
        $ppmp = PPMP::updateOrCreate(
            [
                'office_id' => $office->id,
                'project_code_id' => $projectCode->id,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'is_addendum' => false,
                'remarks' => 'Livestock Development PPMP - Test Data',
                'xlsx_path' => null,
                'budget_notices' => null,
            ]
        );

        $ppmp->categories()->delete();

        // PPMP Category 1: Animal/Zoological Supplies (account 30)
        $ppmpCat1 = PPMPCategory::create([
            'ppmp_id' => $ppmp->id,
            'account_id' => $catAccount30?->id,
            'estimated_budget' => 450000,
            'remaining_budget' => 450000,
        ]);

        $ppmpCat1Items = [
            ['name' => 'Rabies Vaccine', 'quantity' => 2000, 'unit' => 'dose', 'estimated_budget' => 100000, 'remaining_budget' => 100000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 1, 'planned_quantity' => 200], ['month' => 4, 'planned_quantity' => 500], ['month' => 7, 'planned_quantity' => 500], ['month' => 10, 'planned_quantity' => 800]]],
            ['name' => 'Hog Cholera Vaccine', 'quantity' => 1500, 'unit' => 'dose', 'estimated_budget' => 80000, 'remaining_budget' => 80000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 2, 'planned_quantity' => 500], ['month' => 6, 'planned_quantity' => 500], ['month' => 10, 'planned_quantity' => 500]]],
            ['name' => 'Newcastle Disease Vaccine', 'quantity' => 1000, 'unit' => 'dose', 'estimated_budget' => 70000, 'remaining_budget' => 70000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 3, 'planned_quantity' => 300], ['month' => 8, 'planned_quantity' => 400], ['month' => 11, 'planned_quantity' => 300]]],
            ['name' => 'Veterinary Dewormer', 'quantity' => 500, 'unit' => 'bottle', 'estimated_budget' => 50000, 'remaining_budget' => 50000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 2, 'planned_quantity' => 150], ['month' => 5, 'planned_quantity' => 150], ['month' => 9, 'planned_quantity' => 200]]],
            ['name' => 'Disinfectant Solution', 'quantity' => 300, 'unit' => 'gallon', 'estimated_budget' => 75000, 'remaining_budget' => 75000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 1, 'planned_quantity' => 100], ['month' => 4, 'planned_quantity' => 100], ['month' => 7, 'planned_quantity' => 100]]],
            ['name' => 'Multivitamin for Livestock', 'quantity' => 400, 'unit' => 'liter', 'estimated_budget' => 75000, 'remaining_budget' => 75000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 3, 'planned_quantity' => 100], ['month' => 6, 'planned_quantity' => 150], ['month' => 9, 'planned_quantity' => 150]]],
        ];
        foreach ($ppmpCat1Items as $itemData) {
            $months = $itemData['months'];
            unset($itemData['months']);
            $item = PPMPItem::create($itemData + ['ppmp_category_id' => $ppmpCat1->id]);
            foreach ($months as $m) {
                PPMPItemMonth::create([
                    'ppmp_item_id' => $item->id,
                    'month' => $m['month'],
                    'planned_quantity' => $m['planned_quantity'],
                ]);
            }
        }

        // PPMP Category 2: Agricultural and Marine Supplies (account 36)
        $ppmpCat2 = PPMPCategory::create([
            'ppmp_id' => $ppmp->id,
            'account_id' => $catAccount36?->id,
            'estimated_budget' => 375000,
            'remaining_budget' => 375000,
        ]);

        $ppmpCat2Items = [
            ['name' => 'Poultry Feed Supplement', 'quantity' => 500, 'unit' => 'bag', 'estimated_budget' => 125000, 'remaining_budget' => 125000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 1, 'planned_quantity' => 100], ['month' => 4, 'planned_quantity' => 150], ['month' => 7, 'planned_quantity' => 150], ['month' => 10, 'planned_quantity' => 100]]],
            ['name' => 'Swine Grower Feed', 'quantity' => 400, 'unit' => 'bag', 'estimated_budget' => 100000, 'remaining_budget' => 100000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 2, 'planned_quantity' => 100], ['month' => 5, 'planned_quantity' => 100], ['month' => 8, 'planned_quantity' => 100], ['month' => 11, 'planned_quantity' => 100]]],
            ['name' => 'Mineral Salt Block', 'quantity' => 200, 'unit' => 'block', 'estimated_budget' => 75000, 'remaining_budget' => 75000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 3, 'planned_quantity' => 50], ['month' => 6, 'planned_quantity' => 50], ['month' => 9, 'planned_quantity' => 50], ['month' => 12, 'planned_quantity' => 50]]],
            ['name' => 'Fodder Seeds', 'quantity' => 100, 'unit' => 'kilo', 'estimated_budget' => 75000, 'remaining_budget' => 75000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 1, 'planned_quantity' => 25], ['month' => 4, 'planned_quantity' => 25], ['month' => 7, 'planned_quantity' => 25], ['month' => 10, 'planned_quantity' => 25]]],
        ];
        foreach ($ppmpCat2Items as $itemData) {
            $months = $itemData['months'];
            unset($itemData['months']);
            $item = PPMPItem::create($itemData + ['ppmp_category_id' => $ppmpCat2->id]);
            foreach ($months as $m) {
                PPMPItemMonth::create([
                    'ppmp_item_id' => $item->id,
                    'month' => $m['month'],
                    'planned_quantity' => $m['planned_quantity'],
                ]);
            }
        }

        // PPMP Category 3: Breeding Stocks (account 103)
        $ppmpCat3 = PPMPCategory::create([
            'ppmp_id' => $ppmp->id,
            'account_id' => $catAccount103?->id,
            'estimated_budget' => 600000,
            'remaining_budget' => 600000,
        ]);

        $ppmpCat3Items = [
            ['name' => 'Breeding Chicks', 'quantity' => 100, 'unit' => 'head', 'estimated_budget' => 250000, 'remaining_budget' => 250000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 3, 'planned_quantity' => 50], ['month' => 9, 'planned_quantity' => 50]]],
            ['name' => 'Breeding Piglets', 'quantity' => 20, 'unit' => 'head', 'estimated_budget' => 200000, 'remaining_budget' => 200000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 5, 'planned_quantity' => 10], ['month' => 11, 'planned_quantity' => 10]]],
            ['name' => 'Breeding Goats', 'quantity' => 15, 'unit' => 'head', 'estimated_budget' => 150000, 'remaining_budget' => 150000, 'mode_of_procurement' => 'small value', 'months' => [['month' => 4, 'planned_quantity' => 8], ['month' => 10, 'planned_quantity' => 7]]],
        ];
        foreach ($ppmpCat3Items as $itemData) {
            $months = $itemData['months'];
            unset($itemData['months']);
            $item = PPMPItem::create($itemData + ['ppmp_category_id' => $ppmpCat3->id]);
            foreach ($months as $m) {
                PPMPItemMonth::create([
                    'ppmp_item_id' => $item->id,
                    'month' => $m['month'],
                    'planned_quantity' => $m['planned_quantity'],
                ]);
            }
        }

        // ── 4. EMANATING ──────────────────────────────────────────────
        $emanating = Emanating::create([
            'emanating_no' => null,
            'fund_id' => $fund->id,
            'ppmp_id' => $ppmp->id,
            'project_id' => $project->id,
            'account_id' => $catAccount30?->id,
            'ppmp_category_id' => $ppmpCat1->id,
            'charged_to_code' => null,
            'pr_no' => null,
            'fiscal_year' => $fiscalYear,
            'quarter' => 1,
            'month' => 1,
            'is_addendum' => false,
            'remarks' => 'Initial vaccine and medicine procurement for Q1 livestock program',
            'reimbursement' => false,
            'xlsx_path' => null,
            'requesting_officer_name' => 'Dr. Maria Santos',
            'requesting_officer_title' => 'Provincial Veterinarian',
            'items_match_ppmp' => true,
            'is_canvassed' => false,
            'is_approved' => true,
            'approved_at' => now(),
            'rejection_reason' => null,
            'status' => 'approved',
        ]);

        $emanatingItems = [
            ['ppmp_item_name' => 'Rabies Vaccine', 'name' => 'Rabies Vaccine', 'quantity' => 500, 'unit' => 'dose', 'total_price' => 30000],
            ['ppmp_item_name' => 'Hog Cholera Vaccine', 'name' => 'Hog Cholera Vaccine', 'quantity' => 500, 'unit' => 'dose', 'total_price' => 28000],
            ['ppmp_item_name' => 'Disinfectant Solution', 'name' => 'Disinfectant Solution', 'quantity' => 100, 'unit' => 'gallon', 'total_price' => 25000],
            ['ppmp_item_name' => 'Multivitamin for Livestock', 'name' => 'Multivitamin for Livestock', 'quantity' => 100, 'unit' => 'liter', 'total_price' => 20000],
        ];
        foreach ($emanatingItems as $eiData) {
            $ppmpItem = PPMPItem::where('ppmp_category_id', $ppmpCat1->id)
                ->where('name', $eiData['ppmp_item_name'])
                ->first();

            EmanatingItem::create([
                'emanating_id' => $emanating->id,
                'ppmp_item_id' => $ppmpItem?->id,
                'name' => $eiData['name'],
                'quantity' => $eiData['quantity'],
                'unit' => $eiData['unit'],
                'total_price' => $eiData['total_price'],
            ]);
        }

        // ── 5. MASTERLIST ─────────────────────────────────────────────
        $vetCategory = MasterListCategory::firstOrCreate(
            ['name' => 'Veterinary & Agricultural Supplies'],
            ['description' => 'Veterinary medicines, vaccines, feeds, and agricultural supplies', 'is_active' => true]
        );

        $supplier = Supplier::query()->where('name', 'M AND A TRADING')->first()
            ?? Supplier::query()->first();

        $masterListItems = [
            ['item_name' => 'Rabies Vaccine', 'unit' => 'dose', 'default_unit_price' => 60.00],
            ['item_name' => 'Hog Cholera Vaccine', 'unit' => 'dose', 'default_unit_price' => 56.00],
            ['item_name' => 'Disinfectant Solution', 'unit' => 'gallon', 'default_unit_price' => 250.00],
            ['item_name' => 'Multivitamin for Livestock', 'unit' => 'liter', 'default_unit_price' => 200.00],
        ];

        foreach ($masterListItems as $mlItem) {
            MasterListItem::firstOrCreate(
                [
                    'master_list_category_id' => $vetCategory->id,
                    'supplier_id' => $supplier->id,
                    'item_name' => $mlItem['item_name'],
                    'unit' => $mlItem['unit'],
                ],
                [
                    'default_unit_price' => $mlItem['default_unit_price'],
                    'is_phased_out' => false,
                    'search_key' => strtolower($mlItem['item_name']),
                ]
            );
        }
    }
}
