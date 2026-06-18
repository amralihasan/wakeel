@php
    $metaTitle = __('marketing.meta_home_title');
    $metaDesc  = __('marketing.meta_home_desc');
    $locale    = app()->getLocale();
    $isAr      = $locale === 'ar';
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- ============================================================
     HERO
     ============================================================ --}}
<section class="relative overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-zinc-900 text-white">
    {{-- subtle grid overlay --}}
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            {{-- Copy --}}
            <div>
                <div class="inline-flex items-center gap-2 bg-brand-800/60 border border-brand-600/40 rounded-full px-4 py-1.5 text-sm text-brand-200 mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-wa-green animate-pulse"></span>
                    {{ __('marketing.hero_trust') }}
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-snug tracking-tight mb-6">
                    {{ __('marketing.hero_headline') }}
                </h1>

                <p class="text-lg text-brand-100/80 leading-relaxed mb-8 max-w-xl">
                    {{ __('marketing.hero_subhead') }}
                </p>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-wa-green text-zinc-900 font-bold text-base hover:brightness-110 transition-all shadow-lg shadow-wa-green/30"
                    >
                        {{ __('marketing.hero_cta_primary') }}
                        <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a
                        href="{{ route('marketing.how-it-works') }}"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-white/20 text-white font-semibold text-base hover:bg-white/10 transition-all"
                    >
                        {{ __('marketing.hero_cta_secondary') }}
                    </a>
                </div>
            </div>

            {{-- WhatsApp chat mockup --}}
            <div class="flex justify-center lg:justify-end">
                <div class="w-72 bg-[#ECE5DD] rounded-2xl shadow-2xl overflow-hidden">
                    {{-- Chat header --}}
                    <div class="bg-[#075E54] px-4 py-3 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-brand-400 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">و</div>
                        <div>
                            <p class="text-white font-semibold text-sm leading-none mb-0.5">{{ __('marketing.hero_chat_header') }}</p>
                            <p class="text-[#B2DFDB] text-xs">{{ __('marketing.hero_chat_online') }}</p>
                        </div>
                    </div>
                    {{-- Chat messages --}}
                    <div class="p-3 space-y-2 min-h-[280px]">
                        {{-- Customer message --}}
                        <div class="flex {{ $isAr ? 'justify-start' : 'justify-end' }}">
                            <div class="bg-[#DCF8C6] rounded-xl rounded-{{ $isAr ? 'ss' : 'es' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_customer_1') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:14 PM ✓✓</p>
                            </div>
                        </div>
                        {{-- Bot message --}}
                        <div class="flex {{ $isAr ? 'justify-end' : 'justify-start' }} items-end gap-1">
                            <div class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mb-1">و</div>
                            <div class="bg-white rounded-xl rounded-{{ $isAr ? 'es' : 'ss' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_bot_1') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:14 PM</p>
                            </div>
                        </div>
                        {{-- Customer --}}
                        <div class="flex {{ $isAr ? 'justify-start' : 'justify-end' }}">
                            <div class="bg-[#DCF8C6] rounded-xl rounded-{{ $isAr ? 'ss' : 'es' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_customer_2') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:15 PM ✓✓</p>
                            </div>
                        </div>
                        {{-- Bot --}}
                        <div class="flex {{ $isAr ? 'justify-end' : 'justify-start' }} items-end gap-1">
                            <div class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mb-1">و</div>
                            <div class="bg-white rounded-xl rounded-{{ $isAr ? 'es' : 'ss' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_bot_2') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:15 PM</p>
                            </div>
                        </div>
                        {{-- Customer --}}
                        <div class="flex {{ $isAr ? 'justify-start' : 'justify-end' }}">
                            <div class="bg-[#DCF8C6] rounded-xl rounded-{{ $isAr ? 'ss' : 'es' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_customer_3') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:16 PM ✓✓</p>
                            </div>
                        </div>
                        {{-- Bot final --}}
                        <div class="flex {{ $isAr ? 'justify-end' : 'justify-start' }} items-end gap-1">
                            <div class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-white text-[10px] font-bold flex-shrink-0 mb-1">و</div>
                            <div class="bg-white rounded-xl rounded-{{ $isAr ? 'es' : 'ss' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                <p class="text-[#303030] text-xs leading-relaxed">{{ __('marketing.chat_bot_3') }}</p>
                                <p class="text-[#9E9E9E] text-[10px] text-end mt-0.5">9:16 PM</p>
                            </div>
                        </div>
                    </div>
                    {{-- Badges --}}
                    <div class="px-3 pb-3 flex flex-wrap gap-1.5">
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-brand-100 text-brand-800 rounded-full px-2 py-0.5">⚡ {{ __('marketing.chat_badge_instant') }}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-amber-100 text-amber-800 rounded-full px-2 py-0.5">★ {{ __('marketing.chat_badge_qualify') }}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold bg-blue-100 text-blue-800 rounded-full px-2 py-0.5">📅 {{ __('marketing.chat_badge_book') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Wave divider --}}
    <div class="absolute bottom-0 start-0 end-0">
        <svg viewBox="0 0 1440 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full"><path d="M0 40L1440 40L1440 20C1440 20 1080 0 720 0C360 0 0 20 0 20L0 40Z" fill="white"/></svg>
    </div>
</section>


{{-- ============================================================
     PROBLEM → SOLUTION
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-start">

            {{-- Problem --}}
            <div>
                <p class="text-brand-700 font-semibold text-sm uppercase tracking-widest mb-3">{{ __('marketing.problem_title') }}</p>
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 mb-8">{{ __('marketing.problem_subtitle') }}</h2>
                <div class="space-y-6">
                    @foreach([
                        ['icon'=>'🌙','title'=>'problem_1_title','desc'=>'problem_1_desc'],
                        ['icon'=>'⏱️','title'=>'problem_2_title','desc'=>'problem_2_desc'],
                        ['icon'=>'😓','title'=>'problem_3_title','desc'=>'problem_3_desc'],
                    ] as $p)
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-xl flex-shrink-0 mt-0.5">{{ $p['icon'] }}</div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 mb-1">{{ __('marketing.'.$p['title']) }}</h3>
                            <p class="text-zinc-500 text-sm leading-relaxed">{{ __('marketing.'.$p['desc']) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Solution --}}
            <div>
                <p class="text-brand-700 font-semibold text-sm uppercase tracking-widest mb-3">{{ __('marketing.solution_title') }}</p>
                <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 mb-8">{{ __('marketing.solution_subtitle') }}</h2>
                <div class="space-y-6">
                    @foreach([
                        ['icon'=>'⚡','title'=>'solution_1_title','desc'=>'solution_1_desc'],
                        ['icon'=>'🎯','title'=>'solution_2_title','desc'=>'solution_2_desc'],
                        ['icon'=>'📅','title'=>'solution_3_title','desc'=>'solution_3_desc'],
                    ] as $s)
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-xl bg-brand-50 border border-brand-100 flex items-center justify-center text-xl flex-shrink-0 mt-0.5">{{ $s['icon'] }}</div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 mb-1">{{ __('marketing.'.$s['title']) }}</h3>
                            <p class="text-zinc-500 text-sm leading-relaxed">{{ __('marketing.'.$s['desc']) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     FEATURES GRID
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-zinc-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 mb-3">{{ __('marketing.features_title') }}</h2>
            <p class="text-zinc-500 text-lg max-w-xl mx-auto">{{ __('marketing.features_subtitle') }}</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['emoji'=>'🏠','title'=>'feature_catalog_title','desc'=>'feature_catalog_desc'],
                ['emoji'=>'📅','title'=>'feature_booking_title','desc'=>'feature_booking_desc'],
                ['emoji'=>'🎯','title'=>'feature_qualify_title','desc'=>'feature_qualify_desc'],
                ['emoji'=>'📸','title'=>'feature_media_title',  'desc'=>'feature_media_desc'],
                ['emoji'=>'🔔','title'=>'feature_followup_title','desc'=>'feature_followup_desc'],
                ['emoji'=>'🤝','title'=>'feature_handoff_title','desc'=>'feature_handoff_desc'],
            ] as $f)
            <div class="bg-white rounded-2xl p-6 border border-zinc-100 shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all">
                <div class="w-12 h-12 rounded-xl bg-brand-50 flex items-center justify-center text-2xl mb-4">{{ $f['emoji'] }}</div>
                <h3 class="font-semibold text-zinc-900 mb-2">{{ __('marketing.'.$f['title']) }}</h3>
                <p class="text-zinc-500 text-sm leading-relaxed">{{ __('marketing.'.$f['desc']) }}</p>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-10">
            <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-2 text-brand-700 font-semibold hover:text-brand-800 transition-colors">
                {{ $isAr ? 'شوف كل المميزات' : 'See all features' }}
                <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>


{{-- ============================================================
     HOW IT WORKS (3-step snippet)
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 mb-3">{{ __('marketing.hiw_title') }}</h2>
            <p class="text-zinc-500 text-lg">{{ __('marketing.hiw_subtitle') }}</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 relative">
            @foreach([
                ['n'=>'1','title'=>'hiw_step_1_title','desc'=>'hiw_step_1_desc'],
                ['n'=>'2','title'=>'hiw_step_2_title','desc'=>'hiw_step_2_desc'],
                ['n'=>'3','title'=>'hiw_step_3_title','desc'=>'hiw_step_3_desc'],
                ['n'=>'4','title'=>'hiw_step_4_title','desc'=>'hiw_step_4_desc'],
            ] as $i => $step)
            <div class="text-center relative">
                <div class="w-14 h-14 rounded-full bg-brand-700 text-white text-xl font-bold flex items-center justify-center mx-auto mb-4 shadow-lg shadow-brand-700/25">{{ $step['n'] }}</div>
                <h3 class="font-semibold text-zinc-900 mb-2">{{ __('marketing.'.$step['title']) }}</h3>
                <p class="text-zinc-500 text-sm leading-relaxed">{{ __('marketing.'.$step['desc']) }}</p>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-brand-700 text-white font-semibold hover:bg-brand-800 transition-colors shadow-xs">
                {{ __('marketing.hiw_cta') }}
                <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>


{{-- ============================================================
     DASHBOARD PREVIEW
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-zinc-900 text-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3">{{ __('marketing.dashboard_title') }}</h2>
            <p class="text-zinc-400 text-lg">{{ __('marketing.dashboard_subtitle') }}</p>
        </div>

        {{-- Dashboard mockup --}}
        <div class="bg-zinc-800 rounded-2xl border border-zinc-700 overflow-hidden shadow-2xl mx-auto max-w-4xl">
            {{-- Browser bar --}}
            <div class="bg-zinc-700 px-4 py-2.5 flex items-center gap-2">
                <div class="flex gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span><span class="w-3 h-3 rounded-full bg-amber-400"></span><span class="w-3 h-3 rounded-full bg-brand-500"></span></div>
                <div class="flex-1 bg-zinc-600 rounded-md h-5 mx-4 flex items-center px-3"><span class="text-zinc-400 text-xs">app.wakeel.ai/dashboard</span></div>
            </div>
            {{-- Metric cards --}}
            <div class="p-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach([
                    ['label' => $isAr ? 'محادثات اليوم' : 'Today\'s Chats', 'value' => '47', 'change' => '+12%', 'up' => true],
                    ['label' => $isAr ? 'عملاء ساخنون' : 'Hot Leads', 'value' => '8', 'change' => '+3', 'up' => true],
                    ['label' => $isAr ? 'معاينات محجوزة' : 'Viewings Booked', 'value' => '12', 'change' => '+5', 'up' => true],
                    ['label' => $isAr ? 'وقت الرد' : 'Avg Response', 'value' => '22s', 'change' => '–', 'up' => null],
                ] as $metric)
                <div class="bg-zinc-700/50 rounded-xl p-4">
                    <p class="text-zinc-400 text-xs mb-1">{{ $metric['label'] }}</p>
                    <p class="text-white text-2xl font-bold">{{ $metric['value'] }}</p>
                    @if($metric['up'] !== null)
                        <p class="text-brand-400 text-xs mt-0.5">{{ $metric['change'] }}</p>
                    @else
                        <p class="text-zinc-500 text-xs mt-0.5">{{ $metric['change'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Captions --}}
        <div class="grid sm:grid-cols-3 gap-6 mt-10 max-w-4xl mx-auto">
            @foreach(['dashboard_caption_1','dashboard_caption_2','dashboard_caption_3'] as $cap)
            <div class="flex items-center gap-3 text-zinc-400 text-sm">
                <span class="w-6 h-6 rounded-full bg-brand-700 text-white text-xs flex items-center justify-center font-bold flex-shrink-0">✓</span>
                {{ __('marketing.'.$cap) }}
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     STATS BAND
     ============================================================ --}}
<section class="py-16 bg-brand-700 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-brand-200 text-sm font-semibold uppercase tracking-widest mb-8">{{ __('marketing.stats_title') }}</p>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            @foreach([
                ['value'=>'stat_1_value','label'=>'stat_1_label'],
                ['value'=>'stat_2_value','label'=>'stat_2_label'],
                ['value'=>'stat_3_value','label'=>'stat_3_label'],
                ['value'=>'stat_4_value','label'=>'stat_4_label'],
            ] as $stat)
            <div>
                <p class="text-4xl sm:text-5xl font-bold mb-2 tracking-tight">{{ __('marketing.'.$stat['value']) }}</p>
                <p class="text-brand-200 text-sm">{{ __('marketing.'.$stat['label']) }}</p>
            </div>
            @endforeach
        </div>
        <p class="text-center text-brand-300/60 text-xs mt-8">{{ __('marketing.stats_disclaimer') }}</p>
    </div>
</section>


{{-- ============================================================
     PRICING TEASER
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-zinc-900 mb-3">{{ __('marketing.pricing_teaser_title') }}</h2>
            <p class="text-zinc-500 text-lg">{{ __('marketing.pricing_teaser_subtitle') }}</p>
        </div>

        <div class="grid sm:grid-cols-3 gap-6 max-w-4xl mx-auto">
            @foreach([
                ['slug'=>'starter','badge'=>null],
                ['slug'=>'growth', 'badge'=>__('marketing.pricing_popular')],
                ['slug'=>'enterprise','badge'=>null],
            ] as $plan)
            @php $cfg = config('plans.'.$plan['slug']); @endphp
            <div class="relative bg-white rounded-2xl border {{ $plan['badge'] ? 'border-brand-600 shadow-lg shadow-brand-700/10' : 'border-zinc-200' }} p-6 flex flex-col">
                @if($plan['badge'])
                    <span class="absolute -top-3 start-1/2 -translate-x-1/2 inline-flex px-3 py-1 rounded-full bg-brand-700 text-white text-xs font-bold whitespace-nowrap">{{ $plan['badge'] }}</span>
                @endif
                <h3 class="font-bold text-zinc-900 text-lg mb-1">{{ __('marketing.plan_'.$plan['slug'].'_name') }}</h3>
                <p class="text-zinc-500 text-sm mb-4">{{ __('marketing.plan_'.$plan['slug'].'_desc') }}</p>
                <p class="text-3xl font-bold text-zinc-900 mb-1">
                    {{ __('marketing.plan_'.$plan['slug'].'_price') }}
                    <span class="text-sm font-normal text-zinc-500">{{ __('marketing.pricing_currency') }}{{ __('marketing.pricing_per_month') }}</span>
                </p>
                <a
                    href="{{ route('register', ['plan' => $plan['slug']]) }}"
                    class="mt-6 block text-center px-4 py-2.5 rounded-lg {{ $plan['badge'] ? 'bg-brand-700 text-white hover:bg-brand-800' : 'border border-zinc-300 text-zinc-700 hover:bg-zinc-50' }} font-semibold text-sm transition-colors"
                >
                    {{ __('marketing.pricing_cta') }}
                </a>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-8">
            <a href="{{ route('marketing.pricing') }}" class="inline-flex items-center gap-2 text-brand-700 font-semibold hover:text-brand-800 transition-colors">
                {{ __('marketing.pricing_teaser_cta') }}
                <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>


{{-- ============================================================
     FAQ ACCORDION
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-zinc-50">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 text-center mb-12">{{ __('marketing.faq_title') }}</h2>

        <div class="space-y-3" x-data="{ open: null }">
            @foreach([1,2,3,4,5,6] as $i)
            <div class="bg-white rounded-xl border border-zinc-100 overflow-hidden">
                <button
                    @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                    class="w-full flex items-center justify-between px-5 py-4 text-start font-semibold text-zinc-900 hover:bg-zinc-50 transition-colors"
                    :aria-expanded="open === {{ $i }}"
                >
                    <span>{{ __('marketing.faq_'.$i.'_q') }}</span>
                    <svg
                        class="w-5 h-5 text-zinc-400 flex-shrink-0 ms-4 transition-transform duration-200"
                        :class="{ 'rotate-180': open === {{ $i }} }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div
                    x-show="open === {{ $i }}"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="px-5 pb-5"
                >
                    <p class="text-zinc-600 text-sm leading-relaxed">{{ __('marketing.faq_'.$i.'_a') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     FINAL CTA BAND
     ============================================================ --}}
<section class="py-20 lg:py-28 bg-gradient-to-br from-brand-950 to-zinc-900 text-white text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('marketing.final_cta_title') }}</h2>
        <p class="text-brand-200/80 text-lg mb-8">{{ __('marketing.final_cta_subtitle') }}</p>
        <div class="flex flex-wrap justify-center gap-4">
            <a
                href="{{ route('register') }}"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-wa-green text-zinc-900 font-bold text-base hover:brightness-110 transition-all shadow-lg shadow-wa-green/30"
            >
                {{ __('marketing.final_cta_button') }}
                <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            <a
                href="{{ route('marketing.contact') }}"
                class="inline-flex items-center gap-2 px-8 py-3.5 rounded-xl border border-white/20 text-white font-semibold text-base hover:bg-white/10 transition-all"
            >
                {{ __('marketing.final_cta_secondary') }}
            </a>
        </div>
    </div>
</section>

</x-layouts.marketing>
