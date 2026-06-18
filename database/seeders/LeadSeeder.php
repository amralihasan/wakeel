<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Check if leads already exist for this company
            if (Lead::where('company_id', $company->id)->exists()) {
                continue;
            }

            $units = $company->units;
            if ($units->isEmpty()) {
                continue;
            }

            // Create Leads
            Lead::factory(8)->create([
                'company_id' => $company->id,
                'interested_unit_id' => fn () => $units->random()->id,
            ]);
        }
    }
}
