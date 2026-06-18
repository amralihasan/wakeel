<?php

return [
    'title' => 'إعدادات البوت والذكاء الاصطناعي',
    'bot_name' => 'اسم البوت',
    'dialect' => 'اللهجة المفضلة',
    'working_hours' => 'ساعات العمل',
    'bot_active' => 'البوت نشط ويقوم بالرد الآلي',
    'bot_inactive' => 'البوت متوقف حالياً',
    'thresholds' => 'عتبات التحويل ومستوى التنبيه',
    'lead_score_threshold' => 'الدرجة المطلوبة للعميل الساخن (من 100)',
    'unproductive_messages_threshold' => 'عدد الرسائل غير المفيدة قبل التحويل لبشري',
    'sandbox' => 'منطقة اختبار البوت (الساحة التجريبية)',
    'test_placeholder' => 'اكتب رسالة للتجربة...',
    'send' => 'إرسال',
    'save' => 'حفظ الإعدادات',

    // Additional keys for settings UI
    'assistant_settings_title' => 'إعدادات المساعد الذكي',
    'assistant_name_label' => 'اسم المساعد (الروبوت)',
    'preferred_dialect_label' => 'اللهجة المفضلة للمحادثة',
    'working_hours_label' => 'مواعيد عمل الشركة',
    'bot_active_checkbox' => 'تنشيط المساعد (البوت فعال ويرد على العملاء)',
    'escalation_rules_heading' => 'قواعد التحويل لوكيل بشري',
    'lead_score_threshold_label' => 'درجة الاهتمام المطلوبة للتحويل (Lead Score)',
    'unproductive_messages_threshold_label' => 'أقصى عدد رسائل غير منتجة قبل التحويل',
    'followup_rules_heading' => 'إعدادات المتابعة التلقائية',
    'followups_enabled_checkbox' => 'تمكين المتابعة التلقائية للعملاء (إعادة تنشيط العملاء غير النشطين)',
    'max_followups_label' => 'أقصى عدد رسائل متابعة لكل عميل',
    'test_assistant_heading' => 'اختبار المساعد',
    'test_assistant_desc' => 'تحدث مع المساعد لتجربة لهجته وطريقة الرد (لن يتم إرسال رسائل فعلية للواتساب).',
    'test_assistant_placeholder' => 'اكتب رسالة لبدء اختبار المساعد.',
    'test_message_input_placeholder' => 'اكتب رسالة...',
    'always_active' => 'طوال اليوم 24/7',
    'specific_hours' => 'ساعات عمل محددة (9 ص - 9 م)',
    'success_saved' => 'تم حفظ إعدادات البوت بنجاح',

    // Fallbacks & Error greetings
    'fallback_error' => 'معلش حصل خطأ بسيط، ممكن تعيد رسالتك؟',
    'default_followup_message' => 'مرحباً، حابين نتطمن لو لسه مهتم بعروضنا العقارية؟ لو عندك أي استفسار أنا هنا للمساعدة.',
    'lead_hot_notification' => 'تنبيه: العميل أصبح ساخناً وجاهز للتحويل لبشري.',
    'followup_instruction' => 'العميل لم يقم بالرد منذ 23 ساعة بعد آخر رسالة منا. اكتب رسالة متابعة قصيرة، ودودة ومخصصة بناءً على اهتماماته وسياق المحادثة لإعادة تنشيط الحوار. لا تستخدم أي أدوات ولا تقم بإنشاء روابط أو تخمين تفاصيل غير موجودة.',

    // Dialects
    'friendly_egyptian' => 'عامية مصرية ودودة',
    'formal_arabic' => 'عربية فصحى مبسطة',
    'gulf_arabic' => 'لهجة خليجية ملائمة',
    'sandbox_lead_name' => 'تجربة المنصة',
];
