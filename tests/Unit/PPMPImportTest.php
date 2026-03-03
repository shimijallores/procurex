<?php

declare(strict_types=1);

use App\Imports\PPMPImport;
use App\Models\Account;
use App\Models\Office;
use App\Models\PPMP;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('imports ppmp rows with budget from the correct xlsx column', function (): void {
    $office = Office::factory()->create();

    $ppmp = PPMP::create([
        'office_id' => $office->id,
        'fiscal_year' => 2026,
        'is_addendum' => false,
    ]);

    $account = Account::create([
        'main_category' => 'Maintenance and Other Operating Expenses',
        'subcategory' => null,
        'code' => '5-02-01-010',
        'name' => 'Office Supplies',
    ]);

    $import = new PPMPImport($ppmp);

    expect($import->startRow())->toBe(8);

    $rows = new Collection([
        [
            0 => '50201010',
            1 => '',
            2 => 'Office Supplies',
            3 => '',
            4 => '',
            5 => '',
            6 => '',
            7 => '12000.00',
            8 => 'small value',
        ],
        [
            0 => '',
            1 => '',
            2 => 'Bond Paper A4',
            3 => '',
            4 => '10',
            5 => 'ream',
            6 => '500.00',
            7 => '5000.00',
            8 => 'small value',
            9 => 'x',
            10 => '3',
            11 => '',
            12 => '',
            13 => '2',
            14 => '',
            15 => '',
            16 => '',
            17 => '',
            18 => '',
            19 => '',
            20 => '',
        ],
    ]);

    $import->collection($rows);

    $this->assertDatabaseHas('ppmp_categories', [
        'ppmp_id' => $ppmp->id,
        'account_id' => $account->id,
        'estimated_budget' => '12000.00',
    ]);

    $this->assertDatabaseHas('ppmp_items', [
        'name' => 'Bond Paper A4',
        'quantity' => 10,
        'unit' => 'ream',
        'estimated_budget' => '5000.00',
        'mode_of_procurement' => 'small value',
    ]);

    $ppmpItemId = DB::table('ppmp_items')->where('name', 'Bond Paper A4')->value('id');

    expect($ppmpItemId)->not()->toBeNull();

    $this->assertDatabaseHas('ppmp_item_months', [
        'ppmp_item_id' => $ppmpItemId,
        'month' => 2,
        'planned_quantity' => 3,
    ]);

    $this->assertDatabaseHas('ppmp_item_months', [
        'ppmp_item_id' => $ppmpItemId,
        'month' => 5,
        'planned_quantity' => 2,
    ]);

    $this->assertDatabaseMissing('ppmp_item_months', [
        'ppmp_item_id' => $ppmpItemId,
        'month' => 1,
    ]);
});
