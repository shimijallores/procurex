<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\Fund;
use App\Models\Office;
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
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@procurex.com',
        ]);

        User::factory()->create([
            'name' => 'Veterenary Admin',
            'office_id' => Office::firstOrCreate(
                ['name' => 'Provincial Veterinary Office'],
                ['code' => 'PVO-001']
            )->id,
            'email' => 'veterenaryadmin@procurex.com',
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
    }
}
