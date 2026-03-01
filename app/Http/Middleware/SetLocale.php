<?php

namespace App\Http\Middleware;

use App\Support\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            $queryLocale = $request->query('lang');
            if (is_string($queryLocale)) {
                $normalizedLocale = LocaleResolver::normalizeLocale($queryLocale);
                if (LocaleResolver::isSupported($normalizedLocale)) {
                    $canonicalUrl = $this->buildCanonicalLocaleUrl($request, $normalizedLocale);

                    Log::channel('seo')->info('SEO canonical lang redirect', [
                        'event' => 'seo.lang_canonical_redirect',
                        'from' => $request->fullUrl(),
                        'to' => $canonicalUrl,
                        'requested_lang' => $queryLocale,
                        'resolved_locale' => $normalizedLocale,
                        'method' => $request->method(),
                        'path' => '/'.$request->path(),
                        'ip' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 255),
                        'referer' => (string) $request->headers->get('referer', ''),
                    ]);

                    return redirect()->to($canonicalUrl, 301);
                }
            }
        }

        $locale = LocaleResolver::detectFromRequest($request);

        $request->session()->put('locale', $locale);
        Cookie::queue('locale', $locale, 60 * 24 * 365);
        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        return $next($request);
    }

    private function buildCanonicalLocaleUrl(Request $request, string $locale): string
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/'))));
        $nonLocalizedFirstSegments = ['sitemap.xml', 'robots.txt'];

        if (!isset($segments[0]) || !in_array($segments[0], $nonLocalizedFirstSegments, true)) {
            if (isset($segments[0]) && LocaleResolver::isSupported((string) $segments[0])) {
                array_shift($segments);
            }

            if (isset($segments[0]) && $segments[0] === 'language') {
                $segments = [];
            }

            $localizedPath = '/'.$locale;
            if ($segments !== []) {
                $localizedPath .= '/'.implode('/', $segments);
            }
        } else {
            $localizedPath = '/'.implode('/', $segments);
        }

        $queryParams = $request->query();
        unset($queryParams['lang']);
        $queryString = http_build_query($queryParams);

        return $request->getSchemeAndHttpHost().$localizedPath.($queryString !== '' ? '?'.$queryString : '');
    }
}
