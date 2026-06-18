<?php

namespace Database\Seeders;

use App\Enums\ConversationMode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Check if conversations already exist for this company
            if (Conversation::where('company_id', $company->id)->exists()) {
                continue;
            }

            // Get some leads of this company to build conversations
            $leads = Lead::where('company_id', $company->id)->get();
            if ($leads->isEmpty()) {
                continue;
            }

            // Create Conversations for some of the leads
            foreach ($leads->random(min(4, $leads->count())) as $lead) {
                $mode = fake()->randomElement([ConversationMode::Bot, ConversationMode::Human]);
                $assignedRep = $mode === ConversationMode::Human
                    ? User::where('company_id', $company->id)->where('role', UserRole::SalesRep)->first()
                    : null;

                Conversation::factory()->create([
                    'company_id' => $company->id,
                    'lead_id' => $lead->id,
                    'customer_phone' => $lead->customer_phone,
                    'mode' => $mode,
                    'assigned_rep_id' => $assignedRep?->id,
                    'last_message_at' => now(),
                ]);
            }
        }
    }
}
