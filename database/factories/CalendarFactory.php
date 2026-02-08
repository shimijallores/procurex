<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calendar>
 */
class CalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['holiday', 'special_workday', 'blackout', 'suspended']);

        $isWorkingDay = match ($type) {
            'special_workday' => true,
            'holiday', 'suspended', 'blackout' => false,
        };

        return [
            'date' => $this->faker->unique()->dateTimeBetween('now', '+1 year'),
            'type' => $type,
            'is_working_day' => $isWorkingDay,
            'name' => $this->faker->sentence(3),
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the entry is a holiday.
     */
    public function holiday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'holiday',
            'is_working_day' => false,
        ]);
    }

    /**
     * Indicate that the entry is a suspended day (typhoon/weather).
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'suspended',
            'is_working_day' => false,
        ]);
    }

    /**
     * Indicate that the entry is a special workday.
     */
    public function specialWorkday(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'special_workday',
            'is_working_day' => true,
        ]);
    }

    /**
     * Indicate that the entry is a blackout date.
     */
    public function blackout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'blackout',
            'is_working_day' => false,
        ]);
    }
}
