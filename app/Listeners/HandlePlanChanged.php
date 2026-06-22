<?php

namespace App\Listeners;

use App\Events\PlanChanged;
use App\Models\AdminAuditLog;

class HandlePlanChanged
{
    public function handle(PlanChanged $event): void
    {
        AdminAuditLog::record(
            null,
            'plan_changed',
            'subscription',
            $event->subscription->id,
            "Plan changed from {$event->oldPlanKey} to {$event->newPlanKey} for company {$event->subscription->company->name}",
        );
    }
}
