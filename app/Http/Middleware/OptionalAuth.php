<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * Attempts to authenticate the user if a token is present, but doesn't fail if not.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to authenticate using Sanctum if a token is present
        if ($token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && !$accessToken->tokenable->tokenCan('*')) {
                // Token exists and is valid
                Auth::setUser($accessToken->tokenable);
                $request->setUserResolver(fn () => $accessToken->tokenable);
            } elseif ($accessToken) {
                // Token exists
                Auth::setUser($accessToken->tokenable);
                $request->setUserResolver(fn () => $accessToken->tokenable);
            }
        }

        return $next($request);
    }
}
