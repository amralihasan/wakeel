@php $locale = app()->getLocale(); @endphp
<footer class="bg-zinc-900 text-zinc-400">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-10">

            {{-- Brand --}}
            <div class="md:col-span-1">
                <a href="{{ route('marketing.home') }}" class="inline-flex items-center gap-2 mb-4">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-brand-600 text-white font-bold text-sm">
                        و
                    </span>
                    <span class="font-bold text-lg text-white leading-none">
                        {{ $locale === 'ar' ? 'وكيل' : 'Wakeel' }}
                    </span>
                </a>
                <p class="text-sm leading-relaxed text-zinc-500 max-w-xs">
                    {{ __('marketing.footer_tagline') }}
                </p>
                <p class="mt-4 text-xs text-zinc-600">{{ __('marketing.footer_made_in') }}</p>
            </div>

            {{-- Product --}}
            <div>
                <h3 class="text-sm font-semibold text-white mb-4">{{ __('marketing.footer_product') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('marketing.features') }}" class="hover:text-white transition-colors">{{ __('marketing.nav_features') }}</a></li>
                    <li><a href="{{ route('marketing.how-it-works') }}" class="hover:text-white transition-colors">{{ __('marketing.nav_how_it_works') }}</a></li>
                    <li><a href="{{ route('marketing.pricing') }}" class="hover:text-white transition-colors">{{ __('marketing.nav_pricing') }}</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h3 class="text-sm font-semibold text-white mb-4">{{ __('marketing.footer_company_col') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('marketing.about') }}" class="hover:text-white transition-colors">{{ __('marketing.footer_about') }}</a></li>
                    <li><a href="{{ route('marketing.contact') }}" class="hover:text-white transition-colors">{{ __('marketing.footer_contact') }}</a></li>
                </ul>
            </div>

            {{-- Legal + switcher --}}
            <div>
                <h3 class="text-sm font-semibold text-white mb-4">{{ __('marketing.footer_legal') }}</h3>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('marketing.privacy') }}" class="hover:text-white transition-colors">{{ __('marketing.footer_privacy') }}</a></li>
                    <li><a href="{{ route('marketing.terms') }}" class="hover:text-white transition-colors">{{ __('marketing.footer_terms') }}</a></li>
                </ul>
                <div class="mt-6">
                    <a
                        href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}"
                        class="inline-flex items-center gap-1.5 text-xs border border-zinc-700 rounded-md px-3 py-1.5 text-zinc-400 hover:text-white hover:border-zinc-500 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                        {{ __('marketing.switch_lang') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-8 border-t border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-zinc-600">
            <span>{{ str_replace(':year', date('Y'), __('marketing.footer_copyright')) }}</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('marketing.privacy') }}" class="hover:text-zinc-400 transition-colors">{{ __('marketing.footer_privacy') }}</a>
                <a href="{{ route('marketing.terms') }}" class="hover:text-zinc-400 transition-colors">{{ __('marketing.footer_terms') }}</a>
            </div>
        </div>
    </div>
</footer>
