<?php

namespace Database\Seeders;

use App\Models\HeroSettings;
use Illuminate\Database\Seeder;

class HeroSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if hero settings already exist
        if (HeroSettings::count() > 0) {
            return;
        }

        HeroSettings::create([
            'hero_title' => 'Večno sećanje na vaše voljene',
            'hero_subtitle' => 'Kreirajte digitalni memorial koji čuva uspomene i povezuje porodicu i prijatelje',
            'hero_image_url' => null,
            'cta_button_text' => 'Kreirajte memorial',
            'cta_button_link' => '/register',
            'secondary_button_text' => 'Saznajte više',
            'secondary_button_link' => '#how-it-works',
        ]);
    }
}
