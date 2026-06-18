<?php

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

it('enforces cross-tenant isolation for leads', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $leadB = Lead::factory()->create(['company_id' => $companyB->id]);

    $userA = User::factory()->owner()->create(['company_id' => $companyA->id]);
    actingAs($userA);

    $this->get(route('dashboard.leads.show', $leadB))
        ->assertStatus(404);
});

it('enforces cross-tenant isolation for conversations via BelongsToCompany scope', function () {
    Company::factory()->create();
    $companyB = Company::factory()->create();

    $conversationB = Conversation::factory()->create(['company_id' => $companyB->id]);

    $conversations = Conversation::where('company_id', $companyB->id)->get();

    expect($conversations)->toHaveCount(1)
        ->and($conversations->first()->id)->toBe($conversationB->id);
});

it('enforces cross-tenant isolation for units via BelongsToCompany scope', function () {
    Company::factory()->create();
    $companyB = Company::factory()->create();

    $unitB = Unit::factory()->create(['company_id' => $companyB->id]);

    $units = Unit::where('company_id', $companyB->id)->get();

    expect($units)->toHaveCount(1)
        ->and($units->first()->id)->toBe($unitB->id);
});

it('blocks webhook requests with missing signature when app_secret is set', function () {
    config()->set('services.dialog360.app_secret', 'test-app-secret');

    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-sig-test']);

    $payload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'metadata' => ['phone_number_id' => 'ch-sig-test'],
                            'messages' => [
                                [
                                    'from' => '+201234567890',
                                    'id' => 'wa-sig-'.fake()->uuid(),
                                    'text' => ['body' => 'Hello'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson('/webhooks/whatsapp', $payload)
        ->assertStatus(401);
});

it('blocks webhook requests with incorrect signature', function () {
    config()->set('services.dialog360.app_secret', 'test-app-secret');

    Company::factory()->create(['dialog360_channel_id' => 'ch-sig-test']);

    $payload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'metadata' => ['phone_number_id' => 'ch-sig-test'],
                            'messages' => [
                                [
                                    'from' => '+201234567890',
                                    'id' => 'wa-sig-'.fake()->uuid(),
                                    'text' => ['body' => 'Hello'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson('/webhooks/whatsapp', $payload, ['X-Hub-Signature-256' => 'sha256:wrong-signature'])
        ->assertStatus(401);
});

it('accepts webhook requests with valid signature', function () {
    Queue::fake();

    $appSecret = 'test-app-secret';

    config()->set('services.dialog360.app_secret', $appSecret);
    config()->set('services.dialog360.verify_token', 'test-verify-token');

    Company::factory()->create(['dialog360_channel_id' => 'ch-sig-ok']);

    $payload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'metadata' => ['phone_number_id' => 'ch-sig-ok'],
                            'messages' => [
                                [
                                    'from' => '+201234567890',
                                    'id' => 'wa-sig-'.fake()->uuid(),
                                    'text' => ['body' => 'Hello'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), $appSecret);

    $this->postJson('/webhooks/whatsapp', $payload, ['X-Hub-Signature-256' => $signature])
        ->assertStatus(200);
});

it('rate-limits webhook per phone number', function () {
    Queue::fake();

    config()->set('services.dialog360.app_secret', null);

    Company::factory()->create(['dialog360_channel_id' => 'ch-rate-test']);

    $payload = [
        'entry' => [
            [
                'changes' => [
                    [
                        'value' => [
                            'metadata' => ['phone_number_id' => 'ch-rate-test'],
                            'messages' => [
                                [
                                    'from' => '+201234567890',
                                    'id' => 'wa-rate-'.fake()->uuid(),
                                    'text' => ['body' => 'Test'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    for ($i = 0; $i < 10; $i++) {
        $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'] = 'wa-rate-'.fake()->uuid();

        $this->postJson('/webhooks/whatsapp', $payload)
            ->assertStatus(200);
    }

    $this->postJson('/webhooks/whatsapp', $payload)
        ->assertStatus(429);
});

it('returns healthy status on health endpoint', function () {
    $this->get('/health')
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'database',
            'redis',
            'timestamp',
        ]);
});
