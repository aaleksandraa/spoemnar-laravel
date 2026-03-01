<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $localeToHreflang = [
            'sr' => 'sr-RS',
            'hr' => 'hr-HR',
            'bs' => 'bs-BA',
            'de' => 'de-DE',
            'en' => 'en-US',
            'it' => 'it-IT',
        ];
        $localeToRegion = [
            'sr' => 'RS',
            'hr' => 'HR',
            'bs' => 'BA',
            'de' => 'DE',
            'en' => 'US',
            'it' => 'IT',
        ];
        $currentLocale = app()->getLocale();
        $currentHreflang = $localeToHreflang[$currentLocale] ?? 'bs-BA';
        $currentRegion = $localeToRegion[$currentLocale] ?? 'BA';

        $route = request()->route();
        $currentRouteName = $route?->getName();
        $routeParams = $route?->parameters() ?? [];
        unset($routeParams['locale']);
        $queryParams = request()->except('lang');
        $noIndexRoutes = ['login', 'register', 'dashboard', 'memorial.create', 'memorial.edit', 'admin.panel', 'password.forgot', 'password.reset.form'];
        $robotsMetaContent = in_array($currentRouteName, $noIndexRoutes, true)
            ? 'noindex,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1'
            : 'index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1';

        $localizedUrl = function (string $locale) use ($currentRouteName, $routeParams, $queryParams, $localeToHreflang): string {
            if ($currentRouteName && \Illuminate\Support\Facades\Route::has($currentRouteName)) {
                $url = route($currentRouteName, array_merge($routeParams, ['locale' => $locale]));
            } else {
                $segments = array_values(array_filter(explode('/', trim(request()->path(), '/'))));
                if (isset($segments[0]) && array_key_exists($segments[0], $localeToHreflang)) {
                    array_shift($segments);
                }

                $path = '/'.$locale;
                if ($segments !== []) {
                    $path .= '/'.implode('/', $segments);
                }

                $url = url($path);
            }

            if ($queryParams !== []) {
                $url .= '?'.http_build_query($queryParams);
            }

            return $url;
        };

        $canonicalUrl = $localizedUrl($currentLocale);
    @endphp

    <title>{{ config('app.name', 'Spomenar') }} - @yield('title', __('ui.meta.default_title'))</title>

    {{-- SEO Meta Tags Stack (pages can push custom meta tags here) --}}
    @stack('seo-meta-tags')

    {{-- Default meta tags (used if no custom meta tags are pushed) --}}
    @if(!View::hasSection('seo-meta-tags'))
    <meta name="description" content="@yield('meta_description', __('ui.seo.default_description'))">
    @endif

    <meta name="keywords" content="@yield('meta_keywords', __('ui.seo.default_keywords'))">
    <meta name="robots" content="{{ $robotsMetaContent }}">
    <meta name="geo.region" content="{{ $currentRegion }}">
    <meta name="theme-color" content="#313947">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    @foreach($localeToHreflang as $code => $hreflang)
        <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $localizedUrl($code) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $localizedUrl('bs') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ config('app.name', 'Spomenar') }}">
    <meta property="og:title" content="@yield('og_title', config('app.name', 'Spomenar') . ' - ' . __('ui.meta.default_title'))">
    <meta property="og:description" content="@yield('og_description', __('ui.seo.default_description'))">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $currentHreflang) }}">
    @foreach($localeToHreflang as $code => $hreflang)
        @if($code !== $currentLocale)
            <meta property="og:locale:alternate" content="{{ str_replace('-', '_', $hreflang) }}">
        @endif
    @endforeach

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    {{-- Resource Hints for Analytics --}}
    <link rel="preconnect" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://www.google-analytics.com">
    <link rel="dns-prefetch" href="https://stats.g.doubleclick.net">

    {{-- Google Search Console Verification --}}
    @if(config('seo.search_console.verification'))
    <meta name="google-site-verification" content="{{ config('seo.search_console.verification') }}">
    @endif

    {{-- Data Layer Initialization (must be before GTM) --}}
    <x-analytics.data-layer-init />

    {{-- GTM Head Script --}}
    <x-analytics.gtm-head />

    <script>
        (function () {
            try {
                var root = document.documentElement;
                var storedTheme = localStorage.getItem('darkMode');
                var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                root.classList.remove('dark', 'light');

                if (storedTheme === 'dark') {
                    root.classList.add('dark');
                    return;
                }

                if (storedTheme === 'light') {
                    root.classList.add('light');
                    return;
                }

                root.classList.add(systemPrefersDark ? 'dark' : 'light');
            } catch (_error) {
                // Ignore storage/access errors and keep default theme.
            }
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="{{ request()->routeIs('home') ? '' : 'min-h-screen flex flex-col' }}">
    {{-- GTM Body NoScript (must be immediately after opening body tag) --}}
    <x-analytics.gtm-body />

    {{-- Cookie Consent Banner --}}
    <x-analytics.cookie-banner />

    @include('components.skip-link')

    @include('components.header', [
        'transparent' => request()->routeIs('home'),
        'absolute' => request()->routeIs('home')
    ])

    <main id="main-content" class="{{ request()->routeIs('home') ? '' : 'flex-1' }}" tabindex="-1">
        @yield('content')
    </main>

    @include('components.footer')

    @stack('scripts')
</body>
</html>
