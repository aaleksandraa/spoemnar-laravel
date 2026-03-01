<?php

namespace App\Support;

use Illuminate\Http\Request;

class LocaleResolver
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['bs', 'sr', 'hr', 'de', 'en', 'it'];

    /**
     * @var array<string, string>
     */
    private const COUNTRY_TO_LOCALE = [
        'BA' => 'bs',
        'RS' => 'sr',
        'ME' => 'sr',
        'HR' => 'hr',
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',
        'US' => 'en',
        'GB' => 'en',
        'IE' => 'en',
        'CA' => 'en',
        'AU' => 'en',
        'NZ' => 'en',
        'IT' => 'it',
        'SM' => 'it',
        'VA' => 'it',
    ];

    /**
     * @var array<string, string>
     */
    private const LANGUAGE_TO_LOCALE = [
        'bs' => 'bs',
        'sr' => 'sr',
        'hr' => 'hr',
        'de' => 'de',
        'en' => 'en',
        'it' => 'it',
    ];

    /**
     * @return array<int, string>
     */
    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    public static function isSupported(string $locale): bool
    {
        return in_array(self::normalizeLocale($locale), self::SUPPORTED_LOCALES, true);
    }

    public static function normalizeLocale(string $locale): string
    {
        return strtolower(trim($locale));
    }

    public static function detectFromRequest(Request $request): string
    {
        $routeLocale = self::supportedLocaleFromCandidate($request->route('locale'));
        if ($routeLocale !== null) {
            return $routeLocale;
        }

        $queryLocale = self::supportedLocaleFromCandidate($request->query('lang'));
        if ($queryLocale !== null) {
            return $queryLocale;
        }

        $countryLocale = self::localeFromCountry(self::countryFromHeaders($request));
        // Never auto-default to English from geo headers because provider geo
        // headers can be inaccurate on some deployments. English remains
        // available via explicit route/query/session/cookie selection.
        if ($countryLocale !== null && $countryLocale !== 'en') {
            return $countryLocale;
        }

        $sessionLocale = self::supportedLocaleFromCandidate($request->session()->get('locale'));
        if ($sessionLocale !== null && $sessionLocale !== 'en') {
            return $sessionLocale;
        }

        $cookieLocale = self::supportedLocaleFromCandidate($request->cookie('locale'));
        if ($cookieLocale !== null && $cookieLocale !== 'en') {
            return $cookieLocale;
        }

        foreach ($request->getLanguages() as $language) {
            $primary = substr(strtolower($language), 0, 2);
            // Do not auto-default to English from browser language;
            // keep Bosnian market default when geo data is missing.
            if ($primary === 'en') {
                continue;
            }

            if (isset(self::LANGUAGE_TO_LOCALE[$primary])) {
                return self::LANGUAGE_TO_LOCALE[$primary];
            }
        }

        return 'bs';
    }

    public static function localeFromCountry(?string $countryCode): ?string
    {
        if (!is_string($countryCode) || $countryCode === '') {
            return null;
        }

        $country = strtoupper(trim($countryCode));

        return self::COUNTRY_TO_LOCALE[$country] ?? null;
    }

    private static function countryFromHeaders(Request $request): ?string
    {
        $headerKeys = [
            'CF-IPCountry',
            'CloudFront-Viewer-Country',
            'X-Vercel-IP-Country',
            'X-Nf-Geo-Country',
            'Fastly-Geo-Country-Code',
            'True-Client-Country',
            'Geoip-Country-Code',
            'X-AppEngine-Country',
            'X-Country-Code',
            'X-Country',
            'X-Geo-Country',
            'X-NF-Geo',
        ];

        foreach ($headerKeys as $key) {
            $value = $request->header($key);
            if (is_string($value) && trim($value) !== '') {
                $normalizedCountry = self::extractCountryCode(trim($value));
                if ($normalizedCountry !== null) {
                    return $normalizedCountry;
                }
            }
        }

        $serverCountry = $request->server('GEOIP_COUNTRY_CODE');
        if (is_string($serverCountry) && trim($serverCountry) !== '') {
            $normalizedCountry = self::extractCountryCode(trim($serverCountry));
            if ($normalizedCountry !== null) {
                return $normalizedCountry;
            }
        }

        return null;
    }

    private static function supportedLocaleFromCandidate(mixed $candidate): ?string
    {
        if (!is_string($candidate)) {
            return null;
        }

        $normalized = self::normalizeLocale($candidate);
        if (!self::isSupported($normalized)) {
            return null;
        }

        return $normalized;
    }

    private static function extractCountryCode(string $rawValue): ?string
    {
        $value = trim($rawValue);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $candidate = $decoded['country']['code']
                    ?? $decoded['country_code']
                    ?? $decoded['country']
                    ?? null;

                if (is_string($candidate) && preg_match('/^[A-Za-z]{2}$/', trim($candidate))) {
                    return strtoupper(trim($candidate));
                }
            }
        }

        if (preg_match('/^[A-Za-z]{2}$/', $value)) {
            return strtoupper($value);
        }

        if (preg_match('/\b([A-Za-z]{2})\b/', $value, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
