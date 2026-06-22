<?php

use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Models\Message;
use App\Services\WhatsApp\ConversationSession;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('conversations.title')] class extends Component {
    public ?int $activeConversationId = null;

    public string $resolutionNotes = '';

    public string $replyText = '';

    public int $companyId;

    public function mount(): void
    {
        $this->companyId = Auth::user()->company_id;
    }

    #[Computed]
    public function handoffs()
    {
        return Handoff::where('company_id', $this->companyId)
            ->where('status', HandoffStatus::Waiting)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function activeConversations()
    {
        return Conversation::where('company_id', $this->companyId)
            ->whereIn('mode', [ConversationMode::Human, ConversationMode::PendingHandoff])
            ->get();
    }

    #[Computed]
    public function activeConversation()
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return Conversation::with(['lead'])->find($this->activeConversationId);
    }

    #[Computed]
    public function conversationMessages()
    {
        if (! $this->activeConversationId) {
            return collect();
        }

        return Message::where('conversation_id', $this->activeConversationId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function selectConversation(int $id): void
    {
        $this->activeConversationId = $id;
    }

    public function claimHandoff(int $handoffId): void
    {
        $user = Auth::user();

        if (! $user->isOwner() && ! $user->isSalesRep()) {
            Flux::toast(variant: 'error', text: __('conversations.unauthorized'));

            return;
        }

        $handoff = Handoff::where('company_id', $this->companyId)
            ->where('id', $handoffId)
            ->where('status', HandoffStatus::Waiting)
            ->firstOrFail();

        $conversation = $handoff->conversation;

        $session = app(ConversationSession::class);
        $session->setCompany(Company::findOrFail($this->companyId));
        $session->setPhone($conversation->customer_phone);
        $session->setMode('human');

        $handoff->update([
            'status' => HandoffStatus::Active,
            'agent_id' => $user->id,
            'claimed_at' => now(),
        ]);

        $conversation->update([
            'mode' => ConversationMode::Human,
            'assigned_rep_id' => $user->id,
        ]);

        $this->activeConversationId = $conversation->id;
    }

    public function sendMessage(): void
    {
        $this->validate(['replyText' => 'required|string|max:4096']);

        $conversation = Conversation::findOrFail($this->activeConversationId);
        $company = Company::findOrFail($this->companyId);

        SendWhatsAppText::dispatch(
            $company->dialog360_channel_id,
            $conversation->customer_phone,
            $this->replyText,
        );

        Message::create([
            'company_id' => $this->companyId,
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'sender' => MessageSender::Rep,
            'body' => $this->replyText,
            'created_at' => now(),
        ]);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone($conversation->customer_phone);
        $session->pushTurn(['role' => 'assistant', 'content' => $this->replyText]);

        $this->replyText = '';
    }

    public function resolveConversation(): void
    {
        $conversation = Conversation::findOrFail($this->activeConversationId);
        $company = Company::findOrFail($this->companyId);

        $handoff = Handoff::where('conversation_id', $conversation->id)
            ->whereIn('status', [HandoffStatus::Waiting, HandoffStatus::Active])
            ->firstOrFail();

        $handoff->update([
            'status' => HandoffStatus::Resolved,
            'resolved_at' => now(),
            'resolution_notes' => $this->resolutionNotes ?: null,
        ]);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone($conversation->customer_phone);
        $session->setMode('bot');

        $conversation->update([
            'mode' => ConversationMode::Bot,
            'assigned_rep_id' => null,
        ]);

        $leadLocale = $conversation->lead?->locale ?? $company->default_locale ?? 'ar';
        $closingMessage = trans('conversations.closing_greeting', [], $leadLocale);

        SendWhatsAppText::dispatch(
            $company->dialog360_channel_id,
            $conversation->customer_phone,
            $closingMessage,
        );

        Message::create([
            'company_id' => $this->companyId,
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'sender' => MessageSender::Bot,
            'body' => $closingMessage,
            'created_at' => now(),
        ]);

        $session->pushTurn(['role' => 'assistant', 'content' => $closingMessage]);

        $this->activeConversationId = null;
        $this->resolutionNotes = '';
    }

    #[On('echo-private:company.{companyId}.handoffs,.conversation.escalated')]
    public function refreshHandoffList(): void
    {
        unset($this->handoffs);
    }

    #[On('echo-private:company.{companyId}.conversations,.inbound-message.received')]
    public function handleInboundMessage(): void
    {
        unset($this->activeConversations);
        unset($this->handoffs);
    }
}; ?>

<section class="flex h-full w-full flex-1 gap-4" dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}">
    {{-- Side Panel --}}
    <div class="w-80 shrink-0 space-y-4">
        {{-- Handoff Queue --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700">
                <h2 class="text-sm font-semibold">{{ __('conversations.waiting_escalations') }}</h2>
            </div>
            <div class="max-h-64 space-y-1 overflow-y-auto p-2">
                @forelse ($this->handoffs as $handoff)
                    @php $waitMinutes = now()->diffInMinutes($handoff->created_at); @endphp
                    <div class="flex items-center justify-between rounded-lg p-2 {{ $waitMinutes < 2 ? 'bg-green-50 dark:bg-green-900/20' : ($waitMinutes <= 5 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-neutral-600 dark:text-neutral-400">{{ $handoff->ai_summary ?? $handoff->reason }}</p>
                            <span class="text-[10px] {{ $waitMinutes < 2 ? 'text-green-600 dark:text-green-400' : ($waitMinutes <= 5 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $waitMinutes < 1 ? __('conversations.now') : __('conversations.minutes_ago', ['minutes' => $waitMinutes]) }}
                            </span>
                        </div>
                        <flux:button size="xs" wire:click="claimHandoff({{ $handoff->id }})">{{ __('conversations.claim') }}</flux:button>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-neutral-500">{{ __('conversations.no_escalations') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Active Conversations List --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-zinc-900">
            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700">
                <h2 class="text-sm font-semibold">{{ __('conversations.active_conversations') }}</h2>
            </div>
            <div class="max-h-64 space-y-1 overflow-y-auto p-2">
                @forelse ($this->activeConversations as $conversation)
                    <button wire:click="selectConversation({{ $conversation->id }})" class="w-full rounded-lg p-2 text-start text-xs transition hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $activeConversationId === $conversation->id ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}">
                        <span class="font-medium">{{ $conversation->customer_phone }}</span>
                        @if ($conversation->assigned_rep_id)
                            <span class="block text-[10px] text-neutral-500">{{ __('conversations.with_agent', ['name' => $conversation->assignedRep?->name ?? '']) }}</span>
                        @endif
                    </button>
                @empty
                    <p class="py-4 text-center text-xs text-neutral-500">{{ __('conversations.no_conversations') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex flex-1 flex-col rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden shadow-sm h-[600px]">
        @if ($activeConversationId && $this->activeConversation)
            {{-- WhatsApp Header --}}
            <div class="px-4 py-3 bg-[#f0f2f5] dark:bg-[#202c33] border-b border-zinc-200 dark:border-zinc-700/50 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-zinc-300 dark:bg-zinc-700 flex items-center justify-center font-bold text-zinc-700 dark:text-zinc-300">
                        💬
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->activeConversation->customer_phone }}</h2>
                        @if($this->activeConversation->lead?->name)
                            <p class="text-[10px] text-zinc-500 mt-0.5">{{ $this->activeConversation->lead->name }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Message Thread with WhatsApp background --}}
            <div x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight } }" x-init="scrollToBottom(); new MutationObserver(() => scrollToBottom()).observe($el, { childList: true, subtree: true })" class="flex-1 space-y-1 overflow-y-auto p-4 bg-[#efeae2] dark:bg-[#0b141a]">
                @forelse ($this->conversationMessages as $message)
                    <div class="flex {{ $message->direction === MessageDirection::Inbound ? 'justify-start' : 'justify-end' }}">
                        <div class="flex flex-col {{ $message->direction === MessageDirection::Inbound ? 'items-start' : 'items-end' }} max-w-[75%]">
                            {{-- Sender Name --}}
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 mb-0 px-1 font-medium">
                                {{ $message->direction === MessageDirection::Inbound ? __('leads.client') : ($message->sender === MessageSender::Bot ? __('leads.smart_assistant') : __('dashboard.human_agent')) }}
                            </span>

                            {{-- Bubble --}}
                            <div dir="rtl" class="rounded-2xl px-3 py-1 text-[15px] shadow-xs leading-relaxed whitespace-pre-wrap {{ $message->direction === MessageDirection::Inbound ? 'bg-[#d9fdd3] dark:bg-[#005c4b] text-zinc-900 dark:text-zinc-100 rounded-tr-xs border border-[#d1f4cb] dark:border-[#004e3f]' : ($message->sender === MessageSender::Bot ? 'bg-white dark:bg-[#202c33] text-zinc-900 dark:text-zinc-100 rounded-tl-xs border border-zinc-200/50 dark:border-zinc-700/50' : 'bg-[#e7f3ff] dark:bg-[#18222d] text-zinc-900 dark:text-zinc-100 rounded-tl-xs border border-[#d2e8ff] dark:border-[#132c45]') }}">
                                {{ $message->body }}
                            </div>

                            {{-- Timestamp --}}
                            <span class="text-[9px] text-zinc-500 dark:text-zinc-400 mt-0 px-1">
                                {{ \Carbon\Carbon::parse($message->created_at)->format('H:i') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center">
                        <p class="text-sm text-zinc-500">{{ __('conversations.no_messages') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Composer & Resolve resembling WhatsApp --}}
            <div class="p-4 bg-[#f0f2f5] dark:bg-[#202c33] border-t border-zinc-200 dark:border-zinc-700/50">
                <form wire:submit="sendMessage" class="flex gap-2">
                    <flux:textarea wire:model="replyText" placeholder="{{ __('conversations.type_message') }}" class="flex-1 rounded-xl bg-white dark:bg-zinc-800 border-none shadow-none text-sm" rows="2" />
                    <flux:button type="submit" variant="primary" class="self-start rounded-full shrink-0">{{ __('conversations.send') }}</flux:button>
                </form>
                <div class="mt-2 flex items-center gap-2">
                    <flux:input wire:model="resolutionNotes" placeholder="{{ __('conversations.resolution_notes_placeholder') }}" class="flex-1 rounded-full bg-white dark:bg-zinc-800 border-none shadow-none text-xs" />
                    <flux:button wire:click="resolveConversation" class="shrink-0 cursor-pointer rounded-full bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700">{{ __('conversations.resolve') }}</flux:button>
                </div>
            </div>
        @else
            <div class="flex h-full items-center justify-center">
                <p class="text-sm text-zinc-500">{{ __('conversations.select_conversation') }}</p>
            </div>
        @endif
    </div>
</section>
