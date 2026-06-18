<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainCompany = Company::where('slug', 'test-company')->first();

        if (! $mainCompany) {
            $mainCompany = Company::factory()->create([
                'name' => 'Test Company',
                'slug' => 'test-company',
                'plan' => 'growth',
                'onboarding_completed' => true,
            ]);
        }

        // 1. Create main test user (Owner of the main company) if they don't exist
        $testUser = User::where('email', 'test@example.com')->first();
        if (! $testUser) {
            User::factory()->owner()->create([
                'company_id' => $mainCompany->id,
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // 2. Add extra users (1 Owner, 2 SalesReps) for each company
        $companies = Company::all();
        foreach ($companies as $company) {
            // Check if this company already has additional users
            if ($company->users()->count() <= 1) {
                // If it's not the main company, make sure it has an owner
                if ($company->id !== $mainCompany->id) {
                    User::factory()->owner()->create([
                        'company_id' => $company->id,
                    ]);
                }
                // Add two sales reps
                User::factory(2)->create([
                    'company_id' => $company->id,
                    'role' => UserRole::SalesRep,
                ]);
            }
        }
    }
}
