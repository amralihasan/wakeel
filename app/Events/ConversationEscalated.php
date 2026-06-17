<?php

namespace App\Events;

use App\Models\Handoff;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationEscalated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Handoff $handoff,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->handoff->company_id}.handoffs"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.escalated';
    }
}
