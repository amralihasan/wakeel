<?php

namespace Database\Factories;

use App\Enums\HandoffStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Handoff>
 */
class HandoffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'conversation_id' => Conversation::factory(),
            'lead_id' => Lead::factory(),
            'reason' => fake()->sentence(),
            'ai_summary' => fake()->paragraph(),
            'status' => HandoffStatus::Waiting,
            'agent_id' => fake()->optional()->randomElement([User::factory()]),
            'claimed_at' => null,
            'resolved_at' => null,
            'resolution_notes' => null,
        ];
    }
}
