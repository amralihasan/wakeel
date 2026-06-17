<?php

namespace App\Agent\Tools;

use App\Events\VisitBooked;
use App\Models\Unit;
use App\Models\Visit;
use Carbon\Carbon;
use Prism\Prism\Tool;

class BookVisitTool extends Tool
{
    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('book_visit')
            ->for('سجل طلب حجز موعد زيارة للعميل لمعاينة وحدة عقارية محددة')
            ->withNumberParameter('unit_id', 'معرف الوحدة العقارية', required: true)
            ->withStringParameter('date', 'تاريخ الزيادة بصيغة YYYY-MM-DD', required: true)
            ->withStringParameter('time', 'وقت الزيارة بصيغة HH:MM', required: true)
            ->using($this);
    }

    public function __invoke(int $unit_id, string $date, string $time): string
    {
        $unit = Unit::where('id', $unit_id)
            ->where('company_id', $this->companyId)
            ->first();

        if (! $unit) {
            return 'الوحدة غير موجودة أو لا تنتمي لشركتك.';
        }

        $scheduledAt = Carbon::parse("{$date} {$time}", 'Africa/Cairo');

        if ($scheduledAt->isPast()) {
            return 'تاريخ ووقت الزيارة يجب أن يكون في المستقبل.';
        }

        $existingVisit = Visit::where('unit_id', $unit_id)
            ->where('company_id', $this->companyId)
            ->where('scheduled_at', $scheduledAt)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingVisit) {
            return 'هذا الموعد محجوز مسبقاً. يرجى اختيار وقت آخر.';
        }

        $visit = Visit::create([
            'company_id' => $this->companyId,
            'lead_id' => $this->leadId,
            'unit_id' => $unit_id,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
        ]);

        VisitBooked::dispatch($visit);

        return 'تم تسجيل طلب حجز الزيارة بنجاح بانتظار التأكيد.';
    }
}
