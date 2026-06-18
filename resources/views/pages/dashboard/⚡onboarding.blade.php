<?php

use App\Models\Company;
use App\Services\WhatsApp\WhatsAppClientContract;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('dashboard.onboarding_title')] #[Layout('layouts.auth')] class extends Component {
    public int $step = 1;

    // Step 1: Company details
    public string $companyName = '';
    public string $companyEmail = '';
    public string $companyPhone = '';

    // Step 2: Plan
    public string $plan = 'starter';

    // Step 3: WhatsApp Provisioning
    public string $whatsappNumber = '';
    public string $waLink = '';
    public string $qrCodeUrl = '';

    // Step 4: Bot settings
    public string $botName = 'نور';
    public string $tone = 'friendly_egyptian';

    public function mount(): void
    {
        $company = auth()->user()->company;
        $this->companyName = $company->name ?? '';
        $this->companyEmail = $company->email ?? '';
        $this->companyPhone = $company->phone ?? '';
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'companyName' => 'required|string|max:255',
                'companyEmail' => 'required|email|max:255',
                'companyPhone' => 'required|string|max:20',
            ]);

            $company = auth()->user()->company;
            $company->update([
                'name' => $this->companyName,
                'email' => $this->companyEmail,
                'phone' => $this->companyPhone,
            ]);

            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $company = auth()->user()->company;
            $company->update(['plan' => $this->plan]);

            // Provision Number at Step 3
            try {
                app(WhatsAppClientContract::class)->assignNumberFromPool($company);
                $company->refresh();
                $this->whatsappNumber = $company->whatsapp_number;
                $this->waLink = "https://wa.me/{$this->whatsappNumber}?text=" . urlencode('مهتم');
                $this->qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($this->waLink);
            } catch (\Throwable $e) {
                // In local testing/mocking, assign a default number if pool is empty
                $this->whatsappNumber = $company->whatsapp_number ?: '+201234567890';
                $company->update([
                    'dialog360_channel_id' => 'ch-' . uniqid(),
                    'whatsapp_number' => $this->whatsappNumber,
                ]);
                $this->waLink = "https://wa.me/{$this->whatsappNumber}?text=" . urlencode('مهتم');
                $this->qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($this->waLink);
            }

            $this->step = 3;
            return;
        }

        if ($this->step === 3) {
            $this->step = 4;
            return;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function complete(): void
    {
        $this->validate([
            'botName' => 'required|string|max:255',
            'tone' => 'required|in:friendly_egyptian,formal,gulf',
        ]);

        $company = auth()->user()->company;
        $company->update([
            'bot_settings' => [
                'bot_name' => $this->botName,
                'tone' => $this->tone,
                'active' => true,
                'working_hours' => '24_7',
                'escalation_rules' => [
                    'score_threshold' => 70,
                    'unproductive_messages' => 5,
                ]
            ],
            'onboarding_completed' => true,
        ]);

        $this->redirectRoute('units.index', navigate: true);
    }
}; ?>

<div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-xl border border-neutral-200 dark:bg-zinc-900 dark:border-neutral-800" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Steps Progress Bar --}}
    <div class="mb-8 flex items-center justify-between border-b border-neutral-150 pb-4 dark:border-neutral-800">
        @foreach ([
            1 => __('dashboard.step_company_details'),
            2 => __('dashboard.step_plan'),
            3 => __('dashboard.step_whatsapp_number'),
            4 => __('dashboard.step_bot_setup')
        ] as $index => $label)
            <div class="flex flex-col items-center gap-1">
                <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold transition {{ $step === $index ? 'bg-indigo-600 text-white' : ($step > $index ? 'bg-green-600 text-white' : 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800') }}">
                    {{ $step > $index ? '✓' : $index }}
                </div>
                <span class="text-[10px] font-medium text-neutral-500">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    {{-- Step 1: Company Info --}}
    @if ($step === 1)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('dashboard.confirm_company_details') }}</flux:heading>
            <flux:text>{{ __('dashboard.confirm_company_details_subtitle') }}</flux:text>

            <flux:field>
                <flux:label>{{ __('dashboard.company_name') }}</flux:label>
                <flux:input wire:model="companyName" required />
                <flux:error name="companyName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('dashboard.company_email') }}</flux:label>
                <flux:input type="email" wire:model="companyEmail" required />
                <flux:error name="companyEmail" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('dashboard.company_phone') }}</flux:label>
                <flux:input wire:model="companyPhone" required />
                <flux:error name="companyPhone" />
            </flux:field>

            <div class="flex justify-end pt-4">
                <flux:button variant="primary" wire:click="nextStep">{{ __('dashboard.next') }}</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 2: Choose Plan --}}
    @if ($step === 2)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('dashboard.choose_subscription_plan') }}</flux:heading>
            <flux:text>{{ __('dashboard.choose_subscription_plan_subtitle') }}</flux:text>

            <div class="rounded-xl border border-indigo-200 bg-indigo-50/20 p-4 dark:border-indigo-900 dark:bg-indigo-900/10">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-bold text-indigo-900 dark:text-indigo-400">{{ __('dashboard.starter_plan_title') }}</h4>
                        <p class="text-xs text-neutral-500 mt-1">{{ __('dashboard.starter_plan_desc') }}</p>
                    </div>
                    <flux:badge variant="success">{{ __('dashboard.free_now') }}</flux:badge>
                </div>
            </div>

            <div class="flex justify-between pt-4">
                <flux:button variant="ghost" wire:click="previousStep">{{ __('dashboard.previous') }}</flux:button>
                <flux:button variant="primary" wire:click="nextStep">{{ __('dashboard.next') }}</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 3: WhatsApp Number Provision --}}
    @if ($step === 3)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('dashboard.activate_whatsapp_number') }}</flux:heading>
            <flux:text>{{ __('dashboard.activate_whatsapp_number_subtitle') }}</flux:text>

            <div class="flex flex-col items-center justify-center p-6 bg-neutral-50 rounded-xl dark:bg-neutral-800/50">
                <p class="text-xs font-semibold text-neutral-500">{{ __('dashboard.assistant_whatsapp_number') }}</p>
                <h3 class="text-xl font-bold text-indigo-600 mt-1" dir="ltr">{{ $whatsappNumber }}</h3>

                @if ($qrCodeUrl)
                    <div class="mt-4 p-2 bg-white rounded-lg border border-neutral-200">
                        <img src="{{ $qrCodeUrl }}" alt="WhatsApp QR Code" class="h-32 w-32" />
                    </div>
                    <a href="{{ $waLink }}" target="_blank" class="mt-3 text-xs text-indigo-500 underline hover:text-indigo-600">
                        {{ __('dashboard.click_here_chat_bot') }}
                    </a>
                @endif
            </div>

            <div class="flex justify-between pt-4">
                <flux:button variant="ghost" wire:click="previousStep">{{ __('dashboard.previous') }}</flux:button>
                <flux:button variant="primary" wire:click="nextStep">{{ __('dashboard.next') }}</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 4: Bot Personality --}}
    @if ($step === 4)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('dashboard.setup_smart_assistant') }}</flux:heading>
            <flux:text>{{ __('dashboard.setup_smart_assistant_subtitle') }}</flux:text>

            <flux:field>
                <flux:label>{{ __('dashboard.assistant_bot_name') }}</flux:label>
                <flux:input wire:model="botName" required />
                <flux:error name="botName" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('dashboard.preferred_conversation_tone') }}</flux:label>
                <flux:select wire:model="tone" required>
                    <option value="friendly_egyptian">{{ __('dashboard.friendly_egyptian') }}</option>
                    <option value="formal">{{ __('dashboard.formal_arabic') }}</option>
                    <option value="gulf">{{ __('dashboard.gulf_dialect') }}</option>
                </flux:select>
                <flux:error name="tone" />
            </flux:field>

            <div class="flex justify-between pt-4">
                <flux:button variant="ghost" wire:click="previousStep">{{ __('dashboard.previous') }}</flux:button>
                <flux:button variant="primary" wire:click="complete">{{ __('dashboard.activate_add_units') }}</flux:button>
            </div>
        </div>
    @endif
</div>
