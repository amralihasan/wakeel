<?php

use App\Enums\SubscriptionStatus;
use App\Events\PlanChanged;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCanceled;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionPastDue;
use App\Events\SubscriptionRenewed;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\Unit;
use App\Models\User;
use App\Services\ConversationUsage;
use App\Services\PlanCatalog;
use App\Services\PlanGate;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

// ─── State Machine ───────────────────────────────────────────────

it('rejects illegal state transitions', function () {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Expired]);

    expect(fn () => $subscription->transitionTo(SubscriptionStatus::Active))
        ->toThrow(InvalidArgumentException::class);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('accepts legal state transitions and fires events', function () {
    $subscription = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);

    $subscription->transitionTo(SubscriptionStatus::Canceled);

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Canceled);

    Event::assertDispatched(SubscriptionCanceled::class, function ($event) use ($subscription) {
        return $event->subscription->id === $subscription->id
            && $event->previousStatus === SubscriptionStatus::Active;
    });
});

it('fires correct event for each transition', function () {
    // trialing → active
    $sub = Subscription::factory()->trialing()->create();
    $sub->transitionTo(SubscriptionStatus::Active);
    Event::assertDispatched(SubscriptionActivated::class);

    // active → past_due
    $sub = Subscription::factory()->create();
    $sub->transitionTo(SubscriptionStatus::PastDue);
    Event::assertDispatched(SubscriptionPastDue::class);

    // past_due → expired
    $sub = Subscription::factory()->pastDue()->create();
    $sub->transitionTo(SubscriptionStatus::Expired);
    Event::assertDispatched(SubscriptionExpired::class);
});

it('lists all allowed transitions correctly', function () {
    expect(SubscriptionStatus::Trialing->canTransitionTo(SubscriptionStatus::Active))->toBeTrue();
    expect(SubscriptionStatus::Trialing->canTransitionTo(SubscriptionStatus::Canceled))->toBeTrue();
    expect(SubscriptionStatus::Trialing->canTransitionTo(SubscriptionStatus::Expired))->toBeTrue();
    expect(SubscriptionStatus::Trialing->canTransitionTo(SubscriptionStatus::PastDue))->toBeFalse();

    expect(SubscriptionStatus::Active->canTransitionTo(SubscriptionStatus::PastDue))->toBeTrue();
    expect(SubscriptionStatus::Active->canTransitionTo(SubscriptionStatus::Canceled))->toBeTrue();
    expect(SubscriptionStatus::Active->canTransitionTo(SubscriptionStatus::Trialing))->toBeFalse();

    expect(SubscriptionStatus::PastDue->canTransitionTo(SubscriptionStatus::Active))->toBeTrue();
    expect(SubscriptionStatus::PastDue->canTransitionTo(SubscriptionStatus::Expired))->toBeTrue();

    expect(SubscriptionStatus::Canceled->canTransitionTo(SubscriptionStatus::Active))->toBeFalse();
    expect(SubscriptionStatus::Expired->canTransitionTo(SubscriptionStatus::Active))->toBeFalse();
});

// ─── SubscriptionManager idempotence ─────────────────────────────

it('can start a trial', function () {
    $company = Company::factory()->create();
    $manager = app(SubscriptionManager::class);

    $subscription = $manager->startTrial($company, 'starter');

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->plan_key)->toBe('starter')
        ->and($subscription->trial_ends_at)->not->toBeNull();
});

it('can activate a subscription', function () {
    $company = Company::factory()->create();
    $manager = app(SubscriptionManager::class);

    $subscription = $manager->activate($company, 'growth', 'card', 'paymob');

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->plan_key)->toBe('growth')
        ->and($subscription->payment_method)->toBe('card')
        ->and($subscription->company->is_active)->toBeTrue();
});

it('activate is idempotent - updates existing', function () {
    $company = Company::factory()->create();
    $manager = app(SubscriptionManager::class);

    $first = $manager->activate($company, 'starter', 'card', 'paymob');
    $second = $manager->activate($company, 'growth', 'wallet', 'paymob');

    expect($second->id)->toBe($first->id)
        ->and($second->plan_key)->toBe('growth');
});

it('can renew a subscription', function () {
    $company = Company::factory()->create();
    $manager = app(SubscriptionManager::class);
    $subscription = $manager->activate($company, 'starter', 'card', 'paymob');

    $now = now();
    $renewed = $manager->renew($subscription, $now, $now->copy()->addMonth());

    expect($renewed->status)->toBe(SubscriptionStatus::Active)
        ->and($renewed->current_period_start->toDateString())->toBe($now->toDateString());

    Event::assertDispatched(SubscriptionRenewed::class);
});

it('can mark a subscription past due', function () {
    $subscription = Subscription::factory()->create();
    $manager = app(SubscriptionManager::class);

    $pastDue = $manager->markPastDue($subscription);

    expect($pastDue->status)->toBe(SubscriptionStatus::PastDue)
        ->and($pastDue->grace_ends_at)->not->toBeNull();
});

it('can cancel at period end', function () {
    $subscription = Subscription::factory()->create();
    $manager = app(SubscriptionManager::class);

    $result = $manager->cancel($subscription, atPeriodEnd: true);
    expect($result->cancel_at_period_end)->toBeTrue()
        ->and($result->status)->not->toBe(SubscriptionStatus::Canceled);
});

it('can cancel immediately', function () {
    $subscription = Subscription::factory()->create();
    $manager = app(SubscriptionManager::class);

    $result = $manager->cancel($subscription, atPeriodEnd: false);
    expect($result->status)->toBe(SubscriptionStatus::Canceled)
        ->and($result->company->is_active)->toBeFalse();
});

it('can expire a subscription', function () {
    $subscription = Subscription::factory()->pastDue()->create();
    $manager = app(SubscriptionManager::class);

    $result = $manager->expire($subscription);

    expect($result->status)->toBe(SubscriptionStatus::Expired)
        ->and($result->company->is_active)->toBeFalse();
});

it('can change plan', function () {
    $subscription = Subscription::factory()->create(['plan_key' => 'starter']);
    $manager = app(SubscriptionManager::class);

    $updated = $manager->changePlan($subscription, 'growth');

    expect($updated->plan_key)->toBe('growth');

    Event::assertDispatched(PlanChanged::class);
});

it('rejects changing to non-existent plan', function () {
    $subscription = Subscription::factory()->create();
    $manager = app(SubscriptionManager::class);

    expect(fn () => $manager->changePlan($subscription, 'nonexistent'))
        ->toThrow(InvalidArgumentException::class);
});

it('can resume a subscription scheduled for cancellation', function () {
    $subscription = Subscription::factory()->create(['cancel_at_period_end' => true]);
    $manager = app(SubscriptionManager::class);

    $result = $manager->resume($subscription);

    expect($result->cancel_at_period_end)->toBeFalse();
});

// ─── PlanGate ────────────────────────────────────────────────────

it('blocks adding units beyond plan limit', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'starter',
    ]);

    $gate = app(PlanGate::class);
    Unit::factory(10)->create(['company_id' => $company->id]);

    expect($gate->withinLimit($company, 'units', 1))->toBeFalse();
});

it('blocks adding sales reps beyond plan limit', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'starter',
    ]);

    $gate = app(PlanGate::class);
    User::factory()->create(['company_id' => $company->id, 'role' => 'sales_rep']);

    expect($gate->withinLimit($company, 'reps', 1))->toBeFalse();
});

it('allows unlimited limits (null) to never block', function () {
    $company = Company::factory()->create(['plan' => 'growth']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'growth',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->withinLimit($company, 'units', 100))->toBeTrue();
});

it('returns remaining capacity correctly', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'starter',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->remaining($company, 'units'))->toBe(10);
});

it('returns INF for unlimited limits', function () {
    $company = Company::factory()->create(['plan' => 'growth']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'growth',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->remaining($company, 'units'))->toBe(PHP_INT_MAX);
});

// ─── Quota Metering ──────────────────────────────────────────────

it('increments conversation usage once per billable conversation per period', function () {
    $company = Company::factory()->create();
    $company->ensureCurrentBillingCycle();

    $conversation = Conversation::factory()->create(['company_id' => $company->id]);
    $usage = app(ConversationUsage::class);

    $usage->increment($company, $conversation);
    expect($usage->quotaUsed($company->fresh()))->toBe(1);

    $usage->increment($company, $conversation);
    expect($usage->quotaUsed($company->fresh()))->toBe(1);
});

it('resets conversation usage at period boundary', function () {
    $company = Company::factory()->create();
    $company->ensureCurrentBillingCycle();
    $company->update(['conversations_count' => 50]);

    $usage = app(ConversationUsage::class);
    $usage->resetForPeriod($company);

    expect($usage->quotaUsed($company->fresh()))->toBe(0);
});

it('quotaRemaining returns correct remaining count', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'starter',
    ]);

    $company->update(['conversations_count' => 100]);
    $usage = app(ConversationUsage::class);

    expect($usage->quotaRemaining($company))->toBe(400);
});

it('flags when over quota', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    Subscription::factory()->create([
        'company_id' => $company->id,
        'plan_key' => 'starter',
    ]);
    $company->update(['conversations_count' => 500]);

    expect(app(ConversationUsage::class)->isOverQuota($company))->toBeTrue();
});

// ─── End-to-end: Fake gateway drives lifecycle ────────────────────

it('fake gateway drives activate → renew → past_due → expire', function () {
    $manager = app(SubscriptionManager::class);
    $company = Company::factory()->create();

    $now = now();

    // activate
    $sub = $manager->activate($company, 'starter', 'card', 'fake_gateway', 'tok_test');
    expect($sub->status)->toBe(SubscriptionStatus::Active);

    // renew
    $sub = $manager->renew($sub, $now, $now->copy()->addMonth());
    expect($sub->status)->toBe(SubscriptionStatus::Active);

    // mark past due
    $sub = $manager->markPastDue($sub);
    expect($sub->status)->toBe(SubscriptionStatus::PastDue)
        ->and($sub->grace_ends_at)->not->toBeNull();

    // expire
    $sub = $manager->expire($sub);
    expect($sub->status)->toBe(SubscriptionStatus::Expired)
        ->and($sub->company->is_active)->toBeFalse();
});

// ─── Subscription model helpers ──────────────────────────────────

it('isActive returns true for active and trialing statuses', function () {
    $active = Subscription::factory()->create(['status' => SubscriptionStatus::Active]);
    $trialing = Subscription::factory()->trialing()->create();
    $pastDue = Subscription::factory()->pastDue()->create();

    expect($active->isActive())->toBeTrue();
    expect($trialing->isActive())->toBeTrue();
    expect($pastDue->isActive())->toBeFalse();
});

it('onTrial returns true only during trial period', function () {
    $onTrial = Subscription::factory()->trialing()->create();
    expect($onTrial->onTrial())->toBeTrue();

    $expired = Subscription::factory()->trialing()->create(['trial_ends_at' => now()->subDay()]);
    expect($expired->onTrial())->toBeFalse();
});

it('hasEnded returns true for terminal statuses', function () {
    $canceled = Subscription::factory()->canceled()->create();
    $expired = Subscription::factory()->expired()->create();

    expect($canceled->hasEnded())->toBeTrue();
    expect($expired->hasEnded())->toBeTrue();
    expect(Subscription::factory()->create()->hasEnded())->toBeFalse();
});

// ─── PlanCatalog ─────────────────────────────────────────────────

it('PlanCatalog returns plan details', function () {
    $catalog = app(PlanCatalog::class);

    $starter = $catalog->find('starter');

    expect($starter)->not->toBeNull()
        ->and($starter['key'])->toBe('starter')
        ->and($starter['price_cents'])->toBe(9900)
        ->and($starter['limits']['conversation_quota'])->toBe(500)
        ->and($starter['limits']['units'])->toBe(10);
});

it('PlanCatalog returns null for non-existent plan', function () {
    expect(app(PlanCatalog::class)->find('nonexistent'))->toBeNull();
});

it('PlanCatalog lists all plan keys', function () {
    expect(app(PlanCatalog::class)->keys())->toBe(['starter', 'growth', 'enterprise']);
});
