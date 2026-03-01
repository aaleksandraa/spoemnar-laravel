<?php

use App\Http\Controllers\ContactController;
use App\Models\Country;
use App\Models\HeroSettings;
use App\Models\Memorial;
use App\Models\Place;
use App\Support\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

$localePattern = implode('|', LocaleResolver::supportedLocales());

$detectLocale = static function (Request $request): string {
    return LocaleResolver::detectFromRequest($request);
};

$renderHome = static function (Request $request) {
    $heroSettings = HeroSettings::get();
    $searchQuery = '';

    $recentMemorials = Memorial::where('is_public', true)
        ->orderBy('created_at', 'desc')
        ->limit(6)
        ->get();

    $searchResults = collect();

    return view('home', compact('heroSettings', 'recentMemorials', 'searchResults', 'searchQuery'));
};

$renderSearch = static function (Request $request) {
    // Validate search query length to prevent DoS attacks
    $request->validate([
        'q' => ['nullable', 'string', 'max:255'],
    ]);

    $allowedSorts = ['newest', 'oldest', 'name_asc', 'name_desc', 'death_desc'];
    $maxYear = (int) now()->year;

    $normalizeYear = static function (mixed $value, int $maxYear): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $year = (int) $value;
        if ($year < 1800 || $year > $maxYear) {
            return null;
        }

        return $year;
    };

    $normalizePositiveInt = static function (mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    };

    $filters = [
        'q' => trim((string) $request->query('q', '')),
        'birth_country_id' => $normalizePositiveInt($request->query('birth_country_id')),
        'birth_place_id' => $normalizePositiveInt($request->query('birth_place_id')),
        'death_country_id' => $normalizePositiveInt($request->query('death_country_id')),
        'death_place_id' => $normalizePositiveInt($request->query('death_place_id')),
        'birth_year_from' => $normalizeYear($request->query('birth_year_from'), $maxYear),
        'birth_year_to' => $normalizeYear($request->query('birth_year_to'), $maxYear),
        'death_year_from' => $normalizeYear($request->query('death_year_from'), $maxYear),
        'death_year_to' => $normalizeYear($request->query('death_year_to'), $maxYear),
        'has_profile_image' => $request->boolean('has_profile_image'),
        'has_gallery' => $request->boolean('has_gallery'),
        'has_video' => $request->boolean('has_video'),
        'sort' => in_array((string) $request->query('sort', 'newest'), $allowedSorts, true)
            ? (string) $request->query('sort', 'newest')
            : 'newest',
    ];

    if ($filters['birth_year_from'] !== null && $filters['birth_year_to'] !== null && $filters['birth_year_from'] > $filters['birth_year_to']) {
        [$filters['birth_year_from'], $filters['birth_year_to']] = [$filters['birth_year_to'], $filters['birth_year_from']];
    }

    if ($filters['death_year_from'] !== null && $filters['death_year_to'] !== null && $filters['death_year_from'] > $filters['death_year_to']) {
        [$filters['death_year_from'], $filters['death_year_to']] = [$filters['death_year_to'], $filters['death_year_from']];
    }

    $query = Memorial::query()
        ->where('is_public', true)
        ->withCount(['images', 'videos']);

    if ($filters['q'] !== '') {
        $searchTerm = $filters['q'];
        $query->where(function ($innerQuery) use ($searchTerm) {
            $innerQuery->where('first_name', 'like', "%{$searchTerm}%")
                ->orWhere('last_name', 'like', "%{$searchTerm}%")
                ->orWhere('birth_place', 'like', "%{$searchTerm}%")
                ->orWhere('death_place', 'like', "%{$searchTerm}%")
                ->orWhere('biography', 'like', "%{$searchTerm}%")
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$searchTerm}%"]);
        });
    }

    if ($filters['birth_country_id'] !== null) {
        $query->where('birth_country_id', $filters['birth_country_id']);
    }

    if ($filters['birth_place_id'] !== null) {
        $query->where('birth_place_id', $filters['birth_place_id']);
    }

    if ($filters['death_country_id'] !== null) {
        $query->where('death_country_id', $filters['death_country_id']);
    }

    if ($filters['death_place_id'] !== null) {
        $query->where('death_place_id', $filters['death_place_id']);
    }

    if ($filters['birth_year_from'] !== null) {
        $query->whereYear('birth_date', '>=', $filters['birth_year_from']);
    }

    if ($filters['birth_year_to'] !== null) {
        $query->whereYear('birth_date', '<=', $filters['birth_year_to']);
    }

    if ($filters['death_year_from'] !== null) {
        $query->whereYear('death_date', '>=', $filters['death_year_from']);
    }

    if ($filters['death_year_to'] !== null) {
        $query->whereYear('death_date', '<=', $filters['death_year_to']);
    }

    if ($filters['has_profile_image']) {
        $query->whereNotNull('profile_image_url')
            ->where('profile_image_url', '!=', '');
    }

    if ($filters['has_gallery']) {
        $query->has('images');
    }

    if ($filters['has_video']) {
        $query->has('videos');
    }

    switch ($filters['sort']) {
        case 'oldest':
            $query->orderBy('created_at');
            break;
        case 'name_asc':
            $query->orderBy('first_name')->orderBy('last_name');
            break;
        case 'name_desc':
            $query->orderByDesc('first_name')->orderByDesc('last_name');
            break;
        case 'death_desc':
            $query->orderByDesc('death_date')->orderByDesc('created_at');
            break;
        case 'newest':
        default:
            $query->orderByDesc('created_at');
            break;
    }

    $memorials = $query->paginate(18)->withQueryString();

    $countries = Country::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'code']);

    $places = Place::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'country_id', 'name', 'type']);

    if ($filters['birth_place_id'] !== null) {
        $birthPlace = $places->firstWhere('id', $filters['birth_place_id']);
        if ($birthPlace && $filters['birth_country_id'] === null) {
            $filters['birth_country_id'] = (int) $birthPlace->country_id;
        }
    }

    if ($filters['death_place_id'] !== null) {
        $deathPlace = $places->firstWhere('id', $filters['death_place_id']);
        if ($deathPlace && $filters['death_country_id'] === null) {
            $filters['death_country_id'] = (int) $deathPlace->country_id;
        }
    }

    $placesByCountry = $places
        ->groupBy('country_id')
        ->map(static function ($items) {
            return $items
                ->map(static function ($place): array {
                    return [
                        'id' => (int) $place->id,
                        'name' => $place->name,
                        'type' => $place->type,
                    ];
                })
                ->values()
                ->all();
        })
        ->all();

    $countryNames = $countries->pluck('name', 'id')->all();
    $placeNames = $places->pluck('name', 'id')->all();

    return view('search', compact(
        'memorials',
        'filters',
        'maxYear',
        'countries',
        'placesByCountry',
        'countryNames',
        'placeNames'
    ));
};

$renderMemorialBySlug = static function (string $locale, string $slug) {
    $memorial = Memorial::where('slug', $slug)
        ->where('is_public', true)
        ->with([
            'images',
            'videos',
            'tributes' => fn ($query) => $query->orderBy('created_at', 'desc'),
        ])
        ->firstOrFail();

    return view('memorial', compact('memorial'));
};

$storeTribute = static function (Request $request, Memorial $memorial, string $locale) {
    $redirectResponse = redirect()->route('memorial.profile', [
        'locale' => $locale,
        'slug' => $memorial->slug,
    ]);

    if (filled($request->input('website'))) {
        return $redirectResponse;
    }

    $validated = $request->validate([
        'author_name' => 'required|string|max:255',
        'author_email' => 'required|email|max:255',
        'message' => 'required|string|min:10|max:1000',
        'website' => 'nullable|string|max:255',
        'form_rendered_at' => 'required|integer',
        'form_signature' => 'required|string|size:64',
    ]);

    $formRenderedAt = (int) $validated['form_rendered_at'];
    $secondsSinceFormRender = now()->timestamp - $formRenderedAt;

    $expectedSignature = hash_hmac(
        'sha256',
        $formRenderedAt.'|'.$memorial->id.'|'.$request->session()->getId(),
        (string) config('app.key')
    );

    if (!hash_equals($expectedSignature, (string) $validated['form_signature'])) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.security_failed'),
        ]);
    }

    if ($secondsSinceFormRender < 4) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.too_fast'),
        ]);
    }

    if ($secondsSinceFormRender > 7200) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.expired'),
        ]);
    }

    $cleanMessage = trim((string) preg_replace('/\s+/', ' ', $validated['message']));
    if (mb_strlen($cleanMessage) < 10) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.too_short_clean'),
        ]);
    }

    preg_match_all('/(?:https?:\/\/|www\.)/i', $cleanMessage, $urlMatches);
    if (count($urlMatches[0]) > 2) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.too_many_links'),
        ]);
    }

    $normalizedEmail = mb_strtolower($validated['author_email']);
    $hasRecentDuplicate = $memorial->tributes()
        ->whereRaw('LOWER(author_email) = ?', [$normalizedEmail])
        ->whereRaw('LOWER(message) = ?', [mb_strtolower($cleanMessage)])
        ->where('created_at', '>=', now()->subDay())
        ->exists();

    if ($hasRecentDuplicate) {
        return $redirectResponse->withErrors([
            'message' => __('ui.memorial.messages.duplicate'),
        ]);
    }

    $turnstileSiteKey = (string) config('services.turnstile.site_key');
    $turnstileSecretKey = (string) config('services.turnstile.secret_key');

    if ($turnstileSiteKey !== '' && $turnstileSecretKey !== '') {
        $turnstileToken = (string) $request->input('cf-turnstile-response', '');
        if ($turnstileToken === '') {
            return $redirectResponse->withErrors([
                'message' => __('ui.memorial.messages.captcha_required'),
            ]);
        }

        try {
            $turnstileVerifyResponse = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $turnstileSecretKey,
                    'response' => $turnstileToken,
                    'remoteip' => $request->ip(),
                ]);

            if (!$turnstileVerifyResponse->ok() || !$turnstileVerifyResponse->json('success')) {
                return $redirectResponse->withErrors([
                    'message' => __('ui.memorial.messages.captcha_failed'),
                ]);
            }
        } catch (\Throwable $exception) {
            return $redirectResponse->withErrors([
                'message' => __('ui.memorial.messages.captcha_unavailable'),
            ]);
        }
    }

    $memorial->tributes()->create([
        'author_name' => $validated['author_name'],
        'author_email' => $normalizedEmail,
        'message' => $cleanMessage,
    ]);

    return $redirectResponse->with('success', __('ui.memorial.messages.success'));
};

Route::get('/language/{locale}', [\App\Http\Controllers\LocaleController::class, 'switch'])->name('locale.switch');

// Sitemap routes
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{locale}.xml', [\App\Http\Controllers\SitemapController::class, 'show'])->name('sitemap.locale');

// Event tracking validation (non-production only)
Route::get('/analytics/validation', [\App\Http\Controllers\EventTrackingValidationController::class, 'index'])->name('analytics.validation');

Route::get('/robots.txt', static function () {
    $content = file_get_contents(public_path('robots.txt'));
    $content = str_replace('{{SITE_URL}}', url('/'), $content);

    return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('robots');

Route::get('/', static function (Request $request) use ($detectLocale) {
    return redirect()->route('home', ['locale' => $detectLocale($request)]);
})->name('root.redirect');

Route::prefix('{locale}')
    ->where(['locale' => $localePattern])
    ->group(function () use ($renderHome, $renderSearch, $renderMemorialBySlug, $storeTribute) {
        Route::get('/', $renderHome)->name('home');
        Route::get('/search', $renderSearch)->name('search.page');
        Route::get('/about', static fn () => view('about'))->name('about');
        Route::get('/contact', [ContactController::class, 'index'])->name('contact');
        Route::get('/privacy', static fn () => view('privacy'))->name('privacy');
        Route::get('/terms', static fn () => view('terms'))->name('terms');
        Route::get('/cookie-settings', [\App\Http\Controllers\CookieSettingsController::class, 'index'])->name('cookie.settings');
        Route::post('/contact', [ContactController::class, 'store'])
            ->middleware('throttle:5,60')
            ->name('contact.submit');
        Route::get('/register', static fn () => view('register'))->name('register');
        Route::get('/login', static fn () => view('login'))->name('login');
        Route::get('/forgot-password', static fn () => view('forgot-password'))->name('password.forgot');
        Route::get('/reset-password/{token}', static fn (Request $request, string $locale, string $token) => view('reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]))->name('password.reset.form');
        Route::get('/dashboard', static fn (string $locale) => view('dashboard'))->name('dashboard');
        Route::get('/create', static fn (string $locale) => view('memorial-form', ['mode' => 'create', 'slug' => null]))->name('memorial.create');
        Route::get('/edit/{slug}', static fn (string $locale, string $slug) => view('memorial-form', ['mode' => 'edit', 'slug' => $slug]))->middleware('validate.slug')->name('memorial.edit');
        Route::get('/admin', static fn (string $locale) => view('admin'))->name('admin.panel');

        Route::get('/memorial/{slug}', static function (Request $request, string $locale, string $slug) {
            return redirect()->route('memorial.profile', ['locale' => $locale, 'slug' => $slug], 301);
        })->middleware('validate.slug')->name('memorial.show');
        Route::get('/profil/{slug}', $renderMemorialBySlug)->middleware('validate.slug')->name('memorial.profile');

        Route::post('/memorial/{memorial}/tributes', static function (Request $request, string $locale, Memorial $memorial) use ($storeTribute) {
            return $storeTribute($request, $memorial, $locale);
        })->middleware('throttle:tribute-submission')->name('tributes.store');
    });

// Legacy unprefixed redirects for SEO-safe migration.
Route::get('/about', static function (Request $request) use ($detectLocale) {
    return redirect()->route('about', ['locale' => $detectLocale($request)], 301);
});

Route::get('/contact', static function (Request $request) use ($detectLocale) {
    return redirect()->route('contact', ['locale' => $detectLocale($request)], 301);
});

Route::get('/privacy', static function (Request $request) use ($detectLocale) {
    return redirect()->route('privacy', ['locale' => $detectLocale($request)], 301);
});

Route::get('/terms', static function (Request $request) use ($detectLocale) {
    return redirect()->route('terms', ['locale' => $detectLocale($request)], 301);
});

Route::get('/cookie-settings', static function (Request $request) use ($detectLocale) {
    return redirect()->route('cookie.settings', ['locale' => $detectLocale($request)], 301);
});

Route::get('/search', static function (Request $request) use ($detectLocale) {
    return redirect()->route('search.page', ['locale' => $detectLocale($request)] + $request->query(), 301);
});

Route::get('/register', static function (Request $request) use ($detectLocale) {
    return redirect()->route('register', ['locale' => $detectLocale($request)], 301);
});

Route::get('/login', static function (Request $request) use ($detectLocale) {
    return redirect()->route('login', ['locale' => $detectLocale($request)], 301);
});

Route::get('/forgot-password', static function (Request $request) use ($detectLocale) {
    return redirect()->route('password.forgot', ['locale' => $detectLocale($request)], 301);
});

Route::get('/reset-password/{token}', static function (Request $request, string $token) use ($detectLocale) {
    return redirect()->route('password.reset.form', [
        'locale' => $detectLocale($request),
        'token' => $token,
        'email' => $request->query('email'),
    ], 301);
});

Route::get('/dashboard', static function (Request $request) use ($detectLocale) {
    return redirect()->route('dashboard', ['locale' => $detectLocale($request)], 301);
});

Route::get('/create', static function (Request $request) use ($detectLocale) {
    return redirect()->route('memorial.create', ['locale' => $detectLocale($request)], 301);
});

Route::get('/edit/{slug}', static function (Request $request, string $slug) use ($detectLocale) {
    return redirect()->route('memorial.edit', ['locale' => $detectLocale($request), 'slug' => $slug], 301);
})->middleware('validate.slug');

Route::get('/admin', static function (Request $request) use ($detectLocale) {
    return redirect()->route('admin.panel', ['locale' => $detectLocale($request)], 301);
});

Route::get('/memorial/{slug}', static function (Request $request, string $slug) use ($detectLocale) {
    return redirect()->route('memorial.profile', ['locale' => $detectLocale($request), 'slug' => $slug], 301);
})->middleware('validate.slug');

Route::get('/profil/{slug}', static function (Request $request, string $slug) use ($detectLocale) {
    return redirect()->route('memorial.profile', ['locale' => $detectLocale($request), 'slug' => $slug], 301);
})->middleware('validate.slug');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,60');

Route::post('/memorial/{memorial}/tributes', static function (Request $request, Memorial $memorial) use ($detectLocale, $storeTribute) {
    return $storeTribute($request, $memorial, $detectLocale($request));
})->middleware('throttle:tribute-submission');
