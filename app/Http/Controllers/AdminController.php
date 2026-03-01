<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\PaginationRequest;
use App\Models\AppSetting;
use App\Models\Memorial;
use App\Models\Tribute;
use App\Models\User;
use App\Models\UserRole;
use App\Support\LocaleResolver;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $recentMemorials = Memorial::with(['user.profile'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentTributes = Tribute::with(['memorial:id,first_name,last_name,slug'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            // Backward compatible snake_case keys
            'total_users' => User::count(),
            'total_memorials' => Memorial::count(),
            'total_tributes' => Tribute::count(),
            'public_memorials' => Memorial::where('is_public', true)->count(),
            'private_memorials' => Memorial::where('is_public', false)->count(),
            // Frontend friendly camelCase keys
            'totalUsers' => User::count(),
            'totalMemorials' => Memorial::count(),
            'totalTributes' => Tribute::count(),
            'publicMemorials' => Memorial::where('is_public', true)->count(),
            'privateMemorials' => Memorial::where('is_public', false)->count(),
            'recentMemorials' => $recentMemorials->map(static function (Memorial $memorial): array {
                return [
                    'id' => (string) $memorial->id,
                    'firstName' => $memorial->first_name,
                    'lastName' => $memorial->last_name,
                    'birthDate' => $memorial->birth_date?->format('Y-m-d'),
                    'deathDate' => $memorial->death_date?->format('Y-m-d'),
                    'profileImageUrl' => MediaUrl::normalize($memorial->profile_image_url),
                    'slug' => $memorial->slug,
                    'isPublic' => (bool) $memorial->is_public,
                    'createdAt' => $memorial->created_at?->toISOString(),
                    'updatedAt' => $memorial->updated_at?->toISOString(),
                ];
            })->values(),
            'recentTributes' => $recentTributes->map(static function (Tribute $tribute): array {
                return [
                    'id' => (string) $tribute->id,
                    'authorName' => $tribute->author_name,
                    'authorEmail' => $tribute->author_email,
                    'message' => $tribute->message,
                    'createdAt' => $tribute->created_at?->toISOString(),
                    'memorial' => $tribute->memorial ? [
                        'id' => (string) $tribute->memorial->id,
                        'slug' => $tribute->memorial->slug,
                        'firstName' => $tribute->memorial->first_name,
                        'lastName' => $tribute->memorial->last_name,
                    ] : null,
                ];
            })->values(),
        ];

        return response()->json($stats);
    }

    /**
     * Get all memorials (regardless of is_public status).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function memorials(PaginationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['perPage'] ?? $validated['per_page'] ?? 15);
        $search = trim((string) $request->input('search', $request->input('q', '')));

        $query = Memorial::with(['user.profile'])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
            });
        }

        $memorials = $query
            ->paginate($perPage);

        return response()->json([
            'data' => $memorials->getCollection()->map(static function (Memorial $memorial): array {
                return [
                    'id' => (string) $memorial->id,
                    'userId' => (string) $memorial->user_id,
                    'firstName' => $memorial->first_name,
                    'lastName' => $memorial->last_name,
                    'birthDate' => $memorial->birth_date?->format('Y-m-d'),
                    'deathDate' => $memorial->death_date?->format('Y-m-d'),
                    'birthPlace' => $memorial->birth_place,
                    'deathPlace' => $memorial->death_place,
                    'biography' => $memorial->biography,
                    'profileImageUrl' => MediaUrl::normalize($memorial->profile_image_url),
                    'slug' => $memorial->slug,
                    'isPublic' => (bool) $memorial->is_public,
                    'createdAt' => $memorial->created_at?->toISOString(),
                    'updatedAt' => $memorial->updated_at?->toISOString(),
                    'owner' => $memorial->user?->profile ? [
                        'fullName' => $memorial->user->profile->full_name,
                        'email' => $memorial->user->profile->email,
                    ] : [
                        'fullName' => null,
                        'email' => $memorial->user?->email,
                    ],
                ];
            })->values(),
            'meta' => [
                'currentPage' => $memorials->currentPage(),
                'lastPage' => $memorials->lastPage(),
                'perPage' => $memorials->perPage(),
                'total' => $memorials->total(),
            ],
        ]);
    }

    /**
     * Get all users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function users(PaginationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['perPage'] ?? $validated['per_page'] ?? 15);
        $search = trim((string) $request->input('search', $request->input('q', '')));

        $query = User::with(['profile', 'roles'])
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery
                    ->where('email', 'like', "%{$search}%")
                    ->orWhereHas('profile', static function ($profileQuery) use ($search) {
                        $profileQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query
            ->paginate($perPage);

        return response()->json([
            'data' => $users->getCollection()->map(static function (User $user): array {
                $isAdmin = $user->roles->contains(static fn (UserRole $role): bool => $role->role === 'admin')
                    || $user->role === 'admin';

                return [
                    'id' => (string) $user->id,
                    'email' => $user->email,
                    'role' => $isAdmin ? 'admin' : 'user',
                    'roles' => $user->roles->pluck('role')->values(),
                    'profile' => $user->profile ? [
                        'fullName' => $user->profile->full_name,
                        'email' => $user->profile->email,
                    ] : null,
                    'createdAt' => $user->created_at?->toISOString(),
                    'updatedAt' => $user->updated_at?->toISOString(),
                ];
            })->values(),
            'meta' => [
                'currentPage' => $users->currentPage(),
                'lastPage' => $users->lastPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Update user role (add or remove).
     *
     * @param UpdateRoleRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function updateUserRole(UpdateRoleRequest $request, User $user): JsonResponse
    {
        $role = $request->input('role');
        $action = $request->input('action', 'add'); // 'add' or 'remove'

        if ($action === 'add') {
            // Check if role already exists
            $existingRole = $user->roles()->where('role', $role)->first();

            if (!$existingRole) {
                UserRole::create([
                    'user_id' => $user->id,
                    'role' => $role,
                ]);
            }

            if ($role === 'admin' && $user->role !== 'admin') {
                $user->role = 'admin';
                $user->save();
            }

            return response()->json([
                'message' => 'Role added successfully.',
                'user' => $user->load('roles'),
            ]);
        } else {
            // Remove role
            $user->roles()->where('role', $role)->delete();

            if ($role === 'admin') {
                $user->role = 'user';
                $user->save();
            }

            return response()->json([
                'message' => 'Role removed successfully.',
                'user' => $user->load('roles'),
            ]);
        }
    }

    /**
     * Delete any user account (admin privilege).
     */
    public function deleteUser(Request $request, User $user): JsonResponse
    {
        // Use policy authorization instead of manual check
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * Delete any memorial (admin privilege, no ownership check).
     *
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function deleteMemorial(Memorial $memorial): JsonResponse
    {
        $memorial->delete();

        return response()->json([
            'message' => 'Memorial deleted successfully.',
        ]);
    }

    /**
     * Delete any tribute (admin privilege, no ownership check).
     *
     * @param Tribute $tribute
     * @return JsonResponse
     */
    public function deleteTribute(Tribute $tribute): JsonResponse
    {
        $tribute->delete();

        return response()->json([
            'message' => 'Tribute deleted successfully.',
        ]);
    }

    /**
     * Get application toggle settings used by admin panel.
     */
    public function settings(): JsonResponse
    {
        AppSetting::ensureDefaultBooleanSettings();

        $defaults = AppSetting::defaultBooleanSettings();
        $settings = AppSetting::query()
            ->whereIn('setting_key', array_keys($defaults))
            ->get()
            ->map(static function (AppSetting $setting): array {
                return [
                    'key' => $setting->setting_key,
                    'isEnabled' => in_array(strtolower((string) $setting->setting_value), ['1', 'true', 'yes', 'on'], true),
                    'updatedAt' => $setting->updated_at?->toISOString(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Update a single admin toggle setting.
     */
    public function updateSetting(Request $request, string $settingKey): JsonResponse
    {
        $defaults = AppSetting::defaultBooleanSettings();
        if (!array_key_exists($settingKey, $defaults)) {
            return response()->json([
                'message' => 'Unknown setting key.',
            ], 404);
        }

        $validated = $request->validate([
            'is_enabled' => ['required', 'boolean'],
        ]);

        $setting = AppSetting::firstOrCreate(
            ['setting_key' => $settingKey],
            ['setting_value' => $defaults[$settingKey] ? '1' : '0']
        );

        $setting->setting_value = $validated['is_enabled'] ? '1' : '0';
        $setting->save();

        return response()->json([
            'message' => 'Setting updated successfully.',
            'data' => [
                'key' => $setting->setting_key,
                'isEnabled' => $validated['is_enabled'],
                'updatedAt' => $setting->updated_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Internal SEO health check for locale pages.
     *
     * Checks canonical tags, hreflang alternates and sitemap coverage
     * for all supported locales in one request.
     */
    public function localeSeoHealth(Request $request): JsonResponse
    {
        $locales = LocaleResolver::supportedLocales();
        $baseUrl = $this->resolveHealthBaseUrl($request);

        $localeToHreflang = [
            'sr' => 'sr-RS',
            'hr' => 'hr-HR',
            'bs' => 'bs-BA',
            'de' => 'de-DE',
            'en' => 'en-US',
            'it' => 'it-IT',
        ];

        $expectedHreflangs = [];
        foreach ($locales as $locale) {
            $hreflang = strtolower($localeToHreflang[$locale] ?? $locale);
            $expectedHreflangs[$hreflang] = $this->normalizeUrl(
                $baseUrl.route('home', ['locale' => $locale], false)
            );
        }
        $expectedHreflangs['x-default'] = $this->normalizeUrl(
            $baseUrl.route('home', ['locale' => 'bs'], false)
        );

        $localeChecks = [];
        $issues = [];
        $passedLocaleChecks = 0;

        foreach ($locales as $locale) {
            $homePath = route('home', ['locale' => $locale], false);
            $pageUrl = $baseUrl.$homePath;

            $pageResponse = app('router')->dispatch(Request::create($pageUrl, 'GET'));
            $httpStatus = $pageResponse->getStatusCode();
            $html = (string) $pageResponse->getContent();

            $actualCanonical = $this->extractCanonicalLink($html);
            $actualHreflangs = $this->extractAlternateHreflangLinks($html);
            $expectedCanonical = $this->normalizeUrl($pageUrl);

            $canonicalOk = $httpStatus === 200
                && $this->normalizeUrl((string) $actualCanonical) === $expectedCanonical;

            $missingHreflangs = [];
            foreach ($expectedHreflangs as $hreflang => $expectedUrl) {
                if (!isset($actualHreflangs[$hreflang])) {
                    $missingHreflangs[$hreflang] = $expectedUrl;
                    continue;
                }

                if ($this->normalizeUrl($actualHreflangs[$hreflang]) !== $expectedUrl) {
                    $missingHreflangs[$hreflang] = $expectedUrl;
                }
            }

            $hreflangOk = $httpStatus === 200 && $missingHreflangs === [];
            $localeOk = $canonicalOk && $hreflangOk;

            if ($localeOk) {
                $passedLocaleChecks++;
            } else {
                $issues[] = [
                    'type' => 'locale_page',
                    'locale' => $locale,
                    'http_status' => $httpStatus,
                    'canonical_ok' => $canonicalOk,
                    'hreflang_ok' => $hreflangOk,
                ];
            }

            $localeChecks[$locale] = [
                'url' => $pageUrl,
                'http_status' => $httpStatus,
                'canonical' => [
                    'ok' => $canonicalOk,
                    'expected' => $expectedCanonical,
                    'actual' => $actualCanonical,
                ],
                'hreflang' => [
                    'ok' => $hreflangOk,
                    'expected_count' => count($expectedHreflangs),
                    'actual_count' => count($actualHreflangs),
                    'missing_or_mismatched' => $missingHreflangs,
                ],
                'ok' => $localeOk,
            ];
        }

        $sitemapUrl = $baseUrl.'/sitemap.xml';
        $sitemapResponse = app('router')->dispatch(Request::create($sitemapUrl, 'GET'));
        $sitemapStatus = $sitemapResponse->getStatusCode();
        $sitemapContentType = (string) $sitemapResponse->headers->get('Content-Type', '');
        $sitemapXml = (string) $sitemapResponse->getContent();
        $sitemapLocs = $this->extractSitemapLocs($sitemapXml);
        $sitemapLocSet = array_fill_keys(
            array_map(fn (string $url) => $this->normalizeUrl($url), $sitemapLocs),
            true
        );

        $expectedStaticUrls = [];
        foreach ($locales as $locale) {
            foreach (['home', 'about', 'contact', 'search.page'] as $routeName) {
                $expectedStaticUrls[] = $this->normalizeUrl(
                    $baseUrl.route($routeName, ['locale' => $locale], false)
                );
            }
        }
        $missingStaticUrls = array_values(array_filter(
            $expectedStaticUrls,
            fn (string $url) => !isset($sitemapLocSet[$url])
        ));

        $sampleMemorialSlug = Memorial::where('is_public', true)
            ->orderByDesc('updated_at')
            ->value('slug');

        $expectedMemorialUrls = [];
        if (is_string($sampleMemorialSlug) && $sampleMemorialSlug !== '') {
            foreach ($locales as $locale) {
                $expectedMemorialUrls[] = $this->normalizeUrl(
                    $baseUrl.route('memorial.profile', ['locale' => $locale, 'slug' => $sampleMemorialSlug], false)
                );
            }
        }

        $missingMemorialUrls = array_values(array_filter(
            $expectedMemorialUrls,
            fn (string $url) => !isset($sitemapLocSet[$url])
        ));

        $sitemapOk = $sitemapStatus === 200
            && str_contains(strtolower($sitemapContentType), 'xml')
            && $missingStaticUrls === []
            && $missingMemorialUrls === [];

        if (!$sitemapOk) {
            $issues[] = [
                'type' => 'sitemap',
                'http_status' => $sitemapStatus,
                'content_type' => $sitemapContentType,
                'missing_static_urls_count' => count($missingStaticUrls),
                'missing_memorial_urls_count' => count($missingMemorialUrls),
            ];
        }

        $overallOk = $passedLocaleChecks === count($locales) && $sitemapOk;

        return response()->json([
            'status' => $overallOk ? 'ok' : 'fail',
            'checked_at' => now()->toIso8601String(),
            'base_url' => $baseUrl,
            'summary' => [
                'locales_checked' => count($locales),
                'locale_pages_passed' => $passedLocaleChecks,
                'locale_pages_failed' => count($locales) - $passedLocaleChecks,
                'issues_count' => count($issues),
            ],
            'checks' => [
                'locale_pages' => $localeChecks,
                'sitemap' => [
                    'ok' => $sitemapOk,
                    'url' => $sitemapUrl,
                    'http_status' => $sitemapStatus,
                    'content_type' => $sitemapContentType,
                    'urls_found' => count($sitemapLocs),
                    'expected_static_urls' => count($expectedStaticUrls),
                    'missing_static_urls' => $missingStaticUrls,
                    'sample_memorial_slug' => $sampleMemorialSlug,
                    'expected_memorial_urls' => count($expectedMemorialUrls),
                    'missing_memorial_urls' => $missingMemorialUrls,
                ],
            ],
            'issues' => $issues,
        ]);
    }

    private function resolveHealthBaseUrl(Request $request): string
    {
        $requestedBaseUrl = trim((string) $request->query('base_url', ''));
        if ($requestedBaseUrl !== '' && preg_match('#^https?://#i', $requestedBaseUrl) === 1) {
            return rtrim($requestedBaseUrl, '/');
        }

        $configuredAppUrl = trim((string) config('app.url', ''));
        if ($configuredAppUrl !== '' && preg_match('#^https?://#i', $configuredAppUrl) === 1) {
            return rtrim($configuredAppUrl, '/');
        }

        return rtrim($request->getSchemeAndHttpHost(), '/');
    }

    private function extractCanonicalLink(string $html): ?string
    {
        preg_match_all('/<link\b[^>]*>/i', $html, $matches);
        foreach ($matches[0] as $linkTag) {
            $rel = strtolower((string) $this->extractTagAttribute($linkTag, 'rel'));
            if ($rel !== 'canonical') {
                continue;
            }

            return $this->extractTagAttribute($linkTag, 'href');
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function extractAlternateHreflangLinks(string $html): array
    {
        $hreflangs = [];

        preg_match_all('/<link\b[^>]*>/i', $html, $matches);
        foreach ($matches[0] as $linkTag) {
            $rel = strtolower((string) $this->extractTagAttribute($linkTag, 'rel'));
            if ($rel !== 'alternate') {
                continue;
            }

            $hreflang = strtolower((string) $this->extractTagAttribute($linkTag, 'hreflang'));
            $href = (string) $this->extractTagAttribute($linkTag, 'href');

            if ($hreflang === '' || $href === '') {
                continue;
            }

            $hreflangs[$hreflang] = $href;
        }

        return $hreflangs;
    }

    /**
     * @return array<int, string>
     */
    private function extractSitemapLocs(string $xml): array
    {
        preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $matches);

        if (!isset($matches[1]) || !is_array($matches[1])) {
            return [];
        }

        $locs = [];
        foreach ($matches[1] as $loc) {
            if (is_string($loc) && trim($loc) !== '') {
                $locs[] = trim($loc);
            }
        }

        return $locs;
    }

    private function extractTagAttribute(string $tag, string $attribute): ?string
    {
        $pattern = '/\b'.preg_quote($attribute, '/').'\s*=\s*["\']([^"\']+)["\']/i';
        if (preg_match($pattern, $tag, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    private function normalizeUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return '';
        }

        if ($trimmed === '/') {
            return '/';
        }

        return rtrim($trimmed, '/');
    }
}
