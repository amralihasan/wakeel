<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Events\PlanChanged;
use App\Events\SubscriptionRenewed;
use App\Models\Company;
use App\Models\Subscription;

class SubscriptionManager
{
    public function __construct(
        protected PlanCatalog $plans,
    ) {}

    public function startTrial(Company $company, string $planKey): Subscription
    {
        $trialEnd = now()->addDays(14);

        return Subscription::create([
            'company_id' => $company->id,
            'plan_key' => $planKey,
            'status' => SubscriptionStatus::Trialing,
            'trial_ends_at' => $trialEnd,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function activate(
        Company $company,
        string $planKey,
        string $method,
        string $gateway,
        ?string $token = null,
        ?\DateTimeInterface $periodStart = null,
        ?\DateTimeInterface $periodEnd = null,
    ): Subscription {
        $subscription = Subscription::updateOrCreate(
            ['company_id' => $company->id],
            [
                'plan_key' => $planKey,
                'status' => SubscriptionStatus::Active,
                'payment_method' => $method,
                'gateway' => $gateway,
                'gateway_token' => $token,
                'current_period_start' => $periodStart ?? now(),
                'current_period_end' => $periodEnd ?? now()->addMonth(),
                'last_payment_at' => now(),
                'next_charge_at' => $periodEnd ?? now()->addMonth(),
                'trial_ends_at' => null,
                'grace_ends_at' => null,
                'canceled_at' => null,
                'cancel_at_period_end' => false,
            ]
        );

        $company->update(['is_active' => true]);

        return $subscription;
    }

    public function renew(Subscription $subscription, \DateTimeInterface $periodStart, \DateTimeInterface $periodEnd): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'next_charge_at' => $periodEnd,
            'last_payment_at' => now(),
            'grace_ends_at' => null,
        ]);

        $subscription->company->update(['is_active' => true]);

        SubscriptionRenewed::dispatch($subscription);

        return $subscription->fresh();
    }

    public function markPastDue(Subscription $subscription): Subscription
    {
        $subscription->transitionTo(SubscriptionStatus::PastDue);
        $subscription->update([
            'grace_ends_at' => now()->addDays(7),
        ]);

        return $subscription->fresh();
    }

    public function cancel(Subscription $subscription, bool $atPeriodEnd = true): Subscription
    {
        if ($atPeriodEnd) {
            $subscription->update(['cancel_at_period_end' => true]);

            return $subscription;
        }

        $subscription->transitionTo(SubscriptionStatus::Canceled);
        $subscription->company->update(['is_active' => false]);

        return $subscription->fresh();
    }

    public function resume(Subscription $subscription): Subscription
    {
        if ($subscription->status !== SubscriptionStatus::Canceled && ! $subscription->cancel_at_period_end) {
            throw new \InvalidArgumentException('Only subscriptions scheduled for cancellation can be resumed.');
        }

        $subscription->update([
            'cancel_at_period_end' => false,
            'canceled_at' => null,
        ]);

        if ($subscription->status === SubscriptionStatus::Canceled) {
            $subscription->transitionTo(SubscriptionStatus::Active);
            $subscription->company->update(['is_active' => true]);
        }

        return $subscription->fresh();
    }

    public function changePlan(Subscription $subscription, string $newPlanKey, string $strategy = 'immediate'): Subscription
    {
        if (! $this->plans->exists($newPlanKey)) {
            throw new \InvalidArgumentException("Plan \"{$newPlanKey}\" does not exist.");
        }

        $oldPlanKey = $subscription->plan_key;

        if ($strategy === 'immediate') {
            $subscription->update(['plan_key' => $newPlanKey]);

            PlanChanged::dispatch($subscription, $oldPlanKey, $newPlanKey);
        } elseif ($strategy === 'next_cycle') {
            $subscription->update(['plan_key' => $newPlanKey]);

            PlanChanged::dispatch($subscription, $oldPlanKey, $newPlanKey);
        } else {
            throw new \InvalidArgumentException("Unknown strategy \"{$strategy}\".");
        }

        return $subscription->fresh();
    }

    public function expire(Subscription $subscription): Subscription
    {
        $subscription->transitionTo(SubscriptionStatus::Expired);
        $subscription->company->update(['is_active' => false]);

        return $subscription->fresh();
    }
}
