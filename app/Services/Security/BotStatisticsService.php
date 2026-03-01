<?php

namespace App\Services\Security;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class BotStatisticsService
{
    public function __construct(private SecurityLogger $logger) {}

    /**
     * Get overall statistics for the specified number of days
     */
    public function getStatistics(int $days = 7): array
    {
        $logEntries = $this->parseSecurityLog($days);

        $totalBlocked = $logEntries->where('event', 'malicious_request')->sum('count');
        $uniqueIps = $logEntries->where('event', 'malicious_request')->pluck('ip')->unique()->count();
        $totalRateLimited = $logEntries->where('event', 'rate_limit_exceeded')->count();
        $totalIpBlocked = $logEntries->where('event', 'ip_blocked')->count();

        return [
            'total_blocked_requests' => $totalBlocked,
            'unique_ip_addresses' => $uniqueIps,
            'rate_limit_events' => $totalRateLimited,
            'ip_block_events' => $totalIpBlocked,
            'period_days' => $days,
            'start_date' => now()->subDays($days)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ];
    }

    /**
     * Get top attacking IP addresses
     */
    public function getTopAttackingIps(int $limit = 10): Collection
    {
        $logEntries = $this->parseSecurityLog(7);

        return $logEntries
            ->where('event', 'malicious_request')
            ->groupBy('ip')
            ->map(function ($entries) {
                return [
                    'ip' => $entries->first()['ip'],
                    'total_requests' => $entries->sum('count'),
                    'first_seen' => $entries->min('timestamp'),
                    'last_seen' => $entries->max('timestamp'),
                ];
            })
            ->sortByDesc('total_requests')
            ->take($limit)
            ->values();
    }

    /**
     * Get most common attack patterns
     */
    public function getMostCommonPatterns(int $limit = 10): Collection
    {
        $logEntries = $this->parseSecurityLog(7);

        return $logEntries
            ->where('event', 'malicious_request')
            ->whereNotNull('pattern')
            ->groupBy('pattern')
            ->map(function ($entries, $pattern) {
                return [
                    'pattern' => $pattern,
                    'total_requests' => $entries->sum('count'),
                    'unique_ips' => $entries->pluck('ip')->unique()->count(),
                ];
            })
            ->sortByDesc('total_requests')
            ->take($limit)
            ->values();
    }

    /**
     * Get daily statistics breakdown
     */
    public function getDailyStats(int $days = 7): Collection
    {
        $logEntries = $this->parseSecurityLog($days);

        $dailyStats = collect();

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');

            $dayEntries = $logEntries->filter(function ($entry) use ($date) {
                return str_starts_with($entry['timestamp'], $date);
            });

            $dailyStats->push([
                'date' => $date,
                'total_requests' => $dayEntries->where('event', 'malicious_request')->sum('count'),
                'unique_ips' => $dayEntries->where('event', 'malicious_request')->pluck('ip')->unique()->count(),
                'rate_limit_events' => $dayEntries->where('event', 'rate_limit_exceeded')->count(),
                'ip_block_events' => $dayEntries->where('event', 'ip_blocked')->count(),
            ]);
        }

        return $dailyStats->sortBy('date')->values();
    }

    /**
     * Parse security log file and extract relevant entries
     */
    private function parseSecurityLog(int $days): Collection
    {
        $logPath = storage_path('logs/security.log');

        if (!File::exists($logPath)) {
            return collect();
        }

        $cutoffDate = now()->subDays($days);
        $entries = collect();

        try {
            $logContent = File::get($logPath);
            $lines = explode("\n", $logContent);

            foreach ($lines as $line) {
                if (empty(trim($line))) {
                    continue;
                }

                // Parse Laravel log format: [timestamp] environment.LEVEL: message context
                if (preg_match('/\[(.*?)\].*?security\.(WARNING|ERROR|INFO):.*?({.*})/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $contextJson = $matches[3];

                    try {
                        $context = json_decode($contextJson, true);

                        if (!$context || !isset($context['event'])) {
                            continue;
                        }

                        // Check if entry is within the date range
                        $entryDate = \Carbon\Carbon::parse($timestamp);
                        if ($entryDate->lt($cutoffDate)) {
                            continue;
                        }

                        $entries->push([
                            'timestamp' => $timestamp,
                            'event' => $context['event'] ?? null,
                            'ip' => $context['ip'] ?? null,
                            'pattern' => $context['pattern'] ?? null,
                            'count' => $context['count'] ?? 1,
                            'url' => $context['url'] ?? null,
                            'user_agent' => $context['user_agent'] ?? null,
                        ]);
                    } catch (\Exception $e) {
                        // Skip malformed JSON entries
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            // Return empty collection if log file cannot be read
            return collect();
        }

        return $entries;
    }
}
