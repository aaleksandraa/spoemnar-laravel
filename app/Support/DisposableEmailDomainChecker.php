<?php

namespace App\Support;

class DisposableEmailDomainChecker
{
    public static function isDisposableEmail(string $email): bool
    {
        $domain = self::extractDomain($email);

        if ($domain === null) {
            return false;
        }

        foreach (self::blockedDomains() as $blockedDomain) {
            if ($domain === $blockedDomain || str_ends_with($domain, '.'.$blockedDomain)) {
                return true;
            }
        }

        return false;
    }

    public static function extractDomain(string $email): ?string
    {
        $normalizedEmail = mb_strtolower(trim($email), 'UTF-8');
        if ($normalizedEmail === '' || !str_contains($normalizedEmail, '@')) {
            return null;
        }

        $domain = substr(strrchr($normalizedEmail, '@') ?: '', 1);
        $domain = trim((string) $domain, " \t\n\r\0\x0B.");

        return $domain !== '' ? $domain : null;
    }

    /**
     * @return array<int, string>
     */
    public static function blockedDomains(): array
    {
        $configuredDomains = config('security.registration.disposable_email_domains', []);
        $additionalDomains = explode(',', (string) env('REGISTRATION_DISPOSABLE_EMAIL_DOMAINS', ''));

        $domains = array_merge(
            is_array($configuredDomains) ? $configuredDomains : [],
            $additionalDomains
        );

        $normalized = [];
        foreach ($domains as $domain) {
            if (!is_string($domain)) {
                continue;
            }

            $candidate = mb_strtolower(trim($domain), 'UTF-8');
            $candidate = trim($candidate, '.');

            if ($candidate !== '') {
                $normalized[] = $candidate;
            }
        }

        return array_values(array_unique($normalized));
    }
}
