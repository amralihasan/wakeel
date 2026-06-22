<?php

use App\Models\Company;
use App\Services\PaymobService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('billing.title')] class extends Component {
    #[Computed]
    public function company(): Company
    {
        return Auth::user()->company;
    }

    public function checkout(string $planName, PaymobService $paymob): void
    {
        $company = $this->company;
        $plan = config("plans.{$planName}");

        if (! $plan) {
            return;
        }

        try {
            $user = Auth::user();
            $token = $paymob->getAuthToken();

            $merchantOrderId = "company_{$company->id}_plan_{$planName}_" . time();

            $amount = ($plan['price_cents'] ?? 0) / 100;

            $paymobOrderId = $paymob->registerOrder(
                $token,
                $amount,
                $merchantOrderId
            );

            $nameParts = explode(' ', $user->name ?? 'User', 2);
            $firstName = $nameParts[0] ?: 'User';
            $lastName = $nameParts[1] ?? 'User';

            $paymentKey = $paymob->getPaymentKey(
                $token,
                $paymobOrderId,
                $amount,
                [
                    'email' => $company->email ?: $user->email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => $company->phone ?: '+201234567890',
                ]
            );

            $this->redirect($paymob->getCheckoutUrl($paymentKey));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Paymob checkout failed', ['error' => $e->getMessage()]);
            $this->js("alert('" . addslashes(__('billing.checkout_failed_error')) . "')");
        }
    }
}; ?>

<div class="space-y-6" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    <h1 class="text-2xl font-bold tracking-tight">{{ __('billing.title') }}</h1>

    {{-- Current Plan --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold mb-4">{{ __('billing.current_plan') }}</h2>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-2xl font-bold">{{ __('billing.plan_' . $this->company->plan) }}</p>
                <p class="text-sm text-neutral-500 mt-1">
                    @if ($this->company->billing_cycle_start)
                        {{ __('billing.billing_cycle') }}: {{ $this->company->billing_cycle_start->format('d M Y') }} - {{ $this->company->billing_cycle_end?->format('d M Y') }}
                    @else
                        {{ __('billing.no_billing_cycle') }}
                    @endif
                </p>
            </div>
            <div class="flex gap-2">
                @if ($this->company->hasPaymobSubscription())
                    <span class="text-sm text-green-600 font-medium bg-green-50 px-3 py-1 rounded-full dark:bg-green-950 dark:text-green-400">{{ __('billing.current_plan') }}</span>
                @else
                    <flux:button wire:click="checkout('growth')" variant="primary">{{ __('billing.upgrade_plan_btn') }}</flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Usage --}}
    <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold mb-4">{{ __('billing.usage_limits') }}</h2>
        <div class="grid gap-4 md:grid-cols-3">
            {{-- Conversations --}}
            @php $planDetails = $this->company->getPlanDetails(); $convLimit = $planDetails['limits']['conversation_quota'] ?? null; @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">{{ __('billing.conversations') }}</p>
                <p class="text-xl font-bold">{{ $this->company->conversations_count }} / {{ $convLimit === null ? __('billing.unlimited') : $convLimit }}</p>
                @if ($convLimit !== null)
                    <div class="mt-2 h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $this->company->hasReachedConversationsLimit() ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ min(100, round(($this->company->conversations_count / $convLimit) * 100)) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- Units --}}
            @php $unitsLimit = $planDetails['limits']['units'] ?? null; $unitsCount = $this->company->units()->count(); @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">{{ __('billing.units') }}</p>
                <p class="text-xl font-bold">{{ $unitsCount }} / {{ $unitsLimit === null ? __('billing.unlimited') : $unitsLimit }}</p>
                @if ($unitsLimit !== null)
                    <div class="mt-2 h-2 w-full rounded-full bg-neutral-100 dark:bg-neutral-800">
                        <div class="h-2 rounded-full {{ $this->company->hasReachedUnitsLimit() ? 'bg-red-500' : 'bg-indigo-500' }}" style="width: {{ min(100, round(($unitsCount / $unitsLimit) * 100)) }}%"></div>
                    </div>
                @endif
            </div>

            {{-- Sales Reps --}}
            @php $repsLimit = $planDetails['limits']['reps'] ?? null; $repsCount = $this->company->users()->where('role', 'sales_rep')->count(); @endphp
            <div class="rounded-lg border border-neutral-100 p-4 dark:border-neutral-800">
                <p class="text-sm text-neutral-500">{{ __('billing.reps') }}</p>
                <p class="text-xl font-bold">{{ $repsCount }} / {{ $repsLimit === null ? __('billing.unlimited') : $repsLimit }}</p>
                @if ($repsLimit !== null)
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
                <h3 class="text-lg font-bold">{{ __('billing.plan_' . $key) }}</h3>
                <p class="text-2xl font-bold mt-2">{{ number_format(($plan['price_cents'] ?? 0) / 100, 2) }} {{ $plan['currency'] ?? 'EGP' }}<span class="text-sm font-normal text-neutral-500">/{{ __('billing.month') }}</span></p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li>📞 {{ ($plan['limits']['numbers'] ?? null) === null ? __('billing.unlimited') : __('billing.numbers_limit_desc', ['limit' => $plan['limits']['numbers']]) }}</li>
                    <li>🏠 {{ ($plan['limits']['units'] ?? null) === null ? __('billing.unlimited') : __('billing.units_limit_desc', ['limit' => $plan['limits']['units']]) }}</li>
                    <li>👥 {{ ($plan['limits']['reps'] ?? null) === null ? __('billing.unlimited') : __('billing.reps_limit_desc', ['limit' => $plan['limits']['reps']]) }}</li>
                    <li>💬 {{ ($plan['limits']['conversation_quota'] ?? null) === null ? __('billing.unlimited') : __('billing.conversations_limit_desc', ['limit' => number_format($plan['limits']['conversation_quota'])]) }}</li>
                </ul>
                @if ($this->company->plan !== $key)
                    <flux:button wire:click="checkout('{{ $key }}')" variant="primary" class="mt-4 w-full">{{ __('billing.subscribe_to', ['plan' => __('billing.plan_' . $key)]) }}</flux:button>
                @endif
            </div>
        @endforeach
    </div>
</div>
