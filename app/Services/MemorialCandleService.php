<?php

namespace App\Services;

use App\Exceptions\MemorialCandleException;
use App\Models\AppSetting;
use App\Models\Memorial;
use App\Models\MemorialCandle;
use App\Models\User;
use App\Support\LocaleResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class MemorialCandleService
{
    public const DURATION_HOURS = 168;

    private const MAX_RECENT_LIGHTERS = 8;
    private const MAX_WALL_ITEMS = 18;

    /**
     * @return array<string, bool>
     */
    public function settings(): array
    {
        return [
            'enabled' => AppSetting::getBoolean('memorial_candles_enabled'),
            'allow_anonymous' => AppSetting::getBoolean('memorial_candles_allow_anonymous'),
            'show_countdown' => AppSetting::getBoolean('memorial_candles_show_countdown'),
            'show_recent_lighters' => AppSetting::getBoolean('memorial_candles_show_recent_lighters'),
            'messages_enabled' => AppSetting::getBoolean('memorial_candles_messages_enabled'),
            'show_wall' => AppSetting::getBoolean('memorial_candles_show_wall'),
            'family_enabled' => AppSetting::getBoolean('memorial_candles_family_enabled'),
            'premium_enabled' => AppSetting::getBoolean('memorial_candles_premium_enabled'),
            'anniversary_highlights_enabled' => AppSetting::getBoolean('memorial_candles_anniversary_highlights_enabled'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(
        Memorial $memorial,
        ?User $viewer = null,
        ?Request $request = null,
        ?string $locale = null,
    ): array {
        $resolvedLocale = $this->resolveLocale($locale);
        $settings = $this->settings();

        $this->expireStale($memorial);

        $activeCandle = $memorial->candles()
            ->memory()
            ->active()
            ->latest('created_at')
            ->first();

        $familyCandle = $settings['family_enabled']
            ? $memorial->candles()
                ->family()
                ->active()
                ->latest('updated_at')
                ->first()
            : null;

        $totalCandles = $memorial->candles()->count();
        $recentLighters = $settings['show_recent_lighters']
            ? $memorial->candles()
                ->memory()
                ->latest('created_at')
                ->limit(self::MAX_RECENT_LIGHTERS)
                ->get()
            : collect();
        $wallCandles = $settings['show_wall']
            ? $memorial->candles()
                ->orderByRaw("CASE WHEN candle_type = 'family' THEN 0 ELSE 1 END ASC")
                ->latest('created_at')
                ->limit(self::MAX_WALL_ITEMS)
                ->get()
            : collect();

        $requiresAuth = !$settings['allow_anonymous'] && !$viewer;
        $hasVisibleCandle = (bool) ($activeCandle || $familyCandle);

        return [
            'enabled' => $settings['enabled'],
            'state' => !$settings['enabled']
                ? 'disabled'
                : ($hasVisibleCandle ? 'active' : 'inactive'),
            'isActive' => $hasVisibleCandle,
            'canLight' => $settings['enabled'] && !$activeCandle && !$requiresAuth,
            'reason' => $this->resolveSummaryReason($settings['enabled'], (bool) $activeCandle, $requiresAuth),
            'totalCandles' => $totalCandles,
            'activeCandle' => $activeCandle
                ? $this->transformCandle($activeCandle, $resolvedLocale)
                : null,
            'familyCandle' => $familyCandle
                ? $this->transformCandle($familyCandle, $resolvedLocale)
                : null,
            'recentLighters' => $this->transformRecentLighters($recentLighters, $resolvedLocale),
            'wallCandles' => $this->transformWallCandles($wallCandles, $resolvedLocale),
            'anniversary' => $settings['anniversary_highlights_enabled']
                ? $this->resolveAnniversaryHighlight($memorial, $resolvedLocale)
                : null,
            'settings' => [
                'allowAnonymous' => $settings['allow_anonymous'],
                'showCountdown' => $settings['show_countdown'],
                'showRecentLighters' => $settings['show_recent_lighters'],
                'messagesEnabled' => $settings['messages_enabled'],
                'showWall' => $settings['show_wall'],
                'familyEnabled' => $settings['family_enabled'],
                'premiumEnabled' => $settings['premium_enabled'],
                'showAnniversaryHighlights' => $settings['anniversary_highlights_enabled'],
            ],
        ];
    }

    public function light(
        Memorial $memorial,
        ?User $viewer,
        Request $request,
        ?string $locale = null,
        ?string $message = null,
    ): MemorialCandle {
        $resolvedLocale = $this->resolveLocale($locale);
        $settings = $this->settings();

        if (!$settings['enabled']) {
            throw new MemorialCandleException(
                Lang::get('ui.memorial.candle.messages.disabled', [], $resolvedLocale),
                422
            );
        }

        if (!$viewer && !$settings['allow_anonymous']) {
            throw new MemorialCandleException(
                Lang::get('ui.memorial.candle.messages.login_required', [], $resolvedLocale),
                401
            );
        }

        $normalizedMessage = $settings['messages_enabled']
            ? $this->normalizeMessage($message)
            : null;

        $this->expireStale($memorial);

        return DB::transaction(function () use ($memorial, $viewer, $request, $resolvedLocale, $normalizedMessage): MemorialCandle {
            Memorial::query()
                ->whereKey($memorial->getKey())
                ->lockForUpdate()
                ->first();

            $this->expireStale($memorial);

            $activeCandle = $memorial->candles()
                ->memory()
                ->active()
                ->latest('created_at')
                ->first();

            if ($activeCandle) {
                throw new MemorialCandleException(
                    Lang::get('ui.memorial.candle.messages.already_active', [], $resolvedLocale),
                    409
                );
            }

            [$userId, $displayName, $isAnonymous, $visitorHash] = $this->resolveActor($viewer, $request, $resolvedLocale);

            return $memorial->candles()->create([
                'user_id' => $userId,
                'display_name' => $displayName,
                'message' => $normalizedMessage,
                'is_anonymous' => $isAnonymous,
                'visitor_hash' => $visitorHash,
                'candle_type' => MemorialCandle::TYPE_MEMORY,
                'is_premium' => false,
                'status' => MemorialCandle::STATUS_ACTIVE,
                'expires_at' => now()->addHours(self::DURATION_HOURS),
            ]);
        });
    }

    public function upsertFamilyCandle(
        Memorial $memorial,
        User $actor,
        ?string $message = null,
        bool $isPremium = false,
        ?string $locale = null,
    ): MemorialCandle {
        $resolvedLocale = $this->resolveLocale($locale);
        $settings = $this->settings();

        if (!$settings['enabled'] || !$settings['family_enabled']) {
            throw new MemorialCandleException(
                Lang::get('ui.memorial.candle.messages.family_unavailable', [], $resolvedLocale),
                422
            );
        }

        if ($isPremium && !$settings['premium_enabled']) {
            throw new MemorialCandleException(
                Lang::get('ui.memorial.candle.messages.premium_unavailable', [], $resolvedLocale),
                422
            );
        }

        $normalizedMessage = $settings['messages_enabled']
            ? $this->normalizeMessage($message)
            : null;
        $premiumEnabled = $settings['premium_enabled'] && $isPremium;
        $displayName = $this->resolveOwnerDisplayName($actor, $resolvedLocale);

        $this->expireStale($memorial);

        return DB::transaction(function () use ($memorial, $actor, $displayName, $normalizedMessage, $premiumEnabled): MemorialCandle {
            Memorial::query()
                ->whereKey($memorial->getKey())
                ->lockForUpdate()
                ->first();

            $this->expireStale($memorial);

            $familyCandle = $memorial->candles()
                ->family()
                ->latest('created_at')
                ->first();

            $payload = [
                'user_id' => (string) $actor->id,
                'display_name' => $displayName,
                'message' => $normalizedMessage,
                'is_anonymous' => false,
                'visitor_hash' => null,
                'candle_type' => MemorialCandle::TYPE_FAMILY,
                'is_premium' => $premiumEnabled,
                'status' => MemorialCandle::STATUS_ACTIVE,
                'expires_at' => $premiumEnabled ? now()->addYears(100) : now()->addHours(self::DURATION_HOURS),
            ];

            if ($familyCandle) {
                $familyCandle->forceFill(array_merge($payload, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]))->save();

                return $familyCandle->fresh();
            }

            return $memorial->candles()->create($payload);
        });
    }

    public function removeFamilyCandle(
        Memorial $memorial,
        ?string $locale = null,
    ): bool {
        $resolvedLocale = $this->resolveLocale($locale);
        $settings = $this->settings();

        if (!$settings['family_enabled']) {
            throw new MemorialCandleException(
                Lang::get('ui.memorial.candle.messages.family_unavailable', [], $resolvedLocale),
                422
            );
        }

        return DB::transaction(function () use ($memorial, $resolvedLocale): bool {
            Memorial::query()
                ->whereKey($memorial->getKey())
                ->lockForUpdate()
                ->first();

            $familyCandle = $memorial->candles()
                ->family()
                ->active()
                ->latest('updated_at')
                ->first();

            if (!$familyCandle) {
                throw new MemorialCandleException(
                    Lang::get('ui.memorial.candle.messages.family_not_found', [], $resolvedLocale),
                    404
                );
            }

            $familyCandle->forceFill([
                'status' => MemorialCandle::STATUS_EXPIRED,
                'expires_at' => now(),
                'updated_at' => now(),
            ])->save();

            return true;
        });
    }

    public function expireStale(?Memorial $memorial = null): int
    {
        $query = MemorialCandle::query()->stale();

        if ($memorial) {
            $query->where('memorial_id', $memorial->getKey());
        }

        return $query->update([
            'status' => MemorialCandle::STATUS_EXPIRED,
            'updated_at' => now(),
        ]);
    }

    private function resolveSummaryReason(bool $enabled, bool $hasActiveCandle, bool $requiresAuth): string
    {
        if (!$enabled) {
            return 'module_disabled';
        }

        if ($hasActiveCandle) {
            return 'active';
        }

        if ($requiresAuth) {
            return 'auth_required';
        }

        return 'ready';
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCandle(MemorialCandle $candle, string $locale): array
    {
        $secondsRemaining = $this->secondsRemaining($candle->expires_at);

        return [
            'id' => $candle->id,
            'lighterName' => $this->resolveDisplayName($candle, $locale),
            'message' => $candle->message,
            'type' => $candle->candle_type,
            'isFamily' => $candle->candle_type === MemorialCandle::TYPE_FAMILY,
            'isPremium' => $candle->is_premium,
            'isPermanent' => $candle->is_premium,
            'isAnonymous' => $candle->is_anonymous,
            'litAt' => $candle->created_at?->toISOString(),
            'expiresAt' => $candle->expires_at?->toISOString(),
            'secondsRemaining' => $candle->is_premium ? 0 : $secondsRemaining,
            'status' => $candle->status,
        ];
    }

    /**
     * @param Collection<int, MemorialCandle> $candles
     * @return array<int, array<string, mixed>>
     */
    private function transformRecentLighters(Collection $candles, string $locale): array
    {
        return $candles
            ->map(fn (MemorialCandle $candle): array => [
                'id' => $candle->id,
                'lighterName' => $this->resolveDisplayName($candle, $locale),
                'message' => $candle->message,
                'isAnonymous' => $candle->is_anonymous,
                'litAt' => $candle->created_at?->toISOString(),
                'expiresAt' => $candle->expires_at?->toISOString(),
                'status' => $candle->status,
            ])
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, MemorialCandle> $candles
     * @return array<int, array<string, mixed>>
     */
    private function transformWallCandles(Collection $candles, string $locale): array
    {
        return $candles
            ->map(fn (MemorialCandle $candle): array => [
                'id' => $candle->id,
                'lighterName' => $this->resolveDisplayName($candle, $locale),
                'message' => $candle->message,
                'type' => $candle->candle_type,
                'isFamily' => $candle->candle_type === MemorialCandle::TYPE_FAMILY,
                'isPremium' => $candle->is_premium,
                'isPermanent' => $candle->is_premium,
                'isActive' => $candle->status === MemorialCandle::STATUS_ACTIVE
                    && ($candle->is_premium || ($candle->expires_at && $candle->expires_at->isFuture())),
                'litAt' => $candle->created_at?->toISOString(),
                'expiresAt' => $candle->expires_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveAnniversaryHighlight(Memorial $memorial, string $locale): ?array
    {
        $today = now();
        $deathDate = $memorial->death_date instanceof CarbonInterface
            ? $memorial->death_date->copy()
            : ($memorial->death_date ? Carbon::parse($memorial->death_date) : null);
        $birthDate = $memorial->birth_date instanceof CarbonInterface
            ? $memorial->birth_date->copy()
            : ($memorial->birth_date ? Carbon::parse($memorial->birth_date) : null);

        if ($deathDate && (int) $deathDate->month === (int) $today->month && (int) $deathDate->day === (int) $today->day) {
            $years = $deathDate->diffInYears($today);

            return [
                'type' => 'death_anniversary',
                'headline' => Lang::get('ui.memorial.candle.anniversary.death_headline', [], $locale),
                'description' => Lang::get('ui.memorial.candle.anniversary.death_body', [
                    'years' => $years,
                    'name' => trim($memorial->first_name.' '.$memorial->last_name),
                ], $locale),
            ];
        }

        if ($birthDate && (int) $birthDate->month === (int) $today->month && (int) $birthDate->day === (int) $today->day) {
            $age = $birthDate->diffInYears($today);

            return [
                'type' => 'birth_remembrance',
                'headline' => Lang::get('ui.memorial.candle.anniversary.birth_headline', [], $locale),
                'description' => Lang::get('ui.memorial.candle.anniversary.birth_body', [
                    'age' => $age,
                    'name' => trim($memorial->first_name.' '.$memorial->last_name),
                ], $locale),
            ];
        }

        return null;
    }

    private function resolveLocale(?string $locale): string
    {
        $normalizedLocale = LocaleResolver::normalizeLocale((string) $locale);

        if (LocaleResolver::isSupported($normalizedLocale)) {
            return $normalizedLocale;
        }

        $appLocale = LocaleResolver::normalizeLocale((string) app()->getLocale());

        return LocaleResolver::isSupported($appLocale) ? $appLocale : 'bs';
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: bool, 3: string|null}
     */
    private function resolveActor(?User $viewer, Request $request, string $locale): array
    {
        if ($viewer) {
            $viewer->loadMissing('profile');

            $displayName = trim((string) ($viewer->profile?->full_name ?: $viewer->email ?: ''));
            if ($displayName === '') {
                $displayName = Lang::get('ui.memorial.candle.registered_visitor', [], $locale);
            }

            return [(string) $viewer->id, $displayName, false, null];
        }

        return [
            null,
            null,
            true,
            hash_hmac(
                'sha256',
                (string) ($request->ip() ?: 'unknown').'|'.(string) ($request->userAgent() ?: 'unknown'),
                (string) config('app.key')
            ),
        ];
    }

    private function resolveOwnerDisplayName(User $actor, string $locale): string
    {
        $actor->loadMissing('profile');

        $displayName = trim((string) ($actor->profile?->full_name ?: $actor->email ?: ''));

        return $displayName !== ''
            ? $displayName
            : Lang::get('ui.memorial.candle.registered_visitor', [], $locale);
    }

    private function resolveDisplayName(MemorialCandle $candle, string $locale): string
    {
        if ($candle->is_anonymous) {
            return Lang::get('ui.memorial.candle.anonymous_visitor', [], $locale);
        }

        $displayName = trim((string) $candle->display_name);

        return $displayName !== ''
            ? $displayName
            : Lang::get('ui.memorial.candle.registered_visitor', [], $locale);
    }

    private function secondsRemaining(?CarbonInterface $expiresAt): int
    {
        if (!$expiresAt) {
            return 0;
        }

        return max(0, now()->diffInSeconds($expiresAt, false));
    }

    private function normalizeMessage(?string $message): ?string
    {
        if (!is_string($message)) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/u', ' ', strip_tags($message)) ?? '');

        return $normalized !== '' ? $normalized : null;
    }
}
