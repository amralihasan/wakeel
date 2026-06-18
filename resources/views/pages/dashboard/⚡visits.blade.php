<?php

use App\Models\Visit;
use App\Models\User;
use App\Enums\VisitStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('visits.page_title')] #[Layout('layouts.app')] class extends Component {
    public int $companyId;

    public string $statusFilter = 'all';

    public function mount(): void
    {
        $this->companyId = Auth::user()->company_id;
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'pending', 'confirmed', 'completed', 'no_show', 'cancelled'])) {
            $this->statusFilter = $filter;
        }
    }

    public function assignRep(int $visitId, int $repId): void
    {
        $visit = Visit::where('company_id', $this->companyId)->findOrFail($visitId);
        
        // Ensure user belongs to company
        $user = User::where('company_id', $this->companyId)->findOrFail($repId);
        
        $visit->update(['assigned_rep_id' => $user->id]);

        Flux::toast(variant: 'success', text: __('visits.rep_assigned_success'));
    }

    public function updateStatus(int $visitId, string $status): void
    {
        $visit = Visit::where('company_id', $this->companyId)->findOrFail($visitId);

        $statusEnum = VisitStatus::from($status);
        $visit->update(['status' => $statusEnum]);

        Flux::toast(variant: 'success', text: __('visits.status_updated_success'));
    }

    #[Computed]
    public function companyReps()
    {
        return User::where('company_id', $this->companyId)->get();
    }

    #[Computed]
    public function visits()
    {
        $query = Visit::where('company_id', $this->companyId)
            ->with(['lead', 'unit', 'assignedRep']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query->orderBy('scheduled_at', 'desc')->get();
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('visits.header_title') }}</h1>
        <p class="text-sm text-neutral-500 mt-1">{{ __('visits.subtitle') }}</p>
    </div>

    {{-- Filters Layout --}}
    <div class="flex flex-wrap items-center gap-2 bg-white p-4 rounded-xl border border-neutral-200 dark:bg-zinc-900 dark:border-neutral-700">
        <button wire:click="setFilter('all')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'all' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.all') }}
        </button>
        <button wire:click="setFilter('pending')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'pending' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.pending_label') }}
        </button>
        <button wire:click="setFilter('confirmed')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'confirmed' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.confirmed_label') }}
        </button>
        <button wire:click="setFilter('completed')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'completed' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.completed_label') }}
        </button>
        <button wire:click="setFilter('no_show')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'no_show' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.no_show_label') }}
        </button>
        <button wire:click="setFilter('cancelled')" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $statusFilter === 'cancelled' ? 'bg-neutral-800 text-white dark:bg-neutral-200 dark:text-neutral-900' : 'bg-neutral-100 text-neutral-600 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-neutral-400' }}">
            {{ __('visits.cancelled_label') }}
        </button>
    </div>

    {{-- Visits Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden dark:bg-zinc-900 dark:border-neutral-700">
        <table class="w-full {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }} border-collapse">
            <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 dark:bg-neutral-800/50 dark:border-neutral-700">
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">{{ __('visits.date_time_header') }}</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">{{ __('leads.lead_header') }}</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">{{ __('visits.unit_header') }}</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">{{ __('visits.rep_header') }}</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400 text-center">{{ __('visits.status_header') }}</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">{{ __('visits.notes') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                @forelse ($this->visits as $visit)
                    <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition">
                        {{-- Date & Time --}}
                        <td class="p-4 text-xs font-semibold text-neutral-800 dark:text-neutral-200">
                            <div>{{ $visit->scheduled_at->translatedFormat('Y/m/d') }}</div>
                            <div class="text-[10px] text-neutral-500 mt-0.5">{{ $visit->scheduled_at->translatedFormat('h:i a') }}</div>
                        </td>

                        {{-- Customer/Lead --}}
                        <td class="p-4">
                            <div class="font-semibold text-xs text-neutral-800 dark:text-neutral-200">
                                <a href="{{ route('dashboard.leads.show', $visit->lead_id) }}" class="hover:underline text-indigo-600">
                                    {{ $visit->lead?->name ?: __('leads.unregistered_lead') }}
                                </a>
                            </div>
                            <div class="text-[10px] text-neutral-500 mt-0.5" dir="ltr">{{ $visit->lead?->customer_phone }}</div>
                        </td>

                        {{-- Property/Unit --}}
                        <td class="p-4 text-xs text-neutral-800 dark:text-neutral-200">
                            {{ $visit->unit?->title ?: __('dashboard.real_estate_unit') }}
                        </td>

                        {{-- Assigned Rep --}}
                        <td class="p-4">
                            <div class="w-44">
                                <select 
                                    wire:change="assignRep({{ $visit->id }}, $event.target.value)"
                                    class="w-full text-xs rounded-lg border border-neutral-200 bg-white p-1.5 dark:border-neutral-700 dark:bg-zinc-800"
                                >
                                    <option value="">{{ __('visits.choose_rep') }}</option>
                                    @foreach ($this->companyReps as $rep)
                                        <option value="{{ $rep->id }}" @selected($visit->assigned_rep_id === $rep->id)>
                                            {{ $rep->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>

                        {{-- Visit Status --}}
                        <td class="p-4 text-center">
                            <div class="w-36 mx-auto">
                                <select 
                                    wire:change="updateStatus({{ $visit->id }}, $event.target.value)"
                                    class="w-full text-xs font-semibold rounded-lg border border-neutral-200 p-1.5 text-center dark:border-neutral-700 dark:bg-zinc-800
                                        @if($visit->status === VisitStatus::Pending) text-neutral-600 bg-neutral-50
                                        @elseif($visit->status === VisitStatus::Confirmed) text-blue-600 bg-blue-50
                                        @elseif($visit->status === VisitStatus::Completed) text-green-600 bg-green-50
                                        @elseif($visit->status === VisitStatus::NoShow) text-rose-600 bg-rose-50
                                        @elseif($visit->status === VisitStatus::Cancelled) text-amber-600 bg-amber-50
                                        @endif"
                                >
                                    @foreach (VisitStatus::cases() as $case)
                                        <option value="{{ $case->value }}" @selected($visit->status === $case)>
                                            {{ match($case) {
                                                VisitStatus::Pending => __('visits.pending'),
                                                VisitStatus::Confirmed => __('visits.confirmed'),
                                                VisitStatus::Completed => __('visits.completed'),
                                                VisitStatus::Cancelled => __('visits.cancelled'),
                                                VisitStatus::NoShow => __('visits.no_show'),
                                            } }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>

                        {{-- Notes --}}
                        <td class="p-4 text-xs text-neutral-500 max-w-xs truncate">
                            {{ $visit->notes ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-20 text-xs text-neutral-500">
                            {{ __('visits.no_visits') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
