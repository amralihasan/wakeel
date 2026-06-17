<?php

namespace App\Listeners;

use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Events\ConversationEscalated;
use App\Events\LeadBecameHot;
use App\Models\Company;
use App\Models\Handoff;
use App\Services\WhatsApp\ConversationSession;

class EscalateHotLead
{
    public function handle(LeadBecameHot $event): void
    {
        $lead = $event->lead;
        $conversation = $lead->conversation;

        if (! $conversation || $conversation->mode !== ConversationMode::Bot) {
            return;
        }

        $company = Company::find($lead->company_id);

        $session = app(ConversationSession::class);
        if ($company) {
            $session->setCompany($company);
        }
        $session->setPhone($lead->customer_phone);
        $session->setMode(ConversationMode::PendingHandoff->value);

        $conversation->update(['mode' => ConversationMode::PendingHandoff]);

        $handoff = Handoff::create([
            'company_id' => $lead->company_id,
            'conversation_id' => $conversation->id,
            'lead_id' => $lead->id,
            'reason' => 'تأهيل تلقائي: تجاوز العميل درجة الاهتمام المطلوبة',
            'ai_summary' => 'تم تأهيل العميل تلقائياً للتحويل لوكيل بشري.',
            'status' => HandoffStatus::Waiting,
        ]);

        ConversationEscalated::dispatch($handoff);
    }
}
