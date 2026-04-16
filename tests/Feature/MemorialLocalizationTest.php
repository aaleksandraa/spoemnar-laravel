<?php

namespace Tests\Feature;

use App\Models\Memorial;
use App\Models\MemorialTranslation;
use App\Models\User;
use App\Services\SEO\SitemapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemorialLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_memorial_update_persists_translations_and_returns_them_in_api_response(): void
    {
        $user = User::create([
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
        ]);

        $memorial = Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Marko',
            'last_name' => 'Markovic',
            'birth_date' => '1950-01-01',
            'death_date' => '2020-01-01',
            'birth_place' => 'Sarajevo',
            'death_place' => 'Mostar',
            'biography' => 'Originalna biografija',
            'slug' => 'marko.markovic',
            'is_public' => true,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'translations' => [
                'de' => [
                    'birth_place' => 'Sarajewo',
                    'death_place' => 'Mostar',
                    'biography' => 'Ein geliebter Lehrer und Familienmensch.',
                ],
                'en' => [
                    'biography' => 'A beloved teacher and family man.',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.translations.de.birthPlace', 'Sarajewo')
            ->assertJsonPath('data.translations.de.biography', 'Ein geliebter Lehrer und Familienmensch.')
            ->assertJsonPath('data.translations.en.biography', 'A beloved teacher and family man.');

        $this->assertDatabaseHas('memorial_translations', [
            'memorial_id' => $memorial->id,
            'locale' => 'de',
            'birth_place' => 'Sarajewo',
        ]);

        $this->assertDatabaseHas('memorial_translations', [
            'memorial_id' => $memorial->id,
            'locale' => 'en',
            'biography' => 'A beloved teacher and family man.',
        ]);
    }

    public function test_public_profile_renders_locale_specific_translation_when_available(): void
    {
        $user = User::create([
            'email' => 'public@example.com',
            'password' => Hash::make('password123'),
        ]);

        $memorial = Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Ivana',
            'last_name' => 'Horvat',
            'birth_date' => '1948-05-10',
            'death_date' => '2021-09-12',
            'birth_place' => 'Zagreb',
            'death_place' => 'Split',
            'biography' => 'Original biography text that should not be shown on the German page.',
            'slug' => 'ivana.horvat',
            'is_public' => true,
        ]);

        MemorialTranslation::create([
            'memorial_id' => $memorial->id,
            'locale' => 'de',
            'birth_place' => 'Agram',
            'death_place' => 'Split',
            'biography' => 'Deutsche Biografie fuer das Profil.',
        ]);

        $response = $this->get('/de/profil/ivana.horvat');

        $response->assertOk();
        $response->assertSee('Deutsche Biografie fuer das Profil.');
        $response->assertSee('Agram');
        $response->assertDontSee('Original biography text that should not be shown on the German page.');
    }

    public function test_search_page_matches_current_locale_translation_content(): void
    {
        $user = User::create([
            'email' => 'search@example.com',
            'password' => Hash::make('password123'),
        ]);

        $memorial = Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Johann',
            'last_name' => 'Testmann',
            'birth_date' => '1930-02-14',
            'death_date' => '2010-06-01',
            'biography' => 'Original content',
            'slug' => 'johann.testmann',
            'is_public' => true,
        ]);

        MemorialTranslation::create([
            'memorial_id' => $memorial->id,
            'locale' => 'de',
            'biography' => 'Lehrer aus Berlin mit grossem Herz.',
        ]);

        $response = $this->get('/de/search?q=Lehrer');

        $response->assertOk();
        $response->assertSee('Johann Testmann');
        $response->assertSee('Lehrer aus Berlin mit grossem Herz.');
    }

    public function test_api_search_matches_locale_translation_when_locale_is_provided(): void
    {
        $user = User::create([
            'email' => 'api-search@example.com',
            'password' => Hash::make('password123'),
        ]);

        Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Elena',
            'last_name' => 'Rossi',
            'birth_date' => '1935-08-01',
            'death_date' => '2019-04-15',
            'biography' => 'Contenuto originale',
            'slug' => 'elena.rossi',
            'is_public' => true,
        ])->translations()->create([
            'locale' => 'it',
            'biography' => 'Insegnante amata da tutta la comunita.',
        ]);

        $response = $this->getJson('/api/v1/search?query=Insegnante&locale=it');

        $response->assertOk();
        $slugs = array_column($response->json('data'), 'slug');
        $this->assertContains('elena.rossi', $slugs);
    }

    public function test_locale_sitemap_uses_translation_updated_at_for_lastmod(): void
    {
        $user = User::create([
            'email' => 'sitemap@example.com',
            'password' => Hash::make('password123'),
        ]);

        $memorial = Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Ana',
            'last_name' => 'Kovac',
            'birth_date' => '1940-03-03',
            'death_date' => '2020-03-03',
            'slug' => 'ana.kovac',
            'is_public' => true,
        ]);
        $memorial->forceFill([
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
            'updated_at' => Carbon::parse('2026-04-10 10:00:00'),
        ])->saveQuietly();

        $translation = MemorialTranslation::create([
            'memorial_id' => $memorial->id,
            'locale' => 'de',
            'biography' => 'Aktualisierte deutsche Biografie.',
        ]);
        $translation->forceFill([
            'created_at' => Carbon::parse('2026-04-15 08:00:00'),
            'updated_at' => Carbon::parse('2026-04-15 08:00:00'),
        ])->saveQuietly();

        $xml = app(SitemapService::class)->generateSitemap('de');

        $this->assertStringContainsString(route('memorial.profile', ['locale' => 'de', 'slug' => 'ana.kovac']), $xml);
        $this->assertStringContainsString($translation->fresh()->updated_at->toAtomString(), $xml);
    }
}
