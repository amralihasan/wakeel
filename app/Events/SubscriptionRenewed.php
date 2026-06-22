<?php

namespace App\Events;

use App\Models\Subscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class SubscriptionRenewed
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public Subscription $subscription,
    ) {}
}
