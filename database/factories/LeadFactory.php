<?php

namespace Database\Factories;

use App\Enums\LeadTier;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_phone' => fake()->unique()->e164PhoneNumber(),
            'name' => fake()->optional()->name(),
            'budget_max' => fake()->optional()->numberBetween(500000, 15000000),
            'preferred_rooms' => fake()->optional()->numberBetween(1, 6),
            'preferred_location' => fake()->optional()->city(),
            'interested_unit_id' => Unit::factory(),
            'score' => fake()->numberBetween(0, 100),
            'tier' => fake()->optional()->randomElement([LeadTier::Hot, LeadTier::Warm, LeadTier::Cold]),
            'status' => fake()->randomElement(['new', 'qualifying', 'booked', 'with_rep', 'closed']),
            'source' => fake()->randomElement(['facebook', 'website', 'qr', 'other']),
        ];
    }
}
