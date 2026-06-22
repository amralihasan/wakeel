<?php

use App\Enums\HandoffStatus;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppChannel;
use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('allows super-admins to access the admin panel', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    actingAs($superAdmin)
        ->get('/admin')
        ->assertSuccessful();
});

it('forbids normal tenant owners from accessing the admin panel', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->owner()->create([
        'company_id' => $company->id,
        'is_super_admin' => false,
    ]);

    actingAs($owner)
        ->get('/admin')
        ->assertForbidden();
});

it('forbids tenant owners even if they have super-admin flag from accessing the admin panel', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->owner()->create([
        'company_id' => $company->id,
        'is_super_admin' => true,
    ]);

    actingAs($owner)
        ->get('/admin')
        ->assertForbidden();
});

it('provisions a whatsapp number from the database-driven pool', function () {
    Http::fake([
        'waba-v2.360dialog.io/configs/webhook' => Http::response('OK', 200),
    ]);

    $channel = WhatsAppChannel::create([
        'number' => '+201111111111',
        'channel_id' => 'channel-test-db-1',
        'status' => 'available',
    ]);

    $company = Company::factory()->create([
        'whatsapp_number' => null,
        'dialog360_channel_id' => null,
    ]);

    $client = app(WhatsAppClientContract::class);
    $client->assignNumberFromPool($company);

    $company->refresh();
    $channel->refresh();

    expect($company->whatsapp_number)->toBe('+201111111111')
        ->and($company->dialog360_channel_id)->toBe('channel-test-db-1')
        ->and($channel->status)->toBe('assigned')
        ->and($channel->assigned_company_id)->toBe($company->id);
});

it('renders the company resource list page with correct stats', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $company = Company::factory()->create([
        'plan' => 'starter',
        'conversations_count' => 5,
        'billing_cycle_start' => now()->subDays(5),
        'billing_cycle_end' => now()->addDays(25),
    ]);

    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
    ]);

    // Create some messages with tokens
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'Hello',
        'input_tokens' => 1000, // 1000 input tokens = $0.00025
        'output_tokens' => 2000, // 2000 output tokens = $0.0025
        'created_at' => now()->subDays(1),
    ]);

    actingAs($superAdmin)
        ->get('/admin/companies')
        ->assertSuccessful()
        ->assertSee('starter')
        ->assertSee('99.00')
        ->assertSee('0.15'); // Est. Cost: $0.00025 + $0.0025 + (5 * 0.03 = $0.15) = $0.15275, formatted is $0.15
});

it('renders the whatsapp channels resource page', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    WhatsAppChannel::create([
        'number' => '+201111111111',
        'channel_id' => 'channel-test-1',
        'status' => 'available',
    ]);

    actingAs($superAdmin)
        ->get('/admin/whats-app-channels')
        ->assertSuccessful()
        ->assertSee('+201111111111')
        ->assertSee('channel-test-1');
});

it('renders the handoffs resource page', function () {
    $superAdmin = User::factory()->superAdmin()->create();

    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id, 'customer_phone' => '+201111111112']);
    $conversation = Conversation::factory()->create(['company_id' => $company->id, 'lead_id' => $lead->id]);

    Handoff::create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
        'reason' => 'user_requested',
        'ai_summary' => 'Need human help',
        'status' => HandoffStatus::Waiting,
    ]);

    actingAs($superAdmin)
        ->get('/admin/handoffs')
        ->assertSuccessful()
        ->assertSee('user_requested')
        ->assertSee('Need human help');
});
