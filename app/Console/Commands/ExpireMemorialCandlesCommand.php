<?php

namespace App\Console\Commands;

use App\Services\MemorialCandleService;
use Illuminate\Console\Command;

class ExpireMemorialCandlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'memorial-candles:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire memorial candles whose seven-day visibility window has ended.';

    public function handle(MemorialCandleService $memorialCandleService): int
    {
        $expiredCount = $memorialCandleService->expireStale();

        $this->info("Expired {$expiredCount} memorial candles.");

        return self::SUCCESS;
    }
}
