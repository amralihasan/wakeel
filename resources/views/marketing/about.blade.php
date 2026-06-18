@php
    $metaTitle = __('marketing.meta_about_title');
    $metaDesc  = __('marketing.meta_about_desc');
    $isAr      = app()->getLocale() === 'ar';
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

{{-- Hero --}}
<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-20 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-4">{{ __('marketing.about_title') }}</h1>
        <p class="text-brand-200/80 text-lg">{{ __('marketing.about_subtitle') }}</p>
    </div>
</section>

{{-- Mission --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-12 items-start">
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 mb-4">{{ __('marketing.about_mission_title') }}</h2>
                <p class="text-zinc-600 leading-relaxed">{{ __('marketing.about_mission_body') }}</p>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-zinc-900 mb-4">{{ __('marketing.about_who_title') }}</h2>
                <p class="text-zinc-600 leading-relaxed">{{ __('marketing.about_who_body') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- Story --}}
<section class="py-16 bg-zinc-50">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-zinc-900 mb-6">{{ __('marketing.about_story_title') }}</h2>
        <p class="text-zinc-600 leading-relaxed text-lg">{{ __('marketing.about_story_body') }}</p>
    </div>
</section>

{{-- Values --}}
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 text-center mb-12">{{ __('marketing.about_values_title') }}</h2>
        <div class="grid sm:grid-cols-3 gap-8">
            @foreach([
                ['emoji'=>'✨','title'=>'about_value_1_title','desc'=>'about_value_1_desc'],
                ['emoji'=>'🔒','title'=>'about_value_2_title','desc'=>'about_value_2_desc'],
                ['emoji'=>'📈','title'=>'about_value_3_title','desc'=>'about_value_3_desc'],
            ] as $val)
            <div class="text-center p-6 rounded-2xl border border-zinc-100 bg-zinc-50">
                <div class="text-4xl mb-4">{{ $val['emoji'] }}</div>
                <h3 class="font-bold text-zinc-900 mb-2">{{ __('marketing.'.$val['title']) }}</h3>
                <p class="text-zinc-500 text-sm leading-relaxed">{{ __('marketing.'.$val['desc']) }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-16 bg-brand-700 text-white text-center">
    <div class="max-w-xl mx-auto px-4">
        <h2 class="text-2xl font-bold mb-6">{{ $isAr ? 'تعرّف على وكيل بنفسك' : 'Experience Wakeel for yourself' }}</h2>
        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-brand-800 font-bold hover:bg-brand-50 transition-colors">
            {{ __('marketing.nav_start_free') }}
            <svg class="w-4 h-4 {{ $isAr ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</section>

</x-layouts.marketing>
