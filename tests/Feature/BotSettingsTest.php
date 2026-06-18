<?php

use App\Enums\ConversationMode;
use App\Enums\MessageDirection;
use App\Jobs\ProcessInboundMessageJob;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppChannel;
use App\Services\Agent\SystemPromptBuilder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Queue::fake();
});

it('redirects incomplete onboarding users to onboarding page', function () {
    $company = Company::factory()->create(['onboarding_completed' => false]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    get(route('dashboard'))
        ->assertRedirect(route('onboarding.index'));
});

it('allows dashboard access after onboarding completion', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    get(route('dashboard'))
        ->assertOk();
});

it('completes onboarding wizard successfully', function () {
    $company = Company::factory()->create(['onboarding_completed' => false]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    // Seed whatsapp channels pool for step 3
    WhatsAppChannel::create(['number' => 'ch-test-123', 'channel_id' => 'ch-test-123', 'status' => 'available']);

    Livewire::test('pages::dashboard.onboarding')
        ->set('companyName', 'الشركة الجديدة')
        ->set('companyEmail', 'new@company.com')
        ->set('companyPhone', '+201234567890')
        ->call('nextStep') // Step 1 -> Step 2
        ->assertSet('step', 2)
        ->call('nextStep') // Step 2 -> Step 3 (runs provisioning)
        ->assertSet('step', 3)
        ->assertSet('whatsappNumber', 'ch-test-123')
        ->call('nextStep') // Step 3 -> Step 4
        ->assertSet('step', 4)
        ->set('botName', 'جميل')
        ->set('tone', 'gulf')
        ->call('complete')
        ->assertRedirect(route('units.index'));

    $company->refresh();
    expect($company->onboarding_completed)->toBeTrue()
        ->and($company->name)->toBe('الشركة الجديدة')
        ->and($company->bot_settings['bot_name'])->toBe('جميل')
        ->and($company->bot_settings['tone'])->toBe('gulf');
});

it('persists bot settings and updates system prompt', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    Livewire::test('pages::dashboard.bot-settings')
        ->set('botName', 'أدهم')
        ->set('tone', 'formal')
        ->set('workingHours', 'working_hours')
        ->set('active', true)
        ->set('scoreThreshold', 80)
        ->set('unproductiveMessages', 6)
        ->call('saveSettings');

    $company->refresh();
    expect($company->bot_settings['bot_name'])->toBe('أدهم')
        ->and($company->bot_settings['tone'])->toBe('formal')
        ->and($company->bot_settings['working_hours'])->toBe('working_hours')
        ->and($company->bot_settings['escalation_rules']['score_threshold'])->toBe(80);

    // Test system prompt reflects this
    $prompt = app(SystemPromptBuilder::class)->build($company);
    expect($prompt)->toContain('أدهم')
        ->and($prompt)->toContain('العربية الفصحى')
        ->and($prompt)->toContain('80');
});

it('suppresses replies when bot is paused', function () {
    $company = Company::factory()->create([
        'onboarding_completed' => true,
        'bot_settings' => ['active' => false],
    ]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $lead = Lead::factory()->create(['company_id' => $company->id]);
    Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    // Dispatch webhook message
    $job = new ProcessInboundMessageJob($company->id, '+201234567890', 'مرحباً');
    $job->handle();

    // Verify AgentRunner did not execute/dispatch outbound texts
    Queue::assertNothingPushed();
});

it('executes sandbox testing message loop without sending real WhatsApp message', function () {
    $company = Company::factory()->create([
        'onboarding_completed' => true,
        'dialog360_channel_id' => 'ch-real',
    ]);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $fakeResponse = new TextResponse(
        steps: collect(),
        text: 'أهلاً بك في المحاكاة! أنا البوت.',
        finishReason: FinishReason::Stop,
        toolCalls: [],
        toolResults: [],
        usage: new Usage(1, 1),
        meta: new Meta('fake', 'fake'),
        messages: collect(),
        additionalContent: [],
    );

    Prism::fake([$fakeResponse]);

    Livewire::test('pages::dashboard.bot-settings')
        ->set('testMessage', 'مرحباً بالبوت')
        ->call('sendTestMessage')
        ->assertSet('testMessage', '');

    // Assert WhatsApp text was NOT sent
    Queue::assertNothingPushed();

    // Assert message is recorded in database
    $conversation = Conversation::where('company_id', $company->id)
        ->where('customer_phone', '+200000000000')
        ->first();

    expect($conversation)->not->toBeNull();

    $inbound = Message::where('conversation_id', $conversation->id)
        ->where('direction', MessageDirection::Inbound)
        ->first();
    expect($inbound->body)->toBe('مرحباً بالبوت');

    $outbound = Message::where('conversation_id', $conversation->id)
        ->where('direction', MessageDirection::Outbound)
        ->first();
    expect($outbound->body)->toBe('أهلاً بك في المحاكاة! أنا البوت.');
});
