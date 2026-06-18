<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\AdminOverviewWidget;
use App\Filament\Widgets\GrowthChartWidget;
use App\Filament\Widgets\HealthStripWidget;
use App\Filament\Widgets\RevenueVsCostChartWidget;
use App\Filament\Widgets\TopTenantsWidget;
use App\Http\Middleware\SetLocale;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->multiFactorAuthentication([
                AppAuthentication::make(),
            ], isRequired: true)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminOverviewWidget::class,
                HealthStripWidget::class,
                RevenueVsCostChartWidget::class,
                GrowthChartWidget::class,
                TopTenantsWidget::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Horizon')
                    ->url(fn (): string => url('/horizon'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-cpu-chip')
                    ->group('System Monitoring')
                    ->sort(1),
                NavigationItem::make('Reverb')
                    ->url(fn (): string => url('/reverb'), shouldOpenInNewTab: true)
                    ->icon('heroicon-o-server')
                    ->group('System Monitoring')
                    ->sort(2),
            ]);
    }
}
