<?php

namespace App\Agent\Tools;

use App\Models\Unit;
use Prism\Prism\Tool;

class SearchPropertiesTool extends Tool
{
    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('search_properties')
            ->for('ابحث عن الوحدات العقارية المتاحة للبيع حسب ميزانية العميل ورغباته')
            ->withNumberParameter('budget_max', 'أقصى ميزانية للعميل بالجنيه المصري', required: true)
            ->withNumberParameter('rooms', 'عدد الغرف المطلوب (اختياري)', required: false)
            ->withStringParameter('location', 'الموقع المطلوب (اختياري)', required: false)
            ->withStringParameter('type', 'نوع الوحدة: apartment, duplex, penthouse, villa, studio (اختياري)', required: false)
            ->using($this);
    }

    public function __invoke(int $budget_max, ?int $rooms = null, ?string $location = null, ?string $type = null): string
    {
        $query = Unit::where('company_id', $this->companyId)
            ->where('status', 'available')
            ->where('price', '<=', $budget_max);

        if ($rooms) {
            $query->where('rooms', $rooms);
        }

        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($type) {
            $query->where('type', $type);
        }

        $units = $query->orderBy('price')
            ->limit(3)
            ->get()
            ->map(fn (Unit $unit) => [
                'id' => $unit->id,
                'title' => $unit->title,
                'rooms' => $unit->rooms,
                'area' => $unit->area,
                'price' => $unit->price,
                'location' => $unit->location,
                'down_payment' => $unit->down_payment,
                'installment_years' => $unit->installment_years,
                'has_media' => $unit->media()->exists(),
            ]);

        if ($units->isEmpty()) {
            return 'لا توجد وحدات مطابقة للخيارات المدخلة حالياً.';
        }

        return $units->toJson();
    }
}
