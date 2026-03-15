<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Fund;
use App\Models\Office;
use App\Models\Role;
use App\Models\User;
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

        if (Fund::query()->doesntExist()) {
            Fund::factory()->create();
        }
    }
}
