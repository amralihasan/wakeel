<?php

use App\Models\Company;
use App\Models\Message;
use App\Models\PlatformSetting;
use App\Services\AiModelRegistry;
use App\Services\AiModelResolver;
use App\Services\ModelChoice;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    PlatformSetting::set('default_ai_model', 'claude-haiku-4-5');
    PlatformSetting::set('fallback_enabled', true);
    PlatformSetting::set('fallback_ai_model', 'gpt-4o-mini');
});

// ─── AiModelRegistry ─────────────────────────────────────────────

it('returns all models', function () {
    $registry = app(AiModelRegistry::class);

    expect($registry->all())->toHaveKeys([
        'claude-haiku-4-5', 'claude-sonnet-4-6', 'gpt-4o', 'gpt-4o-mini',
    ]);
});

it('finds a model by key', function () {
    $model = app(AiModelRegistry::class)->find('claude-haiku-4-5');

    expect($model)->not->toBeNull()
        ->and($model['provider'])->toBe('anthropic')
        ->and($model['supports_tools'])->toBeTrue();
});

it('returns null for non-existent model', function () {
    expect(app(AiModelRegistry::class)->find('nonexistent'))->toBeNull();
});

it('returns selectable models (enabled + supports_tools)', function () {
    $selectable = app(AiModelRegistry::class)->selectable();

    foreach ($selectable as $model) {
        expect($model['enabled'] ?? true)->toBeTrue();
        expect($model['supports_tools'] ?? false)->toBeTrue();
    }
});

it('checks if a model is selectable', function () {
    $registry = app(AiModelRegistry::class);

    expect($registry->isSelectable('claude-haiku-4-5'))->toBeTrue();
    expect($registry->isSelectable('nonexistent'))->toBeFalse();
});

it('returns cost per million tokens', function () {
    $registry = app(AiModelRegistry::class);

    expect($registry->costPerMtok('claude-haiku-4-5', 'input'))->toBe(0.80);
    expect($registry->costPerMtok('claude-haiku-4-5', 'output'))->toBe(4.00);
    expect($registry->costPerMtok('gpt-4o', 'input'))->toBe(2.50);
});

it('returns zero cost for non-existent model', function () {
    expect(app(AiModelRegistry::class)->costPerMtok('nonexistent', 'input'))->toBe(0.0);
});

it('returns default and fallback keys', function () {
    $registry = app(AiModelRegistry::class);

    expect($registry->default())->toBe('claude-haiku-4-5');
    expect($registry->fallback())->toBe('gpt-4o-mini');
});

// ─── AiModelResolver ─────────────────────────────────────────────

it('resolves to platform default when company has no override', function () {
    $company = Company::factory()->create(['ai_model' => null]);
    $choice = app(AiModelResolver::class)->for($company);

    expect($choice)->toBeInstanceOf(ModelChoice::class)
        ->and($choice->provider)->toBe('anthropic')
        ->and($choice->model)->toBe('claude-haiku-4-5');
});

it('resolves to company override when valid', function () {
    $company = Company::factory()->create(['ai_model' => 'gpt-4o']);
    $choice = app(AiModelResolver::class)->for($company);

    expect($choice->provider)->toBe('openai')
        ->and($choice->model)->toBe('gpt-4o');
});

it('falls back to platform default when company override is disabled', function () {
    $company = Company::factory()->create(['ai_model' => 'gpt-4o']);
    PlatformSetting::set('model_enabled_gpt-4o', false);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn ($msg) => str_contains($msg, 'disabled/non-selectable'));

    $choice = app(AiModelResolver::class)->for($company);

    expect($choice->model)->toBe('claude-haiku-4-5');
});

it('resolves to company override with anthropic model', function () {
    $company = Company::factory()->create(['ai_model' => 'claude-sonnet-4-6']);
    $choice = app(AiModelResolver::class)->for($company);

    expect($choice->provider)->toBe('anthropic')
        ->and($choice->model)->toBe('claude-sonnet-4-6');
});

it('returns fallback model different from primary', function () {
    $resolver = app(AiModelResolver::class);
    $primary = new ModelChoice('anthropic', 'claude-haiku-4-5');
    $fallback = $resolver->fallbackFor($primary);

    expect($fallback)->not->toBeNull()
        ->and($fallback->model)->toBe('gpt-4o-mini');
});

it('returns null fallback when fallback is disabled', function () {
    PlatformSetting::set('fallback_enabled', false);

    $resolver = app(AiModelResolver::class);
    $primary = new ModelChoice('anthropic', 'claude-haiku-4-5');

    expect($resolver->fallbackFor($primary))->toBeNull();
});

it('returns null fallback when fallback model matches primary', function () {
    PlatformSetting::set('fallback_ai_model', 'claude-haiku-4-5');

    $resolver = app(AiModelResolver::class);
    $primary = new ModelChoice('anthropic', 'claude-haiku-4-5');

    expect($resolver->fallbackFor($primary))->toBeNull();
});

it('resolves for null company to config default', function () {
    $choice = app(AiModelResolver::class)->for(null);

    expect($choice)->toBeInstanceOf(ModelChoice::class)
        ->and($choice->provider)->toBe('anthropic');
});

// ─── AgentRunner uses resolved model ──────────────────────────────

it('stores model_used and cost_usd on messages', function () {
    $company = Company::factory()->create(['ai_model' => 'gpt-4o-mini']);

    // We can't easily mock the full Prism call here, but we can verify
    // that the Message model accepts the new fields
    $message = Message::factory()->create([
        'company_id' => $company->id,
        'model_used' => 'gpt-4o-mini',
        'cost_usd' => 0.0015,
    ]);

    expect($message->model_used)->toBe('gpt-4o-mini')
        ->and($message->cost_usd)->toBe(0.0015);
});

// ─── Platform Settings ────────────────────────────────────────────

it('saves and retrieves model platform settings', function () {
    PlatformSetting::set('default_ai_model', 'gpt-4o');
    PlatformSetting::set('fallback_enabled', false);

    expect(PlatformSetting::get('default_ai_model'))->toBe('gpt-4o');
    expect(PlatformSetting::get('fallback_enabled'))->toBeFalse();
});

it('saves and retrieves model enabled status', function () {
    PlatformSetting::set('model_enabled_gpt-4o', false, 'boolean');

    expect(PlatformSetting::get('model_enabled_gpt-4o'))->toBeFalse();
});

// ─── Cost Calculation ────────────────────────────────────────────

it('calculates estimated cost from token usage', function () {
    $registry = app(AiModelRegistry::class);
    $inputTokens = 1000;
    $outputTokens = 500;
    $modelKey = 'claude-haiku-4-5';

    $inputCost = ($inputTokens / 1_000_000) * $registry->costPerMtok($modelKey, 'input');
    $outputCost = ($outputTokens / 1_000_000) * $registry->costPerMtok($modelKey, 'output');
    $total = $inputCost + $outputCost;

    // 1000 * 0.80 / 1M = 0.0008, 500 * 4.00 / 1M = 0.002, total = 0.0028
    expect($total)->toBe(0.0028);
});

it('calculates gpt-4o cost correctly', function () {
    $registry = app(AiModelRegistry::class);
    $inputTokens = 2000;
    $outputTokens = 1000;
    $modelKey = 'gpt-4o';

    $inputCost = ($inputTokens / 1_000_000) * $registry->costPerMtok($modelKey, 'input');
    $outputCost = ($outputTokens / 1_000_000) * $registry->costPerMtok($modelKey, 'output');
    $total = $inputCost + $outputCost;

    // 2000 * 2.50 / 1M = 0.005, 1000 * 10.00 / 1M = 0.01, total = 0.015
    expect($total)->toBe(0.015);
});
