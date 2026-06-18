<?php

use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('prevents adding units beyond plan limit', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    Unit::factory(10)->create(['company_id' => $company->id]);

    expect($company->hasReachedUnitsLimit())->toBeTrue();

    Livewire::test('pages::dashboard.units')
        ->set('title', 'وحدة جديدة')
        ->set('description', 'وصف')
        ->set('type', 'apartment')
        ->set('rooms', 3)
        ->set('area', 150)
        ->set('price', 1000000)
        ->set('location', 'القاهرة')
        ->set('status', 'available')
        ->call('save')
        ->assertHasErrors('title');
});

it('prevents adding sales reps beyond plan limit', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $rep = User::factory()->create(['company_id' => $company->id]); // default SalesRep

    expect($company->fresh()->hasReachedRepsLimit())->toBeTrue();

    Livewire::test('pages::dashboard.team')
        ->set('name', 'مندوب جديد')
        ->set('email', 'rep2@test.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertHasErrors('email');
});

it('increments conversation count on first outbound reply and resets on rollover', function () {
    $company = Company::factory()->create();
    $conversation = Conversation::factory()->create(['company_id' => $company->id]);

    // Create an inbound message first
    Message::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Inbound,
        'sender' => MessageSender::Customer,
        'created_at' => now(),
    ]);

    // First outbound — should increment
    Message::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'created_at' => now(),
    ]);

    expect($company->fresh()->conversations_count)->toBe(1);

    // Second outbound in same conversation — should NOT increment
    Message::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'created_at' => now(),
    ]);

    expect($company->fresh()->conversations_count)->toBe(1);

    // Manually expire the billing cycle and run rollover
    $company->update([
        'billing_cycle_end' => now()->subDay(),
        'conversations_count' => 1,
    ]);

    $this->artisan('billing:rollover');

    $company->refresh();
    expect($company->conversations_count)->toBe(0)
        ->and($company->billing_cycle_start->toDateString())->toBe(now()->toDateString());
});
