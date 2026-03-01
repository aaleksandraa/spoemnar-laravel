<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocaleRequest;
use App\Support\LocaleResolver;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Handle locale switching request.
     */
    public function switch(LocaleRequest $request): RedirectResponse
    {
        // Validation is automatically handled by LocaleRequest
        $normalizedLocale = $request->validated()['locale'];

        $referer = (string) $request->headers->get('referer', '');
        $refererHost = parse_url($referer, PHP_URL_HOST);
        if (!is_string($refererHost) || $refererHost !== $request->getHost()) {
            return redirect()->route('home', ['locale' => $normalizedLocale]);
        }

        $path = (string) parse_url($referer, PHP_URL_PATH);
        $query = [];
        parse_str((string) parse_url($referer, PHP_URL_QUERY), $query);
        unset($query['lang']);

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if (isset($segments[0]) && LocaleResolver::isSupported((string) $segments[0])) {
            array_shift($segments);
        }

        $localizedPath = '/' . $normalizedLocale;
        if ($segments !== []) {
            $localizedPath .= '/' . implode('/', $segments);
        }

        $queryString = http_build_query($query);
        if ($queryString !== '') {
            $localizedPath .= '?' . $queryString;
        }

        return redirect($localizedPath);
    }
}
