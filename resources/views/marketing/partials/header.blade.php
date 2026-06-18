@php $locale = app()->getLocale(); @endphp
<header
    x-data="{ open: false }"
    class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-zinc-100 shadow-xs"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('marketing.home') }}" class="flex items-center gap-2 shrink-0">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-brand-700 text-white font-bold text-sm">
                    و
                </span>
                <span class="font-bold text-lg text-zinc-900 leading-none">
                    {{ $locale === 'ar' ? 'وكيل' : 'Wakeel' }}
                </span>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-zinc-600">
                <a href="{{ route('marketing.features') }}" class="hover:text-brand-700 transition-colors">{{ __('marketing.nav_features') }}</a>
                <a href="{{ route('marketing.how-it-works') }}" class="hover:text-brand-700 transition-colors">{{ __('marketing.nav_how_it_works') }}</a>
                <a href="{{ route('marketing.pricing') }}" class="hover:text-brand-700 transition-colors">{{ __('marketing.nav_pricing') }}</a>
                <a href="{{ route('marketing.about') }}" class="hover:text-brand-700 transition-colors">{{ __('marketing.nav_about') }}</a>
                <a href="{{ route('marketing.contact') }}" class="hover:text-brand-700 transition-colors">{{ __('marketing.nav_contact') }}</a>
            </nav>

            {{-- Right actions --}}
            <div class="hidden md:flex items-center gap-3">
                {{-- Lang switcher --}}
                <a
                    href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}"
                    class="text-xs font-semibold text-zinc-500 hover:text-zinc-900 border border-zinc-200 rounded-md px-2.5 py-1.5 transition-colors"
                    aria-label="{{ __('marketing.switch_lang_label') }}"
                >
                    {{ __('marketing.switch_lang') }}
                </a>

                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-900 transition-colors">
                        {{ __('marketing.nav_dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-700 hover:text-zinc-900 transition-colors">
                        {{ __('marketing.nav_login') }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex items-center px-4 py-2 rounded-lg bg-brand-700 text-white text-sm font-semibold hover:bg-brand-800 transition-colors shadow-xs"
                    >
                        {{ __('marketing.nav_start_free') }}
                    </a>
                @endauth
            </div>

            {{-- Mobile menu button --}}
            <button
                @click="open = !open"
                class="md:hidden p-2 rounded-md text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 transition-colors"
                :aria-expanded="open"
                :aria-label="open ? '{{ __('marketing.close_menu') }}' : '{{ __('marketing.open_menu') }}'"
            >
                <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden border-t border-zinc-100 bg-white"
    >
        <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            <a href="{{ route('marketing.features') }}" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">{{ __('marketing.nav_features') }}</a>
            <a href="{{ route('marketing.how-it-works') }}" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">{{ __('marketing.nav_how_it_works') }}</a>
            <a href="{{ route('marketing.pricing') }}" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">{{ __('marketing.nav_pricing') }}</a>
            <a href="{{ route('marketing.about') }}" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">{{ __('marketing.nav_about') }}</a>
            <a href="{{ route('marketing.contact') }}" @click="open=false" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50 transition-colors">{{ __('marketing.nav_contact') }}</a>

            <div class="pt-3 border-t border-zinc-100 flex flex-col gap-2">
                <a
                    href="{{ route('locale.switch', $locale === 'ar' ? 'en' : 'ar') }}"
                    class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-500 hover:bg-zinc-50 transition-colors"
                >
                    {{ __('marketing.switch_lang') }}
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('marketing.nav_dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2.5 rounded-lg text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('marketing.nav_login') }}</a>
                    <a href="{{ route('register') }}" class="block px-3 py-2.5 rounded-lg bg-brand-700 text-white text-sm font-semibold text-center hover:bg-brand-800 transition-colors">{{ __('marketing.nav_start_free') }}</a>
                @endauth
            </div>
        </div>
    </div>
</header>
