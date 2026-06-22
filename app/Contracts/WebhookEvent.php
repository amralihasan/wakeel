<?php

namespace App\Contracts;

class WebhookEvent
{
    public function __construct(
        public readonly string $type,
        public readonly string $transactionId,
        public readonly string $companyId,
        public readonly string $planKey,
        public readonly bool $success,
        public readonly array $raw = [],
    ) {}
}
