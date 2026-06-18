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
            return match ($company->plan) {
                'starter' => 49.00,
                'growth' => 149.00,
                'enterprise' => 499.00,
                default => 0.00,
            };
        });

        $conversationsToday = Conversation::where('created_at', '>=', now()->startOfDay())->count();
        $messagesSentToday = Message::where('direction', MessageDirection::Outbound)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
        $totalMessagesSent = Message::where('direction', MessageDirection::Outbound)->count();

        return [
            Stat::make('Total Companies', $totalCompanies)
                ->icon('heroicon-o-building-office-2')
                ->description('Registered tenants'),

            Stat::make('Active Subscriptions', $activeSubscriptions)
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->description('Suspensions excluded'),

            Stat::make('Estimated MRR', '$'.number_format($mrr, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->description('Recurring monthly revenue'),

            Stat::make('Conversations Today', $conversationsToday)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->description('Started today'),

            Stat::make('Messages Sent Today', $messagesSentToday)
                ->icon('heroicon-o-paper-airplane')
                ->description("{$totalMessagesSent} all-time"),
        ];
    }
}
