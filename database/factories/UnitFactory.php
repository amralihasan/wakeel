<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(['apartment', 'duplex', 'penthouse', 'villa', 'studio']),
            'rooms' => fake()->numberBetween(1, 6),
            'area' => fake()->numberBetween(50, 500),
            'price' => fake()->numberBetween(500000, 20000000),
            'location' => fake()->city(),
            'down_payment' => fake()->optional()->numberBetween(100000, 5000000),
            'installment_years' => fake()->optional()->numberBetween(5, 20),
            'status' => UnitStatus::Available,
            'delivery_date' => fake()->optional()->date(),
        ];
    }
}
