<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class PlanCatalog
{
    public function all(): array
    {
        return Config::get('plans', []);
    }

    public function find(string $key): ?array
    {
        return Config::get("plans.{$key}");
    }

    public function limit(string $key, string $type): ?int
    {
        return Config::get("plans.{$key}.limits.{$type}");
    }

    public function priceCents(string $key): int
    {
        return (int) Config::get("plans.{$key}.price_cents", 0);
    }

    public function priceInEGP(string $key): float
    {
        return $this->priceCents($key) / 100;
    }

    public function exists(string $key): bool
    {
        return $this->find($key) !== null;
    }

    public function keys(): array
    {
        return array_keys($this->all());
    }
}
