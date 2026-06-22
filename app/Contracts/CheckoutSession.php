<?php

namespace App\Contracts;

class CheckoutSession
{
    public function __construct(
        public readonly string $redirectUrl,
        public readonly string $transactionId,
        public readonly array $raw = [],
    ) {}
}
