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
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('إعدادات البوت')] #[Layout('layouts.app')] class extends Component {
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
            ['name' => 'تجربة المنصة', 'status' => 'new', 'source' => 'other']
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

        Flux::toast(variant: 'success', text: 'تم حفظ إعدادات البوت بنجاح');
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

    #[Computed]
    public function sandboxMessages()
    {
        return Message::where('conversation_id', $this->sandboxConversationId)
            ->orderBy('created_at')
            ->get();
    }
}; ?>

    <div class="flex h-full w-full flex-1 gap-6 p-4" dir="rtl">
        {{-- Settings Form --}}
        <div class="flex-1 space-y-6">
            <h1 class="text-xl font-bold">إعدادات المساعد الذكي</h1>

            <form wire:submit="saveSettings" class="space-y-6 max-w-xl">
                <div class="grid grid-cols-2 gap-4">
                    <flux:field class="col-span-2">
                        <flux:label>اسم المساعد (الروبوت)</flux:label>
                        <flux:input wire:model="botName" required />
                        <flux:error name="botName" />
                    </flux:field>

                    <flux:field>
                        <flux:label>اللهجة المفضلة للمحادثة</flux:label>
                        <flux:select wire:model="tone" required>
                            <option value="friendly_egyptian">عامية مصرية ودودة</option>
                            <option value="formal">عربية فصحى مبسطة</option>
                            <option value="gulf">لهجة خليجية ملائمة</option>
                        </flux:select>
                        <flux:error name="tone" />
                    </flux:field>

                    <flux:field>
                        <flux:label>مواعيد عمل الشركة</flux:label>
                        <flux:select wire:model="workingHours" required>
                            <option value="24_7">طوال اليوم 24/7</option>
                            <option value="working_hours">ساعات عمل محددة (9 ص - 9 م)</option>
                        </flux:select>
                        <flux:error name="workingHours" />
                    </flux:field>

                    <flux:field class="col-span-2">
                        <flux:checkbox wire:model="active" label="تنشيط المساعد (البوت فعال ويرد على العملاء)" />
                    </flux:field>
                </div>

                {{-- Escalation Rules --}}
                <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700 space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">قواعد التحويل لوكيل بشري</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>درجة الاهتمام المطلوبة للتحويل (Lead Score)</flux:label>
                            <flux:input type="number" wire:model="scoreThreshold" required />
                            <flux:error name="scoreThreshold" />
                        </flux:field>

                        <flux:field>
                            <flux:label>أقصى عدد رسائل غير منتجة قبل التحويل</flux:label>
                            <flux:input type="number" wire:model="unproductiveMessages" required />
                            <flux:error name="unproductiveMessages" />
                        </flux:field>
                    </div>
                </div>

                {{-- Follow-Up Rules --}}
                <div class="border-t border-neutral-200 pt-4 dark:border-neutral-700 space-y-4">
                    <h3 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400">إعدادات المتابعة التلقائية</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <flux:field class="col-span-2">
                            <flux:checkbox wire:model="followUpsEnabled" label="تمكين المتابعة التلقائية للعملاء (إعادة تنشيط العملاء غير النشطين)" />
                        </flux:field>

                        <flux:field>
                            <flux:label>أقصى عدد رسائل متابعة لكل عميل</flux:label>
                            <flux:input type="number" wire:model="maxFollowUps" required />
                            <flux:error name="maxFollowUps" />
                        </flux:field>
                    </div>
                </div>

                <div class="flex justify-start">
                    <flux:button type="submit" variant="primary">حفظ الإعدادات</flux:button>
                </div>
            </form>
        </div>

        {{-- Test Bot Box --}}
        <div class="w-96 shrink-0 flex flex-col rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50">
                <h2 class="text-sm font-semibold">اختبار المساعد</h2>
                <p class="text-[10px] text-neutral-500 mt-1">تحدث مع المساعد لتجربة لهجته وطريقة الرد (لن يتم إرسال رسائل فعلية للواتساب).</p>
            </div>

            {{-- Chat History --}}
            <div class="flex-1 space-y-3 overflow-y-auto p-4 max-h-[400px]">
                @forelse ($this->sandboxMessages as $message)
                    <div class="flex {{ $message->direction === 'inbound' ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[80%] rounded-xl px-3 py-1.5 text-xs {{ $message->direction === 'inbound' ? 'bg-neutral-100 dark:bg-neutral-800' : 'bg-blue-500 text-white' }}">
                            <p>{{ $message->body }}</p>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center py-20 text-center">
                        <p class="text-xs text-neutral-500">اكتب رسالة لبدء اختبار المساعد.</p>
                    </div>
                @endforelse
            </div>

            {{-- Message Composer --}}
            <div class="border-t border-neutral-200 p-3 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50">
                <form wire:submit="sendTestMessage" class="flex gap-2">
                    <flux:input wire:model="testMessage" placeholder="اكتب رسالة..." class="flex-1" />
                    <flux:button type="submit" size="sm" variant="primary">إرسال</flux:button>
                </form>
            </div>
        </div>
    </div>
