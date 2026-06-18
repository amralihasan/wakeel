<?php

use App\Enums\ConversationMode;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Enums\VisitStatus;
use App\Jobs\SendWhatsAppTemplate;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\FollowUpLog;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Visit;
use App\Services\Agent\AgentRunner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    Queue::fake();
});

it('sends personalized AI follow-up for 23h silence sequence', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-23h',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => true,
            'max_follow_ups' => 3,
        ],
    ]);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'customer_phone' => '+201234567890',
        'status' => 'new',
    ]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subHours(23.5),
    ]);

    // Create outbound last message
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'آخر رسالة من البوت',
        'created_at' => now()->subHours(23.5),
    ]);

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'رسالة متابعة ذكية بعد 23 ساعة',
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(10, 20),
        meta: new Meta('fake-id', 'fake-model'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    // Run command
    Artisan::call('app:process-followups');

    // Assert AgentRunner generated follow-up
    Queue::assertPushed(SendWhatsAppText::class, function ($job) {
        return $job->channelId === 'ch-test-23h'
            && $job->to === '+201234567890'
            && $job->body === 'رسالة متابعة ذكية بعد 23 ساعة';
    });

    expect(FollowUpLog::where('sequence_type', 'silence_23h')->count())->toBe(1)
        ->and(Message::where('conversation_id', $conversation->id)->count())->toBe(2);
});

it('sends template follow-up for 72h silence sequence', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-72h',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => true,
            'max_follow_ups' => 3,
        ],
    ]);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'customer_phone' => '+201234567891',
        'name' => 'أحمد',
        'status' => 'new',
    ]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567891',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subHours(72.5),
    ]);

    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'آخر رسالة من البوت قبل 72 ساعة',
        'created_at' => now()->subHours(72.5),
    ]);

    Artisan::call('app:process-followups');

    Queue::assertPushed(SendWhatsAppTemplate::class, function ($job) {
        return $job->channelId === 'ch-test-72h'
            && $job->to === '+201234567891'
            && $job->templateName === 'silence_72h_template'
            && $job->languageCode === 'ar'
            && $job->components[0]['parameters'][0]['text'] === 'أحمد';
    });

    expect(FollowUpLog::where('sequence_type', 'silence_72h')->count())->toBe(1);

    $latestMsg = Message::orderBy('id', 'desc')->first();
    expect($latestMsg->body)->toContain('أحمد')
        ->and($latestMsg->body)->toContain('أحدث الوحدات');
});

it('sends visit no-show template follow-up', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-noshow',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => true,
        ],
    ]);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'customer_phone' => '+201234567892',
        'name' => 'سارة',
        'status' => 'new',
    ]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567892',
        'mode' => ConversationMode::Bot,
    ]);

    $visit = Visit::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'status' => VisitStatus::NoShow,
    ]);

    Artisan::call('app:process-followups');

    Queue::assertPushed(SendWhatsAppTemplate::class, function ($job) {
        return $job->channelId === 'ch-test-noshow'
            && $job->to === '+201234567892'
            && $job->templateName === 'visit_no_show_template'
            && $job->components[0]['parameters'][0]['text'] === 'سارة';
    });

    expect(FollowUpLog::where('sequence_type', 'no_show')->count())->toBe(1)
        ->and(FollowUpLog::where('sequence_type', 'no_show')->first()->visit_id)->toBe($visit->id);
});

it('sends cold nurture template follow-up after 7 days idle', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-cold',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => true,
        ],
    ]);

    $lead = Lead::factory()->create([
        'company_id' => $company->id,
        'customer_phone' => '+201234567893',
        'name' => 'محمد',
        'status' => 'new',
    ]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567893',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subDays(8),
    ]);

    Artisan::call('app:process-followups');

    Queue::assertPushed(SendWhatsAppTemplate::class, function ($job) {
        return $job->channelId === 'ch-test-cold'
            && $job->to === '+201234567893'
            && $job->templateName === 'cold_nurture_template'
            && $job->components[0]['parameters'][0]['text'] === 'محمد';
    });

    expect(FollowUpLog::where('sequence_type', 'cold_nurture')->count())->toBe(1);
});

it('respects safety constraints and follow-up caps', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-safety',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => true,
            'max_follow_ups' => 1,
        ],
    ]);

    // 1. In human mode
    $lead1 = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567894', 'status' => 'new']);
    $conversation1 = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead1->id,
        'customer_phone' => '+201234567894',
        'mode' => ConversationMode::Human,
        'last_message_at' => now()->subHours(23.5),
    ]);
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation1->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'message',
    ]);

    // 2. Converted/Closed status
    $lead2 = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567895', 'status' => 'closed']);
    $conversation2 = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead2->id,
        'customer_phone' => '+201234567895',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subHours(23.5),
    ]);
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation2->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'message',
    ]);

    // 3. Exceeded cap
    $lead3 = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567896', 'status' => 'new']);
    $conversation3 = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead3->id,
        'customer_phone' => '+201234567896',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subHours(23.5),
    ]);
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation3->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'message',
    ]);
    FollowUpLog::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation3->id,
        'lead_id' => $lead3->id,
        'sequence_type' => 'some_previous_seq',
    ]);

    Artisan::call('app:process-followups');

    Queue::assertNothingPushed();
});

it('does not send follow-ups when follow_ups_enabled is false', function () {
    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-test-disabled',
        'bot_settings' => [
            'active' => true,
            'follow_ups_enabled' => false,
        ],
    ]);

    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567897', 'status' => 'new']);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567897',
        'mode' => ConversationMode::Bot,
        'last_message_at' => now()->subHours(23.5),
    ]);

    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'hello',
    ]);

    Artisan::call('app:process-followups');

    Queue::assertNothingPushed();
});
