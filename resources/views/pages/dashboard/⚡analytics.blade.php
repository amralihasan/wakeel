<?php

use App\Models\Message;
use App\Models\Lead;
use App\Models\Visit;
use App\Models\Handoff;
use App\Models\User;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('analytics.title')] #[Layout('layouts.app')] class extends Component {
    public int $companyId;

    public function mount(): void
    {
        $this->companyId = Auth::user()->company_id;
    }

    #[Computed]
    public function monthlyTotals(): array
    {
        $conversations = Message::where('company_id', $this->companyId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $qualifiedLeads = Lead::where('company_id', $this->companyId)
            ->where('score', '>=', 70)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $viewings = Visit::where('company_id', $this->companyId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalLeads = Lead::where('company_id', $this->companyId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $completedViewings = Visit::where('company_id', $this->companyId)
            ->where('status', VisitStatus::Completed)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $conversionRate = $totalLeads > 0
            ? round(($completedViewings / $totalLeads) * 100)
            : 0;

        return [
            'conversations' => $conversations,
            'qualified_leads' => $qualifiedLeads,
            'viewings' => $viewings,
            'conversion_rate' => $conversionRate,
        ];
    }

    #[Computed]
    public function leadSources(): array
    {
        $sources = Lead::where('company_id', $this->companyId)
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->get()
            ->pluck('count', 'source')
            ->toArray();

        $fb = $sources['facebook'] ?? 0;
        $web = $sources['website'] ?? 0;
        $qr = $sources['qr'] ?? 0;
        $other = $sources['other'] ?? 0;
        $total = max($fb + $web + $qr + $other, 1);

        return [
            [
                'name' => __('analytics.facebook'),
                'count' => $fb,
                'percentage' => round(($fb / $total) * 100),
                'color' => 'bg-blue-600',
            ],
            [
                'name' => __('analytics.website'),
                'count' => $web,
                'percentage' => round(($web / $total) * 100),
                'color' => 'bg-green-600',
            ],
            [
                'name' => __('analytics.qr_code'),
                'count' => $qr,
                'percentage' => round(($qr / $total) * 100),
                'color' => 'bg-indigo-600',
            ],
            [
                'name' => __('analytics.others'),
                'count' => $other,
                'percentage' => round(($other / $total) * 100),
                'color' => 'bg-neutral-500',
            ],
        ];
    }

    #[Computed]
    public function repPerformance(): array
    {
        $reps = User::where('company_id', $this->companyId)->get();
        $performance = [];

        foreach ($reps as $rep) {
            $handoffs = Handoff::where('company_id', $this->companyId)
                ->where('agent_id', $rep->id)
                ->count();

            $completedVisits = Visit::where('company_id', $this->companyId)
                ->where('assigned_rep_id', $rep->id)
                ->where('status', VisitStatus::Completed)
                ->count();

            $performance[] = [
                'name' => $rep->name,
                'role' => $rep->role->value,
                'handoffs' => $handoffs,
                'conversions' => $completedVisits,
            ];
        }

        // Sort by conversions desc
        usort($performance, fn($a, $b) => $b['conversions'] <=> $a['conversions']);

        return $performance;
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('analytics.header_title') }}</h1>
        <p class="text-sm text-neutral-500 mt-1">{{ __('analytics.subtitle') }}</p>
    </div>

    {{-- Monthly Totals Metrics Grid --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        {{-- conversations --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <span class="block text-xs font-medium text-neutral-500">{{ __('analytics.total_messages_month') }}</span>
            <span class="block text-3xl font-semibold tracking-tight mt-2">{{ $this->monthlyTotals['conversations'] }}</span>
        </div>

        {{-- qualified leads --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <span class="block text-xs font-medium text-neutral-500">{{ __('analytics.qualified_leads_desc') }}</span>
            <span class="block text-3xl font-semibold tracking-tight mt-2 text-rose-600">{{ $this->monthlyTotals['qualified_leads'] }}</span>
        </div>

        {{-- viewings --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <span class="block text-xs font-medium text-neutral-500">{{ __('analytics.booked_visits') }}</span>
            <span class="block text-3xl font-semibold tracking-tight mt-2">{{ $this->monthlyTotals['viewings'] }}</span>
        </div>

        {{-- conversion rate --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-xs dark:border-neutral-700 dark:bg-zinc-900">
            <span class="block text-xs font-medium text-neutral-500">{{ __('analytics.visit_conversion_pct') }}</span>
            <span class="block text-3xl font-semibold tracking-tight mt-2 text-green-600">{{ $this->monthlyTotals['conversion_rate'] }}%</span>
        </div>
    </div>

    {{-- Analysis Split Panels --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Sources Breakdown --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold mb-6">{{ __('analytics.lead_sources') }}</h3>
            
            <div class="space-y-6">
                @foreach ($this->leadSources as $source)
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium text-neutral-800 dark:text-neutral-200">{{ $source['name'] }}</span>
                            <span class="font-semibold text-neutral-600 dark:text-neutral-400">
                                {{ __('analytics.lead_count_pct', ['count' => $source['count'], 'percentage' => $source['percentage']]) }}
                            </span>
                        </div>
                        <div class="h-3 w-full bg-neutral-100 dark:bg-neutral-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $source['color'] }}" style="width: {{ $source['percentage'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Reps Performance Leaderboard --}}
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
            <h3 class="text-sm font-semibold mb-6">{{ __('analytics.performance_leaderboard') }}</h3>
            
            <table class="w-full {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }} border-collapse">
                <thead>
                    <tr class="border-b border-neutral-150 dark:border-neutral-800 text-xs text-neutral-500">
                        <th class="pb-3 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ __('analytics.rep_label') }}</th>
                        <th class="pb-3 text-center">{{ __('analytics.claimed_conversations') }}</th>
                        <th class="pb-3 text-center">{{ __('analytics.completed_visits') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800 text-xs">
                    @forelse ($this->repPerformance as $rep)
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition">
                            <td class="py-3">
                                <div class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $rep['name'] }}</div>
                                <div class="text-[10px] text-neutral-500 mt-0.5">{{ $rep['role'] === 'owner' ? __('analytics.owner') : __('analytics.sales_agent') }}</div>
                            </td>
                            <td class="py-3 text-center font-semibold text-neutral-700 dark:text-neutral-300">
                                {{ $rep['handoffs'] }}
                            </td>
                            <td class="py-3 text-center font-semibold text-green-600">
                                {{ $rep['conversions'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-10 text-neutral-400">{{ __('analytics.no_reps') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
