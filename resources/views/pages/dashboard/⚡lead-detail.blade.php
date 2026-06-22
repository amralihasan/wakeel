<?php

use App\Models\Lead;
use App\Models\Message;
use App\Models\Visit;
use App\Models\Handoff;
use App\Enums\LeadTier;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('leads.lead_profile')] #[Layout('layouts.app')] class extends Component {
    public Lead $lead;

    public string $activeTab = 'chat';

    public function mount(Lead $lead): void
    {
        if ($lead->company_id !== Auth::user()->company_id) {
            abort(403);
        }
        $this->lead = $lead;
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['chat', 'timeline'])) {
            $this->activeTab = $tab;
        }
    }

    #[Computed]
    public function messages()
    {
        if (! $this->lead->conversation) {
            return collect();
        }

        return Message::where('conversation_id', $this->lead->conversation->id)
            ->orderBy('id', 'asc')
            ->get();
    }

    #[Computed]
    public function timelineEvents()
    {
        $events = collect();

        // 1. Lead Created Event
        $events->push([
            'title' => __('leads.event_registered'),
            'description' => __('leads.source') . ': ' . match ($this->lead->source) {
                'facebook' => __('leads.source_facebook'),
                'website' => __('leads.source_website'),
                'qr' => __('leads.source_qr'),
                default => __('leads.source_other')
            },
            'time' => $this->lead->created_at,
            'icon' => 'user-plus',
            'color' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400',
        ]);

        // 2. Visits Events
        $visits = Visit::where('lead_id', $this->lead->id)->get();
        foreach ($visits as $visit) {
            $events->push([
                'title' => __('leads.event_visit_booked'),
                'description' => __('leads.event_visit_desc', [
                    'unit' => ($visit->unit?->title ?? __('leads.unknown')),
                    'status' => $visit->status->value
                ]),
                'time' => $visit->created_at,
                'icon' => 'calendar-days',
                'color' => 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400',
            ]);
        }

        // 3. Handoff Events
        $handoffs = Handoff::where('lead_id', $this->lead->id)->get();
        foreach ($handoffs as $handoff) {
            $events->push([
                'title' => __('leads.event_escalated'),
                'description' => __('leads.event_escalated_desc', [
                    'reason' => $handoff->reason,
                    'summary' => $handoff->ai_summary
                ]),
                'time' => $handoff->created_at,
                'icon' => 'arrow-right-start-on-rectangle',
                'color' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/20 dark:text-rose-400',
            ]);
        }

        // 4. Scored Signals Events
        $signals = $this->lead->scored_signals ?? [];
        foreach ($signals as $signal => $data) {
            $points = $data['points'] ?? 0;
            $timestamp = isset($data['scored_at']) ? \Carbon\Carbon::parse($data['scored_at']) : $this->lead->updated_at;
            
            $events->push([
                'title' => __('leads.event_signal_achieved', [
                    'signal' => match ($signal) {
                        'asked_price' => __('leads.signal_asked_price'),
                        'asked_installment' => __('leads.signal_asked_installment'),
                        'asked_media' => __('leads.signal_asked_media'),
                        'booked_visit' => __('leads.signal_booked_visit'),
                        default => $signal
                    }
                ]),
                'description' => __('leads.event_signal_desc', [
                    'points' => $points,
                    'score' => $this->lead->score
                ]),
                'time' => $timestamp,
                'icon' => 'bolt',
                'color' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400',
            ]);
        }

        return $events->sortByDesc('time')->values();
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Header with back button --}}
    <div class="flex items-center gap-4">
        <flux:button :href="route('dashboard.leads')" :icon="app()->getLocale() === 'ar' ? 'arrow-right' : 'arrow-left'" size="sm" variant="ghost" wire:navigate />
        <div>
            <h1 class="text-xl font-bold tracking-tight">{{ __('leads.lead_details_title', ['name' => $lead->name ?: $lead->customer_phone]) }}</h1>
            <p class="text-xs text-neutral-500 mt-1">{{ __('leads.lead_details_subtitle') }}</p>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profile Panel (Left) --}}
        <div class="space-y-6 lg:col-span-1">
            <div class="bg-white rounded-xl border border-neutral-200 p-6 dark:bg-zinc-900 dark:border-neutral-700">
                <h3 class="text-sm font-semibold mb-4 pb-2 border-b border-neutral-100 dark:border-neutral-800">{{ __('leads.profile_data') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.name') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $lead->name ?: __('leads.not_specified') }}</span>
                    </div>

                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.phone_number') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200" dir="ltr">{{ $lead->customer_phone }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <span class="block text-[10px] text-neutral-400">{{ __('leads.interest_score') }}</span>
                            <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $lead->score }} / 100</span>
                        </div>
                        @if ($lead->tier)
                            @php
                                $color = match ($lead->tier) {
                                    LeadTier::Hot => 'rose',
                                    LeadTier::Warm => 'warning',
                                    LeadTier::Cold => 'neutral',
                                };
                            @endphp
                            <flux:badge :variant="$color" size="sm">{{ $lead->tier->value }}</flux:badge>
                        @endif
                    </div>

                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.lead_status') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $lead->status }}</span>
                    </div>
                </div>
            </div>

            {{-- Preferences Card --}}
            <div class="bg-white rounded-xl border border-neutral-200 p-6 dark:bg-zinc-900 dark:border-neutral-700">
                <h3 class="text-sm font-semibold mb-4 pb-2 border-b border-neutral-100 dark:border-neutral-800">{{ __('leads.real_estate_preferences') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.max_budget') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                            {{ $lead->budget_max ? number_format($lead->budget_max) . ' ' . __('leads.egp') : __('leads.not_specified') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.preferred_rooms_count') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                            {{ $lead->preferred_rooms ? $lead->preferred_rooms . ' ' . __('leads.rooms') : __('leads.not_specified') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.preferred_location') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $lead->preferred_location ?: __('leads.not_specified') }}</span>
                    </div>

                    <div>
                        <span class="block text-[10px] text-neutral-400">{{ __('leads.interested_unit') }}</span>
                        <span class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                            @if ($lead->interestedUnit)
                                <span class="text-indigo-600">{{ $lead->interestedUnit->title }}</span>
                            @else
                                {{ __('leads.not_specified') }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Area (Right) --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-neutral-200 overflow-hidden dark:bg-zinc-900 dark:border-neutral-700 flex flex-col min-h-[500px]">
            {{-- Tabs Header --}}
            <div class="flex border-b border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50">
                <button wire:click="setTab('chat')" class="flex-1 py-3 text-xs font-bold text-center border-b-2 transition {{ $activeTab === 'chat' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                    {{ __('leads.client_conversations') }}
                </button>
                <button wire:click="setTab('timeline')" class="flex-1 py-3 text-xs font-bold text-center border-b-2 transition {{ $activeTab === 'timeline' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                    {{ __('leads.activity_signals_log') }}
                </button>
            </div>

            {{-- Tab Contents --}}
            <div class="flex-1 p-6 overflow-y-auto max-h-[600px]">
                @if ($activeTab === 'chat')
                    {{-- Conversation Messages list --}}
                    <div class="space-y-1.5">
                        @forelse ($this->messages as $message)
                            <div class="flex {{ $message->direction === MessageDirection::Inbound ? 'justify-start' : 'justify-end' }}">
                                <div dir="rtl" class="max-w-[75%] rounded-xl px-3 py-1.5 text-xs shadow-2xs leading-relaxed {{ $message->direction === MessageDirection::Inbound ? 'bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200' : ($message->sender === MessageSender::Bot ? 'bg-blue-600 text-white' : 'bg-indigo-600 text-white') }}">
                                    {{ $message->body }}
                                    @if ($message->media_url)
                                        <div class="mt-2">
                                            @if (str_starts_with($message->media_type, 'image/'))
                                                <img src="{{ $message->media_url }}" class="max-h-48 rounded" />
                                            @else
                                                <a href="{{ $message->media_url }}" target="_blank" class="underline text-[10px]">{{ __('leads.view_attachment', ['type' => $message->media_type]) }}</a>
                                            @endif
                                        </div>
                                    @endif
                                    <span class="block text-[9px] text-left opacity-70 mt-1">
                                        {{ $message->created_at->format('H:i') }} | 
                                        {{ $message->direction === MessageDirection::Inbound ? __('leads.client') : ($message->sender === MessageSender::Bot ? __('leads.smart_assistant') : __('dashboard.human_agent')) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-20 text-neutral-400">{{ __('leads.no_messages_recorded') }}</div>
                        @endforelse
                    </div>
                @else
                    {{-- Timeline Events list --}}
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse ($this->timelineEvents as $index => $event)
                                <li>
                                    <div class="relative pb-8">
                                        @if ($index !== count($this->timelineEvents) - 1)
                                            <span class="absolute top-4 right-4 -ml-px h-full w-0.5 bg-neutral-200 dark:bg-neutral-800" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3 rtl:space-x-reverse">
                                            <div>
                                                <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $event['color'] }}">
                                                    <flux:icon :name="$event['icon']" class="h-4 w-4" />
                                                </span>
                                            </div>
                                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5 rtl:space-x-reverse">
                                                <div>
                                                    <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ $event['title'] }}</p>
                                                    <p class="text-[10px] text-neutral-500 mt-0.5">{{ $event['description'] }}</p>
                                                </div>
                                                <div class="whitespace-nowrap text-left text-[10px] text-neutral-400">
                                                    <time datetime="{{ $event['time'] }}">{{ $event['time']->format('Y/m/d H:i') }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <p class="text-center py-20 text-xs text-neutral-500">{{ __('leads.no_events_recorded') }}</p>
                            @endforelse
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
