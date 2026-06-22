<?php

use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Services\Agent\AgentRunner;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('bot.assistant_settings_title')] #[Layout('layouts.app')] class extends Component {
    public string $botName = '';

    public string $tone = '';

    public string $workingHours = '';

    public bool $active = true;

    public int $scoreThreshold = 70;

    public int $unproductiveMessages = 5;

    public bool $followUpsEnabled = true;

    public int $maxFollowUps = 3;

    public string $testMessage = '';

    public int $companyId;

    public int $sandboxConversationId;

    public function mount(): void
    {
        $company = Auth::user()->company;
        $this->companyId = $company->id;

        $settings = $company->bot_settings ?? [];
        $this->botName = $settings['bot_name'] ?? 'نور';
        $this->tone = $settings['tone'] ?? 'friendly_egyptian';
        $this->workingHours = $settings['working_hours'] ?? '24_7';
        $this->active = $settings['active'] ?? true;
        $this->scoreThreshold = $settings['escalation_rules']['score_threshold'] ?? 70;
        $this->unproductiveMessages = $settings['escalation_rules']['unproductive_messages'] ?? 5;
        $this->followUpsEnabled = $settings['follow_ups_enabled'] ?? true;
        $this->maxFollowUps = $settings['max_follow_ups'] ?? 3;

        // Initialize sandbox conversation for testing
        $lead = Lead::firstOrCreate(
            ['company_id' => $this->companyId, 'customer_phone' => '+200000000000'],
            ['name' => __('bot.sandbox_lead_name'), 'status' => 'new', 'source' => 'other']
        );

        $conversation = Conversation::firstOrCreate(
            ['company_id' => $this->companyId, 'customer_phone' => '+200000000000'],
            ['lead_id' => $lead->id, 'mode' => 'bot']
        );

        $this->sandboxConversationId = $conversation->id;
    }

    public function saveSettings(): void
    {
        $this->validate([
            'botName' => 'required|string|max:255',
            'tone' => 'required|in:friendly_egyptian,formal,gulf',
            'workingHours' => 'required|in:24_7,working_hours',
            'scoreThreshold' => 'required|integer|min:0|max:100',
            'unproductiveMessages' => 'required|integer|min:1|max:50',
            'followUpsEnabled' => 'required|boolean',
            'maxFollowUps' => 'required|integer|min:1|max:10',
        ]);

        $company = Company::findOrFail($this->companyId);
        $company->update([
            'bot_settings' => [
                'bot_name' => $this->botName,
                'tone' => $this->tone,
                'active' => $this->active,
                'working_hours' => $this->workingHours,
                'follow_ups_enabled' => $this->followUpsEnabled,
                'max_follow_ups' => $this->maxFollowUps,
                'escalation_rules' => [
                    'score_threshold' => $this->scoreThreshold,
                    'unproductive_messages' => $this->unproductiveMessages,
                ]
            ]
        ]);

        Flux::toast(variant: 'success', text: __('bot.success_saved'));
    }

    public function sendTestMessage(): void
    {
        if (empty(trim($this->testMessage))) {
            return;
        }

        $company = Company::findOrFail($this->companyId);

        // Store customer inbound message
        Message::create([
            'company_id' => $this->companyId,
            'conversation_id' => $this->sandboxConversationId,
            'direction' => MessageDirection::Inbound,
            'sender' => MessageSender::Customer,
            'body' => $this->testMessage,
            'created_at' => now(),
        ]);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone('+200000000000');
        $session->pushTurn(['role' => 'user', 'content' => $this->testMessage]);

        $text = $this->testMessage;
        $this->testMessage = '';

        // Run agent runner against sandbox phone
        app(AgentRunner::class)->handle($company, '+200000000000', $text);
    }

    public function resetSandbox(): void
    {
        $company = Company::findOrFail($this->companyId);

        // Delete all messages associated with the sandbox conversation
        Message::where('conversation_id', $this->sandboxConversationId)->delete();

        // Clear conversation session history from cache
        Cache::forget("session:{$this->companyId}:+200000000000.history");
        Cache::forget("session:{$this->companyId}:+200000000000.mode");
        Cache::forget("session:{$this->companyId}:+200000000000.updated_at");

        // Reset the Lead state associated with sandbox
        $lead = Lead::where('company_id', $this->companyId)
            ->where('customer_phone', '+200000000000')
            ->first();

        if ($lead) {
            $lead->update([
                'name' => __('bot.sandbox_lead_name'),
                'status' => 'new',
                'budget_max' => null,
                'score' => 0,
            ]);
        }

        // Reset the Conversation mode back to bot
        $conversation = Conversation::find($this->sandboxConversationId);
        if ($conversation) {
            $conversation->update(['mode' => 'bot']);
        }

        Flux::toast(variant: 'success', text: __('bot.sandbox_reset_success'));
    }

    #[Computed]
    public function sandboxMessages()
    {
        return Message::where('conversation_id', $this->sandboxConversationId)
            ->orderBy('id', 'asc')
            ->get();
    }
}; ?>

<div class="flex h-full w-full flex-1 gap-6 p-4" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Settings Form --}}
    <div class="flex-1 space-y-6">
        <h1 class="text-xl font-bold">{{ __('bot.assistant_settings_title') }}</h1>

        <form wire:submit="saveSettings" class="space-y-6 max-w-xl">
            <div class="grid grid-cols-2 gap-4">
                <flux:field class="col-span-2">
                    <flux:label>{{ __('bot.assistant_name_label') }}</flux:label>
                    <flux:input wire:model="botName" required />
                    <flux:error name="botName" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('bot.preferred_dialect_label') }}</flux:label>
                    <flux:select wire:model="tone" required>
                        <option value="friendly_egyptian">{{ __('bot.friendly_egyptian') }}</option>
                        <option value="formal">{{ __('bot.formal_arabic') }}</option>
                        <option value="gulf">{{ __('bot.gulf_arabic') }}</option>
                    </flux:select>
                    <flux:error name="tone" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('bot.working_hours_label') }}</flux:label>
                    <flux:select wire:model="workingHours" required>
                        <option value="24_7">{{ __('bot.always_active') }}</option>
                        <option value="working_hours">{{ __('bot.specific_hours') }}</option>
                    </flux:select>
                    <flux:error name="workingHours" />
                </flux:field>

                <flux:field class="col-span-2">
                    <flux:checkbox wire:model="active" label="{{ __('bot.bot_active_checkbox') }}" />
                </flux:field>
            </div>

            {{-- Escalation Rules --}}
            <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700 space-y-4">
                <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('bot.escalation_rules_heading') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>{{ __('bot.lead_score_threshold_label') }}</flux:label>
                        <flux:input type="number" wire:model="scoreThreshold" required />
                        <flux:error name="scoreThreshold" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('bot.unproductive_messages_threshold_label') }}</flux:label>
                        <flux:input type="number" wire:model="unproductiveMessages" required />
                        <flux:error name="unproductiveMessages" />
                    </flux:field>
                </div>
            </div>

            {{-- Follow-Up Rules --}}
            <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700 space-y-4">
                <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">{{ __('bot.followup_rules_heading') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <flux:field class="col-span-2">
                        <flux:checkbox wire:model="followUpsEnabled" label="{{ __('bot.followups_enabled_checkbox') }}" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('bot.max_followups_label') }}</flux:label>
                        <flux:input type="number" wire:model="maxFollowUps" required />
                        <flux:error name="maxFollowUps" />
                    </flux:field>
                </div>
            </div>

            <div class="flex justify-start">
                <flux:button type="submit" variant="primary">{{ __('bot.save') }}</flux:button>
            </div>
        </form>
    </div>

    {{-- Test Bot Box --}}
    <div class="w-96 shrink-0 flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm h-[600px]">
        {{-- Header resembling WhatsApp header --}}
        <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] border-b border-zinc-200 dark:border-zinc-700/50 flex justify-between items-center">
            <div>
                <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('bot.test_assistant_heading') }}</h2>
                <p class="text-[10px] text-zinc-500 mt-0.5">{{ __('bot.test_assistant_desc') }}</p>
            </div>
            <flux:button wire:click="resetSandbox" size="xs" variant="subtle" icon="arrow-path" class="text-zinc-600 dark:text-zinc-400">
                {{ __('bot.reset_sandbox_btn') }}
            </flux:button>
        </div>

        {{-- Chat History with WhatsApp classic bg --}}
        <div x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight } }" x-init="scrollToBottom(); new MutationObserver(() => scrollToBottom()).observe($el, { childList: true, subtree: true })" class="flex-1 space-y-1 overflow-y-auto p-4 bg-[#efeae2] dark:bg-[#0b141a]">
            @forelse ($this->sandboxMessages as $message)
                <div class="flex {{ $message->direction === MessageDirection::Inbound ? 'justify-start' : 'justify-end' }}">
                    <div class="flex flex-col {{ $message->direction === MessageDirection::Inbound ? 'items-start' : 'items-end' }} max-w-[85%]">
                        {{-- Sender Name --}}
                        <span class="text-[9px] text-zinc-500 dark:text-zinc-400 mb-0 px-1 font-medium">
                            {{ $message->direction === MessageDirection::Inbound ? __('leads.client') : __('leads.smart_assistant') }}
                        </span>

                        {{-- Bubble styled as WhatsApp --}}
                        <div dir="rtl" class="rounded-2xl px-3 py-1 text-sm shadow-xs leading-relaxed whitespace-pre-wrap {{ $message->direction === MessageDirection::Inbound ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-zinc-900 dark:text-zinc-100 rounded-tr-xs border border-[#d1f4cb] dark:border-[#004e3f]' : 'bg-white dark:bg-[#202c33] text-zinc-900 dark:text-zinc-100 rounded-tl-xs border border-zinc-200/50 dark:border-zinc-700/50' }}">
                            {{ $message->body }}
                        </div>

                        {{-- Timestamp --}}
                        <span class="text-[8px] text-zinc-500 dark:text-zinc-400 mt-0 px-1">
                            {{ $message->created_at ? \Carbon\Carbon::parse($message->created_at)->format('H:i') : now()->format('H:i') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="flex h-full items-center justify-center py-20 text-center">
                    <p class="text-xs text-zinc-500">{{ __('bot.test_assistant_placeholder') }}</p>
                </div>
            @endforelse
        </div>

        {{-- Message Composer resembling WhatsApp --}}
        <div class="p-3 bg-[#f0f2f5] dark:bg-[#202c33] border-t border-zinc-200 dark:border-zinc-700/50">
            <form wire:submit="sendTestMessage" class="flex gap-2 items-center">
                <flux:input wire:model="testMessage" placeholder="{{ __('bot.test_message_input_placeholder') }}" class="flex-1 rounded-full bg-white dark:bg-zinc-800 border-none shadow-none text-xs" />
                <flux:button type="submit" size="sm" variant="primary" class="rounded-full shrink-0">{{ __('bot.send') }}</flux:button>
            </form>
        </div>
    </div>
</div>
