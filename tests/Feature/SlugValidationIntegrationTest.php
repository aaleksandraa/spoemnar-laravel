<?php

use App\Models\User;
use App\Models\Memorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * Feature: Slug Validation Integration Test
 *
 * Tests that the slug validation middleware works correctly with actual routes
 * and that memorials with dots in slugs can be accessed properly.
 */

it('allows access to memorial with slug containing dots', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create memorial with slug containing dots
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Stefan',
        'last_name' => 'Stefanovic',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'stefan.stefanovic',
        'is_public' => true,
    ]);

    // Make GET request to memorial endpoint
    $response = $this->getJson('/api/v1/memorials/stefan.stefanovic');

    // Verify the request succeeds
    $response->assertStatus(200);

    // Verify the memorial data is returned correctly
    $response->assertJson([
        'data' => [
            'id' => $memorial->id,
            'firstName' => 'Stefan',
            'lastName' => 'Stefanovic',
            'slug' => 'stefan.stefanovic',
        ]
    ]);
});

it('allows access to memorial with slug containing multiple dots', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create memorial with slug containing multiple dots
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Jean',
        'last_name' => 'Pierre',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'jean.pierre.de.la.cruz',
        'is_public' => true,
    ]);

    // Make GET request to memorial endpoint
    $response = $this->getJson('/api/v1/memorials/jean.pierre.de.la.cruz');

    // Verify the request succeeds
    $response->assertStatus(200);

    // Verify the memorial data is returned correctly
    $response->assertJson([
        'data' => [
            'id' => $memorial->id,
            'slug' => 'jean.pierre.de.la.cruz',
        ]
    ]);
});

it('rejects request with slug containing consecutive dots', function () {
    // Make GET request with invalid slug (consecutive dots)
    $response = $this->getJson('/api/v1/memorials/invalid..slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJson([
        'message' => 'Invalid slug format. Slug must contain only lowercase letters, numbers, hyphens, and dots.',
        'errors' => [
            'slug' => ['The slug format is invalid.']
        ]
    ]);
});

it('rejects request with slug containing consecutive hyphens', function () {
    // Make GET request with invalid slug (consecutive hyphens)
    $response = $this->getJson('/api/v1/memorials/invalid--slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJson([
        'message' => 'Invalid slug format. Slug must contain only lowercase letters, numbers, hyphens, and dots.',
        'errors' => [
            'slug' => ['The slug format is invalid.']
        ]
    ]);
});

it('rejects request with slug starting with dot', function () {
    // Make GET request with invalid slug (starts with dot)
    $response = $this->getJson('/api/v1/memorials/.invalid.slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('rejects request with slug ending with dot', function () {
    // Make GET request with invalid slug (ends with dot)
    $response = $this->getJson('/api/v1/memorials/invalid.slug.');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('rejects request with slug starting with hyphen', function () {
    // Make GET request with invalid slug (starts with hyphen)
    $response = $this->getJson('/api/v1/memorials/-invalid-slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('rejects request with slug ending with hyphen', function () {
    // Make GET request with invalid slug (ends with hyphen)
    $response = $this->getJson('/api/v1/memorials/invalid-slug-');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('rejects request with slug containing uppercase letters', function () {
    // Make GET request with invalid slug (uppercase letters)
    $response = $this->getJson('/api/v1/memorials/Invalid.Slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('rejects request with slug containing special characters', function () {
    // Make GET request with invalid slug (special characters)
    $response = $this->getJson('/api/v1/memorials/invalid_slug');

    // Verify the request fails with 422 status
    $response->assertStatus(422);

    // Verify the error message mentions invalid slug format
    $response->assertJsonStructure([
        'message',
        'errors' => ['slug']
    ]);
});

it('allows access to memorial with mixed dots and hyphens in slug', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create memorial with slug containing mixed dots and hyphens
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Jean-Pierre',
        'last_name' => 'De La Cruz',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'jean-pierre.de-la.cruz',
        'is_public' => true,
    ]);

    // Make GET request to memorial endpoint
    $response = $this->getJson('/api/v1/memorials/jean-pierre.de-la.cruz');

    // Verify the request succeeds
    $response->assertStatus(200);

    // Verify the memorial data is returned correctly
    $response->assertJson([
        'data' => [
            'id' => $memorial->id,
            'slug' => 'jean-pierre.de-la.cruz',
        ]
    ]);
});
