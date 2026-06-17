<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InboundMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $companyId,
        public string $customerPhone,
        public ?string $body
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("company.{$this->companyId}.conversations"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'inbound-message.received';
    }
}
