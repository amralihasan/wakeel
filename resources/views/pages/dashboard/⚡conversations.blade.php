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

new #[Title('Conversations')] class extends Component {
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
    public function conversationMessages()
    {
        if (! $this->activeConversationId) {
            return collect();
        }

        return Message::where('conversation_id', $this->activeConversationId)
            ->orderBy('created_at')
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
            Flux::toast(variant: 'error', text: 'غير مصرح لك باستلام المحادثات.');

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

        $closingMessage = 'أقدر أساعدك في حاجة تانية؟';

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

<section class="flex h-full w-full flex-1 gap-4">
    {{-- Side Panel --}}
    <div class="w-80 shrink-0 space-y-4">
        {{-- Handoff Queue --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700">
                <h2 class="text-sm font-semibold">طلبات التحويل</h2>
            </div>
            <div class="max-h-64 space-y-1 overflow-y-auto p-2">
                @forelse ($this->handoffs as $handoff)
                    @php $waitMinutes = now()->diffInMinutes($handoff->created_at); @endphp
                    <div class="flex items-center justify-between rounded-lg p-2 {{ $waitMinutes < 2 ? 'bg-green-50 dark:bg-green-900/20' : ($waitMinutes <= 5 ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-red-50 dark:bg-red-900/20') }}">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-neutral-600 dark:text-neutral-400">{{ $handoff->ai_summary ?? $handoff->reason }}</p>
                            <span class="text-[10px] {{ $waitMinutes < 2 ? 'text-green-600 dark:text-green-400' : ($waitMinutes <= 5 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $waitMinutes < 1 ? 'الآن' : "منذ {$waitMinutes} دقائق" }}
                            </span>
                        </div>
                        <flux:button size="xs" wire:click="claimHandoff({{ $handoff->id }})">استلام</flux:button>
                    </div>
                @empty
                    <p class="py-4 text-center text-xs text-neutral-500">لا توجد طلبات تحويل</p>
                @endforelse
            </div>
        </div>

        {{-- Active Conversations List --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700">
                <h2 class="text-sm font-semibold">المحادثات النشطة</h2>
            </div>
            <div class="max-h-64 space-y-1 overflow-y-auto p-2">
                @forelse ($this->activeConversations as $conversation)
                    <button wire:click="selectConversation({{ $conversation->id }})" class="w-full rounded-lg p-2 text-right text-xs transition hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $activeConversationId === $conversation->id ? 'bg-neutral-100 dark:bg-neutral-800' : '' }}">
                        <span class="font-medium">{{ $conversation->customer_phone }}</span>
                        @if ($conversation->assigned_rep_id)
                            <span class="block text-[10px] text-neutral-500">مع {{ $conversation->assignedRep?->name ?? 'مندوب' }}</span>
                        @endif
                    </button>
                @empty
                    <p class="py-4 text-center text-xs text-neutral-500">لا توجد محادثات نشطة</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Main Chat Area --}}
    <div class="flex flex-1 flex-col rounded-xl border border-neutral-200 dark:border-neutral-700">
        @if ($activeConversationId)
            {{-- Message Thread --}}
            <div class="flex-1 space-y-3 overflow-y-auto p-4">
                @forelse ($this->conversationMessages as $message)
                    <div class="flex {{ $message->direction === 'inbound' ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[70%] rounded-xl px-4 py-2 text-sm {{ $message->direction === 'inbound' ? 'bg-neutral-100 dark:bg-neutral-800' : ($message->sender === 'bot' ? 'bg-blue-500 text-white' : 'bg-indigo-500 text-white') }}">
                            <p>{{ $message->body }}</p>
                            <span class="block text-[10px] opacity-70">{{ \Carbon\Carbon::parse($message->created_at)->format('H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center">
                        <p class="text-sm text-neutral-500">لا توجد رسائل بعد</p>
                    </div>
                @endforelse
            </div>

            {{-- Composer & Resolve --}}
            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                <form wire:submit="sendMessage" class="flex gap-2">
                    <flux:textarea wire:model="replyText" placeholder="اكتب رسالتك..." class="flex-1" rows="2" />
                    <flux:button type="submit" variant="primary" class="self-start">إرسال</flux:button>
                </form>
                <div class="mt-2 flex items-center gap-2">
                    <flux:input wire:model="resolutionNotes" placeholder="ملاحظات الحل (اختياري)" class="flex-1" />
                    <flux:button wire:click="resolveConversation" class="shrink-0 cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-red-700">إنهاء المحادثة</flux:button>
                </div>
            </div>
        @else
            <div class="flex h-full items-center justify-center">
                <p class="text-sm text-neutral-500">اختر محادثة من القائمة</p>
            </div>
        @endif
    </div>
</section>
