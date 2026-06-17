<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $channelId,
        public string $to,
        public string $url,
        public string $type,
        public ?string $filename = null,
        public ?string $caption = null
    ) {}

    public function handle(WhatsAppClientContract $client): void
    {
        if ($this->type === 'image') {
            $client->sendImage($this->channelId, $this->to, $this->url, $this->caption);
        } else {
            $client->sendDocument($this->channelId, $this->to, $this->url, $this->filename, $this->caption);
        }
    }
}
