<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    /**
     * @return array<string, bool>
     */
    public static function defaultBooleanSettings(): array
    {
        return [
            'card_payment' => true,
            'paypal_payment' => true,
            'physical_qr_delivery' => false,
            'paid_memorials' => false,
            'memorial_candles_enabled' => true,
            'memorial_candles_allow_anonymous' => true,
            'memorial_candles_show_countdown' => true,
            'memorial_candles_show_recent_lighters' => true,
            'memorial_candles_messages_enabled' => true,
            'memorial_candles_show_wall' => true,
            'memorial_candles_family_enabled' => true,
            'memorial_candles_premium_enabled' => true,
            'memorial_candles_anniversary_highlights_enabled' => true,
        ];
    }

    public static function ensureDefaultBooleanSettings(): void
    {
        foreach (self::defaultBooleanSettings() as $key => $defaultValue) {
            self::firstOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $defaultValue ? '1' : '0']
            );
        }
    }

    public static function getBoolean(string $key): bool
    {
        $defaults = self::defaultBooleanSettings();
        $defaultValue = $defaults[$key] ?? false;

        $setting = self::query()
            ->where('setting_key', $key)
            ->first();

        if (!$setting) {
            return $defaultValue;
        }

        return self::isTruthyValue($setting->setting_value);
    }

    private static function isTruthyValue(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
