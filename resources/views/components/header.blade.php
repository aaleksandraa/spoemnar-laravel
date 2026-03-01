@php
    $availableLocales = [
        'sr' => __('ui.languages.sr'),
        'hr' => __('ui.languages.hr'),
        'bs' => __('ui.languages.bs'),
        'de' => __('ui.languages.de'),
        'en' => __('ui.languages.en'),
        'it' => __('ui.languages.it'),
    ];
    $supportedLocaleCodes = array_keys($availableLocales);
    $currentLocale = app()->getLocale();
    $route = request()->route();
    $currentRouteName = $route?->getName();
    $routeParams = $route?->parameters() ?? [];
    unset($routeParams['locale']);
    $queryParams = request()->query();
    unset($queryParams['lang']);

    $localizedUrl = static function (string $locale) use ($currentRouteName, $routeParams, $queryParams, $supportedLocaleCodes): string {
        if ($currentRouteName && \Illuminate\Support\Facades\Route::has($currentRouteName)) {
            $url = route($currentRouteName, array_merge($routeParams, ['locale' => $locale]));
        } else {
            $segments = array_values(array_filter(explode('/', trim(request()->path(), '/'))));
            if (isset($segments[0]) && in_array($segments[0], $supportedLocaleCodes, true)) {
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

    // Determine if header should be transparent with white text
    $isTransparent = $transparent ?? false;
    $isAbsolute = $absolute ?? false;
    $positionClass = $isAbsolute ? 'absolute' : 'sticky';
    $headerBgClass = $isTransparent ? 'bg-transparent' : 'bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60';
    $headerBorderClass = $isTransparent ? 'border-white/20' : 'border-border';
    $textClass = $isTransparent ? 'text-white' : 'text-foreground';
    $hoverTextClass = $isTransparent ? 'hover:text-white/80' : 'hover:text-accent';
    $logoTextClass = $isTransparent ? 'text-white' : 'text-primary dark:text-[#ECEFF3]';
    $buttonBorderClass = $isTransparent ? 'border-white/30 text-white hover:bg-white/10' : 'border-border hover:bg-muted';
    $isCompactAuthLocale = in_array($currentLocale, ['de', 'it'], true);
    $desktopLoginVisibilityClass = $isCompactAuthLocale ? 'hidden 2xl:inline-flex' : 'hidden xl:inline-flex';
    $mobileLoginVisibilityClass = $isCompactAuthLocale ? 'hidden' : 'hidden min-[390px]:inline-flex';
    $mobileMenuPanelClass = $isTransparent
        ? 'bg-black/90 border-white/20 text-white'
        : 'bg-card/95 border-border text-foreground';
    $mobileItemHoverClass = $isTransparent ? 'hover:bg-white/10' : 'hover:bg-muted';
    $accountLabel = __('ui.nav.account');
    if ($accountLabel === 'ui.nav.account') {
        $accountLabel = 'Account';
    }
@endphp

<header
    class="border-b {{ $headerBorderClass }} {{ $headerBgClass }} {{ $positionClass }} top-0 left-0 right-0 z-50"
    x-data="{
        mobileMenuOpen: false,
        langOpen: false,
        isDark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            window.toggleDarkMode();
            this.isDark = document.documentElement.classList.contains('dark');
        }
    }"
    @keydown.escape.window="mobileMenuOpen = false; langOpen = false"
>
    <div class="container mx-auto px-4 flex h-16 items-center justify-between">
        <a href="{{ route('home') }}" class="flex items-center space-x-2">
            <svg class="h-7 w-7 shrink-0 {{ $isTransparent ? 'text-white' : '' }}" style="{{ $isTransparent ? '' : 'color: rgb(224, 186, 133);' }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path
                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5 5.5 0 017.5 3 5.98 5.98 0 0112 5.09 5.98 5.98 0 0116.5 3 5.5 5.5 0 0122 8.5c0 3.78-3.4 6.86-8.55 11.54z"
                    stroke="currentColor"
                    stroke-width="2.4"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <span class="text-xl font-sans font-bold tracking-tight {{ $logoTextClass }} hidden sm:block">{{ __('ui.brand') }}</span>
        </a>

        <nav class="hidden md:flex items-center space-x-6" aria-label="{{ __('ui.nav.home') }}">
            <a href="{{ route('home') }}" class="text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.home') }}</a>
            <a href="{{ route('about') }}" class="text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.about') }}</a>
            <a href="{{ route('contact') }}" class="text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.contact') }}</a>
            <a href="{{ route('search.page') }}" class="text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.home.search_tab') }}</a>
            <a href="{{ route('memorial.create') }}" data-auth-nav class="hidden text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.create') }}</a>
            <a href="{{ route('dashboard') }}" data-auth-nav class="hidden text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.dashboard') }}</a>
            <a href="{{ route('admin.panel') }}" data-admin-nav class="hidden text-sm font-medium {{ $textClass }} transition-colors {{ $hoverTextClass }}">{{ __('ui.nav.admin') }}</a>
        </nav>

        <div class="flex items-center space-x-2 md:space-x-4">
            <!-- Dark mode toggle - desktop only -->
            <div class="hidden md:block">
                @include('components.dark-mode-toggle')
            </div>

            <div class="relative hidden md:block">
                <button
                    type="button"
                    @click="langOpen = !langOpen"
                    @click.away="langOpen = false"
                    class="inline-flex items-center gap-2 px-3 h-10 border rounded-lg transition-colors {{ $buttonBorderClass }} {{ $textClass }}"
                    aria-label="{{ __('ui.languages.choose') }}"
                >
                    <span class="text-sm uppercase">{{ $currentLocale }}</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div
                    x-show="langOpen"
                    x-transition
                    class="absolute right-0 mt-2 w-44 bg-card border border-border rounded-lg shadow-elegant py-1"
                    role="menu"
                >
                    @foreach($availableLocales as $code => $label)
                        <a
                            href="{{ $localizedUrl($code) }}"
                            class="block px-4 py-2 text-sm hover:bg-muted {{ $currentLocale === $code ? 'text-accent font-medium' : '' }}"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div id="headerGuestDesktop" class="hidden md:flex items-center gap-2">
                <a href="{{ route('login') }}" class="{{ $desktopLoginVisibilityClass }} items-center justify-center px-4 h-10 rounded-lg border transition-colors text-sm font-medium {{ $buttonBorderClass }} {{ $textClass }}">
                    {{ __('ui.auth.login_title') }}
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 h-10 rounded-lg bg-gradient-accent text-accent-foreground shadow-gold hover:opacity-90 transition-opacity text-sm font-medium">
                    {{ __('ui.auth.register_title') }}
                </a>
            </div>

            <div id="headerAuthDesktop" class="hidden md:flex items-center gap-2">
                <button type="button" data-logout-btn class="inline-flex items-center justify-center px-4 h-10 rounded-lg border transition-colors text-sm font-medium {{ $buttonBorderClass }} {{ $textClass }}">
                    {{ __('ui.nav.logout') }}
                </button>
            </div>

            <button
                type="button"
                @click="mobileMenuOpen = !mobileMenuOpen"
                class="md:hidden p-2.5 rounded-xl border transition-colors {{ $textClass }} {{ $buttonBorderClass }}"
                aria-label="Open menu"
                :aria-expanded="mobileMenuOpen ? 'true' : 'false'"
            >
                <svg x-show="!mobileMenuOpen" x-transition class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileMenuOpen" x-transition class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </div>

    <div x-show="mobileMenuOpen" x-transition.opacity.duration.200ms class="md:hidden fixed inset-0 z-40">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="mobileMenuOpen = false"></div>
        <div class="absolute left-3 right-3 top-[68px] rounded-2xl border shadow-2xl {{ $mobileMenuPanelClass }}">
            <nav
                class="max-h-[calc(100vh-84px)] overflow-y-auto p-4 space-y-4"
                aria-label="Mobile navigation"
                @click="if ($event.target.closest('a')) { mobileMenuOpen = false }"
            >
                <div class="rounded-xl border {{ $headerBorderClass }} p-1">
                    <button
                        @click="toggleTheme()"
                        class="w-full flex items-center justify-between gap-3 px-3 py-3 rounded-lg transition-colors {{ $mobileItemHoverClass }} {{ $textClass }}"
                        aria-label="{{ __('ui.theme.toggle_dark_mode') }}"
                    >
                        <span class="inline-flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $isTransparent ? 'bg-white/15' : 'bg-muted' }}">
                                <svg class="w-4 h-4 hidden dark:block shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                                <svg class="w-4 h-4 block dark:hidden shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                                </svg>
                            </span>
                            <span class="text-sm font-semibold">{{ __('ui.theme.toggle_dark_mode') }}</span>
                        </span>
                        <span class="text-[11px] uppercase tracking-wide opacity-70" x-text="isDark ? 'Dark' : 'Light'"></span>
                    </button>
                </div>

                <div class="space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>{{ __('ui.nav.home') }}</span>
                </a>
                <a href="{{ route('about') }}" class="flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ __('ui.nav.about') }}</span>
                </a>
                <a href="{{ route('contact') }}" class="flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>{{ __('ui.nav.contact') }}</span>
                </a>
                <a href="{{ route('search.page') }}" class="flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span>{{ __('ui.home.search_tab') }}</span>
                </a>
                <a href="{{ route('memorial.create') }}" data-auth-nav-mobile class="hidden flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('ui.nav.create') }}</span>
                </a>
                <a href="{{ route('dashboard') }}" data-auth-nav-mobile class="hidden flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 13a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/>
                    </svg>
                    <span>{{ __('ui.nav.dashboard') }}</span>
                </a>
                <a href="{{ route('admin.panel') }}" data-admin-nav-mobile class="hidden flex items-center gap-3 px-3 py-3 rounded-lg text-base font-medium {{ $textClass }} {{ $hoverTextClass }} {{ $mobileItemHoverClass }} transition-colors">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>{{ __('ui.nav.admin') }}</span>
                </a>
                </div>

                <div id="headerGuestMobileActions" class="pt-4 mt-2 border-t {{ $headerBorderClass }} space-y-2">
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider {{ $textClass }} opacity-60">{{ $accountLabel }}</span>
                </div>
                <a href="{{ route('login') }}" class="{{ $mobileLoginVisibilityClass }} items-center justify-center w-full px-4 h-11 rounded-lg border text-sm font-medium transition-colors {{ $buttonBorderClass }} {{ $textClass }} {{ $mobileItemHoverClass }}">
                    {{ __('ui.auth.login_title') }}
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full px-4 h-11 rounded-lg bg-gradient-accent text-accent-foreground shadow-gold hover:opacity-90 transition-opacity text-sm font-medium">
                    {{ __('ui.auth.register_title') }}
                </a>
                </div>

                <div id="headerAuthMobileActions" class="hidden pt-4 mt-2 border-t {{ $headerBorderClass }} space-y-2">
                <div class="px-3 mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider {{ $textClass }} opacity-60">{{ $accountLabel }}</span>
                </div>
                <button type="button" data-logout-btn class="w-full inline-flex items-center justify-center px-4 h-11 rounded-lg border text-sm font-medium transition-colors {{ $buttonBorderClass }} {{ $textClass }} {{ $mobileItemHoverClass }}">
                    {{ __('ui.nav.logout') }}
                </button>
                </div>

                <div class="pt-4 mt-2 border-t {{ $headerBorderClass }}">
                <div class="px-3 mb-3 flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider {{ $textClass }} opacity-60">{{ __('ui.languages.choose') }}</span>
                    <span class="text-[11px] {{ $textClass }} opacity-60 uppercase tracking-wide">{{ strtoupper($currentLocale) }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($availableLocales as $code => $label)
                        <a
                            href="{{ $localizedUrl($code) }}"
                            class="flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg border transition-all {{ $currentLocale === $code ? ($isTransparent ? 'bg-white/20 border-white text-white font-semibold' : 'bg-accent/10 border-accent text-accent font-semibold') : ($buttonBorderClass . ' ' . $textClass . ' ' . $mobileItemHoverClass) }}"
                        >
                            <span class="inline-flex items-center gap-2 min-w-0">
                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[10px] font-bold uppercase {{ $currentLocale === $code ? ($isTransparent ? 'bg-white/20' : 'bg-accent/20') : ($isTransparent ? 'bg-white/10' : 'bg-muted') }}">{{ $code }}</span>
                                <span class="text-xs truncate">{{ $label }}</span>
                            </span>
                            @if($currentLocale === $code)
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3.25-3.25a1 1 0 111.414-1.42l2.543 2.544 6.543-6.544a1 1 0 011.415 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </a>
                    @endforeach
                </div>
                </div>
            </nav>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const token = localStorage.getItem('auth_token') || '';
        const homeUrl = @json(route('home'));

        const guestDesktop = document.getElementById('headerGuestDesktop');
        const authDesktop = document.getElementById('headerAuthDesktop');
        const guestMobile = document.getElementById('headerGuestMobileActions');
        const authMobile = document.getElementById('headerAuthMobileActions');
        const authNavDesktop = Array.from(document.querySelectorAll('[data-auth-nav]'));
        const adminNavDesktop = Array.from(document.querySelectorAll('[data-admin-nav]'));
        const authNavMobile = Array.from(document.querySelectorAll('[data-auth-nav-mobile]'));
        const adminNavMobile = Array.from(document.querySelectorAll('[data-admin-nav-mobile]'));
        const logoutButtons = Array.from(document.querySelectorAll('[data-logout-btn]'));

        function setVisibility(elements, visible, displayClass = 'inline-flex') {
            elements.forEach((element) => {
                if (!element) {
                    return;
                }
                if (visible) {
                    element.classList.remove('hidden');
                    if (displayClass) {
                        element.classList.add(displayClass);
                    }
                } else {
                    element.classList.add('hidden');
                    if (displayClass) {
                        element.classList.remove(displayClass);
                    }
                }
            });
        }

        function isAdminUser(user) {
            const relationAdmin = Array.isArray(user?.roles)
                ? user.roles.some((role) => (typeof role === 'string' ? role === 'admin' : role?.role === 'admin'))
                : false;

            return relationAdmin || user?.role === 'admin';
        }

        function isAuthStatus(status) {
            return Number(status) === 401 || Number(status) === 403;
        }

        function isAuthError(error) {
            return isAuthStatus(error?.status);
        }

        async function fetchMe() {
            const response = await fetch('/api/v1/me', {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token}`,
                },
            });

            if (!response.ok) {
                const error = new Error(response.status === 401 || response.status === 403 ? 'Unauthorized' : 'Failed to load user.');
                error.status = response.status;
                throw error;
            }

            const payload = await response.json();
            return payload?.user || null;
        }

        async function logout() {
            try {
                if (token) {
                    await fetch('/api/v1/logout', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                }
            } catch (_error) {
                // Ignore API logout errors and clear client session anyway.
            } finally {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
                window.location.href = homeUrl;
            }
        }

        logoutButtons.forEach((button) => {
            button.addEventListener('click', logout);
        });

        if (!token) {
            setVisibility([guestDesktop], true, 'md:flex');
            setVisibility([guestMobile], true);
            return;
        }

        fetchMe().then((user) => {
            if (!user) {
                throw new Error('Unauthorized');
            }

            setVisibility([authDesktop], true, 'md:flex');
            setVisibility([authMobile], true);
            setVisibility(authNavDesktop, true, 'inline');
            setVisibility(authNavMobile, true, 'block');
            setVisibility([guestDesktop], false, 'md:flex');
            setVisibility([guestMobile], false);

            const admin = isAdminUser(user);
            if (admin) {
                setVisibility(adminNavDesktop, true, 'inline');
                setVisibility(adminNavMobile, true, 'block');
            }
        }).catch((error) => {
            if (isAuthError(error)) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user');
            }
            setVisibility([guestDesktop], true, 'md:flex');
            setVisibility([guestMobile], true);
        });
    });
</script>
