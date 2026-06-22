<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Config;

class AiModelRegistry
{
    public function all(): array
    {
        return Config::get('ai_models.models', []);
    }

    public function find(string $key): ?array
    {
        return Config::get("ai_models.models.{$key}");
    }

    public function isModelEnabled(string $key): bool
    {
        $model = $this->find($key);

        if ($model === null) {
            return false;
        }

        $defaultEnabled = $model['enabled'] ?? true;

        return (bool) PlatformSetting::get("model_enabled_{$key}", $defaultEnabled);
    }

    public function enabled(): array
    {
        return array_filter(
            $this->all(),
            fn (array $model, string $key) => $this->isModelEnabled($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function selectable(): array
    {
        return array_filter(
            $this->all(),
            fn (array $model, string $key) => $this->isModelEnabled($key) && ($model['supports_tools'] ?? false) === true,
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function isSelectable(string $key): bool
    {
        $model = $this->find($key);

        return $model !== null
            && $this->isModelEnabled($key)
            && ($model['supports_tools'] ?? false) === true;
    }

    public function default(): string
    {
        return Config::get('ai_models.default', 'claude-haiku-4-5');
    }

    public function fallback(): string
    {
        return Config::get('ai_models.fallback', 'gpt-4o-mini');
    }

    public function enabledKeys(): array
    {
        return array_keys($this->enabled());
    }

    public function selectableKeys(): array
    {
        return array_keys($this->selectable());
    }

    public function costPerMtok(string $key, string $type = 'input'): float
    {
        $model = $this->find($key);

        if ($model === null) {
            return 0.0;
        }

        return (float) ($type === 'input' ? $model['input_cost_per_mtok'] : $model['output_cost_per_mtok']);
    }
}
