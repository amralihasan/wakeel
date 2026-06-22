<?php

use App\Agent\Tools\CalculateInstallmentTool;
use App\Agent\Tools\SearchPropertiesTool;
use App\Enums\UnitStatus;
use App\Models\Company;
use App\Models\GroundingViolation;
use App\Models\Unit;
use App\Services\Agent\GroundingVerifier;
use App\Services\Agent\RetrievedFacts;

beforeEach(function () {
    $this->retrievedFacts = app(RetrievedFacts::class);
    $this->retrievedFacts->clear();
    $this->verifier = app(GroundingVerifier::class);
});

// ─── RetrievedFacts ──────────────────────────────────────────────

it('stores and retrieves unit facts', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'area' => 120, 'rooms' => 3]);

    expect($this->retrievedFacts->units())->toHaveKey(1)
        ->and($this->retrievedFacts->unitIds())->toContain(1)
        ->and($this->retrievedFacts->allNumericFacts())->toContain(500000, 120, 3);
});

it('stores installment results', function () {
    $this->retrievedFacts->addInstallmentResult(12500.50);

    expect($this->retrievedFacts->installmentResults())->toHaveCount(1)
        ->and($this->retrievedFacts->allNumericFacts())->toContain(12501);
});

it('clears facts', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000]);
    $this->retrievedFacts->clear();

    expect($this->retrievedFacts->isEmpty())->toBeTrue()
        ->and($this->retrievedFacts->unitIds())->toBeEmpty();
});

// ─── SearchPropertiesTool: structured no_matches ─────────────────

it('returns structured no_matches signal when no units found', function () {
    $company = Company::factory()->create();
    $tool = new SearchPropertiesTool($company->id, 0, '+200000000000');

    $result = $tool(1000);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('status', 'no_matches')
        ->and($decoded)->toHaveKey('message')
        ->and(app(RetrievedFacts::class)->isEmpty())->toBeTrue();
});

it('returns unit records with unit_id field', function () {
    $company = Company::factory()->create();
    Unit::factory()->create(['company_id' => $company->id, 'price' => 500000, 'status' => UnitStatus::Available]);

    $tool = new SearchPropertiesTool($company->id, 0, '+200000000000');
    $result = $tool(1000000);

    $decoded = json_decode($result, true);

    expect($decoded)->toBeArray()
        ->and($decoded[0])->toHaveKey('unit_id')
        ->and($decoded[0])->toHaveKey('price')
        ->and($decoded[0])->toHaveKey('area');
});

// ─── GroundingVerifier: detection logic ──────────────────────────

it('passes clean text with no facts retrieved', function () {
    $this->retrievedFacts->clear();

    expect($this->verifier->verify('مرحباً، كيف يمكنني مساعدتك؟'))->toBeNull();
});

it('passes text with no-match honesty keywords', function () {
    $this->retrievedFacts->clear();

    $text = 'ماعنديش وحدة بالمواصفات دي حالياً، تحب أوصّلك بأحد مستشارينا؟';

    expect($this->verifier->verify($text))->toBeNull();
});

it('passes text with valid facts', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'area' => 120, 'rooms' => 3]);

    expect($this->verifier->verify('الوحدة رقم ١ سعرها ٥٠٠٠٠٠ جنيه ومساحتها ١٢٠ متر'))->toBeNull();

    expect($this->verifier->verify('الوحدة رقم 1 سعرها 500,000 جنيه ومساحتها 120 متر و 3 غرف'))->toBeNull();
});

it('blocks text with fabricated price', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'area' => 120, 'rooms' => 3]);

    $violation = $this->verifier->verify('الوحدة دي سعرها 750000 جنيه');

    expect($violation)->not->toBeNull()
        ->and($violation)->toContain('750000');
});

it('passes text with valid installment results', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'down_payment' => 100000, 'installment_years' => 10]);
    $this->retrievedFacts->addInstallmentResult(3333.33);

    expect($this->verifier->verify('القسط الشهري 3333 جنيه'))->toBeNull();
});

it('blocks text with mismatched installment', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'down_payment' => 100000, 'installment_years' => 10]);
    $this->retrievedFacts->addInstallmentResult(3333.33);

    $violation = $this->verifier->verify('القسط الشهري 5000 جنيه');

    expect($violation)->not->toBeNull()
        ->and($violation)->toContain('5000');
});

// ─── Fabricated-number block (Layer 3) ───────────────────────────

it('logs a grounding violation', function () {
    $company = Company::factory()->create();
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000]);

    $this->verifier->logViolation(
        company: $company,
        conversation: null,
        modelUsed: 'claude-haiku-4-5',
        originalText: 'سعر الوحدة 999999 جنيه',
        safeFallbackText: null,
        actionTaken: 'blocked',
        violationReason: 'Fabricated price 999999',
    );

    expect(GroundingViolation::count())->toBe(1)
        ->and(GroundingViolation::first()->action_taken)->toBe('blocked')
        ->and(GroundingViolation::first()->company_id)->toBe($company->id);
});

it('logs regenerated and fallback_sent violations', function () {
    $company = Company::factory()->create();

    $this->verifier->logViolation($company, null, 'gpt-4o', 'fake text', null, 'regenerated', 'Fabricated price');
    $this->verifier->logViolation($company, null, 'gpt-4o-mini', 'fake text 2', 'safe fallback', 'fallback_sent', 'Regeneration failed');

    expect(GroundingViolation::count())->toBe(2);
    expect(GroundingViolation::where('action_taken', 'regenerated')->exists())->toBeTrue();
    expect(GroundingViolation::where('action_taken', 'fallback_sent')->exists())->toBeTrue();
});

// ─── Cross-tenant rejection ──────────────────────────────────────

it('scopes unit search to company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    Unit::factory()->create(['company_id' => $companyA->id, 'price' => 300000, 'status' => UnitStatus::Available]);
    Unit::factory()->create(['company_id' => $companyB->id, 'price' => 500000, 'status' => UnitStatus::Available]);

    $tool = new SearchPropertiesTool($companyA->id, 0, '+200000000000');
    $result = $tool(1000000);

    $decoded = json_decode($result, true);

    expect($decoded)->not->toHaveKey('status', 'no_matches');

    if (isset($decoded[0])) {
        expect($decoded[0]['unit_id'])->not->toBe(Unit::where('company_id', $companyB->id)->first()->id);
    }
});

// ─── Reserved/sold exclusion ─────────────────────────────────────

it('only returns available units', function () {
    $company = Company::factory()->create();
    $available = Unit::factory()->create(['company_id' => $company->id, 'price' => 300000, 'status' => UnitStatus::Available]);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 300000, 'status' => UnitStatus::Sold]);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 300000, 'status' => UnitStatus::Reserved]);

    $tool = new SearchPropertiesTool($company->id, 0, '+200000000000');
    $result = $tool(1000000);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveCount(1)
        ->and($decoded[0]['unit_id'])->toBe($available->id);
});

// ─── Installment integrity ───────────────────────────────────────

it('calculate_installment uses real values', function () {
    $company = Company::factory()->create();
    $tool = new CalculateInstallmentTool($company->id, 0, '+200000000000');

    $result = $tool(500000, 100000, 10);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('monthly_payment')
        ->and((float) $decoded['monthly_payment'])->toBe(3333.33);
});

it('rejects invalid installment inputs', function () {
    $company = Company::factory()->create();
    $tool = new CalculateInstallmentTool($company->id, 0, '+200000000000');

    $result = $tool(100000, 100000, 10);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('error');

    $result2 = $tool(100000, 0, -1);
    $decoded2 = json_decode($result2, true);

    expect($decoded2)->toHaveKey('error');
});

// ─── Digit/format robustness ─────────────────────────────────────

it('verifies facts with Arabic-Indic digits', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'area' => 120]);

    expect($this->verifier->verify('الوحدة رقم ١ سعرها ٥٠٠٠٠٠ جنيه ومساحتها ١٢٠ متر'))->toBeNull();
});

it('verifies facts with mixed digit formats', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 500000, 'area' => 120, 'rooms' => 3]);

    $text = 'سعر الوحدة $500000 ومساحتها 120 متر و 3 غرف';

    expect($this->verifier->verify($text))->toBeNull();
});

it('blocks fabricated facts with Arabic-Indic digits', function () {
    $this->retrievedFacts->addUnit(1, ['unit_id' => 1, 'price' => 300000, 'area' => 100]);

    $violation = $this->verifier->verify('سعر الوحدة ٧٥٠٠٠٠ جنيه');

    expect($violation)->not->toBeNull();
});

// ─── Grounding fallback translation ──────────────────────────────

it('has grounding fallback translation', function () {
    expect(__('bot.grounding_fallback', [], 'ar'))->toBe('اسمحلي أتأكد من التفاصيل دي وأرجعلك.');
    expect(__('bot.grounding_fallback', [], 'en'))->toBe('Excuse me, let me check those details and get back to you.');
});

// ─── No-match honesty via tool ───────────────────────────────────

it('search_properties returns no_matches for empty inventory', function () {
    $company = Company::factory()->create();

    $tool = new SearchPropertiesTool($company->id, 0, '+200000000000');
    $result = $tool(1000000);

    $decoded = json_decode($result, true);

    expect($decoded)->toHaveKey('status', 'no_matches');
});
