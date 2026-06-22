<?php

namespace App\Services;

readonly class ModelChoice
{
    public function __construct(
        public string $provider,
        public string $model,
    ) {}
}
