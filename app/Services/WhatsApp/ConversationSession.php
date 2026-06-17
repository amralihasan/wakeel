<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use Illuminate\Support\Facades\Cache;

class ConversationSession
{
    protected int $maxTurns = 12;

    protected ?Company $company = null;

    protected ?string $phone = null;

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    protected function key(): string
    {
        return 'session:'.$this->company->id.':'.$this->phone;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($this->key().'.'.$key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        Cache::put($this->key().'.'.$key, $value);
    }

    public function getMode(): string
    {
        return $this->get('mode', 'bot');
    }

    public function setMode(string $mode): void
    {
        $this->set('mode', $mode);
    }

    public function pushTurn(array $turn): void
    {
        $history = $this->get('history', []);

        $history[] = $turn;

        if (count($history) > $this->maxTurns) {
            $history = array_slice($history, -$this->maxTurns);
        }

        $this->set('history', $history);
        $this->touch();
    }

    public function history(): array
    {
        return $this->get('history', []);
    }

    public function touch(): void
    {
        $this->set('updated_at', now()->toIso8601String());
    }
}
