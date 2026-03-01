<?php

namespace App\Services\Security;

use App\Repositories\BlockedIpRepository;
use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    private const MINUTE_TTL = 60; // 60 seconds
    private const HOUR_TTL = 3600; // 3600 seconds

    public function __construct(
        private BlockedIpRepository $blockedIpRepository
    ) {}

    /**
     * Record a malicious request from an IP address
     */
    public function recordMaliciousRequest(string $ip): void
    {
        // Increment counters for both time windows
        $minuteCount = $this->incrementCounter($ip, 'minute');
        $hourCount = $this->incrementCounter($ip, 'hour');

        // Check if auto-blocking thresholds are exceeded
        if ($this->shouldAutoBlock($ip)) {
            $this->autoBlockIp($ip, $this->getAutoBlockDuration($minuteCount, $hourCount));
        }
    }

    /**
     * Check if an IP is currently rate limited
     */
    public function isRateLimited(string $ip): bool
    {
        $perMinuteThreshold = config('security.bot_protection.rate_limiting.per_minute', 10);
        $perHourThreshold = config('security.bot_protection.rate_limiting.per_hour', 50);

        $minuteCount = $this->getCounter($ip, 'minute');
        $hourCount = $this->getCounter($ip, 'hour');

        return $minuteCount >= $perMinuteThreshold || $hourCount >= $perHourThreshold;
    }

    /**
     * Get remaining attempts before rate limit
     */
    public function getRemainingAttempts(string $ip): int
    {
        $perMinuteThreshold = config('security.bot_protection.rate_limiting.per_minute', 10);
        $minuteCount = $this->getCounter($ip, 'minute');

        return max(0, $perMinuteThreshold - $minuteCount);
    }

    /**
     * Increment the counter for a specific time window
     */
    private function incrementCounter(string $ip, string $window): int
    {
        $cacheKey = "bot:rate_limit:{$ip}:{$window}";
        $ttl = $window === 'minute' ? self::MINUTE_TTL : self::HOUR_TTL;

        // Use Redis for rate limit counters when available, fallback to file cache
        $store = $this->getCacheStore();

        // Get current count
        $count = $store->get($cacheKey, 0) + 1;

        // Store with TTL
        $store->put($cacheKey, $count, $ttl);

        return $count;
    }

    /**
     * Get the counter value for a specific time window
     */
    private function getCounter(string $ip, string $window): int
    {
        $cacheKey = "bot:rate_limit:{$ip}:{$window}";
        $store = $this->getCacheStore();

        return $store->get($cacheKey, 0);
    }

    /**
     * Get the appropriate cache store (Redis preferred, file as fallback)
     */
    private function getCacheStore(): \Illuminate\Contracts\Cache\Repository
    {
        try {
            // Try to use Redis if available
            if (config('cache.default') === 'redis' || Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                return Cache::store('redis');
            }
        } catch (\Exception $e) {
            // Redis not available, fall through to default
        }

        // Fallback to default cache (usually file)
        return Cache::store();
    }

    /**
     * Check if IP should be auto-blocked based on thresholds
     */
    private function shouldAutoBlock(string $ip): bool
    {
        $perMinuteThreshold = config('security.bot_protection.rate_limiting.per_minute', 10);
        $perHourThreshold = config('security.bot_protection.rate_limiting.per_hour', 50);

        $minuteCount = $this->getCounter($ip, 'minute');
        $hourCount = $this->getCounter($ip, 'hour');

        // Auto-block if either threshold is exceeded
        return $minuteCount >= $perMinuteThreshold || $hourCount >= $perHourThreshold;
    }

    /**
     * Automatically block an IP address
     */
    private function autoBlockIp(string $ip, int $duration): void
    {
        // Check if already blocked to avoid duplicate entries
        if ($this->blockedIpRepository->isBlocked($ip)) {
            return;
        }

        $minuteCount = $this->getCounter($ip, 'minute');
        $hourCount = $this->getCounter($ip, 'hour');

        $reason = "Auto-blocked: {$minuteCount} requests/minute, {$hourCount} requests/hour";

        $blockedIp = $this->blockedIpRepository->block($ip, $reason, $duration);
        $blockedIp->is_auto_blocked = true;
        $blockedIp->malicious_request_count = max($minuteCount, $hourCount);
        $blockedIp->save();
    }

    /**
     * Determine auto-block duration based on violation severity
     */
    private function getAutoBlockDuration(int $minuteCount, int $hourCount): int
    {
        $perMinuteThreshold = config('security.bot_protection.rate_limiting.per_minute', 10);
        $perHourThreshold = config('security.bot_protection.rate_limiting.per_hour', 50);

        // If per-hour threshold exceeded, block for 24 hours
        if ($hourCount >= $perHourThreshold) {
            return 24 * 60; // 24 hours in minutes
        }

        // If per-minute threshold exceeded, block for 15 minutes
        if ($minuteCount >= $perMinuteThreshold) {
            return 15; // 15 minutes
        }

        // Default to 15 minutes
        return 15;
    }
}
