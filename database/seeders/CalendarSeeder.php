<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Calendar;
use Illuminate\Database\Seeder;

class CalendarSeeder extends Seeder
{
    public function run(): void
    {
        $holidaySeedData = [
            ['date' => '2026-01-01', 'type' => 'holiday', 'name' => 'New Year\'s Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-02-25', 'type' => 'holiday', 'name' => 'EDSA People Power Revolution Anniversary', 'remarks' => 'Special non-working holiday'],
            ['date' => '2026-04-09', 'type' => 'holiday', 'name' => 'Araw ng Kagitingan', 'remarks' => 'Regular holiday'],
            ['date' => '2026-05-01', 'type' => 'holiday', 'name' => 'Labor Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-06-12', 'type' => 'holiday', 'name' => 'Independence Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-08-31', 'type' => 'holiday', 'name' => 'National Heroes Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-11-30', 'type' => 'holiday', 'name' => 'Bonifacio Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-12-25', 'type' => 'holiday', 'name' => 'Christmas Day', 'remarks' => 'Regular holiday'],
            ['date' => '2026-12-30', 'type' => 'holiday', 'name' => 'Rizal Day', 'remarks' => 'Regular holiday'],
        ];

        foreach ($holidaySeedData as $holidayData) {
            Calendar::updateOrCreate(
                ['date' => $holidayData['date']],
                [
                    'type' => $holidayData['type'],
                    'name' => $holidayData['name'],
                    'remarks' => $holidayData['remarks'],
                ]
            );
        }
    }
}
