<?php

namespace App\Providers;

use App\Services\WhatsApp\Dialog360Client;
use App\Services\WhatsApp\WhatsAppClientContract;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppClientContract::class, Dialog360Client::class);
    }
}
