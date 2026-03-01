<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register ExploitPatternMatcher with patterns from config
        $this->app->singleton(\App\Services\Security\ExploitPatternMatcher::class, function ($app) {
            $patterns = config('security.bot_protection.exploit_patterns', []);
            return new \App\Services\Security\ExploitPatternMatcher($patterns);
        });

        // Register SecurityLogger as singleton
        $this->app->singleton(\App\Services\Security\SecurityLogger::class);

        // Register RateLimiter as singleton
        $this->app->singleton(\App\Services\Security\RateLimiter::class);

        // Register BlockedIpRepository
        $this->app->bind(\App\Repositories\BlockedIpRepository::class);

        // Register BotStatisticsService
        $this->app->bind(\App\Services\Security\BotStatisticsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit aliases avoid case-sensitive autoload issues on Linux hosts.
        Blade::component('analytics.gtm-head', \App\View\Components\Analytics\GTMHead::class);
        Blade::component('analytics.gtm-body', \App\View\Components\Analytics\GTMBody::class);

        // Load security configuration
        $this->loadSecurityConfiguration();

        // Ensure security log channel is available
        $this->ensureSecurityLogChannel();

        // Cache exploit patterns on boot for performance
        $this->cacheExploitPatterns();

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

    /**
     * Load security configuration.
     */
    protected function loadSecurityConfiguration(): void
    {
        // Ensure config/security.php is loaded
        if (!config()->has('security')) {
            config(['security' => require config_path('security.php')]);
        }
    }

    /**
     * Ensure security log channel is available.
     */
    protected function ensureSecurityLogChannel(): void
    {
        // Verify that the security log channel is configured
        $channels = config('logging.channels', []);

        if (!isset($channels['security'])) {
            // Log a warning if security channel is not configured
            \Log::warning('Security log channel is not configured in config/logging.php');
        }
    }

    /**
     * Cache exploit patterns on boot for performance.
     */
    protected function cacheExploitPatterns(): void
    {
        // Pre-load and cache exploit patterns for the ExploitPatternMatcher
        // This ensures patterns are compiled once at boot time rather than on each request
        if (config('security.bot_protection.enabled', true)) {
            try {
                $patternMatcher = $this->app->make(\App\Services\Security\ExploitPatternMatcher::class);
                // The patterns are already loaded in the constructor, but we ensure
                // the service is instantiated early for optimal performance
            } catch (\Exception $e) {
                \Log::error('Failed to initialize ExploitPatternMatcher: ' . $e->getMessage());
            }
        }
    }
}
