<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $channelId,
        public string $to,
        public string $templateName,
        public string $languageCode,
        public array $components = []
    ) {}

    public function handle(WhatsAppClientContract $client): void
    {
        $client->sendTemplate($this->channelId, $this->to, $this->templateName, $this->languageCode, $this->components);
    }
}
