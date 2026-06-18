<?php

namespace App\Services\WhatsApp;

use App\Models\Company;

interface WhatsAppClientContract
{
    public function sendText(string $channelId, string $to, string $body): string;

    public function sendImage(string $channelId, string $to, string $url, ?string $caption = null): string;

    public function sendDocument(string $channelId, string $to, string $url, ?string $filename = null, ?string $caption = null): string;

    public function sendInteractiveButtons(string $channelId, string $to, string $body, array $buttons): string;

    public function sendInteractiveList(string $channelId, string $to, string $body, array $sections): string;

    public function sendLocation(string $channelId, string $to, float $lat, float $lng, ?string $name = null): string;

    public function sendTemplate(string $channelId, string $to, string $templateName, string $languageCode, array $components = []): string;

    public function assignNumberFromPool(Company $company): void;
}
