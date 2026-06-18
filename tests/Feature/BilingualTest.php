<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;

function bilingualValidWebhookPayload(?string $channelId = null, ?string $from = null, ?string $waId = null): array
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

it('has exact key parity between all Arabic and English translation files', function () {
    $enPath = base_path('lang/en');
    $arPath = base_path('lang/ar');

    $files = File::files($enPath);
    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $filename = $file->getFilename();
        $arFile = $arPath.'/'.$filename;

        expect(File::exists($arFile))->toBeTrue("Arabic translation file missing: {$filename}");

        $enTranslations = require $file->getRealPath();
        $arTranslations = require $arFile;

        $compareKeys = function (array $array1, array $array2, string $prefix = '') use (&$compareKeys) {
            $keys1 = array_keys($array1);
            $keys2 = array_keys($array2);

            sort($keys1);
            sort($keys2);

            expect($keys1)->toEqual($keys2, "Translation key mismatch at {$prefix}");

            foreach ($array1 as $key => $value) {
                if (is_array($value) && is_array($array2[$key])) {
                    $compareKeys($value, $array2[$key], $prefix.$key.'.');
                }
            }
        };

        $compareKeys($enTranslations, $arTranslations, $filename.' => ');
    }
});

it('resolves fallback locale for guest without session', function () {
    config(['app.fallback_locale' => 'en']);
    $this->get(route('home'));
    expect(app()->getLocale())->toBe('en');

    config(['app.fallback_locale' => 'ar']);
    $this->get(route('home'));
    expect(app()->getLocale())->toBe('ar');
});

it('resolves locale based on session preference for guests', function () {
    config(['app.fallback_locale' => 'ar']);

    $this->withSession(['locale' => 'en'])->get(route('home'));
    expect(app()->getLocale())->toBe('en')
        ->and(view()->shared('dir'))->toBe('ltr');

    $this->withSession(['locale' => 'ar'])->get(route('home'));
    expect(app()->getLocale())->toBe('ar')
        ->and(view()->shared('dir'))->toBe('rtl');
});

it('resolves locale based on authenticated user preferences', function () {
    $company = Company::factory()->create(['default_locale' => 'ar']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => 'en',
    ]);

    $this->actingAs($user)->get(route('home'));
    expect(app()->getLocale())->toBe('en')
        ->and(view()->shared('dir'))->toBe('ltr');
});

it('resolves locale based on company default locale if user preference is null', function () {
    $company = Company::factory()->create(['default_locale' => 'en']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => null,
    ]);

    $this->actingAs($user)->get(route('home'));
    expect(app()->getLocale())->toBe('en')
        ->and(view()->shared('dir'))->toBe('ltr');
});

it('switches the locale via switcher route', function () {
    $company = Company::factory()->create(['default_locale' => 'ar']);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => 'ar',
    ]);

    // As a guest
    $response = $this->get(route('locale.switch', ['locale' => 'en']));
    $response->assertRedirect();
    expect(session('locale'))->toBe('en');

    // As a user
    $response = $this->actingAs($user)->get(route('locale.switch', ['locale' => 'en']));
    $response->assertRedirect();
    expect(session('locale'))->toBe('en');
    $user->refresh();
    expect($user->locale)->toBe('en');
});

it('auto-detects Arabic language in webhook messages and sets lead locale', function () {
    Queue::fake();

    $company = Company::factory()->create([
        'dialog360_channel_id' => 'ch-bilingual-001',
        'default_locale' => 'en',
    ]);

    // Send Arabic body
    $payloadAr = bilingualValidWebhookPayload('ch-bilingual-001', '+201111111111', 'wa-msg-ar');
    $payloadAr['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] = 'مرحبا، كيف حالك؟';

    $response = $this->postJson('/webhooks/whatsapp', $payloadAr);
    $response->assertStatus(200);

    assertDatabaseHas('leads', [
        'customer_phone' => '+201111111111',
        'locale' => 'ar',
    ]);

    // Send English body to update/set English
    $payloadEn = bilingualValidWebhookPayload('ch-bilingual-001', '+201111111111', 'wa-msg-en');
    $payloadEn['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] = 'Hello, how are you?';

    $response2 = $this->postJson('/webhooks/whatsapp', $payloadEn);
    $response2->assertStatus(200);

    assertDatabaseHas('leads', [
        'customer_phone' => '+201111111111',
        'locale' => 'en',
    ]);
});
