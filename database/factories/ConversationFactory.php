<?php

namespace Database\Factories;

use App\Enums\ConversationMode;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'customer_phone' => fake()->unique()->e164PhoneNumber(),
            'lead_id' => Lead::factory(),
            'mode' => ConversationMode::Bot,
            'assigned_rep_id' => fake()->optional()->randomElement([User::factory()]),
            'last_message_at' => fake()->optional()->dateTime(),
        ];
    }
}
