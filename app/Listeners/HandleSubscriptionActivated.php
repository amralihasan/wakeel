<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Models\AdminAuditLog;

class HandleSubscriptionActivated
{
    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        $company = $subscription->company;

        $company->update(['is_active' => true]);

        AdminAuditLog::record(
            null,
            'subscription_activated',
            'subscription',
            $subscription->id,
            "Subscription activated for company {$company->name} (plan: {$subscription->plan_key})",
        );
    }
}
