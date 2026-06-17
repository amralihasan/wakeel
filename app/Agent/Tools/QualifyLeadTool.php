<?php

namespace App\Agent\Tools;

use App\Enums\LeadTier;
use App\Models\Lead;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Tool;

class QualifyLeadTool extends Tool
{
    protected static array $scoreMap = [
        'stated_budget' => 30,
        'asked_price' => 25,
        'booked_visit' => 30,
        'asked_installment' => 15,
        'asked_media' => 10,
        'general_inquiry' => 5,
    ];

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
        $score = 0;

        foreach (self::$scoreMap as $signal => $points) {
            if (! empty($signals[$signal])) {
                $score += $points;
            }
        }

        $score = min($score, 100);

        $tier = match (true) {
            $score >= 70 => LeadTier::Hot,
            $score >= 40 => LeadTier::Warm,
            default => LeadTier::Cold,
        };

        Lead::where('id', $this->leadId)
            ->where('company_id', $this->companyId)
            ->update(['score' => $score, 'tier' => $tier]);

        return json_encode([
            'score' => $score,
            'tier' => $tier->value,
        ]);
    }
}
