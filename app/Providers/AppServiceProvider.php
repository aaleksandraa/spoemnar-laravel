<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS URLs in production environment
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        RateLimiter::for('tribute-submission', function (Request $request) {
            $memorialRouteParam = $request->route('memorial');
            $memorialKey = is_object($memorialRouteParam) && isset($memorialRouteParam->id)
                ? (string) $memorialRouteParam->id
                : (string) $memorialRouteParam;

            $email = mb_strtolower(trim((string) $request->input('author_email', '')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(2)->by("tribute:ip:minute:{$ip}"),
                Limit::perHour(8)->by("tribute:ip:hour:{$ip}"),
                Limit::perHour(3)->by("tribute:email:{$ip}:{$email}"),
                Limit::perHour(4)->by("tribute:memorial:{$ip}:{$memorialKey}"),
            ];
        });

        RateLimiter::for('password-reset-link', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(3)->by("password-reset-link:ip:{$ip}"),
                Limit::perHour(10)->by("password-reset-link:email:{$ip}:{$email}"),
            ];
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(5)->by("password-reset-submit:ip:{$ip}"),
                Limit::perHour(15)->by("password-reset-submit:email:{$ip}:{$email}"),
            ];
        });

        RateLimiter::for('search', function (Request $request) {
            $ip = (string) $request->ip();

            return Limit::perMinute(60)->by("search:ip:{$ip}")
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many search requests. Please try again later.'
                    ], 429);
                });
        });
    }
}
