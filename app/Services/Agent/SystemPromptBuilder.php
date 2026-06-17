<?php

namespace App\Services\Agent;

use App\Models\Company;
use App\Models\Lead;
use Carbon\Carbon;

class SystemPromptBuilder
{
    public function build(Company $company, ?Lead $lead = null): string
    {
        $settings = $company->bot_settings ?? [];

        return implode("\n\n", [
            $this->personaSection($company, $settings),
            $this->toolPolicySection(),
            $this->escalationSection($settings),
            $this->contextSection($company, $lead, $settings),
        ]);
    }

    protected function personaSection(Company $company, array $settings): string
    {
        $botName = $settings['bot_name'] ?? 'نور';
        $tone = $settings['tone'] ?? 'friendly_egyptian';

        $toneInstructions = match ($tone) {
            'formal' => 'تحدث باللغة العربية الفصحى المبسطة، وبأسلوب مهني وراقي جداً.',
            'gulf' => 'تحدث بلهجة خليجية ملائمة ومرحبة لعملاء العقارات في الخليج العربي.',
            default => 'تحدث بلهجة مصرية عامية ودودة، سهلة الفهم، مألوفة وقريبة للقلب (Friendly Egyptian).',
        };

        return <<<PROMPT
اسمك هو "$botName" وأنت مساعد عقاري ذكي تعمل لصالح شركة "{$company->name}".
مهمتك الأساسية هي مساعدة العملاء المهتمين بالوحدات العقارية، فهم متطلباتهم (الميزانية، عدد الغرف، الموقع، نظام السداد)، وترشيح الوحدات المناسبة لهم، وتوجيه الجادين لحجز موعد زيارة للموقع.

تعليمات الأسلوب:
$toneInstructions
كن ودوداً، وتجنب الردود الطويلة جداً التي تبدو كصفحات نصية. أجب بشكل مناسب لمحادثات الواتساب.
PROMPT;
    }

    protected function toolPolicySection(): string
    {
        return <<<'PROMPT'
تعليمات استخدام الأدوات والبيانات:
1. لا تخترع أو تخمن تفاصيل العقارات (مثل الأسعار، المساحات، المواقع أو التوفر). يجب عليك استخدام أداة البحث `search_properties` لمعرفة الوحدات المتاحة.
2. إذا سألك العميل عن تفاصيل معينة لا تملكها، استخدم أداة البحث فوراً. وإذا لم تجد وحدات مطابقة، أخبره بلطف وسأله إن كان يود تعديل خياراته (مثل الميزانية أو الموقع).
3. يمكنك إرسال بروشورات أو صور الوحدات عبر أداة `send_unit_media` فقط. لا تدعي إرسال ملفات لم تقم الأداة بإرسالها.
PROMPT;
    }

    protected function escalationSection(array $settings): string
    {
        $threshold = $settings['escalation_rules']['score_threshold'] ?? 70;
        $unproductiveLimit = $settings['escalation_rules']['unproductive_messages'] ?? 5;

        return <<<PROMPT
تعليمات التحويل لوكيل مبيعات بشري (الهاندأوف):
يجب عليك استدعاء أداة `escalate_to_agent` لتحويل العميل لوكيل بشري في الحالات التالية:
1. إذا طلب العميل بوضوح التحدث مع شخص بشري أو مستشار مبيعات.
2. إذا تمكنت من تقييم العميل ووصلت درجة تأهيله (Lead Score) إلى $threshold أو أكثر.
3. إذا تكررت الأسئلة دون تقدم واضح بعد $unproductiveLimit رسائل غير منتجة.
PROMPT;
    }

    protected function contextSection(Company $company, ?Lead $lead, array $settings): string
    {
        $now = Carbon::now('Africa/Cairo');
        $dateStr = $now->locale('ar')->isoFormat('dddd، D MMMM YYYY');
        $timeStr = $now->format('H:i');

        $leadContext = 'غير متوفرة حالياً';
        if ($lead) {
            $leadContext = 'الاسم: '.($lead->name ?? 'غير معروف')."\n";
            $leadContext .= 'الهاتف: '.$lead->customer_phone."\n";
            $leadContext .= 'الميزانية القصوى: '.($lead->budget_max ? number_format($lead->budget_max).' جنيه مصري' : 'لم تحدد بعد')."\n";
            $leadContext .= 'الوحدة المهتم بها: '.($lead->interestedUnit?->title ?? 'لم تحدد بعد')."\n";
            $leadContext .= 'التقييم الحالي: '.($lead->score ?? 'لم يقيم بعد');
        }

        $workingHours = $settings['working_hours'] ?? '24_7';
        $workingHoursContext = $workingHours === '24_7'
            ? 'الشركة تعمل على مدار 24 ساعة طوال أيام الأسبوع.'
            : 'الشركة تعمل طوال أيام الأسبوع من 9 صباحاً وحتى 9 مساءً بتوقيت القاهرة. إذا كان الوقت الحالي خارج أوقات العمل، أخبر العميل بلطف أن مستشاري المبيعات سيتواصلون معه بمجرد بدء مواعيد العمل.';

        return <<<PROMPT
سياق العملية الحالي:
- تاريخ اليوم: $dateStr
- الوقت الحالي: $timeStr بتوقيت القاهرة.
- نظام أوقات العمل: $workingHoursContext

معلومات العميل الحالي:
$leadContext
PROMPT;
    }
}
