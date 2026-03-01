<?php

namespace App\Console\Commands;

use App\Repositories\BlockedIpRepository;
use Illuminate\Console\Command;

class BlockIpCommand extends Command
{
    protected $signature = 'bot:block {ip} {--reason= : Reason for blocking the IP} {--duration= : Duration in minutes (leave empty for permanent)}';

    protected $description = 'Block an IP address';

    public function handle(BlockedIpRepository $repository): int
    {
        $ip = $this->argument('ip');
        $reason = $this->option('reason') ?? 'Manually blocked';
        $duration = $this->option('duration') ? (int) $this->option('duration') : null;

        // Validate IP address format (IPv4, IPv6, or CIDR notation)
        if (!$this->isValidIp($ip)) {
            $this->error("Invalid IP address format: {$ip}");
            return self::FAILURE;
        }

        try {
            $blockedIp = $repository->block($ip, $reason, $duration);

            if ($duration) {
                $this->info("Successfully blocked IP: {$ip} for {$duration} minutes");
                $this->line("Reason: {$reason}");
                $this->line("Expires at: {$blockedIp->expires_at}");
            } else {
                $this->info("Successfully blocked IP: {$ip} permanently");
                $this->line("Reason: {$reason}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to block IP: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Validate IP address format (IPv4, IPv6, or CIDR notation)
     */
    private function isValidIp(string $ip): bool
    {
        // Check for CIDR notation
        if (str_contains($ip, '/')) {
            [$address, $mask] = explode('/', $ip);

            // Validate IPv4 CIDR
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $maskInt = (int) $mask;
                return $maskInt >= 0 && $maskInt <= 32;
            }

            // Validate IPv6 CIDR
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $maskInt = (int) $mask;
                return $maskInt >= 0 && $maskInt <= 128;
            }

            return false;
        }

        // Validate regular IPv4 or IPv6 address
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
