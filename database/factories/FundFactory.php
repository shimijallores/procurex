<?php

namespace Database\Factories;

use App\Models\Office;
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
        return [
            'office_id' => fn () => Office::firstOrCreate(['name' => 'Provincial Veterinary Office'])->id,
            'code' => fake()->unique()->randomNumber(5, true),
            'name' => 'Livestock and Poultry Development Program of the Provincial Veterinary Office',
            'type' => 'project',
            'fiscal_year' => 2026,
        ];
    }
}
