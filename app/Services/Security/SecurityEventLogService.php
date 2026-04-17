<?php

namespace App\Services\Security;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SecurityEventLogService
{
    public function getRecentSuspiciousRegistrations(int $days = 14, int $limit = 50): Collection
    {
        return $this->parseSecurityLogs($days)
            ->where('event', 'suspicious_registration')
            ->sortByDesc(static fn (array $entry) => $entry['parsed_timestamp']?->getTimestamp() ?? 0)
            ->take($limit)
            ->map(static function (array $entry): array {
                return [
                    'timestamp' => $entry['timestamp'],
                    'reason' => $entry['reason'] ?? 'unknown',
                    'reasons' => $entry['reasons'] ?? [],
                    'email' => $entry['email'] ?? null,
                    'emailDomain' => $entry['email_domain'] ?? null,
                    'ip' => $entry['ip'] ?? null,
                    'userAgent' => $entry['user_agent'] ?? null,
                    'requestId' => $entry['request_id'] ?? null,
                    'route' => $entry['route'] ?? null,
                    'source' => $entry['source'] ?? null,
                ];
            })
            ->values();
    }

    public function getSuspiciousRegistrationSummary(int $days = 14): array
    {
        $entries = $this->parseSecurityLogs($days)
            ->where('event', 'suspicious_registration')
            ->values();

        $reasonCounts = $entries
            ->groupBy(static fn (array $entry) => (string) ($entry['reason'] ?? 'unknown'))
            ->map(static fn (Collection $group) => $group->count())
            ->all();

        return [
            'total' => $entries->count(),
            'uniqueIps' => $entries->pluck('ip')->filter()->unique()->count(),
            'uniqueEmails' => $entries->pluck('email')->filter()->unique()->count(),
            'reasonCounts' => $reasonCounts,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function parseSecurityLogs(int $days): Collection
    {
        $cutoffDate = now()->subDays($days);
        $entries = collect();

        foreach ($this->resolveLogPaths() as $path) {
            try {
                $lines = preg_split("/\r\n|\n|\r/", (string) File::get($path)) ?: [];
            } catch (\Throwable $exception) {
                continue;
            }

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                if (preg_match('/\[(.*?)\]\s+[^:]+?\.(WARNING|ERROR|INFO):\s*(.*?)\s+(\{.*\})$/', $line, $matches) !== 1) {
                    continue;
                }

                try {
                    $parsedTimestamp = Carbon::parse($matches[1]);
                    if ($parsedTimestamp->lt($cutoffDate)) {
                        continue;
                    }

                    $context = json_decode($matches[4], true, flags: JSON_THROW_ON_ERROR);
                    if (!is_array($context) || !isset($context['event'])) {
                        continue;
                    }

                    $entries->push(array_merge($context, [
                        'message' => trim($matches[3]),
                        'timestamp' => $parsedTimestamp->toIso8601String(),
                        'parsed_timestamp' => $parsedTimestamp,
                    ]));
                } catch (\Throwable $exception) {
                    continue;
                }
            }
        }

        return $entries;
    }

    /**
     * @return array<int, string>
     */
    private function resolveLogPaths(): array
    {
        $dailyFiles = glob(storage_path('logs/security-*.log')) ?: [];
        $fallbackFile = storage_path('logs/security.log');
        if (File::exists($fallbackFile)) {
            $dailyFiles[] = $fallbackFile;
        }

        $normalizedPaths = array_values(array_unique(array_filter($dailyFiles, 'is_string')));
        rsort($normalizedPaths);

        return $normalizedPaths;
    }
}
