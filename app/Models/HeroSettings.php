<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSettings extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'hero_image_url',
        'cta_button_text',
        'cta_button_link',
        'secondary_button_text',
        'secondary_button_link',
    ];

    /**
     * Validation rules for hero settings.
     *
     * @return array<string, string>
     */
    public static function validationRules(): array
    {
        return [
            'hero_title' => 'required|string|max:255',
            'hero_subtitle' => 'required|string|max:500',
            'hero_image_url' => 'nullable|url|max:500',
            'cta_button_text' => 'required|string|max:100',
            'cta_button_link' => 'required|string|max:255',
            'secondary_button_text' => 'required|string|max:100',
            'secondary_button_link' => 'required|string|max:255',
        ];
    }

    /**
     * Get the singleton hero settings instance.
     *
     * @return self
     */
    public static function get(): self
    {
        $settings = self::first();

        if (!$settings) {
            $settings = self::create([
                'hero_title' => 'Default Title',
                'hero_subtitle' => 'Default Subtitle',
                'cta_button_text' => 'Get Started',
                'cta_button_link' => '/register',
                'secondary_button_text' => 'Learn More',
                'secondary_button_link' => '#features',
            ]);
        }

        return $settings;
    }
}
