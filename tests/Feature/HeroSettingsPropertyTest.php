<?php

use App\Models\HeroSettings;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 40: Hero settings required fields
// Validates: Requirements 9.1, 9.2

it('validates that hero settings contain all required fields', function () {
    // Get or create hero settings
    $settings = HeroSettings::get();

    // Verify all required fields are present
    expect($settings)->toHaveKeys([
        'hero_title',
        'hero_subtitle',
        'cta_button_text',
        'cta_button_link',
        'secondary_button_text',
        'secondary_button_link',
    ]);

    // Verify required fields are not null
    expect($settings->hero_title)->not->toBeNull();
    expect($settings->hero_subtitle)->not->toBeNull();
    expect($settings->cta_button_text)->not->toBeNull();
    expect($settings->cta_button_link)->not->toBeNull();
    expect($settings->secondary_button_text)->not->toBeNull();
    expect($settings->secondary_button_link)->not->toBeNull();

    // Test validation - missing required fields should fail
    $admin = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    $token = $admin->createToken('auth_token')->plainTextToken;

    // Try to update with missing required field
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson('/api/v1/admin/hero-settings', [
        'hero_subtitle' => fake()->sentence(),
        'cta_button_text' => fake()->words(2, true),
        'cta_button_link' => fake()->url(),
        'secondary_button_text' => fake()->words(2, true),
        'secondary_button_link' => fake()->url(),
        // Missing hero_title
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['hero_title']);
})->repeat(10);

// Feature: laravel-migration, Property 41: Hero settings timestamp update
// Validates: Requirements 9.3

it('automatically updates timestamp when hero settings are modified', function () {
    // Create admin user
    $admin = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    $token = $admin->createToken('auth_token')->plainTextToken;

    // Get initial settings
    $initialSettings = HeroSettings::get();
    $initialUpdatedAt = $initialSettings->updated_at;

    // Wait a moment to ensure timestamp difference
    sleep(1);

    // Update hero settings
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson('/api/v1/admin/hero-settings', [
        'hero_title' => fake()->sentence(),
        'hero_subtitle' => fake()->sentence(),
        'hero_image_url' => fake()->imageUrl(),
        'cta_button_text' => fake()->words(2, true),
        'cta_button_link' => fake()->url(),
        'secondary_button_text' => fake()->words(2, true),
        'secondary_button_link' => fake()->url(),
    ]);

    $response->assertStatus(200);

    // Get updated settings
    $updatedSettings = HeroSettings::first();

    // Verify updated_at timestamp was changed
    expect($updatedSettings->updated_at->isAfter($initialUpdatedAt))->toBeTrue();
})->repeat(10);

// Feature: laravel-migration, Property 42: Hero settings singleton
// Validates: Requirements 9.4

it('ensures only one hero settings record exists (singleton pattern)', function () {
    // Get hero settings multiple times
    $settings1 = HeroSettings::get();
    $settings2 = HeroSettings::get();
    $settings3 = HeroSettings::get();

    // All should return the same record
    expect($settings1->id)->toBe($settings2->id);
    expect($settings2->id)->toBe($settings3->id);

    // Verify only one record exists in database
    $count = HeroSettings::count();
    expect($count)->toBe(1);

    // Try to create another record manually
    HeroSettings::create([
        'hero_title' => fake()->sentence(),
        'hero_subtitle' => fake()->sentence(),
        'cta_button_text' => fake()->words(2, true),
        'cta_button_link' => fake()->url(),
        'secondary_button_text' => fake()->words(2, true),
        'secondary_button_link' => fake()->url(),
    ]);

    // Even after manual creation, get() should still work
    $settingsAfter = HeroSettings::get();
    expect($settingsAfter)->not->toBeNull();

    // Verify get() returns the first record
    $firstRecord = HeroSettings::first();
    expect($settingsAfter->id)->toBe($firstRecord->id);
})->repeat(10);

// Feature: laravel-migration, Property 39: Admin can update hero settings
// Validates: Requirements 8.5

it('allows admin to update hero settings', function () {
    // Create admin user
    $admin = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    $adminToken = $admin->createToken('auth_token')->plainTextToken;

    // Generate new hero settings data
    $newData = [
        'hero_title' => fake()->sentence(),
        'hero_subtitle' => fake()->sentence(),
        'hero_image_url' => fake()->imageUrl(),
        'cta_button_text' => fake()->words(2, true),
        'cta_button_link' => fake()->url(),
        'secondary_button_text' => fake()->words(2, true),
        'secondary_button_link' => fake()->url(),
    ];

    // Admin should be able to update hero settings
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->putJson('/api/v1/admin/hero-settings', $newData);

    $response->assertStatus(200);

    // Verify the response contains updated data
    $response->assertJson([
        'hero_title' => $newData['hero_title'],
        'hero_subtitle' => $newData['hero_subtitle'],
        'hero_image_url' => $newData['hero_image_url'],
        'cta_button_text' => $newData['cta_button_text'],
        'cta_button_link' => $newData['cta_button_link'],
        'secondary_button_text' => $newData['secondary_button_text'],
        'secondary_button_link' => $newData['secondary_button_link'],
    ]);

    // Verify changes persisted to database
    $settings = HeroSettings::first();
    expect($settings->hero_title)->toBe($newData['hero_title']);
    expect($settings->hero_subtitle)->toBe($newData['hero_subtitle']);
    expect($settings->hero_image_url)->toBe($newData['hero_image_url']);
    expect($settings->cta_button_text)->toBe($newData['cta_button_text']);
    expect($settings->cta_button_link)->toBe($newData['cta_button_link']);
    expect($settings->secondary_button_text)->toBe($newData['secondary_button_text']);
    expect($settings->secondary_button_link)->toBe($newData['secondary_button_link']);

    // Explicitly logout/clear auth state
    auth()->guard('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();

    // Test that non-admin cannot update hero settings
    $regularUser = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $regularUser->profile()->create(['email' => $regularUser->email]);
    UserRole::create(['user_id' => $regularUser->id, 'role' => 'user']);

    $regularToken = $regularUser->createToken('auth_token')->plainTextToken;

    $forbiddenResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $regularToken,
    ])->putJson('/api/v1/admin/hero-settings', [
        'hero_title' => fake()->sentence(),
        'hero_subtitle' => fake()->sentence(),
        'cta_button_text' => fake()->words(2, true),
        'cta_button_link' => fake()->url(),
        'secondary_button_text' => fake()->words(2, true),
        'secondary_button_link' => fake()->url(),
    ]);

    $forbiddenResponse->assertStatus(403);
})->repeat(10);
