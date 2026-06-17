<?php

namespace App\Agent\Tools;

use App\Jobs\SendWhatsAppMedia;
use App\Models\Company;
use App\Models\Unit;
use App\Models\UnitMedia;
use Prism\Prism\Tool;

class SendUnitMediaTool extends Tool
{
    protected static array $typeMap = [
        'images' => 'image',
        'pdf' => 'pdf',
        'floorplan' => 'floorplan',
        'video' => 'video',
    ];

    public function __construct(
        protected int $companyId,
        protected int $leadId,
        protected string $customerPhone,
    ) {
        $this
            ->as('send_unit_media')
            ->for('أرسل للعميل صور أو ملفات أو كتيبات أو فيديوهات لوحدة عقارية محددة')
            ->withNumberParameter('unit_id', 'معرف الوحدة العقارية', required: true)
            ->withEnumParameter('media_type', 'نوع الملف: images, pdf, floorplan, video', ['images', 'pdf', 'floorplan', 'video'], required: true)
            ->using($this);
    }

    public function __invoke(int $unit_id, string $media_type): string
    {
        $unit = Unit::where('id', $unit_id)
            ->where('company_id', $this->companyId)
            ->first();

        if (! $unit) {
            return 'الوحدة غير موجودة أو لا تنتمي لشركتك.';
        }

        $dbType = self::$typeMap[$media_type] ?? null;

        if (! $dbType) {
            return 'نوع الملف غير مدعوم.';
        }

        $mediaRecords = UnitMedia::where('unit_id', $unit_id)
            ->where('type', $dbType)
            ->get();

        if ($mediaRecords->isEmpty()) {
            return 'لا توجد ملفات من هذا النوع للوحدة المحددة.';
        }

        $company = Company::find($this->companyId);

        if (! $company?->dialog360_channel_id) {
            return 'لم يتم تكوين قناة واتساب بعد.';
        }

        foreach ($mediaRecords as $media) {
            $caption = $media->caption ?? $unit->title;

            SendWhatsAppMedia::dispatch(
                $company->dialog360_channel_id,
                $this->customerPhone,
                $media->path,
                $dbType === 'image' ? 'image' : 'document',
                $media_type === 'pdf' ? $media->path : null,
                $caption,
            );
        }

        return 'تم إرسال الملفات بنجاح للعميل.';
    }
}
