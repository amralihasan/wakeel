<?php

namespace App\Agent\Tools;

use App\Models\Lead;
use App\Services\Leads\LeadScoringService;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Tool;

class QualifyLeadTool extends Tool
{
    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('qualify_lead')
            ->for('قيّم درجة اهتمام العميل بالوحدات العقارية بناءً على إشارات التفاعل')
            ->withObjectParameter('signals', 'إشارات تفاعل العميل (booleans)', [
                new BooleanSchema('stated_budget', 'العميل حدد ميزانيته'),
                new BooleanSchema('asked_price', 'العميل سأل عن السعر'),
                new BooleanSchema('booked_visit', 'العميل حجز زيارة'),
                new BooleanSchema('asked_installment', 'العميل سأل عن التقسيط'),
                new BooleanSchema('asked_media', 'العميل طلب صور أو فيديوهات'),
                new BooleanSchema('general_inquiry', 'استفسار عام'),
            ], requiredFields: ['stated_budget', 'asked_price', 'booked_visit', 'asked_installment', 'asked_media', 'general_inquiry'], required: true)
            ->using($this);
    }

    public function __invoke(array $signals): string
    {
        $lead = Lead::where('id', $this->leadId)
            ->where('company_id', $this->companyId)
            ->first();

        if (! $lead) {
            return json_encode(['score' => 0, 'tier' => 'cold']);
        }

        $lead = app(LeadScoringService::class)->applySignals($lead, $signals);

        return json_encode([
            'score' => $lead->score,
            'tier' => $lead->tier->value,
        ]);
    }
}
