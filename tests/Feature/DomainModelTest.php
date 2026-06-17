<?php

use App\Enums\UnitStatus;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Unit;
use App\Models\UnitMedia;
use App\Models\User;
use App\Models\Visit;

use function Pest\Laravel\actingAs;

it('all factories create valid instances', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);

    actingAs($user);

    $unit = Unit::factory()->create();
    $media = UnitMedia::factory()->create(['unit_id' => $unit->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
    ]);
    $message = Message::factory()->create(['company_id' => $company->id, 'conversation_id' => $conversation->id]);
    $visit = Visit::factory()->create(['company_id' => $company->id, 'lead_id' => $lead->id, 'unit_id' => $unit->id]);
    $handoff = Handoff::factory()->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
        'lead_id' => $lead->id,
    ]);

    expect($unit->exists)->toBeTrue()
        ->and($media->exists)->toBeTrue()
        ->and($lead->exists)->toBeTrue()
        ->and($conversation->exists)->toBeTrue()
        ->and($message->exists)->toBeTrue()
        ->and($visit->exists)->toBeTrue()
        ->and($handoff->exists)->toBeTrue();
});

it('resolves model relationships correctly', function () {
    $company = Company::factory()->create();
    $user = User::factory()->owner()->create(['company_id' => $company->id]);

    actingAs($user);

    $unit = Unit::factory()->create(['company_id' => $company->id]);
    UnitMedia::factory()->count(3)->create(['unit_id' => $unit->id]);
    $lead = Lead::factory()->create(['company_id' => $company->id, 'interested_unit_id' => $unit->id]);
    $conversation = Conversation::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
    ]);
    Message::factory()->count(5)->create([
        'company_id' => $company->id,
        'conversation_id' => $conversation->id,
    ]);
    Visit::factory()->create([
        'company_id' => $company->id,
        'lead_id' => $lead->id,
        'unit_id' => $unit->id,
    ]);

    expect($unit->media)->toHaveCount(3)
        ->and($unit->status)->toBe(UnitStatus::Available)
        ->and($lead->interestedUnit->id)->toBe($unit->id)
        ->and($conversation->lead->id)->toBe($lead->id)
        ->and($conversation->messages)->toHaveCount(5);
});

it('global tenant scope isolates records by company', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $userA = User::factory()->owner()->create(['company_id' => $companyA->id]);
    $userB = User::factory()->owner()->create(['company_id' => $companyB->id]);

    Unit::factory()->create(['company_id' => $companyA->id]);
    Unit::factory()->create(['company_id' => $companyB->id]);

    actingAs($userA);

    expect(Unit::count())->toBe(1);
});
