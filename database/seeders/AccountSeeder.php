<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accountSeedData = [
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 01 010', 'name' => 'Salaries and Wages – Regular'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 01 020', 'name' => 'Salaries and Wages – Casual/Contractual'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 010', 'name' => 'Personal Economic Relief Allowance (PERA)'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 020', 'name' => 'Representation Allowance (RA)'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 030', 'name' => 'Transportation Allowance (TA)'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 040', 'name' => 'Clothing/Uniform Allowance'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 050', 'name' => 'Subsistence Allowance'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 080', 'name' => 'Productivity Incentive Allowance'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 100', 'name' => 'Honoraria'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 110', 'name' => 'Hazard Pay'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 120', 'name' => 'Longevity Pay'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 130', 'name' => 'Overtime and Night Pay'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 140', 'name' => 'Year End Bonus'],
            ['main_category' => 'Personal Services', 'subcategory' => null, 'code' => '5 01 02 150', 'name' => 'Cash Gift'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Personal Benefits Contributions', 'code' => '5 01 03 010', 'name' => 'Retirement and Life Insurance Premiums'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Personal Benefits Contributions', 'code' => '5 01 03 020', 'name' => 'Pag-IBIG Contributions'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Personal Benefits Contributions', 'code' => '5 01 03 030', 'name' => 'PhilHealth Contributions'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Personal Benefits Contributions', 'code' => '5 01 03 040', 'name' => 'Employees Compensation Insurance Premiums'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Other Personnel Benefits', 'code' => '5 01 04 010', 'name' => 'Pension Benefits'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Other Personnel Benefits', 'code' => '5 01 04 020', 'name' => 'Retirement Gratuity'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Other Personnel Benefits', 'code' => '5 01 04 030', 'name' => 'Terminal Leave Benefits'],
            ['main_category' => 'Personal Services', 'subcategory' => 'Other Personnel Benefits', 'code' => '5 01 04 990', 'name' => 'Other Personnel Benefits'],

            ['main_category' => 'MOOE', 'subcategory' => null, 'code' => '5 02 01 010', 'name' => 'Traveling Expenses – Local'],
            ['main_category' => 'MOOE', 'subcategory' => null, 'code' => '5 02 01 020', 'name' => 'Traveling Expenses – Foreign'],
            ['main_category' => 'MOOE', 'subcategory' => null, 'code' => '5 02 10 030', 'name' => 'Extraordinary and Miscellaneous Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Training and Scholarship Expenses', 'code' => '5 02 02 010', 'name' => 'Training Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Training and Scholarship Expenses', 'code' => '5 02 02 020', 'name' => 'Scholarship Grants/Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 010', 'name' => 'Office Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 020', 'name' => 'Accountable Forms Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 040', 'name' => 'Animal/Zoological Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 050', 'name' => 'Food Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 060', 'name' => 'Welfare Goods Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 070', 'name' => 'Drugs and Medicines Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 080', 'name' => 'Medical, Dental and Laboratory Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 090', 'name' => 'Fuel, Oil and Lubricants Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 100', 'name' => 'Agricultural and Marine Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 110', 'name' => 'Textbooks and Instructional Materials Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 120', 'name' => 'Military, Police and Traffic Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 130', 'name' => 'Chemical and Filtering Supplies Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Supplies and Materials Expenses', 'code' => '5 02 03 990', 'name' => 'Other Supplies and Materials Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Utility Expenses', 'code' => '5 02 04 010', 'name' => 'Water Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Utility Expenses', 'code' => '5 02 04 020', 'name' => 'Electricity Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 05 010', 'name' => 'Postage and Courier Services'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 05 020', 'name' => 'Telephone Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 05 030', 'name' => 'Internet Subscription Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 05 040', 'name' => 'Cable, Satellite, Telegraph and Radio Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 060', 'name' => 'Membership due to contributions'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 06 010', 'name' => 'Awards/Rewards Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 010', 'name' => 'Advertising Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 020', 'name' => 'Printing and Publication Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 050', 'name' => 'Rent Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 030', 'name' => 'Representation Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 040', 'name' => 'Transportation and Delivery Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 070', 'name' => 'Subscription Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 99 990', 'name' => 'Other Maintenance and Operating Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 07 010', 'name' => 'Survey Expenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Communication Expenses', 'code' => '5 02 06 020', 'name' => 'Prizes'],
            ['main_category' => 'MOOE', 'subcategory' => 'Professional Services', 'code' => '5 02 11 030', 'name' => 'Consultancy Services'],
            ['main_category' => 'MOOE', 'subcategory' => 'Professional Services', 'code' => '5 02 12 010', 'name' => 'Environment/Sanitary Services'],
            ['main_category' => 'MOOE', 'subcategory' => 'Professional Services', 'code' => '5 02 11 990', 'name' => 'Other Professional Services'],
            ['main_category' => 'MOOE', 'subcategory' => 'Subsidies and Donations', 'code' => '5 02 99 080', 'name' => 'Donations'],
            ['main_category' => 'MOOE', 'subcategory' => 'Subsidies and Donations', 'code' => '5 02 14 020', 'name' => 'Subsidy to NGAs'],
            ['main_category' => 'MOOE', 'subcategory' => 'Subsidies and Donations', 'code' => '5 02 14 030', 'name' => 'Subsidy to Other Local Government Units'],
            ['main_category' => 'MOOE', 'subcategory' => 'Taxes, Insurance Premiums and Other Fees', 'code' => '5 02 16 010', 'name' => 'Taxes, Duties and Licenses'],
            ['main_category' => 'MOOE', 'subcategory' => 'Taxes, Insurance Premiums and Other Fees', 'code' => '5 02 16 020', 'name' => 'Fidelity Bond Premiums'],
            ['main_category' => 'MOOE', 'subcategory' => 'Taxes, Insurance Premiums and Other Fees', 'code' => '5 02 16 030', 'name' => 'Insurance Expenses'],

            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 020', 'name' => 'Repairs and Maintenance – Land Improvements'],
            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 030', 'name' => 'Repairs and Maintenance – Infrastructure Assets'],
            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 040', 'name' => 'Repairs and Maintenance – Buildings and Other Structures'],
            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 050', 'name' => 'Repairs and Maintenance – Machinery and Equipment'],
            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 060', 'name' => 'Repairs and Maintenance – Transportation Equipment'],
            ['main_category' => 'Repair and Maintenance', 'subcategory' => null, 'code' => '5 02 13 070', 'name' => 'Repairs and Maintenance – Furniture and Fixtures'],

            ['main_category' => 'Capital Outlay', 'subcategory' => 'Furniture and Fixtures', 'code' => '1 07 07 010', 'name' => 'Furniture and Fixtures'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Furniture and Fixtures', 'code' => '1 07 07 020', 'name' => 'Books'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Land and Land Improvements', 'code' => '1 07 01 010', 'name' => 'Land'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Land and Land Improvements', 'code' => '1 07 02 010', 'name' => 'Land Improvements, Aquaculture Structures'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Land and Land Improvements', 'code' => '1 07 02 990', 'name' => 'Other Land Improvements'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Land and Land Improvements', 'code' => '1 07 03 990', 'name' => 'Other Infrastructure Assets'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Buildings', 'code' => '1 07 04 010', 'name' => 'Buildings'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Buildings', 'code' => '1 07 04 020', 'name' => 'School Buildings'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Buildings', 'code' => '1 07 04 030', 'name' => 'Hospitals and Health Centers'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Buildings', 'code' => '1 07 04 990', 'name' => 'Other Structures'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 010', 'name' => 'Machinery'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 020', 'name' => 'Office Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 030', 'name' => 'Information and Communication Technology Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 040', 'name' => 'Agricultural and Forestry Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 050', 'name' => 'Marine and Fishery Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 070', 'name' => 'Communication Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 080', 'name' => 'Construction and Heavy Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 090', 'name' => 'Disaster Response and Rescue Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 100', 'name' => 'Medical Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 110', 'name' => 'Military, Police and Security Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 130', 'name' => 'Sports Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 140', 'name' => 'Technical and Scientific Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 990', 'name' => 'Other Machinery and Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Machineries and Equipment', 'code' => '1 07 05 120', 'name' => 'Printing Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Transportation Equipment', 'code' => '1 07 06 010', 'name' => 'Motor Vehicles'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Transportation Equipment', 'code' => '1 07 06 040', 'name' => 'Watercrafts'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Transportation Equipment', 'code' => '1 07 06 990', 'name' => 'Other Transportation Equipment'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Public Infrastructures', 'code' => '1 07 03 010', 'name' => 'Road Networks'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Public Infrastructures', 'code' => '1 07 03 990', 'name' => 'Other Infrastructure Assets'],
            ['main_category' => 'Capital Outlay', 'subcategory' => 'Public Infrastructures', 'code' => '1 08 01 010', 'name' => 'Breeding Stocks'],
        ];

        foreach ($accountSeedData as $accountData) {
            Account::updateOrCreate(
                ['code' => $accountData['code']],
                [
                    'main_category' => $accountData['main_category'],
                    'subcategory' => $accountData['subcategory'],
                    'name' => $accountData['name'],
                ]
            );
        }
    }
}
