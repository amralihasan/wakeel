<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $channelId,
        public string $to,
        public string $body
    ) {}

    public function handle(WhatsAppClientContract $client): void
    {
        try {
            $client->sendText($this->channelId, $this->to, $this->body);
        } catch (\Throwable $exception) {
            $code = $exception->getCode();
            if ($code >= 400 && $code < 500) {
                $this->fail($exception);

                return;
            }
            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppText failed', [
            'channel_id' => $this->channelId,
            'to' => $this->maskPhone($this->to),
            'error' => $exception->getMessage(),
        ]);
    }

    protected function maskPhone(string $phone): string
    {
        $length = strlen($phone);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        $visible = 4;
        $masked = $length - $visible - 3;

        return substr($phone, 0, $visible).str_repeat('*', $masked).substr($phone, -3);
    }
}
