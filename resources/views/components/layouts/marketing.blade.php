<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ $dir ?? (app()->getLocale() === 'ar' ? 'rtl' : 'ltr') }}"
    class="scroll-smooth"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle ?? __('marketing.meta_home_title') }}</title>
    <meta name="description" content="{{ $metaDesc ?? __('marketing.meta_home_desc') }}">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $metaTitle ?? __('marketing.meta_home_title') }}">
    <meta property="og:description" content="{{ $metaDesc ?? __('marketing.meta_home_desc') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ __('marketing.site_name') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle ?? __('marketing.meta_home_title') }}">
    <meta name="twitter:description" content="{{ $metaDesc ?? __('marketing.meta_home_desc') }}">

    {{-- hreflang --}}
    <link rel="alternate" hreflang="ar" href="{{ str_replace('/en/', '/ar/', url()->current()) }}">
    <link rel="alternate" hreflang="en" href="{{ str_replace('/ar/', '/en/', url()->current()) }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Arabic font class --}}
    @if(app()->getLocale() === 'ar')
    <style>
        :root { --font-page: var(--font-arabic, 'Cairo', sans-serif); }
        body { font-family: var(--font-page); }
    </style>
    @endif

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-white text-zinc-900 antialiased">

    {{-- Sticky header --}}
    @include('marketing.partials.header')

    {{-- Page content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @include('marketing.partials.footer')

</body>
</html>
