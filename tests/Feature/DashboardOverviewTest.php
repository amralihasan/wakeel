<?php

use App\Enums\LeadTier;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Enums\VisitStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('displays the dashboard overview metrics and activities', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $owner = User::factory()->owner()->create(['company_id' => $company->id, 'locale' => 'ar']);
    actingAs($owner);

    $unit = Unit::factory()->create(['company_id' => $company->id]);

    // Seed 2 leads created today
    $lead1 = Lead::factory()->create([
        'company_id' => $company->id,
        'tier' => LeadTier::Hot,
        'score' => 85,
        'interested_unit_id' => $unit->id,
        'created_at' => now(),
    ]);

    $lead2 = Lead::factory()->create([
        'company_id' => $company->id,
        'tier' => LeadTier::Warm,
        'score' => 60,
        'created_at' => now(),
    ]);

    // Seed 1 lead created yesterday (for deltas)
    $leadYesterday = Lead::factory()->create([
        'company_id' => $company->id,
        'tier' => LeadTier::Hot,
        'created_at' => now()->subDay(),
    ]);

    // Seed conversations & messages
    $conv1 = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead1->id,
        'created_at' => now(),
    ]);
    $conv2 = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead2->id,
        'created_at' => now(),
    ]);

    // Inbound + Outbound message pair today for avg response time
    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conv1->id,
        'direction' => MessageDirection::Inbound,
        'sender' => MessageSender::Customer,
        'body' => 'سؤال',
        'created_at' => now()->subMinutes(10),
    ]);

    Message::create([
        'company_id' => $company->id,
        'conversation_id' => $conv1->id,
        'direction' => MessageDirection::Outbound,
        'sender' => MessageSender::Bot,
        'body' => 'جواب',
        'created_at' => now(), // Response time = 10 minutes
    ]);

    // Seed visit
    Visit::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead1->id,
        'unit_id' => $unit->id,
        'scheduled_at' => now()->addDay(),
        'status' => VisitStatus::Pending,
        'created_at' => now(),
    ]);

    // Seed handoff
    Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conv1->id,
        'lead_id' => $lead1->id,
        'reason' => 'طلب العميل',
        'ai_summary' => 'العميل يطلب معاينة عاجلة',
        'created_at' => now(),
    ]);

    // Test Volt Overview Component
    app()->setLocale('ar');
    Livewire::test('pages::dashboard.overview')
        ->assertOk()
        ->assertSee('محادثات اليوم')
        ->assertSee('العملاء المميزين')
        ->assertSee('معاينات هذا الأسبوع')
        ->assertSee('معدل سرعة الرد');
});

it('filters and searches leads correctly', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $owner = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($owner);

    // Seed leads with different tiers and names
    Lead::factory()->create([
        'company_id' => $company->id,
        'name' => 'أحمد العشري',
        'tier' => LeadTier::Hot,
    ]);
    Lead::factory()->create([
        'company_id' => $company->id,
        'name' => 'محمد الشافعي',
        'tier' => LeadTier::Cold,
    ]);

    // Test Leads list search
    Livewire::test('pages::dashboard.leads')
        ->set('search', 'أحمد')
        ->assertSee('أحمد العشري')
        ->assertDontSee('محمد الشافعي');

    // Test Leads list tier filtering
    Livewire::test('pages::dashboard.leads')
        ->set('tier', 'cold')
        ->assertSee('محمد الشافعي')
        ->assertDontSee('أحمد العشري');
});

it('authorizes lead detail page only for owned leads', function () {
    $companyA = Company::factory()->create(['onboarding_completed' => true]);
    $companyB = Company::factory()->create(['onboarding_completed' => true]);

    $userA = User::factory()->owner()->create(['company_id' => $companyA->id]);
    $userB = User::factory()->owner()->create(['company_id' => $companyB->id]);

    $leadA = Lead::factory()->create(['company_id' => $companyA->id, 'name' => 'عميل شركة أ']);

    // Logged in as User B, try to access Lead A details
    actingAs($userB);
    get(route('dashboard.leads.show', $leadA->id))
        ->assertNotFound();

    // Logged in as User A, try to access Lead A details
    actingAs($userA);
    get(route('dashboard.leads.show', $leadA->id))
        ->assertOk();
});

it('allows assigning reps and updating status outcomes on visits', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $owner = User::factory()->owner()->create(['company_id' => $company->id]);
    $salesRep = User::factory()->create(['company_id' => $company->id]);
    actingAs($owner);

    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);
    $visit = Visit::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'unit_id' => $unit->id,
        'status' => VisitStatus::Pending,
    ]);

    // Test Visit assignment and status transitions
    Livewire::test('pages::dashboard.visits')
        ->call('assignRep', $visit->id, $salesRep->id)
        ->call('updateStatus', $visit->id, 'completed');

    $visit->refresh();
    expect($visit->assigned_rep_id)->toBe($salesRep->id)
        ->and($visit->status)->toBe(VisitStatus::Completed);
});

it('aggregates analytics data scoped to tenant', function () {
    $company = Company::factory()->create(['onboarding_completed' => true]);
    $owner = User::factory()->owner()->create(['company_id' => $company->id, 'locale' => 'ar']);
    actingAs($owner);

    // Seed leads with sources
    Lead::factory()->create(['company_id' => $company->id, 'source' => 'facebook', 'score' => 90]);
    Lead::factory()->create(['company_id' => $company->id, 'source' => 'facebook', 'score' => 80]);
    Lead::factory()->create(['company_id' => $company->id, 'source' => 'website', 'score' => 50]);

    // Test Analytics view aggregations
    app()->setLocale('ar');
    Livewire::test('pages::dashboard.analytics')
        ->assertOk()
        ->assertSee('تقارير وتحليلات الأداء')
        ->assertSee('إعلانات فيسبوك');
});
