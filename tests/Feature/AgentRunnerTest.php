<?php

use App\Enums\ConversationMode;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Services\Agent\AgentRunner;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\PrismManager;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    Queue::fake();
});

it('executes standard bot flow and dispatches response', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567890', 'name' => 'أحمد']);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'مرحباً أحمد! كيف يمكنني مساعدتك في البحث عن عقار اليوم؟',
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(10, 20),
        meta: new Meta('fake-id', 'fake-model'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    app(AgentRunner::class)->handle($company, '+201234567890', 'مرحباً');

    Queue::assertPushed(SendWhatsAppText::class, function ($job) {
        return $job->channelId === 'ch-test'
            && $job->to === '+201234567890'
            && $job->body === 'مرحباً أحمد! كيف يمكنني مساعدتك في البحث عن عقار اليوم؟';
    });

    $message = Message::where('conversation_id', $conversation->id)
        ->where('direction', MessageDirection::Outbound)
        ->where('sender', MessageSender::Bot)
        ->first();

    expect($message)->not->toBeNull()
        ->and($message->body)->toBe('مرحباً أحمد! كيف يمكنني مساعدتك في البحث عن عقار اليوم؟');

    $session = app(ConversationSession::class);
    $session->setCompany($company);
    $session->setPhone('+201234567890');
    $history = $session->history();

    expect($history)->toHaveCount(1)
        ->and($history[0]['role'])->toBe('assistant')
        ->and($history[0]['content'])->toBe('مرحباً أحمد! كيف يمكنني مساعدتك في البحث عن عقار اليوم؟');
});

it('handles escalated conversation without errors', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567890']);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::PendingHandoff,
    ]);

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'جاري تحويلك لأحد وكلائنا.',
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(5, 10),
        meta: new Meta('fake-id', 'fake-model'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    app(AgentRunner::class)->handle($company, '+201234567890', 'أريد التحدث مع وكيل');

    Queue::assertPushed(SendWhatsAppText::class);

    $conversation->refresh();
    expect($conversation->mode)->toBe(ConversationMode::PendingHandoff);
});

it('respects concurrency lock and exits early', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);

    $lock = Cache::lock("agent_runner:{$company->id}:+201234567890", 30);
    $lock->get();

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'should not be sent',
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(0, 0),
        meta: new Meta('fake', 'fake'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    app(AgentRunner::class)->handle($company, '+201234567890', 'test');

    Queue::assertNothingPushed();

    $lock->release();
});

it('sends fallback message on prism exception', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201234567890']);
    Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $fakeManager = new class extends PrismManager
    {
        public function __construct() {}

        public function resolve(Provider|string $name, array $providerConfig = []): \Prism\Prism\Providers\Provider
        {
            throw new Exception('API Error');
        }
    };
    app()->instance(PrismManager::class, $fakeManager);

    app(AgentRunner::class)->handle($company, '+201234567890', 'سبب خطأ');

    Queue::assertPushed(SendWhatsAppText::class, function ($job) {
        return $job->body === 'معلش حصل خطأ بسيط، ممكن تعيد رسالتك؟';
    });
});
