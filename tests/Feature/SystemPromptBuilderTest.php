<?php

use App\Models\Company;
use App\Models\Lead;
use App\Services\Agent\SystemPromptBuilder;

it('dynamically switches tone instructions', function () {
    $company = Company::factory()->create();

    $builder = app(SystemPromptBuilder::class);

    $formal = $builder->build(tap(clone $company)->forceFill(['bot_settings' => ['tone' => 'formal']])->setRelations([]));
    $gulf = $builder->build(tap(clone $company)->forceFill(['bot_settings' => ['tone' => 'gulf']])->setRelations([]));
    $default = $builder->build($company);

    expect($formal)->toContain('باللغة العربية الفصحى المبسطة')
        ->and($gulf)->toContain('بلهجة خليجية')
        ->and($default)->toContain('بلهجة مصرية عامية ودودة');
});

it('interpolates company name and custom bot settings', function () {
    $company = Company::factory()->create([
        'name' => 'شركة العقارات الذهبية',
        'bot_settings' => ['bot_name' => 'سارة'],
    ]);

    $builder = app(SystemPromptBuilder::class);
    $prompt = $builder->build($company);

    expect($prompt)->toContain('سارة')
        ->and($prompt)->toContain('شركة العقارات الذهبية');
});

it('injects lead context into the prompt', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'name' => 'أحمد علي',
        'customer_phone' => '+201234567890',
        'budget_max' => 5000000,
    ]);

    $builder = app(SystemPromptBuilder::class);
    $prompt = $builder->build($company, $lead);

    expect($prompt)->toContain('أحمد علي')
        ->and($prompt)->toContain('+201234567890')
        ->and($prompt)->toContain('5,000,000 جنيه مصري');
});

it('falls back to default values when bot_settings is null', function () {
    $company = Company::factory()->create(['bot_settings' => null]);

    $builder = app(SystemPromptBuilder::class);

    expect(fn () => $builder->build($company))->not->toThrow(Exception::class);

    $prompt = $builder->build($company);

    expect($prompt)->toContain('نور')
        ->and($prompt)->toContain('بلهجة مصرية عامية ودودة');
});
