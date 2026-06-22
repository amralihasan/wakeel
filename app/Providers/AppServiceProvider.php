<?php

namespace App\Providers;

use App\Events\LeadBecameHot;
use App\Events\PlanChanged;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionCanceled;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionPastDue;
use App\Events\VisitBooked;
use App\Listeners\ApplyVisitSignal;
use App\Listeners\EscalateHotLead;
use App\Listeners\HandlePlanChanged;
use App\Listeners\HandleSubscriptionActivated;
use App\Listeners\HandleSubscriptionCanceled;
use App\Listeners\HandleSubscriptionExpired;
use App\Listeners\HandleSubscriptionPastDue;
use App\Services\Agent\SystemPromptBuilder;
use App\Services\ConversationUsage;
use App\Services\CurrentCompany;
use App\Services\PlanCatalog;
use App\Services\PlanGate;
use App\Services\SubscriptionManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentCompany::class);
        $this->app->singleton(SystemPromptBuilder::class);
        $this->app->singleton(PlanCatalog::class);
        $this->app->singleton(PlanGate::class);
        $this->app->singleton(ConversationUsage::class);
        $this->app->singleton(SubscriptionManager::class);
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerEventListeners(): void
    {
        Event::listen(
            VisitBooked::class,
            ApplyVisitSignal::class,
        );

        Event::listen(
            LeadBecameHot::class,
            EscalateHotLead::class,
        );

        Event::listen(
            SubscriptionActivated::class,
            HandleSubscriptionActivated::class,
        );

        Event::listen(
            SubscriptionPastDue::class,
            HandleSubscriptionPastDue::class,
        );

        Event::listen(
            SubscriptionCanceled::class,
            HandleSubscriptionCanceled::class,
        );

        Event::listen(
            SubscriptionExpired::class,
            HandleSubscriptionExpired::class,
        );

        Event::listen(
            PlanChanged::class,
            HandlePlanChanged::class,
        );
    }
}
