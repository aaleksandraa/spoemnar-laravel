<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemorialTranslation extends Model
{
    use HasFactory, HasUuids;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'memorial_id',
        'locale',
        'birth_place',
        'death_place',
        'biography',
    ];

    public function memorial()
    {
        return $this->belongsTo(Memorial::class);
    }
}
