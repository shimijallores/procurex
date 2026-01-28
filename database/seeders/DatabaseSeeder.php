<?php

namespace Database\Seeders;

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
            'office_id' => Office::firstOrCreate(['name' => 'Provincial Veterinary Office'])->id,
            'email' => 'veterenaryadmin@procurex.com',
        ]);
    }
}
