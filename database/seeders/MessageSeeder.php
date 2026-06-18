<?php

namespace Database\Seeders;

use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conversations = Conversation::all();

        foreach ($conversations as $conversation) {
            // Check if messages already exist for this conversation
            if ($conversation->messages()->exists()) {
                continue;
            }

            $companyId = $conversation->company_id;
            $assignedRep = $conversation->assignedRep;

            // Create a chain of messages (alternating customer and bot/rep)
            $messagesCount = fake()->numberBetween(4, 8);
            for ($i = 0; $i < $messagesCount; $i++) {
                $direction = $i % 2 === 0 ? MessageDirection::Inbound : MessageDirection::Outbound;
                $sender = $direction === MessageDirection::Inbound
                    ? MessageSender::Customer
                    : ($assignedRep ? MessageSender::Rep : MessageSender::Bot);

                Message::factory()->create([
                    'company_id' => $companyId,
                    'conversation_id' => $conversation->id,
                    'direction' => $direction,
                    'sender' => $sender,
                    'created_at' => now()->subMinutes(($messagesCount - $i) * 5),
                ]);
            }
        }
    }
}
