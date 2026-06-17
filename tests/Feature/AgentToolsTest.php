<?php

use App\Agent\Tools\BookVisitTool;
use App\Agent\Tools\CalculateInstallmentTool;
use App\Agent\Tools\EscalateToAgentTool;
use App\Agent\Tools\QualifyLeadTool;
use App\Agent\Tools\SearchPropertiesTool;
use App\Agent\Tools\SendUnitMediaTool;
use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Events\ConversationEscalated;
use App\Events\VisitBooked;
use App\Jobs\SendWhatsAppMedia;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Unit;
use App\Models\UnitMedia;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Event::fake();
    Queue::fake();
});

it('searches available properties with budget filter', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    Unit::factory()->create(['company_id' => $company->id, 'price' => 500000, 'status' => 'available']);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 1500000, 'status' => 'available']);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 3000000, 'status' => 'available']);

    $other = Company::factory()->create();
    Unit::factory()->create(['company_id' => $other->id, 'price' => 1000000, 'status' => 'available']);

    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new SearchPropertiesTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke(2000000);

    $units = json_decode($result, true);

    expect($units)->toHaveCount(2);
    expect(array_map(fn ($u) => $u['price'], $units))->toEqual([500000, 1500000]);
});

it('searches properties with optional filters', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    Unit::factory()->create(['company_id' => $company->id, 'price' => 1000000, 'rooms' => 3, 'type' => 'apartment', 'location' => 'القاهرة الجديدة', 'status' => 'available']);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 2000000, 'rooms' => 4, 'type' => 'villa', 'location' => 'الساحل الشمالي', 'status' => 'available']);
    Unit::factory()->create(['company_id' => $company->id, 'price' => 1500000, 'rooms' => 3, 'type' => 'apartment', 'location' => 'مدينتي', 'status' => 'available']);

    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new SearchPropertiesTool($company->id, $lead->id, '+201234567890');

    $result = $tool->__invoke(5000000, rooms: 3);
    $units = json_decode($result, true);

    expect($units)->toHaveCount(2);

    $result2 = $tool->__invoke(5000000, rooms: 3, location: 'مدينتي');
    $units2 = json_decode($result2, true);

    expect($units2)->toHaveCount(1)
        ->and($units2[0]['location'])->toContain('مدينتي');
});

it('returns no matches message when no units found', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new SearchPropertiesTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke(500000);

    expect($result)->toBe('لا توجد وحدات مطابقة للخيارات المدخلة حالياً.');
});

it('sends unit media and dispatches jobs', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $unit = Unit::factory()->create(['company_id' => $company->id, 'title' => 'شقة فاخرة']);
    $media1 = UnitMedia::factory()->create(['unit_id' => $unit->id, 'type' => 'image', 'path' => 'units/photo1.jpg']);
    $media2 = UnitMedia::factory()->create(['unit_id' => $unit->id, 'type' => 'image', 'path' => 'units/photo2.jpg']);
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new SendUnitMediaTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke($unit->id, 'images');

    expect($result)->toBe('تم إرسال الملفات بنجاح للعميل.');

    Queue::assertPushed(SendWhatsAppMedia::class, 2);
    Queue::assertPushed(SendWhatsAppMedia::class, function ($job) use ($company, $media1) {
        return $job->channelId === $company->dialog360_channel_id
            && $job->to === '+201234567890'
            && $job->url === $media1->path;
    });
});

it('rejects send unit media for wrong company unit', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    $unit = Unit::factory()->create(['company_id' => $other->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new SendUnitMediaTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke($unit->id, 'images');

    expect($result)->toBe('الوحدة غير موجودة أو لا تنتمي لشركتك.');
    Queue::assertNothingPushed();
});

it('calculates monthly installment correctly', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new CalculateInstallmentTool($company->id, $lead->id, '+201234567890');
    $result = json_decode($tool->__invoke(1000000, 200000, 10), true);

    expect($result['monthly_payment'])->toBe(6667)
        ->and($result['message'])->toContain('جنيه مصري');
});

it('books a visit and dispatches VisitBooked event', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    $tool = new BookVisitTool($company->id, $lead->id, '+201234567890');
    $futureDate = Carbon::tomorrow('Africa/Cairo')->format('Y-m-d');
    $futureTime = '10:00';

    $result = $tool->__invoke($unit->id, $futureDate, $futureTime);

    expect($result)->toBe('تم تسجيل طلب حجز الزيارة بنجاح بانتظار التأكيد.');

    $visit = Visit::where('lead_id', $lead->id)->first();
    expect($visit)->not->toBeNull()
        ->and($visit->status->value)->toBe('pending')
        ->and($visit->unit_id)->toBe($unit->id);

    Event::assertDispatched(VisitBooked::class, function ($event) use ($visit) {
        return $event->visit->id === $visit->id;
    });
});

it('rejects visit booking for past dates', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    $tool = new BookVisitTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke($unit->id, '2020-01-01', '10:00');

    expect($result)->toBe('تاريخ ووقت الزيارة يجب أن يكون في المستقبل.');
    expect(Visit::count())->toBe(0);
});

it('rejects double booking for same unit and time', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $company->id]);

    $futureDate = Carbon::tomorrow('Africa/Cairo')->format('Y-m-d');
    $futureTime = '10:00';
    $scheduledAt = Carbon::parse("{$futureDate} {$futureTime}", 'Africa/Cairo');

    Visit::create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'unit_id' => $unit->id,
        'scheduled_at' => $scheduledAt,
        'status' => 'pending',
    ]);

    $tool = new BookVisitTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke($unit->id, $futureDate, $futureTime);

    expect($result)->toBe('هذا الموعد محجوز مسبقاً. يرجى اختيار وقت آخر.');
    expect(Visit::count())->toBe(1);
});

it('rejects booking for wrong company unit', function () {
    $company = Company::factory()->create();
    $other = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $unit = Unit::factory()->create(['company_id' => $other->id]);

    $tool = new BookVisitTool($company->id, $lead->id, '+201234567890');
    $futureDate = Carbon::tomorrow('Africa/Cairo')->format('Y-m-d');
    $result = $tool->__invoke($unit->id, $futureDate, '10:00');

    expect($result)->toBe('الوحدة غير موجودة أو لا تنتمي لشركتك.');
    expect(Visit::count())->toBe(0);
});

it('qualifies lead with correct scoring', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id, 'score' => 0]);

    $tool = new QualifyLeadTool($company->id, $lead->id, '+201234567890');

    $result = json_decode($tool->__invoke([
        'stated_budget' => true,
        'asked_price' => true,
        'booked_visit' => false,
        'asked_installment' => true,
        'asked_media' => false,
        'general_inquiry' => false,
    ]), true);

    expect($result['score'])->toBe(70)
        ->and($result['tier'])->toBe('hot');

    $lead->refresh();
    expect($lead->score)->toBe(70)
        ->and($lead->tier->value)->toBe('hot');
});

it('caps lead score at 100', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id, 'score' => 0]);

    $tool = new QualifyLeadTool($company->id, $lead->id, '+201234567890');

    $result = json_decode($tool->__invoke([
        'stated_budget' => true,
        'asked_price' => true,
        'booked_visit' => true,
        'asked_installment' => true,
        'asked_media' => true,
        'general_inquiry' => true,
    ]), true);

    expect($result['score'])->toBe(100); // 115 capped to 100
});

it('maps score to correct tier', function () {
    $company = Company::factory()->create();

    $cold = Lead::factory()->create(['company_id' => $company->id, 'score' => 0]);
    $warm = Lead::factory()->create(['company_id' => $company->id, 'score' => 0]);
    $hot = Lead::factory()->create(['company_id' => $company->id, 'score' => 0]);

    $toolC = new QualifyLeadTool($company->id, $cold->id, '+201234567890');
    $toolW = new QualifyLeadTool($company->id, $warm->id, '+201234567890');
    $toolH = new QualifyLeadTool($company->id, $hot->id, '+201234567890');

    $toolC->__invoke(['stated_budget' => false, 'asked_price' => false, 'booked_visit' => false, 'asked_installment' => false, 'asked_media' => false, 'general_inquiry' => true]);
    $toolW->__invoke(['stated_budget' => true, 'asked_price' => true, 'booked_visit' => false, 'asked_installment' => false, 'asked_media' => false, 'general_inquiry' => false]);
    $toolH->__invoke(['stated_budget' => true, 'asked_price' => true, 'booked_visit' => true, 'asked_installment' => false, 'asked_media' => false, 'general_inquiry' => false]);

    expect($cold->refresh()->tier->value)->toBe('cold');
    expect($warm->refresh()->tier->value)->toBe('warm');
    expect($hot->refresh()->tier->value)->toBe('hot');
});

it('escalates conversation to agent', function () {
    $company = Company::factory()->create(['dialog360_channel_id' => 'ch-test']);
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'customer_phone' => '+201234567890',
        'mode' => ConversationMode::Bot,
    ]);

    $tool = new EscalateToAgentTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke('طلب العميل التحدث مع وكيل', 'العميل مهتم بشقة في القاهرة الجديدة');

    expect($result)->toBe('تم تحويلك لخدمة العملاء، سيتواصل معك أحد وكلائنا فوراً.');

    $conversation->refresh();
    expect($conversation->mode)->toBe(ConversationMode::PendingHandoff);

    $handoff = Handoff::where('lead_id', $lead->id)->first();
    expect($handoff)->not->toBeNull()
        ->and($handoff->status)->toBeInstanceOf(HandoffStatus::class)
        ->and($handoff->status->value)->toBe('waiting')
        ->and($handoff->reason)->toBe('طلب العميل التحدث مع وكيل')
        ->and($handoff->ai_summary)->toBe('العميل مهتم بشقة في القاهرة الجديدة');

    Event::assertDispatched(ConversationEscalated::class, function ($event) use ($handoff) {
        return $event->handoff->id === $handoff->id;
    });
});

it('returns error when no conversation exists for escalation', function () {
    $company = Company::factory()->create();
    $lead = Lead::factory()->create(['company_id' => $company->id]);

    $tool = new EscalateToAgentTool($company->id, $lead->id, '+201234567890');
    $result = $tool->__invoke('سبب', 'ملخص');

    expect($result)->toBe('لم يتم العثور على محادثة نشطة لهذا العميل.');
});
