<?php

use App\Models\Lead;
use App\Enums\LeadTier;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('العملاء المهتمين')] #[Layout('layouts.app')] class extends Component {
    public int $companyId;

    public string $tier = 'all';

    public string $search = '';

    public function mount(): void
    {
        $this->companyId = Auth::user()->company_id;
    }

    public function setTier(string $tier): void
    {
        if (in_array($tier, ['all', 'hot', 'warm', 'cold'])) {
            $this->tier = $tier;
        }
    }

    #[Computed]
    public function leads()
    {
        $query = Lead::where('company_id', $this->companyId)
            ->with('interestedUnit');

        if ($this->tier !== 'all') {
            $query->where('tier', $this->tier);
        }

        if (! empty(trim($this->search))) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_phone', 'like', '%'.$this->search.'%');
            });
        }

        return $query->orderBy('score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}; ?>

<div class="space-y-6" dir="rtl">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">إدارة العملاء المهتمين</h1>
            <p class="text-sm text-neutral-500 mt-1">عرض وتصنيف جميع العملاء وتتبع اهتماماتهم العقارية وسجل تفاعلاتهم.</p>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-xl border border-neutral-200 dark:bg-zinc-900 dark:border-neutral-700">
        {{-- Tabs --}}
        <div class="flex border-b border-neutral-200 dark:border-neutral-700 w-full sm:w-auto">
            <button wire:click="setTier('all')" class="px-4 py-2 text-xs font-semibold -mb-px border-b-2 transition {{ $tier === 'all' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                الكل
            </button>
            <button wire:click="setTier('hot')" class="px-4 py-2 text-xs font-semibold -mb-px border-b-2 transition {{ $tier === 'hot' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                مهتم جداً (Hot)
            </button>
            <button wire:click="setTier('warm')" class="px-4 py-2 text-xs font-semibold -mb-px border-b-2 transition {{ $tier === 'warm' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                مهتم (Warm)
            </button>
            <button wire:click="setTier('cold')" class="px-4 py-2 text-xs font-semibold -mb-px border-b-2 transition {{ $tier === 'cold' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-neutral-500 hover:text-neutral-700' }}">
                غير نشط (Cold)
            </button>
        </div>

        {{-- Search input --}}
        <div class="w-full sm:w-72">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="البحث بالاسم أو رقم الهاتف..." />
        </div>
    </div>

    {{-- Leads Table --}}
    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden dark:bg-zinc-900 dark:border-neutral-700">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 dark:bg-neutral-800/50 dark:border-neutral-700">
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">العميل</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">الميزانية</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">العقار المهتم به</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400 text-center">التقييم (Score)</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400 text-center">التصنيف (Tier)</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">تاريخ الإضافة</th>
                    <th class="p-4 text-xs font-bold text-neutral-600 dark:text-neutral-400"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                @forelse ($this->leads as $lead)
                    <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition">
                        <td class="p-4">
                            <div class="font-semibold text-xs text-neutral-800 dark:text-neutral-200">{{ $lead->name ?: 'عميل غير مسجل' }}</div>
                            <div class="text-[10px] text-neutral-500 mt-0.5" dir="ltr">{{ $lead->customer_phone }}</div>
                        </td>
                        <td class="p-4 text-xs text-neutral-700 dark:text-neutral-300">
                            @if ($lead->budget_max)
                                {{ number_format($lead->budget_max) }} ج.م
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs text-neutral-700 dark:text-neutral-300">
                            @if ($lead->interestedUnit)
                                <span class="font-medium text-indigo-600">{{ $lead->interestedUnit->title }}</span>
                            @else
                                <span class="text-neutral-400">غير محدد</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs font-semibold text-center text-neutral-700 dark:text-neutral-300">
                            {{ $lead->score }}
                        </td>
                        <td class="p-4 text-center">
                            @if ($lead->tier)
                                @php
                                    $color = match ($lead->tier) {
                                        LeadTier::Hot => 'rose',
                                        LeadTier::Warm => 'warning',
                                        LeadTier::Cold => 'neutral',
                                    };
                                @endphp
                                <flux:badge :variant="$color" size="sm">{{ $lead->tier->value }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </td>
                        <td class="p-4 text-xs text-neutral-500">
                            {{ $lead->created_at->format('Y/m/d H:i') }}
                        </td>
                        <td class="p-4 text-left">
                            <flux:button :href="route('dashboard.leads.show', $lead->id)" size="sm" variant="ghost" icon="eye" wire:navigate />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-20 text-xs text-neutral-500">
                            لا يوجد عملاء يطابقون خيارات البحث الحالية.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
