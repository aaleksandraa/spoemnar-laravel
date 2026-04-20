<?php

use App\Models\AppSetting;
use App\Models\Memorial;
use App\Models\MemorialCandle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPublicMemorialForCandleView(array $overrides = []): Memorial
{
    $owner = User::factory()->create();
    $owner->profile()->create([
        'email' => $owner->email,
        'full_name' => 'Candle View Owner',
    ]);

    return Memorial::factory()->create(array_merge([
        'user_id' => $owner->id,
        'is_public' => true,
        'slug' => $overrides['slug'] ?? fake()->unique()->slug(),
    ], $overrides));
}

it('renders the digital candle module on the public memorial page when enabled', function () {
    $memorial = createPublicMemorialForCandleView([
        'slug' => 'candle-view-enabled',
    ]);

    $response = $this->get(route('memorial.profile', [
        'locale' => 'bs',
        'slug' => $memorial->slug,
    ]));

    $response->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('id="memorialCandleSection"');
    expect($content)->toContain('data-state="inactive"');
    expect($content)->toContain('id="memorialCandleVisualStatus"');
    expect($content)->toContain('bg-transparent');
    expect($content)->toContain('Svijeca sjecanja');
    expect($content)->toContain('Ugasena');
    expect($content)->toContain('/api/v1/memorials/');
    expect($content)->toContain('id="memorialCandleComposer"');
    expect($content)->toContain('id="memorialCandleWallWrap"');
    expect($content)->toContain('id="memorialFamilyManager"');
});

it('does not render the digital candle module when the feature is disabled by admin setting', function () {
    $memorial = createPublicMemorialForCandleView([
        'slug' => 'candle-view-disabled',
    ]);

    AppSetting::create([
        'setting_key' => 'memorial_candles_enabled',
        'setting_value' => '0',
    ]);

    $response = $this->get(route('memorial.profile', [
        'locale' => 'bs',
        'slug' => $memorial->slug,
    ]));

    $response->assertOk();

    $content = $response->getContent();

    expect($content)->not->toContain('id="memorialCandleSection"');
    expect($content)->not->toContain('Svijeca sjecanja');
});

it('renders anniversary and family candle blocks when phase two candle data exists', function () {
    $memorial = createPublicMemorialForCandleView([
        'slug' => 'candle-view-phase-two',
        'death_date' => now()->subYears(6)->format('Y-m-d'),
    ]);

    MemorialCandle::create([
        'memorial_id' => $memorial->id,
        'user_id' => $memorial->user_id,
        'display_name' => 'Candle View Owner',
        'message' => 'Porodicno svjetlo koje ostaje.',
        'is_anonymous' => false,
        'candle_type' => MemorialCandle::TYPE_FAMILY,
        'is_premium' => true,
        'status' => MemorialCandle::STATUS_ACTIVE,
        'expires_at' => now()->addYears(100),
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    $response = $this->get(route('memorial.profile', [
        'locale' => 'bs',
        'slug' => $memorial->slug,
    ]));

    $response->assertOk();

    $content = $response->getContent();

    expect($content)->toContain('id="memorialFamilyCandleWrap"');
    expect($content)->toContain('id="memorialCandleAnniversaryWrap"');
    expect($content)->toContain('Porodicna svijeca');
    expect($content)->toContain('Poruka sjecanja');
});
