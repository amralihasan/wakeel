<?php

namespace App\Listeners;

use App\Events\SubscriptionCanceled;
use App\Models\AdminAuditLog;

class HandleSubscriptionCanceled
{
    public function handle(SubscriptionCanceled $event): void
    {
        $subscription = $event->subscription;

        if ($subscription->cancel_at_period_end === false) {
            $subscription->company->update(['is_active' => false]);
        }

        AdminAuditLog::record(
            null,
            'subscription_canceled',
            'subscription',
            $subscription->id,
            "Subscription canceled for company {$subscription->company->name}",
        );
    }
}
