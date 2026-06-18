<?php

namespace App\Providers;

use App\Events\LeadBecameHot;
use App\Events\VisitBooked;
use App\Listeners\ApplyVisitSignal;
use App\Listeners\EscalateHotLead;
use App\Models\Company;
use App\Services\Agent\SystemPromptBuilder;
use App\Services\CurrentCompany;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrentCompany::class);
        $this->app->singleton(SystemPromptBuilder::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Company::class);

        $this->configureDefaults();
        $this->registerEventListeners();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
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

        Event::listen(WebhookHandled::class, function (WebhookHandled $event) {
            $payload = $event->payload;

            if ($payload['type'] === 'invoice.payment_succeeded') {
                $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

                if ($stripeCustomerId) {
                    $company = Company::where('stripe_id', $stripeCustomerId)->first();

                    if ($company) {
                        $company->rolloverBillingCycle();
                    }
                }
            }
        });
    }
}
