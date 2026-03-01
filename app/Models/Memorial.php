<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memorial extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'death_date',
        'birth_country_id',
        'birth_place_id',
        'birth_place',
        'death_country_id',
        'death_place_id',
        'death_place',
        'biography',
        'profile_image_url',
        'slug',
        'is_public',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'birth_country_id' => 'integer',
            'birth_place_id' => 'integer',
            'death_country_id' => 'integer',
            'death_place_id' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the memorial.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the images for the memorial.
     */
    public function images()
    {
        return $this->hasMany(MemorialImage::class)->orderBy('display_order');
    }

    /**
     * Get the videos for the memorial.
     */
    public function videos()
    {
        return $this->hasMany(MemorialVideo::class)->orderBy('display_order');
    }

    /**
     * Get the tributes for the memorial.
     */
    public function tributes()
    {
        return $this->hasMany(Tribute::class);
    }

    public function birthCountry()
    {
        return $this->belongsTo(Country::class, 'birth_country_id');
    }

    public function deathCountry()
    {
        return $this->belongsTo(Country::class, 'death_country_id');
    }

    public function birthPlaceEntity()
    {
        return $this->belongsTo(Place::class, 'birth_place_id');
    }

    public function deathPlaceEntity()
    {
        return $this->belongsTo(Place::class, 'death_place_id');
    }

    /**
     * Normalize storage URLs to be host-agnostic.
     */
    public function getProfileImageUrlAttribute(?string $value): ?string
    {
        return MediaUrl::normalize($value);
    }

    /**
     * Generate a unique slug from first name and last name.
     *
     * @param string $firstName
     * @param string $lastName
     * @return string
     */
    public static function generateSlug(string $firstName, string $lastName): string
    {
        // Concatenate first name and last name with a dot
        $slug = $firstName . '.' . $lastName;

        // Convert to lowercase first
        $slug = mb_strtolower($slug, 'UTF-8');

        // Transliterate Cyrillic characters to Latin
        $cyrillicToLatin = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'e', 'ж' => 'z', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'c',
            'ш' => 's', 'щ' => 's', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'ju', 'я' => 'ja',
        ];
        $slug = str_replace(array_keys($cyrillicToLatin), array_values($cyrillicToLatin), $slug);

        // Transliterate special Balkan characters (before normalization)
        $balkanTransliterations = [
            'đ' => 'dj', 'ž' => 'z', 'š' => 's', 'č' => 'c', 'ć' => 'c',
        ];
        $slug = str_replace(array_keys($balkanTransliterations), array_values($balkanTransliterations), $slug);

        // Normalize Unicode (NFD - Canonical Decomposition) to separate base characters from diacritics
        if (class_exists('Normalizer')) {
            $slug = \Normalizer::normalize($slug, \Normalizer::NFD);
        }

        // Remove diacritics (combining marks) - this handles ø, é, ñ, etc.
        $slug = preg_replace('/[\x{0300}-\x{036f}]/u', '', $slug);

        // Additional transliterations for characters that don't decompose well
        $additionalTransliterations = [
            'ø' => 'o', 'æ' => 'ae', 'å' => 'a',
            'ß' => 'ss', 'ð' => 'd', 'þ' => 'th',
        ];
        $slug = str_replace(array_keys($additionalTransliterations), array_values($additionalTransliterations), $slug);

        // Replace non-alphanumeric characters with dots
        $slug = preg_replace('/[^a-z0-9.]+/', '.', $slug);

        // Remove consecutive dots
        $slug = preg_replace('/\.+/', '.', $slug);

        // Trim leading and trailing dots
        $slug = trim($slug, '.');

        return $slug;
    }

}
