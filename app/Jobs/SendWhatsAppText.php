<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $channelId,
        public string $to,
        public string $body
    ) {}

    public function handle(WhatsAppClientContract $client): void
    {
        $client->sendText($this->channelId, $this->to, $this->body);
    }
}
