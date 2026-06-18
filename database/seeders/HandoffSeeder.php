<?php

namespace Database\Seeders;

use App\Enums\HandoffStatus;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\User;
use Illuminate\Database\Seeder;

class HandoffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conversations = Conversation::all();
        $index = 0;

        foreach ($conversations as $conversation) {
            // Check if handoff already exists for this conversation
            if (Handoff::where('conversation_id', $conversation->id)->exists()) {
                continue;
            }

            // Create handoff request for the first 2 conversations
            if ($index < 2) {
                Handoff::factory()->create([
                    'company_id' => $conversation->company_id,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $conversation->lead_id,
                    'status' => $index === 0 ? HandoffStatus::Waiting : HandoffStatus::Active,
                    'agent_id' => $index === 1 ? User::where('company_id', $conversation->company_id)->first()?->id : null,
                ]);
            }
            $index++;
        }
    }
}
