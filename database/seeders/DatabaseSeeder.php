<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SystemUserSeeder::class,
            OfficeSeeder::class,
            ProjectCodeSeeder::class,
            AccountSeeder::class,
            SampleDataSeeder::class,
            CalendarSeeder::class,
            SupplierSeeder::class,
            MasterListSeeder::class,
        ]);
    }
}
