<?php

namespace App\Console\Commands;

use App\Repositories\BlockedIpRepository;
use Illuminate\Console\Command;

class ListBlockedIpsCommand extends Command
{
    protected $signature = 'bot:list {--active : Show only active (non-expired) blocks}';

    protected $description = 'List blocked IP addresses';

    public function handle(BlockedIpRepository $repository): int
    {
        $activeOnly = $this->option('active');

        try {
            $blockedIps = $activeOnly ? $repository->getActive() : $repository->getAll();

            if ($blockedIps->isEmpty()) {
                $this->info('No blocked IP addresses found.');
                return self::SUCCESS;
            }

            $this->info($activeOnly ? 'Active Blocked IP Addresses:' : 'All Blocked IP Addresses:');
            $this->newLine();

            $tableData = $blockedIps->map(function ($blockedIp) {
                return [
                    'IP Address' => $blockedIp->ip_address,
                    'Reason' => $blockedIp->reason ?? 'N/A',
                    'Blocked At' => $blockedIp->blocked_at->format('Y-m-d H:i:s'),
                    'Expires At' => $blockedIp->expires_at ? $blockedIp->expires_at->format('Y-m-d H:i:s') : 'Never',
                    'Auto-Blocked' => $blockedIp->is_auto_blocked ? 'Yes' : 'No',
                    'Request Count' => $blockedIp->malicious_request_count,
                ];
            })->toArray();

            $this->table(
                ['IP Address', 'Reason', 'Blocked At', 'Expires At', 'Auto-Blocked', 'Request Count'],
                $tableData
            );

            $this->newLine();
            $this->info("Total: {$blockedIps->count()} blocked IP(s)");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to list blocked IPs: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
