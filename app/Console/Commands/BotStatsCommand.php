<?php

namespace App\Console\Commands;

use App\Services\Security\BotStatisticsService;
use Illuminate\Console\Command;

class BotStatsCommand extends Command
{
    protected $signature = 'bot:stats {--days=7 : Number of days to analyze}';

    protected $description = 'Display bot attack statistics';

    public function handle(BotStatisticsService $stats): int
    {
        $days = (int) $this->option('days');

        try {
            $this->info("Bot Attack Statistics (Last {$days} days)");
            $this->newLine();

            // Overall statistics
            $statistics = $stats->getStatistics($days);

            $this->line('<fg=cyan>Overall Statistics:</>');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Blocked Requests', number_format($statistics['total_blocked_requests'])],
                    ['Unique IP Addresses', number_format($statistics['unique_ip_addresses'])],
                    ['Rate Limit Events', number_format($statistics['rate_limit_events'])],
                    ['IP Block Events', number_format($statistics['ip_block_events'])],
                    ['Period', "{$statistics['start_date']} to {$statistics['end_date']}"],
                ]
            );

            $this->newLine();

            // Top attacking IPs
            $topIps = $stats->getTopAttackingIps(10);

            if ($topIps->isNotEmpty()) {
                $this->line('<fg=cyan>Top 10 Attacking IP Addresses:</>');
                $this->table(
                    ['IP Address', 'Total Requests', 'First Seen', 'Last Seen'],
                    $topIps->map(function ($ip) {
                        return [
                            $ip['ip'],
                            number_format($ip['total_requests']),
                            $ip['first_seen'],
                            $ip['last_seen'],
                        ];
                    })->toArray()
                );
                $this->newLine();
            }

            // Most common patterns
            $commonPatterns = $stats->getMostCommonPatterns(10);

            if ($commonPatterns->isNotEmpty()) {
                $this->line('<fg=cyan>Most Common Attack Patterns:</>');
                $this->table(
                    ['Pattern', 'Total Requests', 'Unique IPs'],
                    $commonPatterns->map(function ($pattern) {
                        return [
                            $pattern['pattern'],
                            number_format($pattern['total_requests']),
                            number_format($pattern['unique_ips']),
                        ];
                    })->toArray()
                );
                $this->newLine();
            }

            // Daily breakdown
            $dailyStats = $stats->getDailyStats($days);

            if ($dailyStats->isNotEmpty()) {
                $this->line('<fg=cyan>Daily Breakdown:</>');
                $this->table(
                    ['Date', 'Requests', 'Unique IPs', 'Rate Limits', 'IP Blocks'],
                    $dailyStats->map(function ($day) {
                        return [
                            $day['date'],
                            number_format($day['total_requests']),
                            number_format($day['unique_ips']),
                            number_format($day['rate_limit_events']),
                            number_format($day['ip_block_events']),
                        ];
                    })->toArray()
                );
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to generate statistics: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
