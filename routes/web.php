<?php

use App\Http\Controllers\WhatsAppWebhookController;
use App\Livewire\Auth\Register;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp');
Route::post('webhooks/whatsapp', [WhatsAppWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware('guest')->group(function () {
    Route::get('register', Register::class)->name('register');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('onboarding', 'pages::dashboard.onboarding')->name('onboarding.index');

    Route::middleware('onboarded')->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
        Route::livewire('dashboard/conversations', 'pages::dashboard.conversations')->name('dashboard.conversations');
        Route::livewire('dashboard/units', 'pages::dashboard.units')->name('units.index');
        Route::livewire('dashboard/bot-settings', 'pages::dashboard.bot-settings')->name('bot-settings.index');
    });
});

require __DIR__.'/settings.php';
