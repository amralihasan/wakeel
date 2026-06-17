<?php

namespace App\Agent\Tools;

use App\Enums\ConversationMode;
use App\Enums\HandoffStatus;
use App\Events\ConversationEscalated;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Services\WhatsApp\ConversationSession;
use Prism\Prism\Tool;

class EscalateToAgentTool extends Tool
{
    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('escalate_to_agent')
            ->for('حوّل العميل لوكيل مبيعات بشري عندما يطلب ذلك أو يستوفي معايير التحويل')
            ->withStringParameter('reason', 'سبب التحويل لوكيل بشري', required: true)
            ->withStringParameter('summary', 'ملخص المحادثة الذي أعده الذكاء الاصطناعي', required: true)
            ->using($this);
    }

    public function __invoke(string $reason, string $summary): string
    {
        $conversation = Conversation::where('company_id', $this->companyId)
            ->where('customer_phone', $this->customerPhone)
            ->first();

        if (! $conversation) {
            return 'لم يتم العثور على محادثة نشطة لهذا العميل.';
        }

        $session = app(ConversationSession::class);
        $company = Company::find($this->companyId);

        if ($company) {
            $session->setCompany($company);
        }

        $session->setPhone($this->customerPhone);
        $session->setMode(ConversationMode::PendingHandoff->value);

        $conversation->update(['mode' => ConversationMode::PendingHandoff]);

        $handoff = Handoff::create([
            'company_id' => $this->companyId,
            'conversation_id' => $conversation->id,
            'lead_id' => $this->leadId,
            'reason' => $reason,
            'ai_summary' => $summary,
            'status' => HandoffStatus::Waiting,
        ]);

        ConversationEscalated::dispatch($handoff);

        return 'تم تحويلك لخدمة العملاء، سيتواصل معك أحد وكلائنا فوراً.';
    }
}
