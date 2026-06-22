<?php

namespace App\Filament\Widgets;

use App\Enums\MessageDirection;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Message;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalCompanies = Company::count();
        $activeSubscriptions = Company::where('is_active', true)->count();

        $mrr = Company::where('is_active', true)->get()->sum(function ($company) {
            $plan = $company->getPlanDetails();

            return ($plan['price_cents'] ?? 0) / 100;
        });

        $conversationsToday = Conversation::where('created_at', '>=', now()->startOfDay())->count();
        $messagesSentToday = Message::where('direction', MessageDirection::Outbound)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $totalMessagesSent = Message::where('direction', MessageDirection::Outbound)->count();

        return [
            Stat::make(__('admin.total_companies'), $totalCompanies)
                ->icon('heroicon-o-building-office-2')
                ->description(__('admin.registered_tenants')),

            Stat::make(__('admin.active_subscriptions'), $activeSubscriptions)
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->description(__('admin.suspensions_excluded')),

            Stat::make(__('admin.estimated_mrr'), '$'.number_format($mrr, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->description(__('admin.recurring_monthly_revenue')),

            Stat::make(__('admin.conversations_today'), $conversationsToday)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description(__('admin.started_today')),

            Stat::make(__('admin.messages_sent_today'), $messagesSentToday)
                ->icon('heroicon-o-paper-airplane')
                ->description(__('admin.all_time', ['count' => $totalMessagesSent])),
        ];
    }
}
