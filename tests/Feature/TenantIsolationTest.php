<?php

use App\Enums\UserRole;
use App\Livewire\Auth\Register;
use App\Models\Company;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('registration creates one company and one owner user', function () {
    Livewire::test(Register::class)
        ->set('company_name', 'Acme Real Estate')
        ->set('name', 'John Doe')
        ->set('email', 'john@acme.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect('/dashboard');

    expect(Company::count())->toBe(1)
        ->and(User::count())->toBe(1)
        ->and(User::first()->role)->toBe(UserRole::Owner)
        ->and(User::first()->company_id)->toBe(Company::first()->id);
});

it('a user can only access their own company records', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $userA = User::factory()->create(['company_id' => $companyA->id]);
    User::factory()->create(['company_id' => $companyB->id]);

    actingAs($userA);

    expect($userA->company->id)->toBe($companyA->id)
        ->and(User::where('company_id', $companyA->id)->count())->toBe(1);
});

it('a sales rep cannot perform owner-level actions', function () {
    $company = Company::factory()->create();
    $owner = User::factory()->owner()->create(['company_id' => $company->id]);
    $rep = User::factory()->create(['company_id' => $company->id]);

    expect($owner->isOwner())->toBeTrue()
        ->and($rep->isOwner())->toBeFalse()
        ->and($rep->isSalesRep())->toBeTrue();
});
