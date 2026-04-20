<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorialCandle extends Model
{
    use HasFactory, HasUuids;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const TYPE_MEMORY = 'memory';
    public const TYPE_FAMILY = 'family';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'memorial_id',
        'user_id',
        'display_name',
        'message',
        'is_anonymous',
        'visitor_hash',
        'candle_type',
        'is_premium',
        'status',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'is_premium' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function memorial()
    {
        return $this->belongsTo(Memorial::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $builder): void {
                $builder
                    ->where('is_premium', true)
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_premium', false)
            ->where('expires_at', '<=', now());
    }

    public function scopeMemory(Builder $query): Builder
    {
        return $query->where('candle_type', self::TYPE_MEMORY);
    }

    public function scopeFamily(Builder $query): Builder
    {
        return $query->where('candle_type', self::TYPE_FAMILY);
    }
}
