<?php

use App\Models\Company;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الاشتراك والفوترة')] class extends Component {
    #[Computed]
    public function company(): Company
    {
        return Auth::user()->company;
    }

    public function checkout(string $planName): void
    {
        $company = $this->company;

        $priceId = config("plans.{$planName}.price_id");

        $this->redirect(
            $company->newSubscription('default', $priceId)
                ->checkout([
                    'success_url' => route('billing.index'),
                    'cancel_url' => route('billing.index'),
                ])
                ->url
        );
    }

    public function manage(): void
    {
        $this->redirect(
            Auth::user()->company->billingPortalUrl(route('billing.index'))
        );
    }
}; ?>

<div class="space-y-6" dir="rtl">
    <h1 class="text-2xl font-bold tracking-tight">الاشتراك والفوترة</h1>

    {{-- Current Plan --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold mb-4">الخطة الحالية</h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold">{{ config("plans.{$this->company->plan}.name") }}</p>
                <p class="text-sm text-neutral-500 mt-1">
                    @if ($this->company->billing_cycle_start)
                        دورة الفوترة: {{ $this->company->billing_cycle_start->format('d M Y') }} - {{ $this->company->billing_cycle_end?->format('d M Y') }}
                    @else
                        لم تبدأ دورة الفوترة بعد
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                @if ($this->company->hasStripeId())
                    <flux:button wire:click="manage" variant="primary">إدارة الاشتراك</flux:button>
                @else
                    <flux:button wire:click="checkout('growth')" variant="primary">ترقية الخطة</flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Usage --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold mb-4">استهلاك الخطة</h2>
        <div class="grid gap-4 md:grid-cols-3">
            {{-- Conversations --}}
            @php $convLimit = config("plans.{$this->company->plan}.conversations_limit"); @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">المحادثات</p>
                <p class="text-xl font-bold">{{ $this->company->conversations_count }} / {{ $convLimit === -1 ? 'غير محدود' : $convLimit }}</p>
                @if ($convLimit !== -1)
                    <div class="mt-2 h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $this->company->hasReachedConversationsLimit() ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ min(100, round(($this->company->conversations_count / $convLimit) * 100)) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- Units --}}
            @php $unitsLimit = config("plans.{$this->company->plan}.units_limit"); $unitsCount = $this->company->units()->count(); @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">الوحدات العقارية</p>
                <p class="text-xl font-bold">{{ $unitsCount }} / {{ $unitsLimit === -1 ? 'غير محدود' : $unitsLimit }}</p>
                @if ($unitsLimit !== -1)
                    <div class="mt-2 h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $this->company->hasReachedUnitsLimit() ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ min(100, round(($unitsCount / $unitsLimit) * 100)) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- Sales Reps --}}
            @php $repsLimit = config("plans.{$this->company->plan}.reps_limit"); $repsCount = $this->company->users()->where('role', 'sales_rep')->count(); @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">ممثلي المبيعات</p>
                <p class="text-xl font-bold">{{ $repsCount }} / {{ $repsLimit === -1 ? 'غير محدود' : $repsLimit }}</p>
                @if ($repsLimit !== -1)
                    <div class="mt-2 h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $this->company->hasReachedRepsLimit() ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ min(100, round(($repsCount / $repsLimit) * 100)) }}%"></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Plans --}}
    <div class="grid gap-4 md:grid-cols-3">
        @foreach (config('plans') as $key => $plan)
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900 {{ $this->company->plan === $key ? 'ring-2 ring-indigo-500' : '' }}">
                <h3 class="text-lg font-bold">{{ $plan['name'] }}</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    <li>📞 {{ $plan['numbers_limit'] === -1 ? 'غير محدود' : $plan['numbers_limit'] }} رقم واتساب</li>
                    <li>🏠 {{ $plan['units_limit'] === -1 ? 'غير محدود' : $plan['units_limit'] }} وحدة عقارية</li>
                    <li>👥 {{ $plan['reps_limit'] === -1 ? 'غير محدود' : $plan['reps_limit'] }} ممثل مبيعات</li>
                    <li>💬 {{ $plan['conversations_limit'] === -1 ? 'غير محدود' : number_format($plan['conversations_limit']) }} محادثة/شهر</li>
                </ul>
                @if ($this->company->plan !== $key)
                    <flux:button wire:click="checkout('{{ $key }}')" variant="primary" class="mt-4 w-full">الاشتراك في {{ $plan['name'] }}</flux:button>
                @endif
            </div>
        @endforeach
    </div>
</div>
