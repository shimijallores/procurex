<?php

namespace Database\Factories;

use App\Models\Office;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Funds>
 */
class FundsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'office_id' => fn () => Office::firstOrCreate(['name' => 'Provincial Veterinary Office'])->id,
            'code' => fake()->unique()->randomNumber(5),
            'type' => fake()->randomElement(['general', 'project']),
            'fiscal_year' => 2026,
            'allocated_amount' => 1298025.00,
            'name' => 'Livestock and Poultry Development Program of the Provincial Veterinary Office',
        ];
    }
}
