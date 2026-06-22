<?php

namespace App\Listeners;

use App\Events\SubscriptionExpired;
use App\Models\AdminAuditLog;

class HandleSubscriptionExpired
{
    public function handle(SubscriptionExpired $event): void
    {
        $subscription = $event->subscription;
        $subscription->company->update(['is_active' => false]);

        AdminAuditLog::record(
            null,
            'subscription_expired',
            'subscription',
            $subscription->id,
            "Subscription expired for company {$subscription->company->name}",
        );
    }
}
