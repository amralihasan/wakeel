<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Console\Command;

class ChargeDueSubscriptionsCommand extends Command
{
    protected $signature = 'billing:charge-due';

    protected $description = 'Charge due subscriptions and handle renewals, dunning, and expiration';

    public function handle(SubscriptionManager $manager): void
    {
        $due = Subscription::whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->whereNotNull('next_charge_at')
            ->where('next_charge_at', '<=', now())
            ->cursor();

        $charged = 0;
        $dunned = 0;
        $expired = 0;

        foreach ($due as $subscription) {
            $this->line("Processing subscription #{$subscription->id} for company #{$subscription->company_id}...");

            try {
                $now = now();
                $nextPeriodStart = $now;
                $nextPeriodEnd = $now->copy()->addMonth();

                $manager->renew($subscription, $nextPeriodStart, $nextPeriodEnd);
                $charged++;
            } catch (\Exception $e) {
                if ($subscription->status === SubscriptionStatus::PastDue) {
                    if ($subscription->grace_ends_at && $subscription->grace_ends_at->isPast()) {
                        $manager->expire($subscription);
                        $expired++;
                    } else {
                        $this->warn("Charge failed for subscription #{$subscription->id}, already in dunning.");
                        $dunned++;
                    }
                } else {
                    $manager->markPastDue($subscription);
                    $dunned++;
                }
            }
        }

        $this->info("Charged: {$charged}, Dunned: {$dunned}, Expired: {$expired}");
    }
}
