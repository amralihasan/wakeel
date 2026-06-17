<?php

use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Enums\LeadTier;
use App\Events\LeadBecameHot;
use App\Events\VisitBooked;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use App\Services\Agent\AgentRunner;
use App\Services\Leads\LeadScoringService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ToolCall;
use Prism\Prism\ValueObjects\Usage;

use function Pest\Laravel\actingAs;

it('applies signals idempotently', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'score' => 0,
        'scored_signals' => [],
    ]);

    $service = app(LeadScoringService::class);

    $lead = $service->applySignals($lead, ['stated_budget' => true, 'asked_price' => true]);
    expect($lead->score)->toBe(55)
        ->and($lead->tier)->toBe(LeadTier::Warm);

    $lead = $service->applySignals($lead, ['stated_budget' => true, 'asked_price' => true, 'booked_visit' => true]);
    expect($lead->score)->toBe(85) // 55 + 30, not double-counting
        ->and($lead->tier)->toBe(LeadTier::Hot);
});

it('dispatches LeadBecameHot only on tier transition to hot', function () {
    Event::fake();

    $company = Company::factory()->create();
    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'score' => 0,
        'tier' => LeadTier::Cold,
        'scored_signals' => [],
    ]);

    $service = app(LeadScoringService::class);

    $service->applySignals($lead, ['general_inquiry' => true]);

    Event::assertNotDispatched(LeadBecameHot::class);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'score' => 60,
        'tier' => LeadTier::Warm,
        'scored_signals' => [],
    ]);

    $service->applySignals($lead, ['stated_budget' => true, 'asked_price' => true]);

    Event::assertDispatched(LeadBecameHot::class, function ($event) use ($lead) {
        return $event->lead->id === $lead->id;
    });
});

it('applies booked_visit signal when visit is booked', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'score' => 20,
        'tier' => LeadTier::Cold,
        'scored_signals' => [],
    ]);

    $unit = Unit::factory()->create(['company_id' => $company->id]);

    Visit::create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'unit_id' => $unit->id,
        'scheduled_at' => Carbon::tomorrow('Africa/Cairo'),
        'status' => 'pending',
    ]);

    // VisitBooked is dispatched by BookVisitTool; we dispatch it directly to test the listener chain
    VisitBooked::dispatch($visit = Visit::where('lead_id', $lead->id)->first());

    $lead->refresh();
    expect($lead->score)->toBe(50) // 20 + 30
        ->and($lead->tier)->toBe(LeadTier::Warm);
});

it('auto-escalates when lead becomes hot', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'score' => 60,
        'tier' => LeadTier::Warm,
        'scored_signals' => [],
    ]);

    Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => $lead->customer_phone,
        'mode' => ConversationMode::Bot,
    ]);

    $service = app(LeadScoringService::class);
    $service->applySignals($lead, ['stated_budget' => true, 'asked_price' => true]);

    $conversation = $lead->fresh()->conversation;
    expect($conversation->mode)->toBe(ConversationMode::PendingHandoff);

    $handoff = Handoff::where('lead_id', $lead->id)->first();
    expect($handoff)->not->toBeNull()
        ->and($handoff->status->value)->toBe(HandoffStatus::Waiting->value)
        ->and($handoff->reason)->toBe('تأهيل تلقائي: تجاوز العميل درجة الاهتمام المطلوبة');
});

it('infers signals from tool calls in agent runner', function () {
    Queue::fake();

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'customer_phone' => '+201234567890',
        'score' => 0,
        'budget_max' => null,
        'scored_signals' => [],
    ]);

    Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'تم إرسال الصور بنجاح',
        finishReason: FinishReason::Stop,
        toolCalls: [
            new ToolCall('call-1', 'send_unit_media', '{"unit_id": 1, "media_type": "images"}'),
        ],
        toolResults: [],
        usage: new Usage(10, 5),
        meta: new Meta('fake', 'fake'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    app(AgentRunner::class)->handle($company, '+201234567890', 'أرسل الصور');

    $lead->refresh();
    expect($lead->score)->toBe(10) // asked_media = 10 points
        ->and(in_array('asked_media', $lead->scored_signals))->toBeTrue();
});
