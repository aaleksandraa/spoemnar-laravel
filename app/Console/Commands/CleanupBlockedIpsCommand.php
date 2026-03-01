<?php

namespace App\Console\Commands;

use App\Repositories\BlockedIpRepository;
use Illuminate\Console\Command;

class CleanupBlockedIpsCommand extends Command
{
    protected $signature = 'bot:cleanup';

    protected $description = 'Remove expired IP blocks';

    public function handle(BlockedIpRepository $repository): int
    {
        try {
            $this->info('Cleaning up expired IP blocks...');

            $removedCount = $repository->removeExpired();

            if ($removedCount > 0) {
                $this->info("Successfully removed {$removedCount} expired IP block(s).");
            } else {
                $this->info('No expired IP blocks found.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup expired IPs: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
