<?php

namespace App\Http\Middleware;

use App\Repositories\BlockedIpRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IpBlocker
{
    public function __construct(
        private BlockedIpRepository $blockedIpRepository,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check whitelist first - whitelisted IPs bypass all checks
        if ($this->isIpWhitelisted($ip)) {
            return $next($request);
        }

        // Check if IP is blocked
        if ($this->isIpBlocked($ip)) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if an IP address is blocked
     */
    private function isIpBlocked(string $ip): bool
    {
        $blockedIps = $this->getBlockedIpList();

        // Check exact match
        if ($blockedIps->contains('ip_address', $ip)) {
            return true;
        }

        // Check CIDR ranges
        $cidrRanges = $blockedIps->filter(function ($blockedIp) {
            return str_contains($blockedIp->ip_address, '/');
        });

        foreach ($cidrRanges as $blockedIp) {
            if ($this->matchesCidrRange($ip, $blockedIp->ip_address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address is whitelisted
     */
    private function isIpWhitelisted(string $ip): bool
    {
        $whitelist = config('security.bot_protection.whitelist', []);

        // Check exact match
        if (in_array($ip, $whitelist)) {
            return true;
        }

        // Check CIDR ranges in whitelist
        foreach ($whitelist as $whitelistedIp) {
            if (str_contains($whitelistedIp, '/') && $this->matchesCidrRange($ip, $whitelistedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP matches a CIDR range
     */
    private function matchesCidrRange(string $ip, string $cidr): bool
    {
        // Parse CIDR notation
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $mask] = explode('/', $cidr);

        // Convert IP addresses to long integers for comparison
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        // Handle invalid IP addresses
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // Calculate the network mask
        $maskLong = -1 << (32 - (int)$mask);

        // Check if IP is in the subnet
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Get the list of blocked IPs with caching
     */
    private function getBlockedIpList(): Collection
    {
        return Cache::remember('bot:blocked_ips', 300, function () {
            return $this->blockedIpRepository->getActive();
        });
    }
}
