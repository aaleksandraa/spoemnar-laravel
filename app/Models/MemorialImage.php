<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorialImage extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'memorial_id',
        'image_url',
        'caption',
        'display_order',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the memorial that owns the image.
     */
    public function memorial()
    {
        return $this->belongsTo(Memorial::class);
    }

    /**
     * Normalize storage URLs to be host-agnostic.
     */
    public function getImageUrlAttribute(?string $value): ?string
    {
        return MediaUrl::normalize($value);
    }
}
