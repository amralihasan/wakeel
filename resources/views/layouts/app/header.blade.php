<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" @if(app()->getLocale() === 'ar') dir="rtl" @endif>
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chat-bubble-left-right" :href="route('dashboard.conversations')" :current="request()->routeIs('dashboard.conversations')" wire:navigate>
                    {{ __('المحادثات') }}
                </flux:navbar.item>
                <flux:navbar.item icon="users" :href="route('dashboard.leads')" :current="request()->routeIs('dashboard.leads*')" wire:navigate>
                    {{ __('العملاء المهتمين') }}
                </flux:navbar.item>
                <flux:navbar.item icon="calendar-days" :href="route('dashboard.visits')" :current="request()->routeIs('dashboard.visits')" wire:navigate>
                    {{ __('الزيارات') }}
                </flux:navbar.item>
                <flux:navbar.item icon="building-office-2" :href="route('units.index')" :current="request()->routeIs('units.*')" wire:navigate>
                    {{ __('الوحدات العقارية') }}
                </flux:navbar.item>
                <flux:navbar.item icon="cog-6-tooth" :href="route('bot-settings.index')" :current="request()->routeIs('bot-settings.*')" wire:navigate>
                    {{ __('إعدادات البوت') }}
                </flux:navbar.item>
                <flux:navbar.item icon="chart-bar" :href="route('dashboard.analytics')" :current="request()->routeIs('dashboard.analytics')" wire:navigate>
                    {{ __('التحليلات') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('dashboard.conversations')" :current="request()->routeIs('dashboard.conversations')" wire:navigate>
                        {{ __('المحادثات') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('dashboard.leads')" :current="request()->routeIs('dashboard.leads*')" wire:navigate>
                        {{ __('العملاء المهتمين') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar-days" :href="route('dashboard.visits')" :current="request()->routeIs('dashboard.visits')" wire:navigate>
                        {{ __('الزيارات') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('units.index')" :current="request()->routeIs('units.*')" wire:navigate>
                        {{ __('الوحدات العقارية') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('bot-settings.index')" :current="request()->routeIs('bot-settings.*')" wire:navigate>
                        {{ __('إعدادات البوت') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('dashboard.analytics')" :current="request()->routeIs('dashboard.analytics')" wire:navigate>
                        {{ __('التحليلات') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
