<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Log;

class AiModelResolver
{
    public function __construct(
        protected AiModelRegistry $registry,
    ) {}

    public function for(?Company $company): ModelChoice
    {
        $configDefault = $this->registry->default();
        $platformDefault = PlatformSetting::get('default_ai_model', $configDefault);

        if ($company === null) {
            $model = $this->registry->find($platformDefault);

            if ($model !== null && $this->registry->isModelEnabled($platformDefault)) {
                return new ModelChoice($model['provider'], $platformDefault);
            }

            return new ModelChoice(
                $this->registry->find($configDefault)['provider'] ?? 'anthropic',
                $configDefault,
            );
        }

        $companyOverride = $company->ai_model;

        // Company override takes precedence
        if ($companyOverride !== null && $this->registry->isSelectable($companyOverride)) {
            $model = $this->registry->find($companyOverride);

            return new ModelChoice($model['provider'], $companyOverride);
        }

        // If company override exists but is no longer selectable, warn and fall through
        if ($companyOverride !== null) {
            Log::warning("Company #{$company->id} has disabled/non-selectable model '{$companyOverride}', falling back to platform default.");
        }

        // Platform default
        $platformModel = $this->registry->find($platformDefault);
        if ($platformModel !== null && $this->registry->isModelEnabled($platformDefault)) {
            return new ModelChoice($platformModel['provider'], $platformDefault);
        }

        // Config default as last resort
        $configModel = $this->registry->find($configDefault);

        return new ModelChoice(
            $configModel['provider'] ?? 'anthropic',
            $configDefault,
        );
    }

    public function fallbackFor(ModelChoice $primary): ?ModelChoice
    {
        $fallbackEnabled = PlatformSetting::get('fallback_enabled', true);

        if (! $fallbackEnabled) {
            return null;
        }

        $fallbackKey = PlatformSetting::get('fallback_ai_model', $this->registry->fallback());

        if ($fallbackKey === $primary->model) {
            return null;
        }

        $fallbackModel = $this->registry->find($fallbackKey);

        if ($fallbackModel === null || ! $this->registry->isModelEnabled($fallbackKey)) {
            return null;
        }

        return new ModelChoice($fallbackModel['provider'], $fallbackKey);
    }
}
