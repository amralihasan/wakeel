<?php

namespace App\Events;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionPastDue
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Subscription $subscription,
        public SubscriptionStatus $previousStatus,
    ) {}
}
