<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::firstOrCreate(
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

        Supplier::firstOrCreate(
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

        Supplier::firstOrCreate(
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
    }
}
