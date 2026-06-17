<?php

namespace App\Events;

use App\Models\Handoff;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationEscalated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Handoff $handoff,
    ) {}
}
