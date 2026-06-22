<?php

namespace App\Contracts;

class ChargeResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $failureReason = null,
        public readonly array $raw = [],
    ) {}
}
