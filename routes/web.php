<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Livewire\Auth\Register;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware('guest')->group(function () {
    Route::get('register', Register::class)->name('register');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('onboarding', 'pages::dashboard.onboarding')->name('onboarding.index');

    Route::middleware('onboarded')->group(function () {
        Route::livewire('dashboard', 'pages::dashboard.overview')->name('dashboard');
        Route::livewire('dashboard/conversations', 'pages::dashboard.conversations')->name('dashboard.conversations');
        Route::livewire('dashboard/units', 'pages::dashboard.units')->name('units.index');
        Route::livewire('dashboard/bot-settings', 'pages::dashboard.bot-settings')->name('bot-settings.index');
        Route::livewire('dashboard/leads', 'pages::dashboard.leads')->name('dashboard.leads');
        Route::livewire('dashboard/leads/{lead}', 'pages::dashboard.lead-detail')->name('dashboard.leads.show');
        Route::livewire('dashboard/visits', 'pages::dashboard.visits')->name('dashboard.visits');
        Route::livewire('dashboard/analytics', 'pages::dashboard.analytics')->name('dashboard.analytics');

        Route::livewire('dashboard/billing', 'pages::dashboard.billing')->name('billing.index');
        Route::livewire('dashboard/team', 'pages::dashboard.team')->name('dashboard.team');
    });
});

require __DIR__.'/settings.php';

Route::get('health', function () {
    $db = true;
    $redis = true;

    try {
        DB::connection()->getPdo();
    } catch (Throwable) {
        $db = false;
    }

    try {
        Redis::connection()->ping();
    } catch (Throwable) {
        $redis = false;
    }

    $healthy = $db && $redis;

    return response()->json([
        'status' => $healthy ? 'healthy' : 'degraded',
        'database' => $db ? 'connected' : 'unreachable',
        'redis' => $redis ? 'connected' : 'unreachable',
        'timestamp' => now()->toIso8601String(),
    ], $healthy ? 200 : 503);
})->name('health');

Route::get('lang/{locale}', function (string $locale) {
    if (in_array($locale, ['ar', 'en'])) {
        session(['locale' => $locale]);
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }
    }

    return back();
})->name('locale.switch');
