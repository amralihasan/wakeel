<?php

namespace Database\Factories;

use App\Enums\VisitStatus;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visit>
 */
class VisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'lead_id' => Lead::factory(),
            'unit_id' => Unit::factory(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => VisitStatus::Pending,
            'assigned_rep_id' => fake()->optional()->randomElement([User::factory()]),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
