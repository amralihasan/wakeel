<?php

namespace App\Listeners;

use App\Events\SubscriptionPastDue;
use App\Models\AdminAuditLog;

class HandleSubscriptionPastDue
{
    public function handle(SubscriptionPastDue $event): void
    {
        $subscription = $event->subscription;

        AdminAuditLog::record(
            null,
            'subscription_past_due',
            'subscription',
            $subscription->id,
            "Subscription past due for company {$subscription->company->name}",
        );
    }
}
