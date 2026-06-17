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
        return [
            'name' => fake()->company(),
            'slug' => Str::slug(fake()->company()),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'plan' => 'starter',
            'is_active' => true,
        ];
    }
}
