<?php

namespace App\Services\Agent;

use App\Agent\Tools\BookVisitTool;
use App\Agent\Tools\CalculateInstallmentTool;
use App\Agent\Tools\EscalateToAgentTool;
use App\Agent\Tools\QualifyLeadTool;
use App\Agent\Tools\SearchPropertiesTool;
use App\Agent\Tools\SendUnitMediaTool;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Jobs\SendWhatsAppText;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Message;
use App\Services\Leads\LeadScoringService;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class AgentRunner
{
    public function handle(Company $company, string $customerPhone, string $incomingText): void
    {
        $lock = Cache::lock("agent_runner:{$company->id}:{$customerPhone}", 30);

        if (! $lock->get()) {
            Log::warning('AgentRunner lock not acquired', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
            ]);

            return;
        }

        try {
            $this->execute($company, $customerPhone, $incomingText);
        } finally {
            $lock->release();
        }
    }

    protected function execute(Company $company, string $customerPhone, string $incomingText): void
    {
        $lead = Lead::firstOrCreate(
            ['company_id' => $company->id, 'customer_phone' => $customerPhone],
            ['name' => null, 'status' => 'new', 'source' => 'whatsapp'],
        );

        $conversation = Conversation::firstOrCreate(
            ['company_id' => $company->id, 'customer_phone' => $customerPhone],
            ['lead_id' => $lead->id, 'mode' => 'bot'],
        );

        $systemPrompt = app(SystemPromptBuilder::class)->build($company, $lead);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone($customerPhone);

        $prismMessages = $this->buildMessageHistory($session, $incomingText);

        $tools = [
            new SearchPropertiesTool($company->id, $lead->id, $customerPhone),
            new SendUnitMediaTool($company->id, $lead->id, $customerPhone),
            new CalculateInstallmentTool($company->id, $lead->id, $customerPhone),
            new BookVisitTool($company->id, $lead->id, $customerPhone),
            new QualifyLeadTool($company->id, $lead->id, $customerPhone),
            new EscalateToAgentTool($company->id, $lead->id, $customerPhone),
        ];

        $inputTokens = null;
        $outputTokens = null;

        try {
            $response = Prism::text()
                ->using(config('prism.default_provider'), config('prism.default_model'))
                ->withSystemPrompt($systemPrompt)
                ->withMessages($prismMessages)
                ->withTools($tools)
                ->withMaxSteps(5)
                ->asText();

            $assistantText = $response->text;
            $inputTokens = $response->usage->inputTokens ?? null;
            $outputTokens = $response->usage->outputTokens ?? null;

            $this->applyToolSignals($lead, $response);
        } catch (\Throwable $e) {
            Log::error('AgentRunner Prism error', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
                'error' => $e->getMessage(),
            ]);

            $assistantText = 'معلش حصل خطأ بسيط، ممكن تعيد رسالتك؟';
        }

        if ($customerPhone !== '+200000000000') {
            SendWhatsAppText::dispatch(
                $company->dialog360_channel_id,
                $customerPhone,
                $assistantText,
            );
        }

        Message::create([
            'company_id' => $company->id,
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'sender' => MessageSender::Bot,
            'body' => $assistantText,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ]);

        $session->pushTurn(['role' => 'assistant', 'content' => $assistantText]);

        $conversation->touch();

        $session->touch();
    }

    protected function buildMessageHistory(ConversationSession $session, string $incomingText): array
    {
        $history = $session->history();
        $messages = [];

        foreach ($history as $turn) {
            $role = $turn['role'] ?? 'user';
            $content = $turn['content'] ?? '';

            $messages[] = $role === 'assistant'
                ? new AssistantMessage($content)
                : new UserMessage($content);
        }

        $messages[] = new UserMessage($incomingText);

        return $messages;
    }

    protected function applyToolSignals(Lead $lead, $response): void
    {
        $signals = [];

        foreach ($response->toolCalls as $toolCall) {
            if ($toolCall->name === 'send_unit_media') {
                $signals['asked_media'] = true;
            }

            if ($toolCall->name === 'calculate_installment') {
                $signals['asked_installment'] = true;
            }
        }

        if (! empty($lead->budget_max)) {
            $signals['stated_budget'] = true;
        }

        if (! empty($signals)) {
            app(LeadScoringService::class)->applySignals($lead, $signals);
        }
    }

    public function generateFollowUp(Company $company, string $customerPhone): string
    {
        $lock = Cache::lock("agent_runner:{$company->id}:{$customerPhone}", 30);

        if (! $lock->get()) {
            Log::warning('AgentRunner lock not acquired for follow-up', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
            ]);

            return '';
        }

        try {
            return $this->executeFollowUp($company, $customerPhone);
        } finally {
            $lock->release();
        }
    }

    protected function executeFollowUp(Company $company, string $customerPhone): string
    {
        $lead = Lead::where('company_id', $company->id)
            ->where('customer_phone', $customerPhone)
            ->first();

        if (! $lead) {
            return '';
        }

        $conversation = Conversation::where('company_id', $company->id)
            ->where('customer_phone', $customerPhone)
            ->first();

        if (! $conversation) {
            return '';
        }

        $systemPrompt = app(SystemPromptBuilder::class)->build($company, $lead);

        $session = app(ConversationSession::class);
        $session->setCompany($company);
        $session->setPhone($customerPhone);

        $history = $session->history();
        $messages = [];

        foreach ($history as $turn) {
            $role = $turn['role'] ?? 'user';
            $content = $turn['content'] ?? '';

            $messages[] = $role === 'assistant'
                ? new AssistantMessage($content)
                : new UserMessage($content);
        }

        $followUpInstruction = 'العميل لم يقم بالرد منذ 23 ساعة بعد آخر رسالة منا. اكتب رسالة متابعة قصيرة، ودودة ومخصصة بناءً على اهتماماته وسياق المحادثة لإعادة تنشيط الحوار. لا تستخدم أي أدوات ولا تقم بإنشاء روابط أو تخمين تفاصيل غير موجودة.';

        $messages[] = new UserMessage($followUpInstruction);

        $inputTokens = null;
        $outputTokens = null;

        try {
            $response = Prism::text()
                ->using(config('prism.default_provider'), config('prism.default_model'))
                ->withSystemPrompt($systemPrompt)
                ->withMessages($messages)
                ->withMaxSteps(3)
                ->asText();

            $assistantText = $response->text;
            $inputTokens = $response->usage->inputTokens ?? null;
            $outputTokens = $response->usage->outputTokens ?? null;
        } catch (\Throwable $e) {
            Log::error('AgentRunner Prism follow-up error', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
                'error' => $e->getMessage(),
            ]);

            $assistantText = 'مرحباً، حابين نتطمن لو لسه مهتم بعروضنا العقارية؟ لو عندك أي استفسار أنا هنا للمساعدة.';
        }

        if ($customerPhone !== '+200000000000') {
            SendWhatsAppText::dispatch(
                $company->dialog360_channel_id,
                $customerPhone,
                $assistantText,
            );
        }

        Message::create([
            'company_id' => $company->id,
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'sender' => MessageSender::Bot,
            'body' => $assistantText,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ]);

        $session->pushTurn(['role' => 'assistant', 'content' => $assistantText]);

        $conversation->touch();
        $session->touch();

        return $assistantText;
    }
}
