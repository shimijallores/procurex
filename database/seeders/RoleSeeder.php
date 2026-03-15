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
        foreach (RoleType::cases() as $systemRole) {
            Role::updateOrCreate(
                ['name' => $systemRole->value],
                ['is_system_role' => true, 'office_id' => null]
            );
        }

        Role::query()
            ->where('is_system_role', true)
            ->whereNotIn('name', RoleType::systemRoles())
            ->delete();
    }
}
