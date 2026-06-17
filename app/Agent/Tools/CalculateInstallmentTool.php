<?php

namespace App\Agent\Tools;

use Prism\Prism\Tool;

class CalculateInstallmentTool extends Tool
{
    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('calculate_installment')
            ->for('احسب قيمة القسط الشهري المتوقع للوحدة بناءً على السعر ومقدم الحجز وفترة التقسيط')
            ->withNumberParameter('price', 'السعر الإجمالي للوحدة بالجنيه المصري', required: true)
            ->withNumberParameter('down_payment', 'قيمة مقدم الحجز بالجنيه المصري', required: true)
            ->withNumberParameter('years', 'عدد سنوات التقسيط', required: true)
            ->using($this);
    }

    public function __invoke(int $price, int $down_payment, int $years): string
    {
        $monthlyPayment = (int) round(($price - $down_payment) / ($years * 12));
        $formatted = number_format($monthlyPayment);

        return json_encode([
            'monthly_payment' => $monthlyPayment,
            'message' => "القسط الشهري المتوقع هو {$formatted} جنيه مصري شهرياً على مدار {$years} سنوات.",
        ]);
    }
}
