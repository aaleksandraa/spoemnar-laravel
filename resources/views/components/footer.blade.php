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
@endphp

<footer class="border-t border-border bg-card mt-auto">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-7 w-7 shrink-0" style="color: rgb(224, 186, 133);" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path
                            d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5A5.5 5.5 0 017.5 3 5.98 5.98 0 0112 5.09 5.98 5.98 0 0116.5 3 5.5 5.5 0 0122 8.5c0 3.78-3.4 6.86-8.55 11.54z"
                            stroke="currentColor"
                            stroke-width="2.4"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <h3 class="text-2xl font-sans font-semibold tracking-tight text-primary">{{ __('ui.brand') }}</h3>
                </div>
                <p class="text-muted-foreground">
                    {{ __('ui.footer.tagline') }}
                </p>
            </div>

            <div>
                <h4 class="font-semibold mb-4">{{ __('ui.footer.links') }}</h4>
                <nav class="flex flex-col space-y-2">
                    <a href="{{ route('home') }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.nav.home') }}</a>
                    <a href="{{ route('about') }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.nav.about') }}</a>
                    <a href="{{ route('contact') }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.nav.contact') }}</a>
                </nav>
            </div>

            <div>
                <h4 class="font-semibold mb-4">{{ __('ui.footer.legal') }}</h4>
                <nav class="flex flex-col space-y-2">
                    <a href="{{ route('privacy', ['locale' => $currentLocale]) }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.footer.privacy') }}</a>
                    <a href="{{ route('terms', ['locale' => $currentLocale]) }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.footer.terms') }}</a>
                    <a href="{{ route('cookie.settings', ['locale' => $currentLocale]) }}" class="text-sm text-muted-foreground hover:text-accent transition-colors">{{ __('ui.footer.cookie_settings') }}</a>
                </nav>
            </div>
        </div>

        <div class="mt-8 pt-6 border-t border-border flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ __('ui.languages.label') }}:</span>
                <select
                    class="h-9 px-3 rounded-md border border-border bg-background text-sm"
                    onchange="window.location.href=this.options[this.selectedIndex].dataset.url"
                    aria-label="{{ __('ui.languages.choose') }}"
                >
                    @foreach($availableLocales as $code => $label)
                        <option value="{{ $code }}" data-url="{{ $localizedUrl($code) }}" @selected($currentLocale === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-sm text-muted-foreground">
                &copy; {{ date('Y') }} {{ __('ui.brand') }}. {{ __('ui.footer.rights') }}
            </p>
        </div>
    </div>
</footer>
