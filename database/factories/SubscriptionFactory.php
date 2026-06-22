<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'plan_key' => 'starter',
            'status' => SubscriptionStatus::Active,
            'payment_method' => 'card',
            'gateway' => 'paymob',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'next_charge_at' => now()->addMonth(),
            'cancel_at_period_end' => false,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::PastDue,
            'grace_ends_at' => now()->addDays(3),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Canceled,
            'canceled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Expired,
        ]);
    }
}
