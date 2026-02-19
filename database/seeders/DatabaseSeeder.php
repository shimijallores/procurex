<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Calendar;
use App\Models\Fund;
use App\Models\MasterListCategory;
use App\Models\MasterListItem;
use App\Models\Office;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create system roles first
        $superadminRole = Role::firstOrCreate(
            ['name' => RoleType::SUPERADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        $bacResoRole = Role::firstOrCreate(
            ['name' => RoleType::BAC_RESO_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        $budgetingAdminRole = Role::firstOrCreate(
            ['name' => RoleType::BUDGETING_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        $canvassingAdminRole = Role::firstOrCreate(
            ['name' => RoleType::CANVASSING_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        // Create system users
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@procurex.com',
            'role_id' => $superadminRole->id,
            'office_id' => null,
        ]);

        User::factory()->create([
            'name' => 'BAC Reso Admin',
            'email' => 'bacreso@procurex.com',
            'role_id' => $bacResoRole->id,
            'office_id' => null,
        ]);

        User::factory()->create([
            'name' => 'Budgeting Admin',
            'email' => 'budgeting@procurex.com',
            'role_id' => $budgetingAdminRole->id,
            'office_id' => null,
        ]);

        User::factory()->create([
            'name' => 'Canvassing Admin',
            'email' => 'canvassing@procurex.com',
            'role_id' => $canvassingAdminRole->id,
            'office_id' => null,
        ]);

        // Create an example office role
        $veterinaryOffice = Office::firstOrCreate(
            ['name' => 'Provincial Veterinary Office'],
            ['code' => 'PVO-001']
        );

        $veterinaryRole = Role::firstOrCreate(
            ['name' => 'Veterinary Staff'],
            ['is_system_role' => false, 'office_id' => $veterinaryOffice->id]
        );

        User::factory()->create([
            'name' => 'Veterinary Admin',
            'email' => 'veterenaryadmin@procurex.com',
            'role_id' => $veterinaryRole->id,
            'office_id' => $veterinaryOffice->id,
        ]);

        Fund::factory()->create();

        // Seed Philippine holidays for 2026
        Calendar::create([
            'date' => '2026-01-01',
            'type' => 'holiday',
            'name' => 'New Year\'s Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-02-25',
            'type' => 'holiday',
            'name' => 'EDSA People Power Revolution Anniversary',
            'remarks' => 'Special non-working holiday',
        ]);

        Calendar::create([
            'date' => '2026-04-09',
            'type' => 'holiday',
            'name' => 'Araw ng Kagitingan',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-05-01',
            'type' => 'holiday',
            'name' => 'Labor Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-06-12',
            'type' => 'holiday',
            'name' => 'Independence Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-08-31',
            'type' => 'holiday',
            'name' => 'National Heroes Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-11-30',
            'type' => 'holiday',
            'name' => 'Bonifacio Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-12-25',
            'type' => 'holiday',
            'name' => 'Christmas Day',
            'remarks' => 'Regular holiday',
        ]);

        Calendar::create([
            'date' => '2026-12-30',
            'type' => 'holiday',
            'name' => 'Rizal Day',
            'remarks' => 'Regular holiday',
        ]);

        // ----------------------------------------------------------------
        // Suppliers
        // ----------------------------------------------------------------
        $merryMerchants = Supplier::firstOrCreate(
            ['name' => 'MERRY MERCHANTS TRADING AND GENERAL MERCHANDISE'],
            [
                'contact_person' => 'Maria Santos',
                'contact_number' => '0917-123-4567',
                'email' => 'merry.merchants@email.com',
                'address' => '123 Rizal Avenue, Batangas City, Batangas',
                'tin' => '123-456-789-000',
                'remarks' => 'General merchandise and office supplies',
                'is_active' => true,
            ]
        );

        $luckyBk = Supplier::firstOrCreate(
            ['name' => 'LUCKY-BK JANITORIAL OFFICE SUPPLIES TRADING'],
            [
                'contact_person' => 'Jose Cruz',
                'contact_number' => '0918-987-6543',
                'email' => 'luckybk.trading@gmail.com',
                'address' => '456 Mabini Street, Lipa City, Batangas',
                'tin' => '234-567-890-000',
                'remarks' => 'Janitorial and office supplies trading',
                'is_active' => true,
            ]
        );

        $fyker = Supplier::firstOrCreate(
            ['name' => 'FYKER ENTERPRISES'],
            [
                'contact_person' => 'Fernando Yap',
                'contact_number' => '0919-555-7890',
                'email' => 'fyker.enterprises@yahoo.com',
                'address' => '789 Capitol Drive, Batangas City, Batangas',
                'tin' => '345-678-901-000',
                'remarks' => 'Office furniture, IT equipment, and fixtures',
                'is_active' => true,
            ]
        );

        // ----------------------------------------------------------------
        // Master List Categories
        // ----------------------------------------------------------------
        $officeSupplies = MasterListCategory::firstOrCreate(
            ['name' => 'Office Supplies'],
            ['description' => 'Pens, paper, folders, and other office consumables', 'is_active' => true]
        );

        $janitorial = MasterListCategory::firstOrCreate(
            ['name' => 'Janitorial Supplies'],
            ['description' => 'Cleaning materials and sanitation products', 'is_active' => true]
        );

        $itEquipment = MasterListCategory::firstOrCreate(
            ['name' => 'IT Equipment & Accessories'],
            ['description' => 'Computers, peripherals, ink, and accessories', 'is_active' => true]
        );

        $furnitureFixtures = MasterListCategory::firstOrCreate(
            ['name' => 'Furniture & Fixtures'],
            ['description' => 'Office furniture, chairs, tables, and fixtures', 'is_active' => true]
        );

        $trainingMaterials = MasterListCategory::firstOrCreate(
            ['name' => 'Training & Seminar Materials'],
            ['description' => 'Materials used for trainings, seminars, and workshops', 'is_active' => true]
        );

        $catering = MasterListCategory::firstOrCreate(
            ['name' => 'Catering Services'],
            ['description' => 'Food, meals, snacks, and beverage services', 'is_active' => true]
        );

        $apparel = MasterListCategory::firstOrCreate(
            ['name' => 'Apparel'],
            ['description' => 'Uniforms, t-shirts, and clothing items', 'is_active' => true]
        );

        $services = MasterListCategory::firstOrCreate(
            ['name' => 'Services'],
            ['description' => 'Professional services, honorariums, and consultation fees', 'is_active' => true]
        );

        // ----------------------------------------------------------------
        // Master List Items — Office Supplies
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $officeSupplies->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Ballpen, Black, 0.5mm', 'unit' => 'box'],
            ['default_unit_price' => 120.00, 'is_phased_out' => false, 'search_key' => 'ballpen black pen writing']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $officeSupplies->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Short Bond Paper, 70gsm', 'unit' => 'ream'],
            ['default_unit_price' => 215.00, 'is_phased_out' => false, 'search_key' => 'bond paper short 70gsm printing']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $officeSupplies->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Long Bond Paper, 70gsm', 'unit' => 'ream'],
            ['default_unit_price' => 235.00, 'is_phased_out' => false, 'search_key' => 'bond paper long 70gsm printing']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $officeSupplies->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Stapler, Standard Size', 'unit' => 'piece'],
            ['default_unit_price' => 185.00, 'is_phased_out' => false, 'search_key' => 'stapler standard size']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $officeSupplies->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Scotch Tape, 1 inch', 'unit' => 'roll'],
            ['default_unit_price' => 25.00, 'is_phased_out' => false, 'search_key' => 'scotch tape clear adhesive']
        );

        // ----------------------------------------------------------------
        // Master List Items — Janitorial Supplies
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $janitorial->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Dishwashing Liquid, 1L', 'unit' => 'bottle'],
            ['default_unit_price' => 95.00, 'is_phased_out' => false, 'search_key' => 'dishwashing liquid soap']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $janitorial->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Bleach / Zonrox, 1L', 'unit' => 'bottle'],
            ['default_unit_price' => 55.00, 'is_phased_out' => false, 'search_key' => 'bleach zonrox disinfectant']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $janitorial->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Mop with Wringer Bucket Set', 'unit' => 'set'],
            ['default_unit_price' => 750.00, 'is_phased_out' => false, 'search_key' => 'mop bucket wringer floor cleaning']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $janitorial->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Garbage Bag, Large (10pcs/pack)', 'unit' => 'pack'],
            ['default_unit_price' => 65.00, 'is_phased_out' => false, 'search_key' => 'garbage bag large plastic trash']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $janitorial->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Alcohol, Isopropyl 70%, 500ml', 'unit' => 'bottle'],
            ['default_unit_price' => 85.00, 'is_phased_out' => false, 'search_key' => 'alcohol isopropyl 70 percent sanitizer']
        );

        // ----------------------------------------------------------------
        // Master List Items — IT Equipment & Accessories
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $itEquipment->id, 'supplier_id' => $fyker->id, 'item_name' => 'USB Flash Drive, 32GB', 'unit' => 'piece'],
            ['default_unit_price' => 350.00, 'is_phased_out' => false, 'search_key' => 'usb flash drive 32gb storage']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $itEquipment->id, 'supplier_id' => $fyker->id, 'item_name' => 'Ink Cartridge, Black (HP Compatible)', 'unit' => 'piece'],
            ['default_unit_price' => 450.00, 'is_phased_out' => false, 'search_key' => 'ink cartridge black hp compatible printer']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $itEquipment->id, 'supplier_id' => $fyker->id, 'item_name' => 'Extension Cord, 3-gang, 3m', 'unit' => 'piece'],
            ['default_unit_price' => 280.00, 'is_phased_out' => false, 'search_key' => 'extension cord 3 gang electrical']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $itEquipment->id, 'supplier_id' => $fyker->id, 'item_name' => 'HDMI Cable, 1.5m', 'unit' => 'piece'],
            ['default_unit_price' => 195.00, 'is_phased_out' => false, 'search_key' => 'hdmi cable monitor display']
        );

        // ----------------------------------------------------------------
        // Master List Items — Furniture & Fixtures
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $furnitureFixtures->id, 'supplier_id' => $fyker->id, 'item_name' => 'Monobloc Chair, White', 'unit' => 'piece'],
            ['default_unit_price' => 380.00, 'is_phased_out' => false, 'search_key' => 'monobloc chair white plastic']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $furnitureFixtures->id, 'supplier_id' => $fyker->id, 'item_name' => 'Folding Table, 6ft', 'unit' => 'piece'],
            ['default_unit_price' => 1850.00, 'is_phased_out' => false, 'search_key' => 'folding table 6 feet rectangular']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $furnitureFixtures->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Whiteboard, 4ft x 3ft', 'unit' => 'piece'],
            ['default_unit_price' => 1200.00, 'is_phased_out' => false, 'search_key' => 'whiteboard magnetic dry erase board']
        );

        // ----------------------------------------------------------------
        // Master List Items — Training & Seminar Materials
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $trainingMaterials->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Manila Paper, A0', 'unit' => 'sheet'],
            ['default_unit_price' => 12.00, 'is_phased_out' => false, 'search_key' => 'manila paper a0 newsprint']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $trainingMaterials->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Permanent Marker, Black', 'unit' => 'piece'],
            ['default_unit_price' => 55.00, 'is_phased_out' => false, 'search_key' => 'permanent marker black sharpie']
        );
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $trainingMaterials->id, 'supplier_id' => $luckyBk->id, 'item_name' => 'Double-Sided Tape, 1 inch', 'unit' => 'roll'],
            ['default_unit_price' => 45.00, 'is_phased_out' => false, 'search_key' => 'double sided tape adhesive mounting']
        );

        // ----------------------------------------------------------------
        // Master List Items — Training Kit
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $trainingMaterials->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Training Kit', 'unit' => 'set'],
            ['default_unit_price' => 2500.00, 'is_phased_out' => false, 'search_key' => 'training kit seminar materials']
        );

        // ----------------------------------------------------------------
        // Master List Items — Catering Services
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $catering->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Meals & Snacks (AM Snacks & Lunch)', 'unit' => 'person'],
            ['default_unit_price' => 250.00, 'is_phased_out' => false, 'search_key' => 'meals snacks morning snacks lunch catering']
        );

        // ----------------------------------------------------------------
        // Master List Items — Apparel
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $apparel->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'T-shirt with collar and print premium dry fit', 'unit' => 'piece'],
            ['default_unit_price' => 450.00, 'is_phased_out' => false, 'search_key' => 'tshirt collar print premium dry fit polo']
        );

        // ----------------------------------------------------------------
        // Master List Items — Services
        // ----------------------------------------------------------------
        MasterListItem::firstOrCreate(
            ['master_list_category_id' => $services->id, 'supplier_id' => $merryMerchants->id, 'item_name' => 'Honorarium for Resource Person', 'unit' => 'person'],
            ['default_unit_price' => 5000.00, 'is_phased_out' => false, 'search_key' => 'honorarium resource person speaker consultant']
        );
    }
}
