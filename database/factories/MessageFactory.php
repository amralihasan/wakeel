<?php

namespace Database\Factories;

use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'conversation_id' => Conversation::factory(),
            'direction' => fake()->randomElement([MessageDirection::Inbound, MessageDirection::Outbound]),
            'sender' => fake()->randomElement([MessageSender::Customer, MessageSender::Bot, MessageSender::Rep]),
            'body' => fake()->optional()->sentence(),
            'media_url' => fake()->optional()->url(),
            'media_type' => fake()->optional()->randomElement(['image', 'document', 'audio']),
            'wa_message_id' => fake()->optional()->uuid(),
            'created_at' => fake()->dateTimeThisYear(),
        ];
    }
}
