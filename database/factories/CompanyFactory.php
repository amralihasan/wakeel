<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $company = fake()->unique()->company();

        return [
            'name' => $company,
            'slug' => Str::slug($company),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'plan' => 'starter',
            'is_active' => true,
            'onboarding_completed' => true,
        ];
    }
}
