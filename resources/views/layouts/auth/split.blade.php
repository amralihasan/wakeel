@php
    $locale = app()->getLocale();
    $isAr   = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" dir="{{ $dir ?? ($isAr ? 'rtl' : 'ltr') }}">
    <head>
        @include('partials.head')
        @fonts

        {{-- Arabic font class matching landing page --}}
        @if($isAr)
        <style>
            :root { --font-page: var(--font-arabic, 'Cairo', sans-serif); }
            body, button, input, textarea, select { font-family: var(--font-page) !important; }
        </style>
        @endif
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        {{-- Language Selector in the top corner --}}
        <div class="absolute top-4 end-4 z-50">
            @if($isAr)
                <a href="{{ route('locale.switch', 'en') }}" wire:navigate class="text-sm font-semibold hover:underline text-zinc-600 dark:text-zinc-400">
                    English
                </a>
            @else
                <a href="{{ route('locale.switch', 'ar') }}" wire:navigate class="text-sm font-semibold hover:underline text-zinc-600 dark:text-zinc-400">
                    العربية
                </a>
            @endif
        </div>

        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            
            {{-- Left Side Panel (Marketing Brand Theme) --}}
            <div class="relative hidden h-full flex-col p-10 text-white lg:flex bg-gradient-to-br from-brand-950 via-brand-900 to-zinc-900 overflow-hidden border-e border-brand-900/40">
                {{-- Subtle grid overlay --}}
                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=%2260%22 height=%2260%22 viewBox=%220 0 60 60%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cg fill=%22none%22 fill-rule=%22evenodd%22%3E%3Cg fill=%22%23ffffff%22 fill-opacity=%220.03%22%3E%3Cpath d=%22M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%22/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
                
                {{-- Branding --}}
                <div class="relative z-20 flex items-center justify-between">
                    <a href="{{ route('marketing.home') }}" class="flex items-center gap-2" wire:navigate>
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-wa-green text-zinc-900 font-bold text-sm">
                            و
                        </span>
                        <span class="font-bold text-xl text-white leading-none">
                            {{ $isAr ? 'وكيل' : 'Wakeel' }}
                        </span>
                    </a>
                </div>

                {{-- Mockup & Features --}}
                <div class="relative z-20 my-auto flex flex-col items-center">
                    {{-- Glowing Trust Badge --}}
                    <div class="inline-flex items-center gap-2 bg-brand-800/40 border border-brand-600/30 rounded-full px-4 py-1.5 text-xs text-brand-200 mb-6 backdrop-blur-xs">
                        <span class="w-2 h-2 rounded-full bg-wa-green animate-pulse"></span>
                        {{ __('marketing.hero_trust') }}
                    </div>

                    {{-- WhatsApp chat mockup --}}
                    <div class="w-72 bg-[#ECE5DD] rounded-2xl shadow-2xl overflow-hidden border border-white/10 mb-8">
                        {{-- Chat header --}}
                        <div class="bg-[#075E54] px-4 py-3 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-brand-400 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">و</div>
                            <div>
                                <p class="text-white font-semibold text-sm leading-none mb-0.5">{{ __('marketing.hero_chat_header') }}</p>
                                <p class="text-[#B2DFDB] text-xs">{{ __('marketing.hero_chat_online') }}</p>
                            </div>
                        </div>
                        {{-- Chat messages --}}
                        <div class="p-3 space-y-2 min-h-[220px]">
                            {{-- Customer message --}}
                            <div class="flex {{ $isAr ? 'justify-start' : 'justify-end' }}">
                                <div class="bg-[#DCF8C6] rounded-xl rounded-{{ $isAr ? 'ss' : 'es' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                    <p class="text-[#303030] text-[11px] leading-normal">{{ __('marketing.chat_customer_1') }}</p>
                                </div>
                            </div>
                            {{-- Bot message --}}
                            <div class="flex {{ $isAr ? 'justify-end' : 'justify-start' }} items-end gap-1">
                                <div class="w-5 h-5 rounded-full bg-brand-600 flex items-center justify-center text-white text-[9px] font-bold flex-shrink-0 mb-1">و</div>
                                <div class="bg-white rounded-xl rounded-{{ $isAr ? 'es' : 'ss' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                    <p class="text-[#303030] text-[11px] leading-normal">{{ __('marketing.chat_bot_1') }}</p>
                                </div>
                            </div>
                            {{-- Customer --}}
                            <div class="flex {{ $isAr ? 'justify-start' : 'justify-end' }}">
                                <div class="bg-[#DCF8C6] rounded-xl rounded-{{ $isAr ? 'ss' : 'es' }}-sm px-3 py-2 max-w-[85%] shadow-xs">
                                    <p class="text-[#303030] text-[11px] leading-normal">{{ __('marketing.chat_customer_2') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tagline --}}
                <div class="relative z-20 mt-auto">
                    <blockquote class="space-y-2 border-s-2 border-wa-green pl-4 rtl:pr-4 rtl:pl-0">
                        <flux:heading size="lg" class="text-white font-medium">
                            {{ $isAr ? 'تأهيل العملاء وحجز المعاينات تلقائيًا عبر واتساب' : 'Qualify leads and book property viewings automatically on WhatsApp' }}
                        </flux:heading>
                        <footer>
                            <flux:heading size="sm" class="text-brand-300">
                                {{ $isAr ? 'وفر وقت فريق المبيعات بنسبة 80%' : 'Save your sales team 80% of their time' }}
                            </flux:heading>
                        </footer>
                    </blockquote>
                </div>
            </div>

            {{-- Right Side Form Wrapper --}}
            <div class="w-full lg:p-8 flex items-center justify-center min-h-screen">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[380px]">
                    {{-- Mobile Logo --}}
                    <div class="lg:hidden flex justify-center mb-2">
                        <a href="{{ route('marketing.home') }}" class="flex items-center gap-2" wire:navigate>
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-brand-700 text-white font-bold text-base">
                                و
                            </span>
                            <span class="font-bold text-xl text-zinc-950 dark:text-white leading-none">
                                {{ $isAr ? 'وكيل' : 'Wakeel' }}
                            </span>
                        </a>
                    </div>
                    
                    {{-- Content Slot --}}
                    <div class="px-4 sm:px-0">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
