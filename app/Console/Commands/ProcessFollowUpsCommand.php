<?php

namespace App\Console\Commands;

use App\Enums\ConversationMode;
use App\Enums\MessageDirection;
use App\Enums\MessageSender;
use App\Enums\VisitStatus;
use App\Jobs\SendWhatsAppTemplate;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\FollowUpLog;
use App\Models\Message;
use App\Models\Visit;
use App\Services\Agent\AgentRunner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:process-followups')]
#[Description('Process automated follow-up sequences for dormant leads.')]
class ProcessFollowUpsCommand extends Command
{
    public function handle(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $settings = $company->bot_settings ?? [];
            $isActive = $settings['active'] ?? true;
            $followUpsEnabled = $settings['follow_ups_enabled'] ?? true;

            if (! $isActive || ! $followUpsEnabled) {
                continue;
            }

            $maxFollowUps = (int) ($settings['max_follow_ups'] ?? 3);

            $this->processSilence23h($company, $maxFollowUps);
            $this->processSilence72h($company, $maxFollowUps);
            $this->processNoShow($company, $maxFollowUps);
            $this->processColdNurture($company, $maxFollowUps);
        }
    }

    protected function processSilence23h(Company $company, int $maxFollowUps): void
    {
        $minTime = now()->subHours(24);
        $maxTime = now()->subHours(23);

        $conversations = Conversation::where('company_id', $company->id)
            ->where('mode', ConversationMode::Bot)
            ->whereBetween('last_message_at', [$minTime, $maxTime])
            ->get();

        foreach ($conversations as $conversation) {
            $lead = $conversation->lead;
            if (! $lead || in_array($lead->status, ['closed', 'opted_out'])) {
                continue;
            }

            // Verify the last message was outbound
            $lastMessage = $conversation->messages()->orderBy('id', 'desc')->first();
            if (! $lastMessage || $lastMessage->direction !== MessageDirection::Outbound) {
                continue;
            }

            // Check if silence_23h already sent
            $alreadySent = FollowUpLog::where('lead_id', $lead->id)
                ->where('sequence_type', 'silence_23h')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // Check cap limit
            $followUpCount = FollowUpLog::where('lead_id', $lead->id)->count();
            if ($followUpCount >= $maxFollowUps) {
                continue;
            }

            // Execute follow-up
            try {
                app(AgentRunner::class)->generateFollowUp($company, $conversation->customer_phone);

                FollowUpLog::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'sequence_type' => 'silence_23h',
                ]);
            } catch (\Throwable $e) {
                Log::error('Error processing 23h follow-up', [
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processSilence72h(Company $company, int $maxFollowUps): void
    {
        $minTime = now()->subHours(73);
        $maxTime = now()->subHours(72);

        $conversations = Conversation::where('company_id', $company->id)
            ->where('mode', ConversationMode::Bot)
            ->whereBetween('last_message_at', [$minTime, $maxTime])
            ->get();

        foreach ($conversations as $conversation) {
            $lead = $conversation->lead;
            if (! $lead || in_array($lead->status, ['closed', 'opted_out'])) {
                continue;
            }

            // Verify the last message was outbound
            $lastMessage = $conversation->messages()->orderBy('id', 'desc')->first();
            if (! $lastMessage || $lastMessage->direction !== MessageDirection::Outbound) {
                continue;
            }

            // Check if silence_72h already sent
            $alreadySent = FollowUpLog::where('lead_id', $lead->id)
                ->where('sequence_type', 'silence_72h')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // Check cap limit
            $followUpCount = FollowUpLog::where('lead_id', $lead->id)->count();
            if ($followUpCount >= $maxFollowUps) {
                continue;
            }

            // Execute follow-up via Template
            try {
                $channelId = $company->dialog360_channel_id ?? $company->whatsapp_number ?? '';
                if (empty($channelId)) {
                    continue;
                }

                $name = $lead->name ?? 'عميلنا العزيز';
                $components = [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $name],
                        ],
                    ],
                ];

                SendWhatsAppTemplate::dispatch(
                    $channelId,
                    $conversation->customer_phone,
                    'silence_72h_template',
                    'ar',
                    $components
                );

                $bodyText = "مرحباً {$name}، حابين نشارك معاك أحدث الوحدات والشرائح العقارية المتاحة حالياً في مشروعنا. هل تحب نرسل لك التفاصيل؟";
                Message::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'direction' => MessageDirection::Outbound,
                    'sender' => MessageSender::Bot,
                    'body' => $bodyText,
                ]);

                FollowUpLog::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'sequence_type' => 'silence_72h',
                ]);
            } catch (\Throwable $e) {
                Log::error('Error processing 72h follow-up', [
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processNoShow(Company $company, int $maxFollowUps): void
    {
        $visits = Visit::where('company_id', $company->id)
            ->where('status', VisitStatus::NoShow)
            ->get();

        foreach ($visits as $visit) {
            $lead = $visit->lead;
            if (! $lead || in_array($lead->status, ['closed', 'opted_out'])) {
                continue;
            }

            $conversation = $lead->conversation;
            if (! $conversation || $conversation->mode !== ConversationMode::Bot) {
                continue;
            }

            // Check if no_show already sent for this visit
            $alreadySent = FollowUpLog::where('visit_id', $visit->id)
                ->where('sequence_type', 'no_show')
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // Check cap limit
            $followUpCount = FollowUpLog::where('lead_id', $lead->id)->count();
            if ($followUpCount >= $maxFollowUps) {
                continue;
            }

            // Execute follow-up via Template
            try {
                $channelId = $company->dialog360_channel_id ?? $company->whatsapp_number ?? '';
                if (empty($channelId)) {
                    continue;
                }

                $name = $lead->name ?? 'عميلنا العزيز';
                $components = [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $name],
                        ],
                    ],
                ];

                SendWhatsAppTemplate::dispatch(
                    $channelId,
                    $conversation->customer_phone,
                    'visit_no_show_template',
                    'ar',
                    $components
                );

                $bodyText = "مرحباً {$name}، لاحظنا أنك لم تتمكن من حضور موعد الزيارة المحدد اليوم. هل تود تحديد موعد آخر يناسبك؟";
                Message::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'direction' => MessageDirection::Outbound,
                    'sender' => MessageSender::Bot,
                    'body' => $bodyText,
                ]);

                FollowUpLog::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'visit_id' => $visit->id,
                    'sequence_type' => 'no_show',
                ]);
            } catch (\Throwable $e) {
                Log::error('Error processing no-show follow-up', [
                    'company_id' => $company->id,
                    'visit_id' => $visit->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processColdNurture(Company $company, int $maxFollowUps): void
    {
        $idleTime = now()->subDays(7);

        $conversations = Conversation::where('company_id', $company->id)
            ->where('mode', ConversationMode::Bot)
            ->where('last_message_at', '<=', $idleTime)
            ->get();

        foreach ($conversations as $conversation) {
            $lead = $conversation->lead;
            if (! $lead || in_array($lead->status, ['closed', 'opted_out'])) {
                continue;
            }

            // Check if cold_nurture already sent in the last 30 days
            $recentNurture = FollowUpLog::where('lead_id', $lead->id)
                ->where('sequence_type', 'cold_nurture')
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if ($recentNurture) {
                continue;
            }

            // Check cap limit
            $followUpCount = FollowUpLog::where('lead_id', $lead->id)->count();
            if ($followUpCount >= $maxFollowUps) {
                continue;
            }

            // Execute follow-up via Template
            try {
                $channelId = $company->dialog360_channel_id ?? $company->whatsapp_number ?? '';
                if (empty($channelId)) {
                    continue;
                }

                $name = $lead->name ?? 'عميلنا العزيز';
                $components = [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $name],
                        ],
                    ],
                ];

                SendWhatsAppTemplate::dispatch(
                    $channelId,
                    $conversation->customer_phone,
                    'cold_nurture_template',
                    'ar',
                    $components
                );

                $bodyText = "مرحباً {$name}، عندنا وحدات عقارية مميزة وجديدة تناسب اهتماماتك. هل تحب نرسل لك تفاصيلها؟";
                Message::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'direction' => MessageDirection::Outbound,
                    'sender' => MessageSender::Bot,
                    'body' => $bodyText,
                ]);

                FollowUpLog::create([
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'sequence_type' => 'cold_nurture',
                ]);
            } catch (\Throwable $e) {
                Log::error('Error processing cold nurture follow-up', [
                    'company_id' => $company->id,
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
