<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class VisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conversations = Conversation::all();
        $index = 0;

        foreach ($conversations as $conversation) {
            $companyId = $conversation->company_id;

            // Limit to seeding a few visits
            if ($index === 1 || $index === 2) {
                $lead = $conversation->lead;
                if (! $lead) {
                    continue;
                }

                // Check if visit already exists
                if (Visit::where('lead_id', $lead->id)->exists()) {
                    continue;
                }

                $rep = User::where('company_id', $companyId)->where('role', UserRole::SalesRep)->first();
                $units = $conversation->company->units;
                if ($units->isEmpty()) {
                    continue;
                }

                Visit::factory()->create([
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'unit_id' => $lead->interested_unit_id ?? $units->random()->id,
                    'status' => fake()->randomElement([VisitStatus::Pending, VisitStatus::Completed]),
                    'assigned_rep_id' => $rep?->id,
                ]);
            }
            $index++;
        }
    }
}
