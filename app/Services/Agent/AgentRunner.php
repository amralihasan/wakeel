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
use App\Services\AiModelRegistry;
use App\Services\AiModelResolver;
use App\Services\Leads\LeadScoringService;
use App\Services\WhatsApp\ConversationSession;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class AgentRunner
{
    public function __construct(
        protected AiModelResolver $modelResolver,
        protected AiModelRegistry $modelRegistry,
    ) {}

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

        $modelChoice = $this->modelResolver->for($company);

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
        $actualModel = $modelChoice->model;
        $costUsd = null;
        $usedFallback = false;

        try {
            $response = Prism::text()
                ->using($modelChoice->provider, $modelChoice->model)
                ->withSystemPrompt($systemPrompt)
                ->withMessages($prismMessages)
                ->withTools($tools)
                ->withMaxSteps(5)
                ->asText();

            $assistantText = $response->text;
            $inputTokens = $response->usage->inputTokens ?? null;
            $outputTokens = $response->usage->outputTokens ?? null;

            $this->logTokenAnomalies($company, $customerPhone, $inputTokens, $outputTokens);

            $this->applyToolSignals($lead, $response);
        } catch (\Throwable $e) {
            Log::error('AgentRunner Prism error', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
                'model' => $modelChoice->model,
                'provider' => $modelChoice->provider,
                'error' => $e->getMessage(),
            ]);

            $fallback = $this->modelResolver->fallbackFor($modelChoice);

            if ($fallback !== null) {
                Log::info('AgentRunner falling back to fallback model', [
                    'company_id' => $company->id,
                    'fallback_model' => $fallback->model,
                    'fallback_provider' => $fallback->provider,
                ]);

                try {
                    $response = Prism::text()
                        ->using($fallback->provider, $fallback->model)
                        ->withSystemPrompt($systemPrompt)
                        ->withMessages($prismMessages)
                        ->withTools($tools)
                        ->withMaxSteps(3)
                        ->asText();

                    $assistantText = $response->text;
                    $inputTokens = $response->usage->inputTokens ?? null;
                    $outputTokens = $response->usage->outputTokens ?? null;
                    $actualModel = $fallback->model;
                    $usedFallback = true;

                    $this->logTokenAnomalies($company, $customerPhone, $inputTokens, $outputTokens);
                    $this->applyToolSignals($lead, $response);
                } catch (\Throwable $fallbackError) {
                    Log::error('AgentRunner fallback also failed', [
                        'company_id' => $company->id,
                        'customer_phone' => $customerPhone,
                        'error' => $fallbackError->getMessage(),
                    ]);

                    $locale = $lead->locale ?? $company->default_locale ?? 'ar';
                    $assistantText = __('bot.fallback_error', [], $locale);
                }
            } else {
                $locale = $lead->locale ?? $company->default_locale ?? 'ar';
                $assistantText = __('bot.fallback_error', [], $locale);
            }
        }

        if (isset($assistantText) && $inputTokens !== null && $outputTokens !== null) {
            $inputCostPerMtok = $this->modelRegistry->costPerMtok($actualModel, 'input');
            $outputCostPerMtok = $this->modelRegistry->costPerMtok($actualModel, 'output');
            $costUsd = (($inputTokens / 1_000_000) * $inputCostPerMtok)
                + (($outputTokens / 1_000_000) * $outputCostPerMtok);
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
            'model_used' => $actualModel,
            'cost_usd' => $costUsd,
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

    protected function logTokenAnomalies(Company $company, string $customerPhone, ?int $inputTokens, ?int $outputTokens): void
    {
        if (($inputTokens !== null && $inputTokens > 15000) || ($outputTokens !== null && $outputTokens > 4000)) {
            Log::warning('AgentRunner LLM token anomaly detected', [
                'company_id' => $company->id,
                'customer_phone' => substr($customerPhone, 0, 4).'***'.substr($customerPhone, -3),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ]);
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

        $modelChoice = $this->modelResolver->for($company);

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

        $locale = $lead->locale ?? $company->default_locale ?? 'ar';
        $followUpInstruction = __('bot.followup_instruction', [], $locale);

        $messages[] = new UserMessage($followUpInstruction);

        $inputTokens = null;
        $outputTokens = null;
        $actualModel = $modelChoice->model;
        $costUsd = null;

        try {
            $response = Prism::text()
                ->using($modelChoice->provider, $modelChoice->model)
                ->withSystemPrompt($systemPrompt)
                ->withMessages($messages)
                ->withMaxSteps(3)
                ->asText();

            $assistantText = $response->text;
            $inputTokens = $response->usage->inputTokens ?? null;
            $outputTokens = $response->usage->outputTokens ?? null;

            $this->logTokenAnomalies($company, $customerPhone, $inputTokens, $outputTokens);
        } catch (\Throwable $e) {
            Log::error('AgentRunner Prism follow-up error', [
                'company_id' => $company->id,
                'customer_phone' => $customerPhone,
                'model' => $modelChoice->model,
                'provider' => $modelChoice->provider,
                'error' => $e->getMessage(),
            ]);

            $locale = $lead->locale ?? $company->default_locale ?? 'ar';
            $assistantText = __('bot.default_followup_message', [], $locale);
        }

        if (isset($assistantText) && $inputTokens !== null && $outputTokens !== null) {
            $inputCostPerMtok = $this->modelRegistry->costPerMtok($actualModel, 'input');
            $outputCostPerMtok = $this->modelRegistry->costPerMtok($actualModel, 'output');
            $costUsd = (($inputTokens / 1_000_000) * $inputCostPerMtok)
                + (($outputTokens / 1_000_000) * $outputCostPerMtok);
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
            'model_used' => $actualModel,
            'cost_usd' => $costUsd,
        ]);

        $session->pushTurn(['role' => 'assistant', 'content' => $assistantText]);

        $conversation->touch();
        $session->touch();

        return $assistantText;
    }
}
