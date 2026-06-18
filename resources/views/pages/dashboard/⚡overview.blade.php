<?php

use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Models\Visit;
use App\Models\Handoff;
use App\Enums\MessageDirection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Title('dashboard.title')] #[Layout('layouts.app')] class extends Component {
    public int $companyId;

    public function mount(): void
    {
        $this->companyId = Auth::user()->company_id;
    }

    #[On('echo-private:company.{companyId}.conversations,.inbound-message.received')]
    #[On('echo-private:company.{companyId}.handoffs,.conversation.escalated')]
    public function refreshStats(): void
    {
        // Triggers re-render and re-fetches computed properties
    }

    #[Computed]
    public function stats(): array
    {
        // 1. Conversations Today
        $conversationsToday = Conversation::where('company_id', $this->companyId)
            ->whereDate('created_at', today())
            ->count();
        $conversationsYesterday = Conversation::where('company_id', $this->companyId)
            ->whereDate('created_at', today()->subDay())
            ->count();
        $conversationsDelta = $conversationsYesterday > 0
            ? round((($conversationsToday - $conversationsYesterday) / $conversationsYesterday) * 100)
            : ($conversationsToday > 0 ? 100 : 0);

        // 2. Hot Leads
        $hotLeads = Lead::where('company_id', $this->companyId)
            ->where('tier', 'hot')
            ->count();
        $hotLeadsYesterday = Lead::where('company_id', $this->companyId)
            ->where('tier', 'hot')
            ->whereDate('created_at', '<=', today()->subDay())
            ->count();
        $hotLeadsDelta = $hotLeadsYesterday > 0
            ? round((($hotLeads - $hotLeadsYesterday) / $hotLeadsYesterday) * 100)
            : ($hotLeads > 0 ? 100 : 0);

        // 3. Viewings This Week
        $viewingsThisWeek = Visit::where('company_id', $this->companyId)
            ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $viewingsLastWeek = Visit::where('company_id', $this->companyId)
            ->whereBetween('scheduled_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $viewingsDelta = $viewingsLastWeek > 0
            ? round((($viewingsThisWeek - $viewingsLastWeek) / $viewingsLastWeek) * 100)
            : ($viewingsThisWeek > 0 ? 100 : 0);

        // 4. Average Response Time
        $inboundToday = Message::where('company_id', $this->companyId)
            ->where('direction', MessageDirection::Inbound)
            ->whereDate('created_at', today())
            ->get();
        $totalDiff = 0;
        $countPairs = 0;
        foreach ($inboundToday as $inbound) {
            $outbound = Message::where('conversation_id', $inbound->conversation_id)
                ->where('direction', MessageDirection::Outbound)
                ->where('created_at', '>', $inbound->created_at)
                ->orderBy('created_at')
                ->first();
            if ($outbound) {
                $totalDiff += $outbound->created_at->diffInSeconds($inbound->created_at);
                $countPairs++;
            }
        }
        $avgResponseToday = $countPairs > 0 ? round($totalDiff / $countPairs / 60) : 0;

        $inboundYesterday = Message::where('company_id', $this->companyId)
            ->where('direction', MessageDirection::Inbound)
            ->whereDate('created_at', today()->subDay())
            ->get();
        $totalDiffYest = 0;
        $countPairsYest = 0;
        foreach ($inboundYesterday as $inbound) {
            $outbound = Message::where('conversation_id', $inbound->conversation_id)
                ->where('direction', MessageDirection::Outbound)
                ->where('created_at', '>', $inbound->created_at)
                ->orderBy('created_at')
                ->first();
            if ($outbound) {
                $totalDiffYest += $outbound->created_at->diffInSeconds($inbound->created_at);
                $countPairsYest++;
            }
        }
        $avgResponseYesterday = $countPairsYest > 0 ? round($totalDiffYest / $countPairsYest / 60) : 0;
        $avgResponseDelta = $avgResponseYesterday > 0
            ? round((($avgResponseToday - $avgResponseYesterday) / $avgResponseYesterday) * 100)
            : ($avgResponseToday > 0 ? 100 : 0);

        return [
            'conversations_today' => $conversationsToday,
            'conversations_delta' => $conversationsDelta,
            'hot_leads' => $hotLeads,
            'hot_leads_delta' => $hotLeadsDelta,
            'viewings_this_week' => $viewingsThisWeek,
            'viewings_delta' => $viewingsDelta,
            'avg_response_time' => $avgResponseToday,
            'avg_response_delta' => $avgResponseDelta,
        ];
    }

    #[Computed]
    public function latestLeads()
    {
        return Lead::where('company_id', $this->companyId)
            ->with('interestedUnit')
            ->latest()
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentActivities()
    {
        $leads = Lead::where('company_id', $this->companyId)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($item) => [
                'type' => 'new_lead',
                'title' => __('dashboard.new_lead_activity', ['name' => ($item->name ?: $item->customer_phone)]),
                'time' => $item->created_at,
                'icon' => 'user-plus',
                'badge_color' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
            ]);

        $visits = Visit::where('company_id', $this->companyId)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($item) => [
                'type' => 'visit_booked',
                'title' => __('dashboard.visit_booked_activity', [
                    'lead' => ($item->lead?->name ?: $item->lead?->customer_phone),
                    'unit' => ($item->unit?->title ?? __('dashboard.real_estate_unit'))
                ]),
                'time' => $item->created_at,
                'icon' => 'calendar-days',
                'badge_color' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400',
            ]);

        $handoffs = Handoff::where('company_id', $this->companyId)
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($item) => [
                'type' => 'escalated',
                'title' => __('dashboard.escalated_activity', [
                    'lead' => ($item->lead?->name ?: $item->lead?->customer_phone)
                ]),
                'time' => $item->created_at,
                'icon' => 'arrow-right-start-on-rectangle',
                'badge_color' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400',
            ]);

        return collect()->concat($leads)->concat($visits)->concat($handoffs)
            ->sortByDesc('time')
            ->take(10)
            ->values();
    }

    #[Computed]
    public function weeklyChartData(): array
    {
        $data = [];
        $max = 0;

        // Loop last 7 days (including today)
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Message::where('company_id', $this->companyId)
                ->whereDate('created_at', $date->toDateString())
                ->count();

            if ($count > $max) {
                $max = $count;
            }

            $data[] = [
                'day' => $date->translatedFormat('D'),
                'count' => $count,
            ];
        }

        foreach ($data as &$dayData) {
            $dayData['percentage'] = $max > 0 ? round(($dayData['count'] / $max) * 100) : 0;
        }

        return $data;
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Top Heading --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('dashboard.overview_title') }}</h1>
        <div class="flex items-center gap-4">
            <div class="text-sm text-neutral-500">
                {{ __('dashboard.today_date', ['date' => now()->translatedFormat('l, d F Y')]) }}
            </div>
        </div>
    </div>

    {{-- Subscription Usage Card --}}
    @php
        $company = Auth::user()->company;
        $plan = config("plans.{$company->plan}");
        $convLimit = $plan['conversations_limit'] ?? 0;
        $convUsed = $company->conversations_count;
        $convPct = $convLimit > 0 ? min(100, round(($convUsed / $convLimit) * 100)) : 0;
    @endphp
    <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-xs dark:border-neutral-700 dark:bg-zinc-900 {{ $company->hasReachedConversationsLimit() ? 'border-red-300 dark:border-red-700' : '' }}">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-3">
                <span class="text-sm font-semibold">{{ $plan['name'] ?? 'Starter' }}</span>
                @if ($company->billing_cycle_start)
                    <span class="text-xs text-neutral-500">
                        {{ $company->billing_cycle_start->format('d M') }} - {{ $company->billing_cycle_end?->format('d M Y') }}
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs text-neutral-500">
                    {{ __('dashboard.conversations') }}: <strong>{{ $convUsed }}</strong> / {{ $convLimit === -1 ? __('dashboard.unlimited') : $convLimit }}
                </span>
                @if ($convLimit > 0)
                    <div class="h-2 w-24 rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $convPct >= 80 ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ $convPct }}%"></div>
                    </div>
                @endif
                @if ($company->hasReachedConversationsLimit())
                    <flux:badge variant="danger" size="sm">{{ __('dashboard.limit_reached') }}</flux:badge>
                @elseif ($convPct >= 80)
                    <flux:badge variant="warning" size="sm">{{ __('dashboard.limit_warning') }}</flux:badge>
                @endif
                <flux:button size="xs" variant="ghost" :href="route('billing.index')" wire:navigate>{{ __('dashboard.manage_subscription') }}</flux:button>
            </div>
        </div>
    </div>

    {{-- Metric Cards Grid --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Conversations Today --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-500">{{ __('dashboard.conversations_today') }}</span>
                <flux:icon name="chat-bubble-left-right" class="h-5 w-5 text-neutral-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-semibold tracking-tight">{{ $this->stats['conversations_today'] }}</span>
                <span class="inline-flex items-center text-xs font-medium {{ $this->stats['conversations_delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->stats['conversations_delta'] >= 0 ? '↑' : '↓' }} {{ abs($this->stats['conversations_delta']) }}%
                </span>
            </div>
            <p class="text-[10px] text-neutral-400 mt-1">{{ __('dashboard.vs_yesterday_desc') }}</p>
        </div>

        {{-- Card 2: Hot Leads --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-500">{{ __('dashboard.hot_leads_card') }}</span>
                <flux:icon name="fire" class="h-5 w-5 text-rose-500" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-semibold tracking-tight">{{ $this->stats['hot_leads'] }}</span>
                <span class="inline-flex items-center text-xs font-medium {{ $this->stats['hot_leads_delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->stats['hot_leads_delta'] >= 0 ? '↑' : '↓' }} {{ abs($this->stats['hot_leads_delta']) }}%
                </span>
            </div>
            <p class="text-[10px] text-neutral-400 mt-1">{{ __('dashboard.vs_yesterday_total') }}</p>
        </div>

        {{-- Card 3: Viewings This Week --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-500">{{ __('dashboard.viewings_this_week_card') }}</span>
                <flux:icon name="calendar-days" class="h-5 w-5 text-neutral-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-semibold tracking-tight">{{ $this->stats['viewings_this_week'] }}</span>
                <span class="inline-flex items-center text-xs font-medium {{ $this->stats['viewings_delta'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->stats['viewings_delta'] >= 0 ? '↑' : '↓' }} {{ abs($this->stats['viewings_delta']) }}%
                </span>
            </div>
            <p class="text-[10px] text-neutral-400 mt-1">{{ __('dashboard.vs_last_week') }}</p>
        </div>

        {{-- Card 4: Avg Response Time --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-neutral-500">{{ __('dashboard.avg_response_time_card') }}</span>
                <flux:icon name="clock" class="h-5 w-5 text-neutral-400" />
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-semibold tracking-tight">{{ $this->stats['avg_response_time'] }} <span class="text-xs text-neutral-500">{{ __('dashboard.minutes') }}</span></span>
                <span class="inline-flex items-center text-xs font-medium {{ $this->stats['avg_response_delta'] <= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->stats['avg_response_delta'] <= 0 ? '↓' : '↑' }} {{ abs($this->stats['avg_response_delta']) }}%
                </span>
            </div>
            <p class="text-[10px] text-neutral-400 mt-1">{{ __('dashboard.vs_yesterday_avg') }}</p>
        </div>
    </div>

    {{-- Main Contents split --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Chart and Activities --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Weekly Chart Panel --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold mb-4">{{ __('dashboard.message_volume') }}</h3>
                <div class="flex items-end justify-between h-48 pt-4 gap-2">
                    @foreach ($this->weeklyChartData as $day)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            <div class="w-full bg-neutral-100 rounded-t-md dark:bg-neutral-800 relative h-36 flex items-end">
                                <div class="w-full bg-indigo-600 rounded-t-md hover:bg-indigo-500 transition-all duration-300" style="height: {{ $day['percentage'] }}%"></div>
                                <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-[10px] font-medium text-neutral-500">{{ $day['count'] }}</span>
                            </div>
                            <span class="text-xs text-neutral-500">{{ $day['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Recent Activity Feed --}}
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <h3 class="text-sm font-semibold mb-4">{{ __('dashboard.recent_activity') }}</h3>
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse ($this->recentActivities as $index => $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if ($index !== count($this->recentActivities) - 1)
                                        <span class="absolute top-4 right-4 -ml-px h-full w-0.5 bg-neutral-200 dark:bg-neutral-800" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3 rtl:space-x-reverse">
                                        <div>
                                            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $activity['badge_color'] }}">
                                                <flux:icon :name="$activity['icon']" class="h-4 w-4" />
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5 rtl:space-x-reverse">
                                            <div>
                                                <p class="text-xs text-neutral-800 dark:text-neutral-200">{{ $activity['title'] }}</p>
                                            </div>
                                            <div class="whitespace-nowrap text-left text-[10px] text-neutral-500">
                                                <time datetime="{{ $activity['time'] }}">{{ $activity['time']->diffForHumans() }}</time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <p class="text-center py-6 text-xs text-neutral-500">{{ __('dashboard.no_activity') }}</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Side column: Latest Leads --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900 h-fit">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold">{{ __('dashboard.latest_leads') }}</h3>
                <a href="{{ route('dashboard.leads') }}" class="text-xs text-indigo-600 underline hover:text-indigo-700">{{ __('dashboard.view_all') }}</a>
            </div>

            <div class="space-y-4">
                @forelse ($this->latestLeads as $lead)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-neutral-100 dark:border-neutral-800">
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs font-semibold truncate">{{ $lead->name ?: $lead->customer_phone }}</h4>
                            <p class="text-[10px] text-neutral-500 mt-1 truncate">
                                @if ($lead->interestedUnit)
                                    {{ __('dashboard.interested_in', ['unit' => $lead->interestedUnit->title]) }}
                                @else
                                    {{ __('dashboard.no_specific_interest') }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            @if ($lead->tier)
                                @php
                                    $color = match ($lead->tier) {
                                        App\Enums\LeadTier::Hot => 'rose',
                                        App\Enums\LeadTier::Warm => 'warning',
                                        App\Enums\LeadTier::Cold => 'neutral',
                                    };
                                @endphp
                                <flux:badge :variant="$color" size="sm">{{ $lead->tier->value }}</flux:badge>
                            @endif
                            <span class="text-[10px] text-neutral-400">{{ $lead->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-center py-10 text-xs text-neutral-500">{{ __('dashboard.no_leads') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
