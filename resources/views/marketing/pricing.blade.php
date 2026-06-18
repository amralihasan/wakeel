@php
    $metaTitle = __('marketing.meta_pricing_title');
    $metaDesc  = __('marketing.meta_pricing_desc');
    $isAr      = app()->getLocale() === 'ar';
    $plans = [
        'starter' => [
            'features' => [
                ['key' => 'plan_feat_conversations', 'count' => '500'],
                ['key' => 'plan_feat_units',         'count' => '10'],
                ['key' => 'plan_feat_reps',          'count' => '1'],
                ['key' => 'plan_feat_whatsapp_1'],
                ['key' => 'plan_feat_ai_reply'],
                ['key' => 'plan_feat_booking'],
                ['key' => 'plan_feat_scoring'],
                ['key' => 'plan_feat_dashboard'],
            ],
        ],
        'growth' => [
            'badge' => true,
            'features' => [
                ['key' => 'plan_feat_conversations', 'count' => '2,000'],
                ['key' => 'plan_feat_units_unlimited'],
                ['key' => 'plan_feat_reps',           'count' => '3'],
                ['key' => 'plan_feat_whatsapp_1'],
                ['key' => 'plan_feat_ai_reply'],
                ['key' => 'plan_feat_booking'],
                ['key' => 'plan_feat_scoring'],
                ['key' => 'plan_feat_followup'],
                ['key' => 'plan_feat_handoff'],
                ['key' => 'plan_feat_dashboard'],
                ['key' => 'plan_feat_advanced_analytics'],
            ],
        ],
        'enterprise' => [
            'features' => [
                ['key' => 'plan_feat_conversations', 'count' => '10,000'],
                ['key' => 'plan_feat_units_unlimited'],
                ['key' => 'plan_feat_reps_unlimited'],
                ['key' => 'plan_feat_whatsapp_2'],
                ['key' => 'plan_feat_ai_reply'],
                ['key' => 'plan_feat_booking'],
                ['key' => 'plan_feat_scoring'],
                ['key' => 'plan_feat_followup'],
                ['key' => 'plan_feat_handoff'],
                ['key' => 'plan_feat_dashboard'],
                ['key' => 'plan_feat_advanced_analytics'],
                ['key' => 'plan_feat_priority_support'],
                ['key' => 'plan_feat_custom_training'],
                ['key' => 'plan_feat_dedicated_manager'],
            ],
        ],
    ];
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- Hero --}}
<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-20 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">{{ __('marketing.pricing_title') }}</h1>
        <p class="text-brand-200/80 text-lg">{{ __('marketing.pricing_subtitle') }}</p>
    </div>
</section>

{{-- Billing toggle + plans --}}
<section class="py-20 lg:py-28 bg-white" x-data="{ annual: false }">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Toggle --}}
        <div class="flex items-center justify-center gap-4 mb-14">
            <span class="text-sm font-medium" :class="annual ? 'text-zinc-400' : 'text-zinc-900'">{{ __('marketing.pricing_monthly') }}</span>
            <button
                @click="annual = !annual"
                class="relative w-12 h-6 rounded-full transition-colors"
                :class="annual ? 'bg-brand-600' : 'bg-zinc-200'"
                :aria-checked="annual"
                role="switch"
            >
                <span
                    class="absolute top-0.5 start-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200"
                    :class="annual ? 'translate-x-6' : 'translate-x-0'"
                ></span>
            </button>
            <span class="text-sm font-medium flex items-center gap-2" :class="annual ? 'text-zinc-900' : 'text-zinc-400'">
                {{ __('marketing.pricing_annual') }}
                <span class="inline-flex px-2 py-0.5 rounded-full bg-brand-100 text-brand-700 text-xs font-bold">{{ __('marketing.pricing_annual_badge') }}</span>
            </span>
        </div>

        {{-- Plans --}}
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($plans as $slug => $plan)
            <div class="relative flex flex-col rounded-2xl border {{ isset($plan['badge']) && $plan['badge'] ? 'border-brand-600 shadow-xl shadow-brand-700/10' : 'border-zinc-200' }} p-7">
                @if(isset($plan['badge']) && $plan['badge'])
                    <span class="absolute -top-3.5 start-1/2 -translate-x-1/2 inline-flex px-4 py-1 rounded-full bg-brand-700 text-white text-xs font-bold whitespace-nowrap">{{ __('marketing.pricing_popular') }}</span>
                @endif

                <h2 class="text-xl font-bold text-zinc-900 mb-1">{{ __('marketing.plan_'.$slug.'_name') }}</h2>
                <p class="text-zinc-500 text-sm mb-6">{{ __('marketing.plan_'.$slug.'_desc') }}</p>

                <div class="mb-6">
                    <p class="text-4xl font-bold text-zinc-900">
                        <span x-show="!annual">{{ __('marketing.plan_'.$slug.'_price') }}</span>
                        <span x-show="annual" x-cloak>{{ __('marketing.plan_'.$slug.'_price_annual') }}</span>
                        <span class="text-base font-normal text-zinc-500 ms-1">{{ __('marketing.pricing_currency') }}{{ __('marketing.pricing_per_month') }}</span>
                    </p>
                </div>

                <a
                    href="{{ route('register', ['plan' => $slug]) }}"
                    class="block text-center px-4 py-3 rounded-xl font-semibold text-sm transition-colors mb-7 {{ isset($plan['badge']) && $plan['badge'] ? 'bg-brand-700 text-white hover:bg-brand-800' : 'border border-zinc-300 text-zinc-700 hover:bg-zinc-50' }}"
                >
                    {{ __('marketing.pricing_cta') }}
                </a>

                <ul class="space-y-3 flex-1">
                    @foreach($plan['features'] as $feat)
                    <li class="flex items-start gap-2.5 text-sm text-zinc-600">
                        <svg class="w-4 h-4 text-brand-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @if(isset($feat['count']))
                            {{ str_replace(':count', $feat['count'], __('marketing.'.$feat['key'])) }}
                        @else
                            {{ __('marketing.'.$feat['key']) }}
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        {{-- Enterprise contact note --}}
        <p class="text-center text-zinc-500 text-sm mt-10">
            {{ $isAr ? 'تحتاج خطة مخصصة لعدة مشاريع؟' : 'Need a custom plan for multiple projects?' }}
            <a href="{{ route('marketing.contact') }}" class="text-brand-700 font-semibold hover:underline ms-1">{{ __('marketing.pricing_contact_sales') }}</a>
        </p>
    </div>
</section>

{{-- Feature comparison table --}}
<section class="py-16 lg:py-24 bg-zinc-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 text-center mb-12">{{ $isAr ? 'مقارنة الخطط' : 'Plan comparison' }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200">
                        <th class="text-start py-3 px-4 font-semibold text-zinc-900">{{ $isAr ? 'المميزة' : 'Feature' }}</th>
                        <th class="py-3 px-4 font-semibold text-zinc-900 text-center">{{ __('marketing.plan_starter_name') }}</th>
                        <th class="py-3 px-4 font-semibold text-brand-700 text-center">{{ __('marketing.plan_growth_name') }}</th>
                        <th class="py-3 px-4 font-semibold text-zinc-900 text-center">{{ __('marketing.plan_enterprise_name') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach([
                        [$isAr?'المحادثات':'Conversations', '500', '2,000', '10,000'],
                        [$isAr?'الوحدات العقارية':'Property units', '10', '∞', '∞'],
                        [$isAr?'مناديب المبيعات':'Sales reps', '1', '3', '∞'],
                        [$isAr?'أرقام الواتساب':'WhatsApp numbers', '1', '1', '2'],
                        [$isAr?'ردود تلقائية':'AI auto-replies', '✓', '✓', '✓'],
                        [$isAr?'حجز معاينات':'Booking', '✓', '✓', '✓'],
                        [$isAr?'تأهيل العملاء':'Lead scoring', '✓', '✓', '✓'],
                        [$isAr?'متابعة تلقائية':'Auto follow-ups', '—', '✓', '✓'],
                        [$isAr?'تسليم بشري':'Human handoff', '—', '✓', '✓'],
                        [$isAr?'تحليلات متقدمة':'Advanced analytics', '—', '✓', '✓'],
                        [$isAr?'دعم أولوية':'Priority support', '—', '—', '✓'],
                        [$isAr?'تدريب مخصص':'Custom bot training', '—', '—', '✓'],
                    ] as [$label, $starter, $growth, $ent])
                    <tr class="hover:bg-zinc-50 transition-colors">
                        <td class="py-3 px-4 text-zinc-700">{{ $label }}</td>
                        <td class="py-3 px-4 text-center text-zinc-600">{{ $starter }}</td>
                        <td class="py-3 px-4 text-center text-brand-700 font-medium">{{ $growth }}</td>
                        <td class="py-3 px-4 text-center text-zinc-600">{{ $ent }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- Pricing FAQ --}}
<section class="py-20 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 text-center mb-12">{{ __('marketing.pricing_faq_title') }}</h2>
        <div class="space-y-3" x-data="{ open: null }">
            @foreach([1,2,3,4] as $i)
            <div class="bg-zinc-50 rounded-xl border border-zinc-100 overflow-hidden">
                <button
                    @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                    class="w-full flex items-center justify-between px-5 py-4 text-start font-semibold text-zinc-900 hover:bg-zinc-100 transition-colors"
                    :aria-expanded="open === {{ $i }}"
                >
                    <span>{{ __('marketing.pricing_faq_'.$i.'_q') }}</span>
                    <svg class="w-5 h-5 text-zinc-400 flex-shrink-0 ms-4 transition-transform duration-200" :class="{ 'rotate-180': open === {{ $i }} }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open === {{ $i }}" x-transition class="px-5 pb-5">
                    <p class="text-zinc-600 text-sm leading-relaxed">{{ __('marketing.pricing_faq_'.$i.'_a') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

</x-layouts.marketing>
