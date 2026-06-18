<?php

namespace App\Filament\Widgets;

use App\Enums\ConversationMode;
use App\Models\Company;
use App\Models\Conversation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class HealthStripWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $failedJobs = DB::table('failed_jobs')->count();

        $pastDueCount = Company::where('is_active', true)
            ->whereNotNull('billing_cycle_end')
            ->where('billing_cycle_end', '<', now())
            ->count();

        $stuckHandoffs = Conversation::where('mode', ConversationMode::PendingHandoff)
            ->where('updated_at', '<', now()->subHours(2))
            ->count();

        $overQuotaCount = Company::where('is_active', true)
            ->get()
            ->filter(fn (Company $c) => $c->hasReachedConversationsLimit())
            ->count();

        return [
            Stat::make(__('admin.health_failed_jobs'), $failedJobs)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failedJobs > 0 ? 'danger' : 'success')
                ->description($failedJobs > 0 ? __('admin.health_check_horizon') : __('admin.health_all_clear'))
                ->url('/horizon/failed'),

            Stat::make(__('admin.health_past_due'), $pastDueCount)
                ->icon('heroicon-o-credit-card')
                ->color($pastDueCount > 0 ? 'warning' : 'success')
                ->description($pastDueCount > 0 ? __('admin.health_needs_attention') : __('admin.health_all_clear')),

            Stat::make(__('admin.health_stuck_handoffs'), $stuckHandoffs)
                ->icon('heroicon-o-clock')
                ->color($stuckHandoffs > 0 ? 'warning' : 'success')
                ->description(__('admin.health_pending_over_2h')),

            Stat::make(__('admin.health_over_quota'), $overQuotaCount)
                ->icon('heroicon-o-chart-bar')
                ->color($overQuotaCount > 0 ? 'danger' : 'success')
                ->description($overQuotaCount > 0 ? __('admin.health_needs_attention') : __('admin.health_all_clear')),
        ];
    }
}
