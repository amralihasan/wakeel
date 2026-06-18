@php
    $metaTitle = __('marketing.meta_terms_title');
    $metaDesc  = __('marketing.meta_terms_desc');
@endphp

<x-layouts.marketing :metaTitle="$metaTitle" :metaDesc="$metaDesc">

<section class="bg-gradient-to-br from-brand-950 to-zinc-900 text-white py-16 text-center">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl sm:text-4xl font-bold mb-3">{{ __('marketing.terms_title') }}</h1>
        <p class="text-zinc-400 text-sm">{{ __('marketing.terms_last_updated') }}: {{ date('Y-m-d') }}</p>
    </div>
</section>

<section class="py-16 bg-white">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="prose prose-zinc max-w-none">
            <p class="text-zinc-600 text-base leading-relaxed mb-8">{{ __('marketing.terms_intro') }}</p>

            @foreach([1,2,3,4,5,6] as $i)
            <div class="mb-8">
                <h2 class="text-xl font-bold text-zinc-900 mb-3">{{ __('marketing.terms_section_'.$i.'_title') }}</h2>
                <p class="text-zinc-600 leading-relaxed">{{ __('marketing.terms_section_'.$i.'_body') }}</p>
            </div>
            @endforeach

            <div class="mt-10 p-5 bg-brand-50 border border-brand-100 rounded-xl">
                <p class="text-brand-800 text-sm">
                    {{ __('marketing.terms_contact') }}:
                    <a href="{{ route('marketing.contact') }}" class="font-semibold underline hover:no-underline">{{ app()->getLocale() === 'ar' ? 'صفحة التواصل' : 'contact page' }}</a>
                </p>
            </div>
        </div>
    </div>
</section>

</x-layouts.marketing>
