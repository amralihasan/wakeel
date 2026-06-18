@php
    $metaTitle = __('marketing.meta_features_title');
    $metaDesc  = __('marketing.meta_features_desc');
    $isAr      = app()->getLocale() === 'ar';
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- Hero --}}
<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-20 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">{{ __('marketing.features_page_title') }}</h1>
        <p class="text-brand-200/80 text-lg">{{ __('marketing.features_page_subtitle') }}</p>
    </div>
</section>

{{-- Features detailed --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20">

        @foreach([
            ['emoji'=>'🏠','title'=>'feat_detail_catalog_title', 'body'=>'feat_detail_catalog_body', 'reverse'=>false],
            ['emoji'=>'📅','title'=>'feat_detail_booking_title', 'body'=>'feat_detail_booking_body', 'reverse'=>true],
            ['emoji'=>'🎯','title'=>'feat_detail_qualify_title', 'body'=>'feat_detail_qualify_body', 'reverse'=>false],
            ['emoji'=>'📸','title'=>'feat_detail_media_title',   'body'=>'feat_detail_media_body',   'reverse'=>true],
            ['emoji'=>'🔔','title'=>'feat_detail_followup_title','body'=>'feat_detail_followup_body','reverse'=>false],
            ['emoji'=>'🤝','title'=>'feat_detail_handoff_title', 'body'=>'feat_detail_handoff_body', 'reverse'=>true],
            ['emoji'=>'📊','title'=>'feat_detail_dashboard_title','body'=>'feat_detail_dashboard_body','reverse'=>false],
        ] as $feat)
        <div class="grid md:grid-cols-2 gap-10 items-center {{ $feat['reverse'] ? 'md:[&>*:first-child]:order-last' : '' }}">
            <div>
                <div class="w-16 h-16 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-3xl mb-5">{{ $feat['emoji'] }}</div>
                <h2 class="text-2xl font-bold text-zinc-900 mb-3">{{ __('marketing.'.$feat['title']) }}</h2>
                <p class="text-zinc-600 text-base leading-relaxed">{{ __('marketing.'.$feat['body']) }}</p>
            </div>
            <div class="bg-zinc-50 rounded-2xl border border-zinc-100 h-52 flex items-center justify-center text-6xl">
                {{ $feat['emoji'] }}
            </div>
        </div>
        @endforeach

    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-brand-700 text-white text-center">
    <div class="max-w-xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-3">{{ $isAr ? 'جاهز تجرب؟' : 'Ready to try it?' }}</h2>
        <p class="text-brand-200 mb-6">{{ $isAr ? 'ابدأ مجاناً بدون بطاقة ائتمان' : 'Start free, no credit card required' }}</p>
        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-brand-800 font-bold hover:bg-brand-50 transition-colors">
            {{ __('marketing.nav_start_free') }}
            <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

</x-layouts.marketing>
