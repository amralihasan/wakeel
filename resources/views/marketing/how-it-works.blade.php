@php
    $metaTitle = __('marketing.meta_hiw_title');
    $metaDesc  = __('marketing.meta_hiw_desc');
    $isAr      = app()->getLocale() === 'ar';
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- Hero --}}
<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-20 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">{{ __('marketing.hiw_title_page') }}</h1>
        <p class="text-brand-200/80 text-lg">{{ __('marketing.hiw_subtitle_page') }}</p>
    </div>
</section>

{{-- Buyer journey --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 mb-12 text-center">{{ __('marketing.hiw_customer_title') }}</h2>
        <div class="relative">
            {{-- Connector line --}}
            <div class="absolute {{ $isAr ? 'right-7' : 'left-7' }} top-8 bottom-8 w-0.5 bg-brand-100 hidden sm:block"></div>
            <div class="space-y-8">
                @foreach([
                    ['n'=>'1','title'=>'hiw_customer_step_1_title','desc'=>'hiw_customer_step_1_desc','emoji'=>'💬'],
                    ['n'=>'2','title'=>'hiw_customer_step_2_title','desc'=>'hiw_customer_step_2_desc','emoji'=>'⚡'],
                    ['n'=>'3','title'=>'hiw_customer_step_3_title','desc'=>'hiw_customer_step_3_desc','emoji'=>'🏠'],
                    ['n'=>'4','title'=>'hiw_customer_step_4_title','desc'=>'hiw_customer_step_4_desc','emoji'=>'📅'],
                ] as $step)
                <div class="flex gap-5 items-start">
                    <div class="relative z-10 w-14 h-14 rounded-full bg-brand-700 text-white text-xl font-bold flex items-center justify-center flex-shrink-0 shadow-lg shadow-brand-700/25">{{ $step['n'] }}</div>
                    <div class="flex-1 bg-zinc-50 rounded-2xl p-5 border border-zinc-100">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xl">{{ $step['emoji'] }}</span>
                            <h3 class="font-semibold text-zinc-900">{{ __('marketing.'.$step['title']) }}</h3>
                        </div>
                        <p class="text-zinc-600 text-sm leading-relaxed">{{ __('marketing.'.$step['desc']) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Company onboarding --}}
<section class="py-20 lg:py-28 bg-zinc-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 mb-12 text-center">{{ __('marketing.hiw_company_title') }}</h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach([
                ['n'=>'1','title'=>'hiw_company_step_1_title','desc'=>'hiw_company_step_1_desc','emoji'=>'✍️'],
                ['n'=>'2','title'=>'hiw_company_step_2_title','desc'=>'hiw_company_step_2_desc','emoji'=>'🏗️'],
                ['n'=>'3','title'=>'hiw_company_step_3_title','desc'=>'hiw_company_step_3_desc','emoji'=>'📲'],
                ['n'=>'4','title'=>'hiw_company_step_4_title','desc'=>'hiw_company_step_4_desc','emoji'=>'📊'],
            ] as $step)
            <div class="bg-white rounded-2xl border border-zinc-100 p-5 text-center shadow-xs">
                <div class="w-12 h-12 rounded-full bg-brand-700 text-white font-bold text-lg flex items-center justify-center mx-auto mb-3">{{ $step['n'] }}</div>
                <p class="text-2xl mb-3">{{ $step['emoji'] }}</p>
                <h3 class="font-semibold text-zinc-900 text-sm mb-2">{{ __('marketing.'.$step['title']) }}</h3>
                <p class="text-zinc-500 text-xs leading-relaxed">{{ __('marketing.'.$step['desc']) }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-brand-700 text-white text-center">
    <div class="max-w-xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-6">{{ $isAr ? 'ابدأ في ٥ دقايق' : 'Get started in 5 minutes' }}</h2>
        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-brand-800 font-bold hover:bg-brand-50 transition-colors">
            {{ __('marketing.nav_start_free') }}
            <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

</x-layouts.marketing>
