<?php

namespace App\Support;

class RegistrationFormProtection
{
    /**
     * @return array{form_rendered_at: int, form_signature: string}
     */
    public static function issue(string $ipAddress, ?int $renderedAt = null): array
    {
        $timestamp = $renderedAt ?? now()->timestamp;

        return [
            'form_rendered_at' => $timestamp,
            'form_signature' => self::sign($timestamp, $ipAddress),
        ];
    }

    public static function sign(int $renderedAt, string $ipAddress): string
    {
        return hash_hmac(
            'sha256',
            $renderedAt.'|'.$ipAddress,
            (string) config('app.key')
        );
    }

    public static function hasValidSignature(int $renderedAt, string $signature, string $ipAddress): bool
    {
        if ($renderedAt <= 0 || $signature === '' || strlen($signature) !== 64) {
            return false;
        }

        return hash_equals(self::sign($renderedAt, $ipAddress), $signature);
    }
}
