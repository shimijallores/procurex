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
        $rolesByName = Role::query()
            ->whereIn('name', RoleType::systemRoles())
            ->get()
            ->keyBy('name');

        $seedUsers = [
            [
                'email' => 'superadmin@procurex.com',
                'name' => 'Super Admin',
                'role_names' => [RoleType::SUPERADMIN->value],
            ],

            // Checking
            [
                'email' => 'keith@procurex.com',
                'name' => 'Keith',
                'role_names' => [RoleType::CHECKING_ADMIN->value],
            ],
            [
                'email' => 'onin@procurex.com',
                'name' => 'Onin',
                'role_names' => [RoleType::CHECKING_ADMIN->value],
            ],
            [
                'email' => 'michelle@procurex.com',
                'name' => 'Michelle',
                'role_names' => [RoleType::CHECKING_ADMIN->value],
            ],
            [
                'email' => 'joshua@procurex.com',
                'name' => 'Joshua',
                'role_names' => [RoleType::CHECKING_ADMIN->value],
            ],

            // Canvassing
            [
                'email' => 'tess@procurex.com',
                'name' => 'Tess',
                'role_names' => [RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'kim@procurex.com',
                'name' => 'Kim',
                'role_names' => [RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'romnick@procurex.com',
                'name' => 'Romnick',
                'role_names' => [RoleType::CANVASSING_ADMIN->value],
            ],

            // PR
            [
                'email' => 'nica@procurex.com',
                'name' => 'Nica',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],
            [
                'email' => 'kristel@procurex.com',
                'name' => 'Kristel',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],
            [
                'email' => 'yedda@procurex.com',
                'name' => 'Yedda',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],
            [
                'email' => 'jr@procurex.com',
                'name' => 'Jr',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],
            [
                'email' => 'wilma@procurex.com',
                'name' => 'Wilma',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],
            [
                'email' => 'shiela@procurex.com',
                'name' => 'Shiela',
                'role_names' => [RoleType::PR_ADMIN->value],
            ],

            // RFQ
            [
                'email' => 'pabs@procurex.com',
                'name' => 'Pabs',
                'role_names' => [RoleType::RFQ_ADMIN->value],
            ],

            // Abstract
            [
                'email' => 'ivy@procurex.com',
                'name' => 'Ivy',
                'role_names' => [RoleType::ABSTRACT_ADMIN->value],
            ],

            // Resolution and PR
            [
                'email' => 'ariel@procurex.com',
                'name' => 'Ariel',
                'role_names' => [RoleType::RESOLUTION_ADMIN->value, RoleType::PR_ADMIN->value],
            ],

            // NOA and PO
            [
                'email' => 'rizza@procurex.com',
                'name' => 'Rizza',
                'role_names' => [RoleType::NOA_ADMIN->value, RoleType::PO_ADMIN->value],
            ],

            // Inspection and Canvassing
            [
                'email' => 'analyn@procurex.com',
                'name' => 'Analyn',
                'role_names' => [RoleType::INSPECTION_ADMIN->value, RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'renato@procurex.com',
                'name' => 'Renato',
                'role_names' => [RoleType::INSPECTION_ADMIN->value, RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'ram@procurex.com',
                'name' => 'Ram',
                'role_names' => [RoleType::INSPECTION_ADMIN->value, RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'gino@procurex.com',
                'name' => 'Gino',
                'role_names' => [RoleType::INSPECTION_ADMIN->value, RoleType::CANVASSING_ADMIN->value],
            ],
            [
                'email' => 'jasmin@procurex.com',
                'name' => 'Jasmin',
                'role_names' => [RoleType::INSPECTION_ADMIN->value, RoleType::CANVASSING_ADMIN->value],
            ],
        ];

        foreach ($seedUsers as $seedUser) {
            $systemUser = User::updateOrCreate([
                'email' => $seedUser['email'],
            ], [
                'name' => $seedUser['name'],
                'office_id' => null,
                'password' => 'password',
            ]);

            $roleIds = collect($seedUser['role_names'])
                ->map(fn(string $roleName): int => $rolesByName->get($roleName)->id)
                ->all();

            $systemUser->roles()->sync($roleIds);
        }
    }
}
