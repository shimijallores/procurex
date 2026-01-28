<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }

    /**
     * Create the Provincial Veterinary Office.
     */
    public function provincialVeterinaryOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Provincial Veterinary Office',
        ]);
    }

    public function governingOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Governing Office',
        ]);
    }
}
