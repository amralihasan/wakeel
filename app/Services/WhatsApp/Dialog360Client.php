<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppException;
use App\Models\Company;
use App\Models\WhatsAppChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Dialog360Client implements WhatsAppClientContract
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.dialog360.base_url'), '/');
        $this->apiKey = (string) config('services.dialog360.api_key');
    }

    public function sendText(string $channelId, string $to, string $body): string
    {
        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $body],
        ]);
    }

    public function sendImage(string $channelId, string $to, string $url, ?string $caption = null): string
    {
        $image = ['link' => $url];

        if ($caption !== null) {
            $image['caption'] = $caption;
        }

        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => $image,
        ]);
    }

    public function sendDocument(string $channelId, string $to, string $url, ?string $filename = null, ?string $caption = null): string
    {
        $document = ['link' => $url];

        if ($filename !== null) {
            $document['filename'] = $filename;
        }

        if ($caption !== null) {
            $document['caption'] = $caption;
        }

        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'document',
            'document' => $document,
        ]);
    }

    public function sendInteractiveButtons(string $channelId, string $to, string $body, array $buttons): string
    {
        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $buttons],
            ],
        ]);
    }

    public function sendInteractiveList(string $channelId, string $to, string $body, array $sections): string
    {
        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $body],
                'action' => ['sections' => $sections],
            ],
        ]);
    }

    public function sendLocation(string $channelId, string $to, float $lat, float $lng, ?string $name = null): string
    {
        $location = [
            'latitude' => $lat,
            'longitude' => $lng,
        ];

        if ($name !== null) {
            $location['name'] = $name;
        }

        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'location',
            'location' => $location,
        ]);
    }

    public function sendTemplate(string $channelId, string $to, string $templateName, string $languageCode, array $components = []): string
    {
        return $this->post($channelId, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components,
            ],
        ]);
    }

    public function assignNumberFromPool(Company $company): void
    {
        $channel = WhatsAppChannel::where('status', 'available')->first();

        if (! $channel) {
            throw new WhatsAppException('No channel IDs available in the pool.');
        }

        $company->update([
            'dialog360_channel_id' => $channel->channel_id,
            'whatsapp_number' => $channel->number,
        ]);

        $channel->update([
            'status' => 'assigned',
            'assigned_company_id' => $company->id,
        ]);

        $this->registerWebhook($channel->channel_id, $company);
    }

    protected function registerWebhook(string $channelId, Company $company): void
    {
        $url = route('webhooks.whatsapp', ['company' => $company->id]);

        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->post("{$this->baseUrl}/configs/webhook", [
                'url' => $url,
                'headers' => [
                    'Authorization' => 'Bearer '.config('app.key'),
                ],
            ]);

        if ($response->failed()) {
            Log::warning('Failed to register 360dialog webhook for company', [
                'company_id' => $company->id,
                'channel_id' => $channelId,
                'status' => $response->status(),
            ]);
        }
    }

    protected function post(string $channelId, array $payload): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(10)
            ->post("{$this->baseUrl}/{$channelId}/messages", $payload);

        if ($response->failed()) {
            Log::warning('360dialog API request failed', [
                'channel_id' => $channelId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new WhatsAppException(
                "360dialog API returned status {$response->status()}: {$response->body()}",
                $response->status()
            );
        }

        return $response->body();
    }
}
