<?php

namespace App\Console\Commands;

use App\Repositories\BlockedIpRepository;
use Illuminate\Console\Command;

class UnblockIpCommand extends Command
{
    protected $signature = 'bot:unblock {ip}';

    protected $description = 'Unblock an IP address';

    public function handle(BlockedIpRepository $repository): int
    {
        $ip = $this->argument('ip');

        try {
            $result = $repository->unblock($ip);

            if ($result) {
                $this->info("Successfully unblocked IP: {$ip}");
                return self::SUCCESS;
            } else {
                $this->warn("IP address not found in block list: {$ip}");
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("Failed to unblock IP: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
