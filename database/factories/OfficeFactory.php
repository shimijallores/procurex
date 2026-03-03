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
            'code' => fake()->unique()->bothify('???-###'),
            'acronym' => fake()->boolean() ? strtoupper(fake()->lexify('???')) : null,
        ];
    }

    /**
     * Create the Provincial Veterinary Office.
     */
    public function provincialVeterinaryOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Provincial Veterinary Office',
            'code' => '8721',
            'acronym' => null,
        ]);
    }

    public function governingOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Governing Office',
            'code' => 'GOV-001',
            'acronym' => null,
        ]);
    }
}
