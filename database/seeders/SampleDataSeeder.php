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
use App\Models\Role;
use App\Models\User;
use App\Models\WorkProgram;
use App\Models\WorkProgramItem;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $veterinaryOffice = Office::query()->where('code', '8721')->first();

        if (! $veterinaryOffice) {
            return;
        }

        $veterinaryRole = Role::firstOrCreate(
            ['name' => 'Veterinary Staff'],
            ['is_system_role' => false, 'office_id' => $veterinaryOffice->id]
        );

        $officeUser = User::updateOrCreate(
            ['email' => 'veterenaryadmin@procurex.com'],
            [
                'name' => 'Veterinary Admin',
                'office_id' => $veterinaryOffice->id,
                'password' => 'password',
            ]
        );

        $officeUser->roles()->sync([$veterinaryRole->id]);

        $dataPath = base_path('database/seeders/data/sample-data.json');

        if (! is_file($dataPath)) {
            $this->command?->warn('No sample-data.json found at database/seeders/data/. Run php artisan data:export first.');

            return;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode(file_get_contents($dataPath), true, 512, JSON_THROW_ON_ERROR);

        // ── 1. APPS ───────────────────────────────────────────────────
        foreach ($data['apps'] as $appData) {
            $office = Office::where('code', $appData['office_code'])->first();
            if (! $office) {
                continue;
            }

            $app = APP::updateOrCreate(
                ['office_id' => $office->id, 'fiscal_year' => $appData['fiscal_year']],
                ['uploaded_file' => $appData['uploaded_file']]
            );

            $app->APPCategories()->delete();

            foreach ($appData['categories'] as $catData) {
                $account = Account::where('code', $catData['account_code'])->first();

                $category = APPCategory::create([
                    'app_id' => $app->id,
                    'account_id' => $account?->id,
                    'early_procurement' => $catData['early_procurement'],
                    'mode_of_procurement' => $catData['mode_of_procurement'],
                    'schedule_from_month' => $catData['schedule_from_month'],
                    'schedule_to_month' => $catData['schedule_to_month'],
                    'source_of_fund' => $catData['source_of_fund'],
                    'estimated_budget' => $catData['estimated_budget'],
                    'mooe_amount' => $catData['mooe_amount'],
                    'co_amount' => $catData['co_amount'],
                    'remarks' => $catData['remarks'],
                ]);

                foreach ($catData['items'] as $itemData) {
                    APPItem::create([
                        'app_category_id' => $category->id,
                        'name' => $itemData['name'],
                        'estimated_budget' => $itemData['estimated_budget'],
                        'mooe_amount' => $itemData['mooe_amount'],
                        'co_amount' => $itemData['co_amount'],
                        'remarks' => $itemData['remarks'],
                    ]);
                }
            }
        }

        // ── 2. FUNDS ──────────────────────────────────────────────────
        foreach ($data['funds'] as $fundData) {
            $office = Office::where('code', $fundData['office_code'])->first();
            $projectCode = ProjectCode::where('code', $fundData['project_code'])->first();
            if (! $office || ! $projectCode) {
                continue;
            }

            $fund = Fund::updateOrCreate(
                [
                    'office_id' => $office->id,
                    'project_code_id' => $projectCode->id,
                    'name' => $fundData['name'],
                ],
                [
                    'type' => $fundData['type'],
                    'fiscal_year' => $fundData['fiscal_year'],
                    'remarks' => $fundData['remarks'],
                ]
            );

            if (! $fundData['project']) {
                continue;
            }

            $projData = $fundData['project'];

            $project = Project::updateOrCreate(
                ['fund_id' => $fund->id],
                ['name' => $projData['name'], 'remarks' => $projData['remarks']]
            );

            if ($projData['work_program']) {
                $wp = $projData['work_program'];
                $wpModel = WorkProgram::updateOrCreate(
                    ['project_id' => $project->id],
                    ['file_url' => $wp['file_url']]
                );
                $wpModel->items()->delete();

                foreach ($wp['items'] as $wpi) {
                    WorkProgramItem::create([
                        'work_program_id' => $wpModel->id,
                        'item_name' => $wpi['item_name'],
                        'quantity' => $wpi['quantity'],
                        'unit' => $wpi['unit'],
                        'amount' => $wpi['amount'],
                        'row_order' => $wpi['row_order'],
                    ]);
                }
            }

            if ($projData['project_brief']) {
                $pb = $projData['project_brief'];
                $pbModel = ProjectBrief::updateOrCreate(
                    ['project_id' => $project->id],
                    ['file_url' => $pb['file_url']]
                );
                $pbModel->items()->delete();

                foreach ($pb['items'] as $pbi) {
                    ProjectBriefItem::create([
                        'project_brief_id' => $pbModel->id,
                        'item_name' => $pbi['item_name'],
                        'quantity' => $pbi['quantity'],
                        'unit' => $pbi['unit'],
                        'amount' => $pbi['amount'],
                        'row_order' => $pbi['row_order'],
                    ]);
                }
            }

            if ($projData['project_proposal']) {
                ProjectProposal::updateOrCreate(
                    ['project_id' => $project->id],
                    ['file_url' => $projData['project_proposal']['file_url']]
                );
            }
        }

        // ── 3. PPMPS ─────────────────────────────────────────────────
        foreach ($data['ppmps'] as $ppmpData) {
            $office = Office::where('code', $ppmpData['office_code'])->first();
            $projectCode = $ppmpData['project_code'] ? ProjectCode::where('code', $ppmpData['project_code'])->first() : null;
            if (! $office) {
                continue;
            }

            $ppmp = PPMP::updateOrCreate(
                [
                    'office_id' => $office->id,
                    'project_code_id' => $projectCode?->id,
                    'fiscal_year' => $ppmpData['fiscal_year'],
                ],
                [
                    'is_addendum' => $ppmpData['is_addendum'],
                    'remarks' => $ppmpData['remarks'],
                    'xlsx_path' => $ppmpData['xlsx_path'],
                    'budget_notices' => $ppmpData['budget_notices'],
                ]
            );

            $ppmp->categories()->delete();

            foreach ($ppmpData['categories'] as $catData) {
                $account = Account::where('code', $catData['account_code'])->first();

                $category = PPMPCategory::create([
                    'ppmp_id' => $ppmp->id,
                    'account_id' => $account?->id,
                    'estimated_budget' => $catData['estimated_budget'],
                    'remaining_budget' => $catData['remaining_budget'],
                ]);

                foreach ($catData['items'] as $itemData) {
                    $item = PPMPItem::create([
                        'ppmp_category_id' => $category->id,
                        'name' => $itemData['name'],
                        'quantity' => $itemData['quantity'],
                        'unit' => $itemData['unit'],
                        'estimated_budget' => $itemData['estimated_budget'],
                        'remaining_budget' => $itemData['remaining_budget'],
                        'mode_of_procurement' => $itemData['mode_of_procurement'],
                    ]);

                    foreach ($itemData['months'] as $monthData) {
                        PPMPItemMonth::create([
                            'ppmp_item_id' => $item->id,
                            'month' => $monthData['month'],
                            'planned_quantity' => $monthData['planned_quantity'],
                        ]);
                    }
                }
            }
        }

        // ── 4. EMANATINGS ────────────────────────────────────────────
        foreach ($data['emanatings'] as $emanatingData) {
            $fund = null;
            if ($emanatingData['fund_office_code']) {
                $office = Office::where('code', $emanatingData['fund_office_code'])->first();
                $pCode = $emanatingData['fund_project_code'] ? ProjectCode::where('code', $emanatingData['fund_project_code'])->first() : null;
                $fund = Fund::where('office_id', $office?->id)
                    ->where('project_code_id', $pCode?->id)
                    ->where('name', $emanatingData['fund_name'])
                    ->first();
            }

            if (! $fund) {
                continue;
            }

            $ppmp = null;
            if ($emanatingData['ppmp_office_code']) {
                $off = Office::where('code', $emanatingData['ppmp_office_code'])->first();
                $pc = $emanatingData['ppmp_project_code'] ? ProjectCode::where('code', $emanatingData['ppmp_project_code'])->first() : null;
                $ppmp = PPMP::where('office_id', $off?->id)
                    ->where('project_code_id', $pc?->id)
                    ->where('fiscal_year', $emanatingData['ppmp_fiscal_year'])
                    ->first();
            }

            $account = $emanatingData['account_code'] ? Account::where('code', $emanatingData['account_code'])->first() : null;
            $ppmpCategory = null;
            if ($emanatingData['ppmp_category_account_code'] && $ppmp) {
                $catAccount = Account::where('code', $emanatingData['ppmp_category_account_code'])->first();
                $ppmpCategory = PPMPCategory::where('ppmp_id', $ppmp->id)
                    ->where('account_id', $catAccount?->id)
                    ->first();
            }

            $project = $fund->project;

            $emanating = Emanating::create([
                'emanating_no' => $emanatingData['emanating_no'],
                'fund_id' => $fund->id,
                'ppmp_id' => $ppmp?->id,
                'project_id' => $project?->id,
                'account_id' => $account?->id,
                'ppmp_category_id' => $ppmpCategory?->id,
                'charged_to_code' => $emanatingData['charged_to_code'],
                'pr_no' => $emanatingData['pr_no'],
                'fiscal_year' => $emanatingData['fiscal_year'],
                'quarter' => $emanatingData['quarter'],
                'month' => $emanatingData['month'],
                'is_addendum' => $emanatingData['is_addendum'],
                'remarks' => $emanatingData['remarks'],
                'reimbursement' => $emanatingData['reimbursement'],
                'xlsx_path' => $emanatingData['xlsx_path'],
                'requesting_officer_name' => $emanatingData['requesting_officer_name'],
                'requesting_officer_title' => $emanatingData['requesting_officer_title'],
                'items_match_ppmp' => $emanatingData['items_match_ppmp'],
                'is_canvassed' => $emanatingData['is_canvassed'],
                'is_approved' => $emanatingData['is_approved'],
                'approved_at' => $emanatingData['approved_at'],
                'rejection_reason' => $emanatingData['rejection_reason'],
                'status' => $emanatingData['status'],
            ]);

            foreach ($emanatingData['items'] as $eiData) {
                $ppmpItem = null;
                if ($eiData['ppmp_item_name'] && $ppmpCategory) {
                    $ppmpItem = PPMPItem::where('ppmp_category_id', $ppmpCategory->id)
                        ->where('name', $eiData['ppmp_item_name'])
                        ->first();
                }

                EmanatingItem::create([
                    'emanating_id' => $emanating->id,
                    'ppmp_item_id' => $ppmpItem?->id,
                    'name' => $eiData['name'],
                    'quantity' => $eiData['quantity'],
                    'unit' => $eiData['unit'],
                    'total_price' => $eiData['total_price'],
                ]);
            }
        }
    }
}
