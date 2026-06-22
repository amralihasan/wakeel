<?php

namespace App\Filament\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BillingOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $activeCount = Subscription::where('status', SubscriptionStatus::Active)->count();
        $trialingCount = Subscription::where('status', SubscriptionStatus::Trialing)->count();
        $pastDueCount = Subscription::where('status', SubscriptionStatus::PastDue)->count();
        $expiredCount = Subscription::where('status', SubscriptionStatus::Expired)->count();

        $mrr = Subscription::whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
            ->get()
            ->sum(fn (Subscription $s) => ($s->plan()['price_cents'] ?? 0) / 100);

        $totalSubs = Subscription::count();

        return [
            Stat::make(__('admin.estimated_mrr'), '$'.number_format($mrr, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->description(__('admin.recurring_monthly_revenue')),

            Stat::make(__('admin.active_count'), $activeCount)
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->description(__('admin.total').': '.$totalSubs),

            Stat::make(__('admin.trialing_count'), $trialingCount)
                ->icon('heroicon-o-beaker')
                ->color('info'),

            Stat::make(__('admin.past_due_count'), $pastDueCount)
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->description(__('admin.expired').': '.$expiredCount),
        ];
    }
}
