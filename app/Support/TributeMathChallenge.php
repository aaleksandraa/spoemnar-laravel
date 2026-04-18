<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class TributeMathChallenge
{
    /**
     * @return array{payload: string, image_data_uri: string}
     */
    public static function issue(string $memorialId, string $sessionId, int $formRenderedAt): array
    {
        $operators = ['+', '-'];
        $operator = $operators[random_int(0, count($operators) - 1)];
        $left = random_int(2, 12);
        $right = random_int(1, 9);

        if ($operator === '-' && $right > $left) {
            [$left, $right] = [$right, $left];
        }

        $answer = $operator === '+' ? $left + $right : $left - $right;
        $expression = sprintf('%d %s %d = ?', $left, $operator, $right);

        $payload = Crypt::encryptString((string) json_encode([
            'left' => $left,
            'right' => $right,
            'operator' => $operator,
            'answer' => $answer,
            'memorial_id' => $memorialId,
            'session_id' => $sessionId,
            'form_rendered_at' => $formRenderedAt,
        ], JSON_THROW_ON_ERROR));

        return [
            'payload' => $payload,
            'image_data_uri' => 'data:image/svg+xml;base64,'.base64_encode(self::renderSvg($expression, $left, $right)),
        ];
    }

    public static function verify(
        string $payload,
        string $providedAnswer,
        string $memorialId,
        string $sessionId,
        int $formRenderedAt
    ): bool {
        try {
            $decoded = json_decode(Crypt::decryptString($payload), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException $exception) {
            return false;
        }

        if (!is_array($decoded)) {
            return false;
        }

        $expectedMemorialId = (string) ($decoded['memorial_id'] ?? '');
        $expectedSessionId = (string) ($decoded['session_id'] ?? '');
        $expectedRenderedAt = (int) ($decoded['form_rendered_at'] ?? 0);
        $expectedAnswer = (string) ($decoded['answer'] ?? '');

        if (
            $expectedMemorialId === ''
            || $expectedSessionId === ''
            || $expectedRenderedAt <= 0
            || !hash_equals($expectedMemorialId, $memorialId)
            || !hash_equals($expectedSessionId, $sessionId)
            || $expectedRenderedAt !== $formRenderedAt
        ) {
            return false;
        }

        $normalizedAnswer = preg_replace('/\s+/', '', $providedAnswer);
        if (!is_string($normalizedAnswer) || $normalizedAnswer === '' || preg_match('/^-?\d+$/', $normalizedAnswer) !== 1) {
            return false;
        }

        return hash_equals($expectedAnswer, ltrim($normalizedAnswer, '+'));
    }

    private static function renderSvg(string $expression, int $left, int $right): string
    {
        $noiseStrokeOne = sprintf(
            'M 12 %d C 48 %d, 98 %d, 168 %d',
            16 + ($left % 10),
            6 + ($right % 12),
            44 - ($left % 9),
            18 + (($left + $right) % 18)
        );
        $noiseStrokeTwo = sprintf(
            'M 14 %d C 52 %d, 108 %d, 170 %d',
            36 + ($right % 9),
            18 + ($left % 11),
            20 + (($left * 2) % 14),
            42 - (($left + $right) % 11)
        );
        $rotation = ($left - $right) % 7 - 3;
        $safeExpression = e($expression);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="58" viewBox="0 0 180 58" role="img" aria-hidden="true">
  <rect width="180" height="58" rx="12" fill="#f7efe3"/>
  <rect x="1.5" y="1.5" width="177" height="55" rx="10.5" fill="none" stroke="#d6c3aa" stroke-width="1.5"/>
  <path d="{$noiseStrokeOne}" fill="none" stroke="#d8b98f" stroke-width="1.5" stroke-linecap="round" opacity="0.8"/>
  <path d="{$noiseStrokeTwo}" fill="none" stroke="#b88b5e" stroke-width="1.2" stroke-linecap="round" opacity="0.65"/>
  <text x="90" y="36" text-anchor="middle" font-family="Georgia, serif" font-size="27" letter-spacing="2" fill="#4f3422" transform="rotate({$rotation} 90 29)">{$safeExpression}</text>
</svg>
SVG;
    }
}
