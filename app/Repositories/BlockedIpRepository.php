<?php

namespace App\Repositories;

use App\Models\BlockedIp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BlockedIpRepository
{
    private const CACHE_KEY = 'bot:blocked_ips';
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(private BlockedIp $model) {}

    /**
     * Check if an IP address is blocked
     */
    public function isBlocked(string $ip): bool
    {
        // Check exact match first
        $exactMatch = $this->model->active()
            ->where('ip_address', $ip)
            ->exists();

        if ($exactMatch) {
            return true;
        }

        // Check CIDR ranges
        $cidrRanges = $this->model->active()
            ->where('ip_address', 'like', '%/%')
            ->get();

        foreach ($cidrRanges as $blockedIp) {
            if ($this->matchesCidr($ip, $blockedIp->ip_address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Block an IP address
     */
    public function block(string $ip, string $reason, ?int $duration = null): BlockedIp
    {
        $expiresAt = $duration ? now()->addMinutes($duration) : null;

        $blockedIp = $this->model->updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_at' => now(),
                'expires_at' => $expiresAt,
                'is_auto_blocked' => false,
                'malicious_request_count' => 0,
            ]
        );

        // Invalidate cache when IP is blocked
        $this->invalidateCache();

        return $blockedIp;
    }

    /**
     * Unblock an IP address
     */
    public function unblock(string $ip): bool
    {
        $deleted = $this->model->where('ip_address', $ip)->delete() > 0;

        // Invalidate cache when IP is unblocked
        if ($deleted) {
            $this->invalidateCache();
        }

        return $deleted;
    }

    /**
     * Get all blocked IPs
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get active (non-expired) blocked IPs
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * Remove expired IP blocks
     */
    public function removeExpired(): int
    {
        $count = $this->model->expired()->delete();

        // Invalidate cache when expired IPs are removed
        if ($count > 0) {
            $this->invalidateCache();
        }

        return $count;
    }

    /**
     * Invalidate the blocked IPs cache
     */
    private function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if an IP matches a CIDR range
     */
    private function matchesCidr(string $ip, string $cidr): bool
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
}
