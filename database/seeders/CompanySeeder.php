<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if main company exists or create it
        $mainCompany = Company::where('slug', 'test-company')->first();
        if (! $mainCompany) {
            Company::factory()->create([
                'name' => 'Test Company',
                'slug' => 'test-company',
                'plan' => 'growth',
                'onboarding_completed' => true,
            ]);
        }

        // Create 2 other companies for multi-tenancy demo if there are less than 3 total companies
        if (Company::count() < 3) {
            Company::factory(2)->create([
                'onboarding_completed' => true,
                'plan' => 'starter',
            ]);
        }
    }
}
