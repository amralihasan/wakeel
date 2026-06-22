<?php

namespace App\Agent\Tools;

use App\Services\Agent\RetrievedFacts;
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
            ->for('احسب قيمة القسط الشهري بناءً على سعر الوحدة، الدفعة المقدمة، وعدد سنوات التقسيط')
            ->withNumberParameter('price', 'سعر الوحدة بالجنيه المصري', required: true)
            ->withNumberParameter('down_payment', 'قيمة الدفعة المقدمة بالجنيه المصري', required: true)
            ->withNumberParameter('years', 'عدد سنوات التقسيط', required: true)
            ->using($this);
    }

    public function __invoke(int $price, int $down_payment, int $years): string
    {
        $remaining = $price - $down_payment;

        if ($remaining <= 0 || $years <= 0) {
            return json_encode([
                'error' => 'قيم الإدخال غير صالحة. يجب أن يكون المبلغ المتبقي وعدد السنوات أكبر من صفر.',
            ]);
        }

        $monthlyPayment = round($remaining / ($years * 12), 2);

        app(RetrievedFacts::class)->addInstallmentResult($monthlyPayment);

        return json_encode([
            'monthly_payment' => $monthlyPayment,
            'formatted' => number_format($monthlyPayment, 2).' جنيه مصري شهرياً',
        ]);
    }
}
