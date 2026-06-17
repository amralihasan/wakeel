<?php

use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Events\ConversationEscalated;
use App\Events\InboundMessageReceived;
use App\Jobs\ProcessInboundMessageJob;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Queue::fake();
});

it('broadcasts ConversationEscalated on private channel', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $conversation = Conversation::factory()->create(['company_id' => $company->id, 'lead_id' => $lead->id]);
    $handoff = Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
        'status' => HandoffStatus::Waiting,
    ]);

    $event = new ConversationEscalated($handoff);

    expect($event)->toBeInstanceOf(ShouldBroadcast::class);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class);

    expect($event->broadcastAs())->toBe('conversation.escalated');
});

it('claims a handoff and switches conversation to human mode', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567890']);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $handoff = Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
        'status' => HandoffStatus::Waiting,
        'reason' => 'طلب عميل',
        'ai_summary' => 'العميل يريد التحدث مع وكيل',
    ]);

    Livewire::test('pages::dashboard.conversations')
        ->call('claimHandoff', $handoff->id)
        ->assertSet('activeConversationId', $conversation->id);

    $handoff->refresh();
    expect($handoff->status)->toBe(HandoffStatus::Active)
        ->and($handoff->agent_id)->toBe($user->id)
        ->and($handoff->claimed_at)->not->toBeNull();

    $conversation->refresh();
    expect($conversation->mode)->toBe(ConversationMode::Human)
        ->and($conversation->assigned_rep_id)->toBe($user->id);
});

it('suppresses bot responses when conversation is in human mode', function () {
    Event::fake();

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567890']);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $handoff = Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
        'status' => HandoffStatus::Waiting,
    ]);

    // Claim the handoff — sets session mode to 'human'
    Livewire::test('pages::dashboard.conversations')
        ->call('claimHandoff', $handoff->id);

    // Dispatch inbound message after mode is human
    $job = new ProcessInboundMessageJob($company->id, '+201234567890', 'مرحباً');
    $job->handle();

    // AgentRunner should not have been called
    Queue::assertNothingPushed();

    // InboundMessageReceived should have been dispatched for dashboard
    Event::assertDispatched(InboundMessageReceived::class, function ($event) use ($company) {
        return $event->companyId === $company->id
            && $event->customerPhone === '+201234567890'
            && $event->body === 'مرحباً';
    });
});

it('dispatches outbound messages from rep', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Human,
    ]);

    Livewire::test('pages::dashboard.conversations')
        ->set('activeConversationId', $conversation->id)
        ->set('replyText', 'مرحباً، كيف يمكنني مساعدتك؟')
        ->call('sendMessage')
        ->assertSet('replyText', '');

    Queue::assertPushed(SendWhatsAppText::class, function ($job) use ($company) {
        return $job->channelId === $company->dialog360_channel_id
            && $job->to === '+201234567890'
            && $job->body === 'مرحباً، كيف يمكنني مساعدتك؟';
    });

    $message = Message::where('conversation_id', $conversation->id)
        ->where('sender', MessageSender::Rep)
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('مرحباً، كيف يمكنني مساعدتك؟')
        ->and($message->direction)->toBe(MessageDirection::Outbound);
});

it('resolves conversation and returns to bot mode', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Human,
        'assigned_rep_id' => $user->id,
    ]);

    $handoff = Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
        'status' => HandoffStatus::Active,
        'agent_id' => $user->id,
        'claimed_at' => now(),
    ]);

    Livewire::test('pages::dashboard.conversations')
        ->set('activeConversationId', $conversation->id)
        ->set('resolutionNotes', 'تم الرد على استفسار العميل')
        ->call('resolveConversation')
        ->assertSet('activeConversationId', null)
        ->assertSet('resolutionNotes', '');

    $handoff->refresh();
    expect($handoff->status)->toBe(HandoffStatus::Resolved)
        ->and($handoff->resolved_at)->not->toBeNull()
        ->and($handoff->resolution_notes)->toBe('تم الرد على استفسار العميل');

    $conversation->refresh();
    expect($conversation->mode)->toBe(ConversationMode::Bot)
        ->and($conversation->assigned_rep_id)->toBeNull();

    Queue::assertPushed(SendWhatsAppText::class, function ($job) use ($company) {
        return $job->channelId === $company->dialog360_channel_id
            && $job->to === '+201234567890'
            && $job->body === 'أقدر أساعدك في حاجة تانية؟';
    });
});
