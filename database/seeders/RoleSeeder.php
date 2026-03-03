<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => RoleType::SUPERADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::BAC_RESO_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::BUDGETING_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::CANVASSING_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::DOCUMENT_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::PR_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );

        Role::firstOrCreate(
            ['name' => RoleType::QUOTATION_ADMIN->value],
            ['is_system_role' => true, 'office_id' => null]
        );
    }
}
