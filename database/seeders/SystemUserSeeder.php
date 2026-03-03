<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemUserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = Role::where('name', RoleType::SUPERADMIN->value)->firstOrFail();
        $bacResoRole = Role::where('name', RoleType::BAC_RESO_ADMIN->value)->firstOrFail();
        $budgetingAdminRole = Role::where('name', RoleType::BUDGETING_ADMIN->value)->firstOrFail();
        $canvassingAdminRole = Role::where('name', RoleType::CANVASSING_ADMIN->value)->firstOrFail();
        $documentAdminRole = Role::where('name', RoleType::DOCUMENT_ADMIN->value)->firstOrFail();
        $prAdminRole = Role::where('name', RoleType::PR_ADMIN->value)->firstOrFail();
        $quotationAdminRole = Role::where('name', RoleType::QUOTATION_ADMIN->value)->firstOrFail();

        User::updateOrCreate([
            'email' => 'superadmin@procurex.com',
        ], [
            'name' => 'Super Admin',
            'role_id' => $superadminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'bacreso@procurex.com',
        ], [
            'name' => 'BAC Reso Admin',
            'role_id' => $bacResoRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'budgeting@procurex.com',
        ], [
            'name' => 'Budgeting Admin',
            'role_id' => $budgetingAdminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'canvassing@procurex.com',
        ], [
            'name' => 'Canvassing Admin',
            'role_id' => $canvassingAdminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'document@procurex.com',
        ], [
            'name' => 'Document Admin',
            'role_id' => $documentAdminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'pradmin@procurex.com',
        ], [
            'name' => 'PR Admin',
            'role_id' => $prAdminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);

        User::updateOrCreate([
            'email' => 'quotation@procurex.com',
        ], [
            'name' => 'Quotation Admin',
            'role_id' => $quotationAdminRole->id,
            'office_id' => null,
            'password' => 'password',
        ]);
    }
}
