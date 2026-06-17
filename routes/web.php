<?php

use App\Livewire\Auth\Register;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('webhooks/whatsapp/{company}', function () {
    return response()->json(['status' => 'ok']);
})->name('webhooks.whatsapp')->withoutMiddleware([VerifyCsrfToken::class]);

Route::middleware('guest')->group(function () {
    Route::get('register', Register::class)->name('register');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
