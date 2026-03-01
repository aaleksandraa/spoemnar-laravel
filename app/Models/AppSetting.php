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
}

