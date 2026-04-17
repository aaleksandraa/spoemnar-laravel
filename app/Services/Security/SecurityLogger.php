<?php

namespace App\Services\Security;

use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;

class SecurityLogger
{
    public function __construct(
        private LogManager $log,
        private Cache $cache
    ) {}

    /**
     * Log a malicious request to the security channel
     */
    public function logMaliciousRequest(Request $request, string $pattern): void
    {
        $fingerprint = $this->getRequestFingerprint($request, $pattern);

        if ($this->shouldAggregate($fingerprint)) {
            $this->incrementAggregateCount($fingerprint);
            return;
        }

        $count = $this->incrementAggregateCount($fingerprint);

        $this->log->channel('security')->warning('Malicious request detected', [
            'event' => 'malicious_request',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'pattern' => $pattern,
            'request_id' => $request->header('X-Request-ID'),
            'timestamp' => now()->toIso8601String(),
            'count' => $count,
        ]);
    }

    /**
     * Log an IP blocking action
     */
    public function logIpBlocked(string $ip, string $reason, int $duration): void
    {
        $this->log->channel('security')->warning('IP address blocked', [
            'event' => 'ip_blocked',
            'ip' => $ip,
            'reason' => $reason,
            'duration_minutes' => $duration,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a rate limit exceeded event
     */
    public function logRateLimitExceeded(string $ip, int $attempts): void
    {
        $this->log->channel('security')->warning('Rate limit exceeded', [
            'event' => 'rate_limit_exceeded',
            'ip' => $ip,
            'attempts' => $attempts,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a suspicious registration attempt for admin review.
     *
     * @param array<string, mixed> $context
     */
    public function logSuspiciousRegistration(Request $request, ?string $email, string $reason, array $context = []): void
    {
        $emailDomain = is_string($context['email_domain'] ?? null)
            ? $context['email_domain']
            : null;

        $reasons = $context['reasons'] ?? [$reason];
        if (!is_array($reasons)) {
            $reasons = [$reason];
        }

        $this->log->channel('security')->warning('Suspicious registration attempt detected', array_merge([
            'event' => 'suspicious_registration',
            'reason' => $reason,
            'reasons' => array_values(array_unique(array_map('strval', $reasons))),
            'email' => $email,
            'email_domain' => $emailDomain,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->path(),
            'source' => $request->expectsJson() ? 'api' : 'web',
            'request_id' => $request->header('X-Request-ID'),
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    /**
     * Generate a unique fingerprint for the request
     */
    private function getRequestFingerprint(Request $request, string $pattern): string
    {
        return md5($request->ip() . '|' . $request->path() . '|' . $pattern);
    }

    /**
     * Check if this request should be aggregated (already logged recently)
     */
    private function shouldAggregate(string $fingerprint): bool
    {
        $cacheKey = "bot:log_aggregate:{$fingerprint}";
        return $this->cache::has($cacheKey);
    }

    /**
     * Increment the aggregate count for this fingerprint
     */
    private function incrementAggregateCount(string $fingerprint): int
    {
        $cacheKey = "bot:log_aggregate:{$fingerprint}";
        $aggregationMinutes = config('security.bot_protection.logging.aggregation_minutes', 5);

        $count = $this->cache::get($cacheKey, 0) + 1;
        $this->cache::put($cacheKey, $count, now()->addMinutes($aggregationMinutes));

        return $count;
    }
}
