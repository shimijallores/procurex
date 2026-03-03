<?php

namespace Database\Factories;

use App\Models\Office;
use App\Models\ProjectCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fund>
 */
class FundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    // Generate 1 fund for the Provincial Veterinary Office
    public function definition(): array
    {
        $office = Office::firstOrCreate(['name' => 'Provincial Veterinary Office']);
        $projectCode = ProjectCode::query()->where('office_id', $office->id)->first();

        if (! $projectCode) {
            $projectCode = ProjectCode::create([
                'office_id' => $office->id,
                'code' => (string) fake()->unique()->numberBetween(90000, 99999),
                'name' => fake()->unique()->sentence(4),
            ]);
        }

        return [
            'office_id' => $office->id,
            'project_code_id' => $projectCode->id,
            'code' => fake()->unique()->randomNumber(5, true),
            'name' => 'Livestock and Poultry Development Program of the Provincial Veterinary Office',
            'type' => 'project',
            'fiscal_year' => 2026,
        ];
    }
}
