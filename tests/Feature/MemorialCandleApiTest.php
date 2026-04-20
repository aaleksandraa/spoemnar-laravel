<?php

use App\Models\AppSetting;
use App\Models\Memorial;
use App\Models\MemorialCandle;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPublicMemorialForCandles(array $overrides = []): Memorial
{
    $owner = User::factory()->create([
        'email' => $overrides['owner_email'] ?? fake()->unique()->safeEmail(),
    ]);

    $owner->profile()->create([
        'email' => $owner->email,
        'full_name' => $overrides['owner_name'] ?? 'Memorial Owner',
    ]);

    return Memorial::factory()->create(array_merge([
        'user_id' => $owner->id,
        'is_public' => true,
        'slug' => $overrides['slug'] ?? fake()->unique()->slug(),
    ], $overrides));
}

it('allows a guest to light a memorial candle and exposes the active summary', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'candle-guest-lighting',
    ]);

    $summaryResponse = $this->getJson("/api/v1/memorials/{$memorial->id}/candle?lang=bs");

    $summaryResponse->assertOk();
    $summaryResponse->assertJsonPath('data.state', 'inactive');
    $summaryResponse->assertJsonPath('data.canLight', true);
    $summaryResponse->assertJsonPath('data.totalCandles', 0);

    $lightResponse = $this->postJson("/api/v1/memorials/{$memorial->id}/candle", [
        'locale' => 'bs',
        'message' => 'Neka ova svjetlost ostane kao znak sjecanja.',
    ]);

    $lightResponse->assertCreated();
    $lightResponse->assertJsonPath('message', 'Svijeca sjecanja je uspjesno upaljena.');
    $lightResponse->assertJsonPath('data.state', 'active');
    $lightResponse->assertJsonPath('data.totalCandles', 1);
    $lightResponse->assertJsonPath('data.activeCandle.lighterName', 'Anonimni posjetilac');
    $lightResponse->assertJsonPath('data.activeCandle.message', 'Neka ova svjetlost ostane kao znak sjecanja.');
    $lightResponse->assertJsonPath('data.settings.allowAnonymous', true);
    $lightResponse->assertJsonPath('data.settings.messagesEnabled', true);
    $lightResponse->assertJsonPath('data.wallCandles.0.message', 'Neka ova svjetlost ostane kao znak sjecanja.');

    $this->assertDatabaseHas('memorial_candles', [
        'memorial_id' => $memorial->id,
        'status' => MemorialCandle::STATUS_ACTIVE,
        'is_anonymous' => true,
        'message' => 'Neka ova svjetlost ostane kao znak sjecanja.',
    ]);
});

it('blocks lighting a second candle while one is still active', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'candle-second-light-blocked',
    ]);

    $firstResponse = $this->postJson("/api/v1/memorials/{$memorial->id}/candle", [
        'locale' => 'en',
    ]);

    $firstResponse->assertCreated();

    $secondResponse = $this->postJson("/api/v1/memorials/{$memorial->id}/candle", [
        'locale' => 'en',
    ]);

    $secondResponse->assertStatus(409);
    $secondResponse->assertJson([
        'message' => 'A remembrance candle is already burning on this memorial.',
    ]);
});

it('requires authentication for lighting when anonymous candle lighting is disabled', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'candle-auth-required',
    ]);

    AppSetting::create([
        'setting_key' => 'memorial_candles_allow_anonymous',
        'setting_value' => '0',
    ]);

    $summaryResponse = $this->getJson("/api/v1/memorials/{$memorial->id}/candle?lang=en");

    $summaryResponse->assertOk();
    $summaryResponse->assertJsonPath('data.canLight', false);
    $summaryResponse->assertJsonPath('data.reason', 'auth_required');

    $lightResponse = $this->postJson("/api/v1/memorials/{$memorial->id}/candle", [
        'locale' => 'en',
    ]);

    $lightResponse->assertStatus(401);
    $lightResponse->assertJson([
        'message' => 'Please sign in to light a candle on this memorial.',
    ]);
});

it('expires stale candles through the scheduled command and allows a new candle afterwards', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'candle-expire-command',
    ]);

    MemorialCandle::create([
        'memorial_id' => $memorial->id,
        'display_name' => 'Anonimni posjetilac',
        'is_anonymous' => true,
        'status' => MemorialCandle::STATUS_ACTIVE,
        'expires_at' => now()->subDay(),
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);

    $this->artisan('memorial-candles:expire')
        ->assertExitCode(0);

    $this->assertDatabaseHas('memorial_candles', [
        'memorial_id' => $memorial->id,
        'status' => MemorialCandle::STATUS_EXPIRED,
    ]);

    $lightResponse = $this->postJson("/api/v1/memorials/{$memorial->id}/candle", [
        'locale' => 'en',
    ]);

    $lightResponse->assertCreated();
    $lightResponse->assertJsonPath('data.totalCandles', 2);
    $lightResponse->assertJsonPath('data.state', 'active');
});

it('allows the memorial owner to create a premium family candle without blocking regular candle lighting', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'family-candle-owner',
    ]);

    $owner = $memorial->user()->firstOrFail();
    $token = $owner->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->putJson("/api/v1/memorials/{$memorial->id}/candle/family", [
        'locale' => 'en',
        'message' => 'Forever in our family prayers.',
        'is_premium' => true,
    ]);

    $response->assertOk();
    $response->assertJsonPath('message', 'The family candle has been updated successfully.');
    $response->assertJsonPath('data.familyCandle.isFamily', true);
    $response->assertJsonPath('data.familyCandle.isPremium', true);
    $response->assertJsonPath('data.familyCandle.isPermanent', true);
    $response->assertJsonPath('data.familyCandle.message', 'Forever in our family prayers.');
    $response->assertJsonPath('data.canLight', true);

    $this->assertDatabaseHas('memorial_candles', [
        'memorial_id' => $memorial->id,
        'candle_type' => MemorialCandle::TYPE_FAMILY,
        'is_premium' => true,
        'status' => MemorialCandle::STATUS_ACTIVE,
        'message' => 'Forever in our family prayers.',
    ]);
});

it('keeps premium family candles active when the expiry command runs', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'premium-family-persists',
    ]);

    MemorialCandle::create([
        'memorial_id' => $memorial->id,
        'user_id' => $memorial->user_id,
        'display_name' => 'Memorial Owner',
        'message' => 'Permanent family light.',
        'is_anonymous' => false,
        'candle_type' => MemorialCandle::TYPE_FAMILY,
        'is_premium' => true,
        'status' => MemorialCandle::STATUS_ACTIVE,
        'expires_at' => now()->subDay(),
        'created_at' => now()->subDays(15),
        'updated_at' => now()->subDays(15),
    ]);

    $this->artisan('memorial-candles:expire')
        ->assertExitCode(0);

    $this->assertDatabaseHas('memorial_candles', [
        'memorial_id' => $memorial->id,
        'candle_type' => MemorialCandle::TYPE_FAMILY,
        'is_premium' => true,
        'status' => MemorialCandle::STATUS_ACTIVE,
    ]);
});

it('rejects premium family candle requests when premium mode is disabled by admin', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'premium-family-disabled',
    ]);

    AppSetting::create([
        'setting_key' => 'memorial_candles_premium_enabled',
        'setting_value' => '0',
    ]);

    $owner = $memorial->user()->firstOrFail();
    $token = $owner->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->putJson("/api/v1/memorials/{$memorial->id}/candle/family", [
        'locale' => 'en',
        'message' => 'Forever in our family prayers.',
        'is_premium' => true,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'message' => 'Permanent family candle is currently unavailable.',
    ]);

    $this->assertDatabaseMissing('memorial_candles', [
        'memorial_id' => $memorial->id,
        'candle_type' => MemorialCandle::TYPE_FAMILY,
        'is_premium' => true,
    ]);
});

it('includes anniversary highlight data when today matches the memorial death date', function () {
    $memorial = createPublicMemorialForCandles([
        'slug' => 'anniversary-highlight',
        'death_date' => now()->subYears(4)->format('Y-m-d'),
        'birth_date' => now()->subYears(70)->subMonth()->format('Y-m-d'),
    ]);

    $response = $this->getJson("/api/v1/memorials/{$memorial->id}/candle?lang=en");

    $response->assertOk();
    $response->assertJsonPath('data.anniversary.type', 'death_anniversary');
    $response->assertJsonPath('data.anniversary.headline', 'Anniversary of passing');
});

it('exposes memorial candle settings in the admin settings endpoint', function () {
    $admin = User::factory()->create([
        'email' => 'candle-admin@example.com',
    ]);
    $admin->profile()->create([
        'email' => $admin->email,
        'full_name' => 'Candle Admin',
    ]);
    UserRole::create([
        'user_id' => $admin->id,
        'role' => 'admin',
    ]);

    $token = $admin->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/admin/settings');

    $response->assertOk();

    $keys = collect($response->json('data'))->pluck('key')->all();

    expect($keys)->toContain('memorial_candles_enabled');
    expect($keys)->toContain('memorial_candles_allow_anonymous');
    expect($keys)->toContain('memorial_candles_show_countdown');
    expect($keys)->toContain('memorial_candles_show_recent_lighters');
    expect($keys)->toContain('memorial_candles_messages_enabled');
    expect($keys)->toContain('memorial_candles_show_wall');
    expect($keys)->toContain('memorial_candles_family_enabled');
    expect($keys)->toContain('memorial_candles_premium_enabled');
    expect($keys)->toContain('memorial_candles_anniversary_highlights_enabled');
});
