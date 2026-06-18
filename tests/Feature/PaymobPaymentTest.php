<?php

use App\Models\Company;
use App\Models\User;
use App\Services\PaymobService;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('redirects to paymob checkout URL when checkout is clicked', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    $user = User::factory()->owner()->create(['company_id' => $company->id]);
    actingAs($user);

    $mockPaymob = Mockery::mock(PaymobService::class);
    $mockPaymob->shouldReceive('getAuthToken')->andReturn('fake-auth-token');
    $mockPaymob->shouldReceive('registerOrder')->andReturn(12345);
    $mockPaymob->shouldReceive('getPaymentKey')->andReturn('fake-payment-key');
    $mockPaymob->shouldReceive('getCheckoutUrl')->with('fake-payment-key')->andReturn('https://iframe.paymob.test/fake');

    $this->app->instance(PaymobService::class, $mockPaymob);

    Livewire::test('pages::dashboard.billing')
        ->call('checkout', 'growth')
        ->assertRedirect('https://iframe.paymob.test/fake');
});

it('rejects paymob webhook with invalid signature', function () {
    $payload = [
        'obj' => [
            'id' => 123456,
            'success' => true,
            'amount_cents' => 150000,
            'currency' => 'EGP',
            'order' => [
                'id' => 987654,
                'merchant_order_id' => 'company_1_plan_growth_123',
            ],
            'source_data' => [
                'pan' => '1234',
                'sub_type' => 'card',
                'type' => 'visa',
            ],
        ],
    ];

    postJson(route('webhooks.paymob').'?hmac=invalid-hmac', $payload)
        ->assertStatus(400);
});

it('updates company plan and rolls over billing on successful paymob webhook', function () {
    $company = Company::factory()->create(['plan' => 'starter']);
    config(['paymob.hmac_secret' => 'test-secret']);

    $payload = [
        'obj' => [
            'id' => 123456,
            'pending' => false,
            'amount_cents' => 150000,
            'success' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_voided' => false,
            'is_refunded' => false,
            'currency' => 'EGP',
            'created_at' => '2026-06-18T00:00:00.000Z',
            'integration_id' => 112233,
            'is_3d_secure' => true,
            'is_standalone_payment' => true,
            'owner' => 99,
            'order' => [
                'id' => 987654,
                'merchant_order_id' => "company_{$company->id}_plan_growth_123456",
            ],
            'source_data' => [
                'pan' => '1234',
                'sub_type' => 'card',
                'type' => 'visa',
            ],
        ],
    ];

    $obj = $payload['obj'];
    $order = $obj['order'];
    $sourceData = $obj['source_data'];

    $stringToHash =
        $obj['amount_cents'].
        $obj['created_at'].
        $obj['currency'].
        ($obj['error_occured'] ?? false ? 'true' : 'false').
        ($obj['has_parent_transaction'] ?? false ? 'true' : 'false').
        $obj['id'].
        $obj['integration_id'].
        ($obj['is_3d_secure'] ? 'true' : 'false').
        ($obj['is_auth'] ? 'true' : 'false').
        ($obj['is_capture'] ? 'true' : 'false').
        ($obj['is_refunded'] ? 'true' : 'false').
        ($obj['is_standalone_payment'] ? 'true' : 'false').
        ($obj['is_voided'] ? 'true' : 'false').
        $order['id'].
        $obj['owner'].
        ($obj['pending'] ? 'true' : 'false').
        $sourceData['pan'].
        $sourceData['sub_type'].
        $sourceData['type'].
        ($obj['success'] ? 'true' : 'false');

    $hmac = hash_hmac('sha512', $stringToHash, 'test-secret');

    postJson(route('webhooks.paymob').'?hmac='.$hmac, $payload)
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $company->refresh();
    expect($company->plan)->toBe('growth')
        ->and($company->paymob_subscription_id)->toBe('123456')
        ->and($company->billing_cycle_start)->not->toBeNull()
        ->and($company->billing_cycle_end)->not->toBeNull();
});
