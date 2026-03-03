<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $officeSeedData = [
            ['code' => '1011', 'acronym' => 'OPG', 'name' => 'Office of the Provincial Governor'],
            ['code' => '1012', 'acronym' => null, 'name' => 'Provincial Jail'],
            ['code' => '1013', 'acronym' => 'PPOSS', 'name' => 'Provincial Peace and Order and Safety Services'],
            ['code' => '1015', 'acronym' => 'PDRRMO', 'name' => 'Provincial Disaster Risk Reduction and Management Office'],
            ['code' => '1016', 'acronym' => 'OVG', 'name' => 'Office of the Vice Governor'],
            ['code' => '1021', 'acronym' => 'SP', 'name' => 'Sangguniang Panlalawigan'],
            ['code' => '1022', 'acronym' => null, 'name' => 'Provincial Library'],
            ['code' => '1031', 'acronym' => null, 'name' => 'Office of the Provincial Administrator'],
            ['code' => '1032', 'acronym' => 'HRMO', 'name' => 'Human Resource Management Office'],
            ['code' => '1041', 'acronym' => 'OPPDC', 'name' => 'Office of the Provincial Planning and Development Coordinator'],
            ['code' => '1061', 'acronym' => 'GSO', 'name' => 'General Services Office'],
            ['code' => '1071', 'acronym' => null, 'name' => 'Provincial Budget Office'],
            ['code' => '1081', 'acronym' => null, 'name' => 'Provincial Accounting Office'],
            ['code' => '1091', 'acronym' => null, 'name' => 'Provincial Treasurer\'s Office'],
            ['code' => '1101', 'acronym' => null, 'name' => 'Provincial Assessor\'s Office'],
            ['code' => '1111', 'acronym' => 'COA', 'name' => 'Commission on Audit'],
            ['code' => '1121', 'acronym' => null, 'name' => 'Internal Audit Service Office'],
            ['code' => '1123', 'acronym' => null, 'name' => 'Provincial Tourism Office'],
            ['code' => '1124', 'acronym' => 'PIO', 'name' => 'Public Information Office'],
            ['code' => '1131', 'acronym' => null, 'name' => 'Provincial Legal Office'],
            ['code' => '1141', 'acronym' => null, 'name' => 'Office of the Provincial Prosecutor'],
            ['code' => '1151', 'acronym' => 'RTC', 'name' => 'Regional Trial Court'],
            ['code' => '1152', 'acronym' => 'MTC', 'name' => 'Municipal Trial Court'],
            ['code' => '1153', 'acronym' => 'PAO', 'name' => 'Public Attorney\'s Office'],
            ['code' => '1161', 'acronym' => null, 'name' => 'Register of Deeds Office'],
            ['code' => '4411', 'acronym' => 'PHO', 'name' => 'Provincial Health Office'],
            ['code' => '6541', 'acronym' => 'PACD', 'name' => 'Provincial Administrator for Community Development'],
            ['code' => '7611', 'acronym' => 'PSWDO', 'name' => 'Provincial Social Welfare and Development Office'],
            ['code' => '8711', 'acronym' => null, 'name' => 'Provincial Agriculture Office'],
            ['code' => '8721', 'acronym' => null, 'name' => 'Provincial Veterinary Office'],
            ['code' => '8731', 'acronym' => 'ENRO', 'name' => 'Environment and Natural Resources Office'],
            ['code' => '8751', 'acronym' => 'PEO', 'name' => 'Provincial Engineering Office'],
            ['code' => '8761', 'acronym' => 'PCLEDO', 'name' => 'Provincial Cooperative, Livelihood and Entrepreneurial Development Office'],
            ['code' => '3311', 'acronym' => 'PSB', 'name' => 'Provincial School Board'],
        ];

        foreach ($officeSeedData as $officeData) {
            Office::updateOrCreate(
                ['code' => $officeData['code']],
                ['name' => $officeData['name'], 'acronym' => $officeData['acronym']]
            );
        }
    }
}
