<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Office;
use App\Models\ProjectCode;
use Illuminate\Database\Seeder;

class ProjectCodeSeeder extends Seeder
{
    public function run(): void
    {
        $projectCodeSeedData = [
            ['office_code' => '1011', 'code' => '90102', 'name' => 'Public Assistance Services'],
            ['office_code' => '1011', 'code' => '90103', 'name' => 'Local Board of Assessment Appeals'],
            ['office_code' => '1011', 'code' => '90104', 'name' => 'Loan Amortization to LBP (Land Bank of the Philippines)'],
            ['office_code' => '1011', 'code' => '90105', 'name' => 'Loan Amortization to DBP (Development Bank of the Philippines)'],
            ['office_code' => '1011', 'code' => '90107', 'name' => 'Emergency Employment Services (Job Order)'],
            ['office_code' => '1011', 'code' => '90109', 'name' => 'Educational Assistance Program'],
            ['office_code' => '1011', 'code' => '90110', 'name' => 'Executive Management Services'],
            ['office_code' => '1011', 'code' => '90111', 'name' => 'Information, Communication and Technology Management Services'],
            ['office_code' => '1013', 'code' => '90301', 'name' => 'Peace and Order Services'],
            ['office_code' => '1015', 'code' => '91801', 'name' => '5% Provincial Disaster Risk Reduction Management Fund'],
            ['office_code' => '1031', 'code' => '90601', 'name' => 'Urban Development'],
            ['office_code' => '1032', 'code' => '90701', 'name' => "Human Resource Development and Employee's Advancement Services"],
            ['office_code' => '1041', 'code' => '90810', 'name' => 'Planning and Development Services'],
            ['office_code' => '1061', 'code' => '90901', 'name' => 'Maintenance of Parks, Buildings and Grounds'],
            ['office_code' => '1071', 'code' => '91001', 'name' => 'Public Financial Management Improvement Services'],
            ['office_code' => '1081', 'code' => '91101', 'name' => 'Enhancement of E-NGAS'],
            ['office_code' => '1091', 'code' => '91201', 'name' => 'Real Property Tax Administration Services'],
            ['office_code' => '1091', 'code' => '91202', 'name' => 'Local Revenue Enhancement Management Services'],
            ['office_code' => '1101', 'code' => '91301', 'name' => 'Computerization'],
            ['office_code' => '1101', 'code' => '91302', 'name' => 'General Revision'],
            ['office_code' => '1101', 'code' => '91303', 'name' => 'Tax Mapping'],
            ['office_code' => '1123', 'code' => '92201', 'name' => 'Tourism Development Services'],
            ['office_code' => '1123', 'code' => '92202', 'name' => 'Batangas Culture and Arts Council'],
            ['office_code' => '1124', 'code' => '91501', 'name' => 'Public Information and Dissemination Services'],
            ['office_code' => '1131', 'code' => '91601', 'name' => 'Legal Assistance Services'],
            ['office_code' => '4411', 'code' => '91901', 'name' => 'Health Services and Facilities Enhancement Services (20% Development Fund)'],
            ['office_code' => '4411', 'code' => '91902', 'name' => 'Health Services and Facilities Enhancement Services'],
            ['office_code' => '4411', 'code' => '91903', 'name' => 'Nutrition Development Program'],
            ['office_code' => '4411', 'code' => '91904', 'name' => 'Integrated Service Volunteer'],
            ['office_code' => '4411', 'code' => '91905', 'name' => 'Tuberculosis Elimination Services'],
            ['office_code' => '6541', 'code' => '92001', 'name' => 'Provincial Service Incentive'],
            ['office_code' => '6541', 'code' => '92002', 'name' => 'Community Development Services'],
            ['office_code' => '6541', 'code' => '92003', 'name' => 'Youth and Sports Development Services'],
            ['office_code' => '6541', 'code' => '92004', 'name' => 'Public Employment Services'],
            ['office_code' => '6541', 'code' => '92005', 'name' => 'Provincial Anti-Drug Abuse Services'],
            ['office_code' => '7611', 'code' => '92101', 'name' => 'Emergency Assistance Services'],
            ['office_code' => '7611', 'code' => '92102', 'name' => 'Family and Community Welfare Services'],
            ['office_code' => '7611', 'code' => '92103', 'name' => 'Integrated Social Services'],
            ['office_code' => '7611', 'code' => '92104', 'name' => 'Special Concern Services'],
            ['office_code' => '8711', 'code' => '92301', 'name' => 'Agricultural and Aquaculture Production Intensification and Diversification'],
            ['office_code' => '8721', 'code' => '92401', 'name' => 'Livestock and Poultry Development Services'],
            ['office_code' => '8721', 'code' => '92402', 'name' => 'Livestock Development and Animal Care Services'],
            ['office_code' => '8721', 'code' => '92403', 'name' => 'Rabies Prevention, Control and Eradication Services'],
            ['office_code' => '8721', 'code' => '92404', 'name' => 'Highly Pathogenic Avian Influenza Preparedness, Program Prevention and Control'],
            ['office_code' => '8731', 'code' => '92501', 'name' => 'Environment and Natural Resources Management Services'],
            ['office_code' => '8751', 'code' => '92601', 'name' => 'Infrastructure Projects (20% Development Fund)'],
            ['office_code' => '8761', 'code' => '92701', 'name' => 'Cooperative, Livelihood and Entrepreneurial Development Services'],
            ['office_code' => '8761', 'code' => '92702', 'name' => 'Economic Enterprise'],
            ['office_code' => '3311', 'code' => '92801', 'name' => 'Other Supplies'],
            ['office_code' => '3311', 'code' => '92802', 'name' => 'School Supplies'],
            ['office_code' => '3311', 'code' => '92803', 'name' => 'Printing and Binding'],
            ['office_code' => '3311', 'code' => '92804', 'name' => 'Capital Outlay'],
            ['office_code' => '3311', 'code' => '92805', 'name' => 'Batangas Provincial Science High School'],
            ['office_code' => '3311', 'code' => '92806', 'name' => 'Batangas Provincial High School for Culture and Arts'],
            ['office_code' => '3311', 'code' => '92807', 'name' => 'Internet Access and Computer Literacy Program'],
            ['office_code' => '3311', 'code' => '92808', 'name' => 'Provincial Athletic Meet'],
            ['office_code' => '3311', 'code' => '92809', 'name' => 'Regional and National Athletic Competition'],
            ['office_code' => '3311', 'code' => '92810', 'name' => 'Special Children Sports Activities'],
            ['office_code' => '3311', 'code' => '92811', 'name' => 'Division Meet'],
            ['office_code' => '3311', 'code' => '92812', 'name' => 'Repair and Maintenance'],
            ['office_code' => '3311', 'code' => '92813', 'name' => 'Educational Research'],
            ['office_code' => '3311', 'code' => '92814', 'name' => 'Instructional Materials'],
            ['office_code' => '3311', 'code' => '92815', 'name' => 'School Tables and Chairs'],
            ['office_code' => '3311', 'code' => '92816', 'name' => 'K-12 Implementation'],
        ];

        $officesByCode = Office::query()->get()->keyBy('code');

        foreach ($projectCodeSeedData as $projectCodeData) {
            $office = $officesByCode->get($projectCodeData['office_code']);

            if (! $office) {
                continue;
            }

            ProjectCode::updateOrCreate(
                ['code' => $projectCodeData['code']],
                [
                    'office_id' => $office->id,
                    'name' => $projectCodeData['name'],
                ]
            );
        }
    }
}
