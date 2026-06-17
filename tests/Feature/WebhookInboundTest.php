<?php

use App\Jobs\ProcessInboundMessageJob;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Message;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

function validWebhookPayload(?string $channelId = null, ?string $from = null, ?string $waId = null): array
{
    return [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'metadata' => [
                                'phone_number_id' => $channelId ?? 'ch-test-001',
                            ],
                            'messages' => [
                                [
                                    'from' => $from ?? '+201234567890',
                                    'id' => $waId ?? 'wa-msg-'.fake()->uuid(),
                                    'text' => [
                                        'body' => 'Hello, I am interested in a property',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

beforeEach(function () {
    config()->set('services.dialog360.verify_token', 'test-verify-token');
});

it('handles webhook verification handshake', function () {
    $response = $this->get('/webhooks/whatsapp?'.http_build_query([
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'test-verify-token',
        'hub_challenge' => 'challenge-string-123',
    ]));

    $response->assertStatus(200)
        ->assertSee('challenge-string-123');
});

it('rejects verification with wrong token', function () {
    $response = $this->get('/webhooks/whatsapp?'.http_build_query([
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'wrong-token',
        'hub_challenge' => 'challenge-string-123',
    ]));

    $response->assertStatus(403);
});

it('parses inbound message and creates lead conversation and message', function () {
    Queue::fake();

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test-001']);

    $payload = validWebhookPayload('ch-test-001', '+201234567890', 'wa-msg-001');

    $response = $this->postJson('/webhooks/whatsapp', $payload);

    $response->assertStatus(200);

    assertDatabaseHas('leads', [
        'company_id' => $company->id,
        'customer_phone' => '+201234567890',
        'source' => 'whatsapp',
    ]);

    assertDatabaseHas('conversations', [
        'company_id' => $company->id,
        'customer_phone' => '+201234567890',
        'mode' => 'bot',
    ]);

    assertDatabaseHas('messages', [
        'wa_message_id' => 'wa-msg-001',
        'body' => 'Hello, I am interested in a property',
        'direction' => 'inbound',
        'sender' => 'customer',
    ]);

    Queue::assertPushed(ProcessInboundMessageJob::class);
});

it('deduplicates messages by wa_message_id', function () {
    Queue::fake();

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test-002']);

    $payload = validWebhookPayload('ch-test-002', '+201234567891', 'wa-msg-002');

    $this->postJson('/webhooks/whatsapp', $payload);
    $this->postJson('/webhooks/whatsapp', $payload);

    expect(Message::where('wa_message_id', 'wa-msg-002')->count())->toBe(1);

    Queue::assertPushed(ProcessInboundMessageJob::class, 1);
});

it('manages conversation session history correctly', function () {
    $company = Company::factory()->create();

    $session = app(ConversationSession::class);
    $session->setCompany($company)->setPhone('+201234567892');

    expect($session->getMode())->toBe('bot');

    $session->setMode('human');
    expect($session->getMode())->toBe('human');

    $session->setMode('bot');
    $session->pushTurn(['role' => 'user', 'content' => 'Message 1']);

    expect($session->history())->toHaveCount(1);

    for ($i = 2; $i <= 15; $i++) {
        $session->pushTurn(['role' => 'user', 'content' => "Message {$i}"]);
    }

    expect($session->history())->toHaveCount(12);
});

it('gates bot reply when mode is human', function () {
    Queue::fake();

    $company = Company::factory()->create();

    $session = app(ConversationSession::class);
    $session->setCompany($company)->setPhone('+201234567893');
    $session->setMode('human');

    $job = new ProcessInboundMessageJob($company->id, '+201234567893', 'Hello');
    $job->handle();

    Queue::assertNotPushed(SendWhatsAppText::class);
});

it('sends bot auto-reply when mode is bot', function () {
    Queue::fake();

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-reply']);

    $session = app(ConversationSession::class);
    $session->setCompany($company)->setPhone('+201234567894');
    $session->setMode('bot');

    $job = new ProcessInboundMessageJob($company->id, '+201234567894', 'Hello');
    $job->handle();

    Queue::assertPushed(SendWhatsAppText::class, function ($job) {
        return $job->channelId === 'ch-reply'
            && $job->to === '+201234567894';
    });
});
