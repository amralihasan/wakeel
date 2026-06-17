<?php

namespace App\Services\Agent;

use App\Jobs\SendWhatsAppText;
use App\Models\Company;

class AgentRunner
{
    public function handle(Company $company, string $customerPhone, string $incomingText): void
    {
        SendWhatsAppText::dispatch(
            $company->dialog360_channel_id,
            $customerPhone,
            'مرحباً بك في وكيل العقاري! كيف يمكنني مساعدتك اليوم؟'
        );
    }
}
