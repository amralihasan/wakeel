<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\FollowUpLog;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class FollowUpLogSeeder extends Seeder
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
            $lead = $conversation->lead;

            if ($index === 1 || $index === 2) {
                if (! $lead) {
                    continue;
                }

                $visit = Visit::where('lead_id', $lead->id)->first();
                if (! $visit) {
                    continue;
                }

                // Check if FollowUpLog already exists
                if (FollowUpLog::where('visit_id', $visit->id)->exists()) {
                    continue;
                }

                FollowUpLog::create([
                    'company_id' => $companyId,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'visit_id' => $visit->id,
                    'sequence_type' => 'visit_reminder_24h',
                ]);
            }
            $index++;
        }
    }
}
