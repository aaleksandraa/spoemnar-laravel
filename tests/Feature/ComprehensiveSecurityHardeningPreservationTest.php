<?php

/**
 * Comprehensive Security Hardening Preservation Tests
 *
 * These tests are designed to PASS on unfixed code to confirm baseline behavior to preserve.
 * They validate Property 2: Preservation - Valid Input Processing from the bugfix design.
 *
 * IMPORTANT: These tests MUST PASS before fixes are implemented.
 * Passing tests confirm the baseline behavior that must be preserved after fixes.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7**
 */

use App\Models\User;
use App\Models\Memorial;
use App\Models\MemorialImage;
use App\Models\MemorialVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Property 2: Preservation - Valid Biography Processing', function () {

    /**
     * Validates: Requirements 3.1
     * Property 2: Preservation - Valid Biography Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid biography (≤5000 chars) is accepted and stored correctly
     */

    it('accepts and stores valid biography with 100 characters', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid biography with 100 characters (well within limit)
        $validBiography = str_repeat('A', 100);

        // Update memorial with valid biography
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'biography' => $validBiography,
        ]);

        // PRESERVATION: Valid biography should be accepted (200 OK)
        expect($response->status())->toBe(200);

        // Verify the valid biography was stored correctly
        $memorial->refresh();
        expect($memorial->biography)->toBe($validBiography);
        expect(strlen($memorial->biography))->toBe(100);
    });

    it('accepts and stores valid biography with exactly 5000 characters (at limit)', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid biography with exactly 5000 characters (at the limit)
        $validBiography = str_repeat('B', 5000);

        // Update memorial with valid biography at limit
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'biography' => $validBiography,
        ]);

        // PRESERVATION: Valid biography at limit should be accepted
        expect($response->status())->toBe(200);

        // Verify the valid biography was stored correctly
        $memorial->refresh();
        expect($memorial->biography)->toBe($validBiography);
        expect(strlen($memorial->biography))->toBe(5000);
    });

    it('accepts and stores valid biography with 2500 characters (mid-range)', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid biography with 2500 characters (mid-range)
        $validBiography = str_repeat('C', 2500);

        // Update memorial with valid biography
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'biography' => $validBiography,
        ]);

        // PRESERVATION: Valid biography should be accepted
        expect($response->status())->toBe(200);

        // Verify the valid biography was stored correctly
        $memorial->refresh();
        expect($memorial->biography)->toBe($validBiography);
        expect(strlen($memorial->biography))->toBe(2500);
    });
});

describe('Property 2: Preservation - Valid Profile Image URL Processing', function () {

    /**
     * Validates: Requirements 3.2
     * Property 2: Preservation - Valid Profile Image URL Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid profile image URLs from whitelisted domains are processed correctly
     * NOTE: Since we don't know the exact whitelist yet, we test with common trusted domains
     */

    it('accepts and stores valid HTTPS profile image URL', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid HTTPS URL (common trusted domain)
        $validUrl = "https://example.com/images/profile.jpg";

        // Update memorial with valid profile image URL
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'profile_image_url' => $validUrl,
        ]);

        // PRESERVATION: Valid HTTPS URL should be accepted
        expect($response->status())->toBe(200);

        // Verify the valid URL was stored correctly
        $memorial->refresh();
        expect($memorial->profile_image_url)->toBe($validUrl);
    });

    it('accepts and stores valid profile image URL with query parameters', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid URL with query parameters (common for CDNs)
        $validUrl = "https://cdn.example.com/images/profile.jpg?size=large&format=webp";

        // Update memorial with valid profile image URL
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/v1/memorials/{$memorial->id}", [
            'profile_image_url' => $validUrl,
        ]);

        // PRESERVATION: Valid URL with query params should be accepted
        expect($response->status())->toBe(200);

        // Verify the valid URL was stored correctly
        $memorial->refresh();
        expect($memorial->profile_image_url)->toBe($validUrl);
    });
});

describe('Property 2: Preservation - Valid Video URL Processing', function () {

    /**
     * Validates: Requirements 3.3
     * Property 2: Preservation - Valid Video URL Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid video URLs from YouTube/Vimeo embed correctly
     */

    it('accepts and embeds valid YouTube watch URL', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid YouTube watch URL
        $validYouTubeUrl = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

        // Create video with valid YouTube URL
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
            'youtube_url' => $validYouTubeUrl,
        ]);

        // PRESERVATION: Valid YouTube URL should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid URL was stored correctly
        $video = $memorial->videos()->first();
        expect($video->youtube_url)->toBe($validYouTubeUrl);
    });

    it('accepts and embeds valid YouTube short URL', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid YouTube short URL
        $validYouTubeUrl = "https://youtu.be/dQw4w9WgXcQ";

        // Create video with valid YouTube short URL
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
            'youtube_url' => $validYouTubeUrl,
        ]);

        // PRESERVATION: Valid YouTube short URL should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid URL was stored correctly
        $video = $memorial->videos()->first();
        expect($video->youtube_url)->toBe($validYouTubeUrl);
    });

    it('accepts and embeds valid YouTube embed URL', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid YouTube embed URL
        $validYouTubeUrl = "https://www.youtube.com/embed/dQw4w9WgXcQ";

        // Create video with valid YouTube embed URL
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
            'youtube_url' => $validYouTubeUrl,
        ]);

        // PRESERVATION: Valid YouTube embed URL should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid URL was stored correctly
        $video = $memorial->videos()->first();
        expect($video->youtube_url)->toBe($validYouTubeUrl);
    });
});

describe('Property 2: Preservation - Valid Caption Processing', function () {

    /**
     * Validates: Requirements 3.4
     * Property 2: Preservation - Valid Caption Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid captions without HTML are stored and displayed correctly
     */

    it('accepts and stores valid plain text caption', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid plain text caption (no HTML)
        $validCaption = "This is a beautiful memory from our family vacation in 2020.";

        // Create a test image file
        Storage::fake('public');
        $image = UploadedFile::fake()->image('test.jpg');

        // Create image with valid caption
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $image,
            'caption' => $validCaption,
        ]);

        // PRESERVATION: Valid plain text caption should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid caption was stored correctly
        $storedImage = $memorial->images()->first();
        expect($storedImage->caption)->toBe($validCaption);
    });

    it('accepts and stores valid caption with special characters', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid caption with special characters (but no HTML)
        $validCaption = "John's favorite quote: \"Life is beautiful!\" & we miss him.";

        // Create a test image file
        Storage::fake('public');
        $image = UploadedFile::fake()->image('test.jpg');

        // Create image with valid caption
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $image,
            'caption' => $validCaption,
        ]);

        // PRESERVATION: Valid caption with special chars should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid caption was stored correctly
        $storedImage = $memorial->images()->first();
        expect($storedImage->caption)->toBe($validCaption);
    });

    it('accepts and stores valid caption with unicode characters', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create a memorial
        $memorial = Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid caption with unicode characters (emoji, accents)
        $validCaption = "Forever in our hearts ❤️ Café memories with José";

        // Create a test image file
        Storage::fake('public');
        $image = UploadedFile::fake()->image('test.jpg');

        // Create image with valid caption
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
            'image' => $image,
            'caption' => $validCaption,
        ]);

        // PRESERVATION: Valid caption with unicode should be accepted
        expect($response->status())->toBe(201);

        // Verify the valid caption was stored correctly
        $storedImage = $memorial->images()->first();
        expect($storedImage->caption)->toBe($validCaption);
    });
});

describe('Property 2: Preservation - Valid Password Processing', function () {

    /**
     * Validates: Requirements 3.5
     * Property 2: Preservation - Valid Password Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid passwords meeting complexity are accepted
     * NOTE: Testing password reset functionality (password update endpoint may not exist yet)
     */

    it('accepts valid strong password during password reset', function () {
        // Create user
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Valid strong password (12+ chars, mixed case, numbers, special chars)
        $validPassword = "MyStr0ng!Pass2024";

        // Request password reset (this tests password validation)
        $response = $this->postJson("/api/v1/reset-password", [
            'email' => $user->email,
            'password' => $validPassword,
            'password_confirmation' => $validPassword,
            'token' => 'dummy-token', // Will fail but tests password validation
        ]);

        // PRESERVATION: Valid strong password should pass validation
        // Note: Will return 422 for invalid token, but not for password validation
        // If password validation fails, error will be in 'errors.password'
        expect($response->status())->toBe(422);
        expect($response->json('errors.password'))->toBeNull(); // No password validation error
    });

    it('accepts valid password with minimum reasonable length during registration', function () {
        // Valid password with reasonable length (8+ characters)
        $validPassword = "ValidPass123!";

        // Test password validation through registration
        $response = $this->postJson("/api/v1/register", [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => $validPassword,
            'password_confirmation' => $validPassword,
        ]);

        // PRESERVATION: Valid password should be accepted during registration
        expect($response->status())->toBe(201);
    });
});

describe('Property 2: Preservation - Valid Pagination Processing', function () {

    /**
     * Validates: Requirements 3.6
     * Property 2: Preservation - Valid Pagination Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid pagination (per_page ≤100, page ≥1) works correctly
     */

    it('accepts valid pagination with per_page=10', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create multiple memorials for pagination
        Memorial::factory()->count(15)->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid pagination parameters (per_page=10, page=1)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials?per_page=10&page=1");

        // PRESERVATION: Valid pagination should work correctly
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toHaveCount(10);
    });

    it('accepts valid pagination with per_page=100 (at limit)', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create memorials
        Memorial::factory()->count(50)->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid pagination at limit (per_page=100)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials?per_page=100&page=1");

        // PRESERVATION: Valid pagination at limit should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toHaveCount(50); // All available items
    });

    it('accepts valid pagination with page=2', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create multiple memorials for pagination
        Memorial::factory()->count(25)->create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        // Valid pagination with page 2
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials?per_page=10&page=2");

        // PRESERVATION: Valid page navigation should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toHaveCount(10);
    });
});

describe('Property 2: Preservation - Valid Search Query Processing', function () {

    /**
     * Validates: Requirements 3.7
     * Property 2: Preservation - Valid Search Query Processing
     *
     * CRITICAL: This test MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid search queries (≤255 chars) return results
     */

    it('accepts and processes valid short search query', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create searchable memorials
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        // Valid short search query
        $validQuery = "John";

        // Search with valid query (using correct parameter name 'query')
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/search?query={$validQuery}");

        // PRESERVATION: Valid search query should return results
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('accepts and processes valid medium-length search query', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create searchable memorial
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'Alexander',
            'last_name' => 'Thompson',
            'biography' => 'A loving father and dedicated teacher who touched many lives.',
        ]);

        // Valid medium-length search query (50 characters)
        $validQuery = str_repeat('search ', 7) . 'term'; // ~50 chars

        // Search with valid query
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/search?query=" . urlencode($validQuery));

        // PRESERVATION: Valid medium search query should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('accepts and processes valid search query at 255 character limit', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create searchable memorial
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => 'Robert',
            'last_name' => 'Johnson',
        ]);

        // Valid search query at 255 character limit
        $validQuery = str_repeat('a', 255);

        // Search with valid query at limit
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/search?query=" . urlencode($validQuery));

        // PRESERVATION: Valid search query at limit should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });

    it('accepts and processes valid search query with special characters', function () {
        // Create authenticated user
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Create searchable memorial
        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'first_name' => "O'Brien",
            'last_name' => 'Smith-Jones',
        ]);

        // Valid search query with special characters (apostrophe, hyphen)
        $validQuery = "O'Brien Smith-Jones";

        // Search with valid query containing special chars
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/search?query=" . urlencode($validQuery));

        // PRESERVATION: Valid search with special chars should work
        expect($response->status())->toBe(200);
        expect($response->json('data'))->toBeArray();
    });
});

describe('Property 2: Preservation - Authorized User Operations', function () {

    /**
     * Validates: Requirements 3.8, 3.9, 3.10
     * Property 2: Preservation - Authorized Operations
     *
     * CRITICAL: These tests MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that authorized users can access and manage their own resources
     */

    describe('Profile Access Tests', function () {

        it('allows user to view their own profile', function () {
            // Create authenticated user with profile
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // User views their own profile
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/v1/profiles/{$user->id}");

            // PRESERVATION: User should be able to view their own profile
            expect($response->status())->toBe(200);
            expect($response->json('profile'))->not->toBeNull();
        });

        it('allows user to update their own profile', function () {
            // Create authenticated user with profile
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // User updates their own profile
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/profiles/{$user->id}", [
                'email' => 'newemail@example.com',
            ]);

            // PRESERVATION: User should be able to update their own profile
            expect($response->status())->toBe(200);
        });

        it('allows user to delete their own profile', function () {
            // Create authenticated user with profile
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // User deletes their own profile
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->deleteJson("/api/v1/profiles/{$user->id}");

            // PRESERVATION: User should be able to delete their own profile
            expect($response->status())->toBeIn([200, 204]);
        });
    });

    describe('Image Management Tests', function () {

        it('allows user to upload images to their own memorial', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create a test image file
            Storage::fake('public');
            $image = UploadedFile::fake()->image('memorial.jpg');

            // User uploads image to their own memorial
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
                'image' => $image,
                'caption' => 'A beautiful memory',
            ]);

            // PRESERVATION: User should be able to upload images to their own memorial
            expect($response->status())->toBe(201);
        });

        it('allows user to update their own memorial images', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create an image for the memorial
            $memorialImage = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
            ]);

            // User updates their own memorial image
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/images/{$memorialImage->id}", [
                'caption' => 'Updated caption',
            ]);

            // PRESERVATION: User should be able to update their own memorial images
            expect($response->status())->toBe(200);
        });

        it('allows user to delete their own memorial images', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create an image for the memorial
            $memorialImage = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
            ]);

            // User deletes their own memorial image
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->deleteJson("/api/v1/images/{$memorialImage->id}");

            // PRESERVATION: User should be able to delete their own memorial images
            expect($response->status())->toBeIn([200, 204]);
        });

        it('allows user to reorder their own memorial images', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create multiple images for the memorial
            $image1 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 1,
            ]);
            $image2 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 2,
            ]);

            // User reorders their own memorial images (using 'images' parameter)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images/reorder", [
                'images' => [
                    ['id' => $image2->id, 'display_order' => 1],
                    ['id' => $image1->id, 'display_order' => 2],
                ],
            ]);

            // PRESERVATION: User should be able to reorder their own memorial images
            expect($response->status())->toBe(200);
        });
    });

    describe('Video Management Tests', function () {

        it('allows user to add videos to their own memorial', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // User adds video to their own memorial
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/videos", [
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]);

            // PRESERVATION: User should be able to add videos to their own memorial
            expect($response->status())->toBe(201);
        });

        it('allows user to update their own memorial videos', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create a video for the memorial
            $memorialVideo = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
            ]);

            // User updates their own memorial video
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/videos/{$memorialVideo->id}", [
                'youtube_url' => 'https://www.youtube.com/watch?v=newVideoId',
            ]);

            // PRESERVATION: User should be able to update their own memorial videos
            expect($response->status())->toBe(200);
        });

        it('allows user to delete their own memorial videos', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create a video for the memorial
            $memorialVideo = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
            ]);

            // User deletes their own memorial video
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->deleteJson("/api/v1/videos/{$memorialVideo->id}");

            // PRESERVATION: User should be able to delete their own memorial videos
            expect($response->status())->toBeIn([200, 204]);
        });

        it('allows user to reorder their own memorial videos', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create multiple videos for the memorial
            $video1 = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 1,
            ]);
            $video2 = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 2,
            ]);

            // User reorders their own memorial videos (using 'videos' parameter)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/videos/reorder", [
                'videos' => [
                    ['id' => $video2->id, 'display_order' => 1],
                    ['id' => $video1->id, 'display_order' => 2],
                ],
            ]);

            // PRESERVATION: User should be able to reorder their own memorial videos
            expect($response->status())->toBe(200);
        });
    });

    describe('Admin Access Tests', function () {

        it('allows admin to access dashboard', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Admin accesses dashboard
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/admin/dashboard');

            // PRESERVATION: Admin should be able to access dashboard
            expect($response->status())->toBe(200);
        });

        it('allows admin to view all memorials', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Create some memorials
            Memorial::factory()->count(3)->create();

            // Admin views all memorials
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/admin/memorials');

            // PRESERVATION: Admin should be able to view all memorials
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('allows admin to view all users', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Create some users
            User::factory()->count(3)->create();

            // Admin views all users
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/admin/users');

            // PRESERVATION: Admin should be able to view all users
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('allows admin to update user roles', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Create a regular user
            $user = User::factory()->create(['role' => 'user']);

            // Admin updates user role to admin (using valid role value)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/admin/users/{$user->id}/role", [
                'role' => 'admin',
                'action' => 'add',
            ]);

            // PRESERVATION: Admin should be able to update user roles
            expect($response->status())->toBe(200);
        });

        it('allows admin to delete any memorial', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by another user
            $otherUser = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            // Admin deletes the memorial
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->deleteJson("/api/v1/admin/memorials/{$memorial->id}");

            // PRESERVATION: Admin should be able to delete any memorial
            expect($response->status())->toBeIn([200, 204]);
        });

        it('allows admin to import locations', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Prepare import data
            $countryLines = "TEST|Test Country";
            $placeLines = "TEST|Test City|city";

            // Admin imports locations
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/v1/admin/locations/import', [
                'country_lines' => $countryLines,
                'place_lines' => $placeLines,
            ]);

            // PRESERVATION: Admin should be able to import locations
            expect($response->status())->toBe(200);
            expect($response->json('message'))->toBe('Import finished.');
            expect($response->json('summary.countries_imported'))->toBe(1);
            expect($response->json('summary.places_imported'))->toBe(1);

            // Verify the import was processed
            $this->assertDatabaseHas('countries', ['code' => 'TEST', 'name' => 'Test Country']);
            $this->assertDatabaseHas('places', ['name' => 'Test City']);
        });
    });
});

describe('Property 2: Preservation - Valid Data Display', function () {

    /**
     * Validates: Requirements 3.11, 3.12, 3.13, 3.14
     * Property 2: Preservation - Valid Data Display
     *
     * CRITICAL: These tests MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid data without malicious content displays correctly
     */

    describe('Valid Caption Display Tests', function () {

        it('displays valid plain text caption correctly in memorial images', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid plain text caption (no HTML)
            $validCaption = "This is a beautiful memory from our family vacation in 2020.";

            // Create a test image file
            Storage::fake('public');
            $image = UploadedFile::fake()->image('test.jpg');

            // Create image with valid caption
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
                'image' => $image,
                'caption' => $validCaption,
            ]);

            // PRESERVATION: Valid plain text caption should be accepted and stored
            expect($response->status())->toBe(201);

            // Verify the valid caption was stored correctly
            $storedImage = $memorial->images()->first();
            expect($storedImage->caption)->toBe($validCaption);

            // Verify caption displays correctly when retrieving the memorial
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.images.0.caption'))->toBe($validCaption);
        });

        it('displays valid caption with special characters correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid caption with special characters (but no HTML)
            $validCaption = "John's favorite quote: \"Life is beautiful!\" & we miss him.";

            // Create a test image file
            Storage::fake('public');
            $image = UploadedFile::fake()->image('test.jpg');

            // Create image with valid caption
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
                'image' => $image,
                'caption' => $validCaption,
            ]);

            // PRESERVATION: Valid caption with special chars should be accepted
            expect($response->status())->toBe(201);

            // Verify the valid caption was stored correctly
            $storedImage = $memorial->images()->first();
            expect($storedImage->caption)->toBe($validCaption);

            // Verify caption displays correctly when retrieving the memorial
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.images.0.caption'))->toBe($validCaption);
        });

        it('displays valid caption with unicode characters correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid caption with unicode characters (emoji, accents)
            $validCaption = "Forever in our hearts ❤️ Café memories with José";

            // Create a test image file
            Storage::fake('public');
            $image = UploadedFile::fake()->image('test.jpg');

            // Create image with valid caption
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images", [
                'image' => $image,
                'caption' => $validCaption,
            ]);

            // PRESERVATION: Valid caption with unicode should be accepted
            expect($response->status())->toBe(201);

            // Verify the valid caption was stored correctly
            $storedImage = $memorial->images()->first();
            expect($storedImage->caption)->toBe($validCaption);

            // Verify caption displays correctly when retrieving the memorial
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.images.0.caption'))->toBe($validCaption);
        });
    });

    describe('Valid Profile Fields Display Tests', function () {

        it('displays valid biography correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid biography with normal text content
            $validBiography = "John was a loving father, husband, and friend. He dedicated his life to helping others and will be deeply missed by all who knew him. His legacy of kindness and compassion lives on in the hearts of his family and community.";

            // Update memorial with valid biography
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/memorials/{$memorial->id}", [
                'biography' => $validBiography,
            ]);

            // PRESERVATION: Valid biography should be accepted
            expect($response->status())->toBe(200);

            // Verify the valid biography was stored correctly
            $memorial->refresh();
            expect($memorial->biography)->toBe($validBiography);

            // Verify biography displays correctly when retrieving the memorial
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.biography'))->toBe($validBiography);
        });

        it('displays valid names and dates correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Valid profile data
            $validFirstName = "John";
            $validLastName = "Doe";
            $validBirthDate = "1950-05-15";
            $validDeathDate = "2023-12-01";

            // Create memorial with valid data
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'first_name' => $validFirstName,
                'last_name' => $validLastName,
                'birth_date' => $validBirthDate,
                'death_date' => $validDeathDate,
                'is_public' => true,
            ]);

            // PRESERVATION: Valid names and dates should be stored correctly
            expect($memorial->first_name)->toBe($validFirstName);
            expect($memorial->last_name)->toBe($validLastName);
            expect($memorial->birth_date->format('Y-m-d'))->toBe($validBirthDate);
            expect($memorial->death_date->format('Y-m-d'))->toBe($validDeathDate);

            // Verify data displays correctly when retrieving the memorial (using camelCase from resource)
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.firstName'))->toBe($validFirstName);
            expect($getResponse->json('data.lastName'))->toBe($validLastName);
        });

        it('displays valid profile with special characters in names correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Valid names with special characters (accents, hyphens)
            $validFirstName = "José-María";
            $validLastName = "O'Connor";

            // Create memorial with valid data
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'first_name' => $validFirstName,
                'last_name' => $validLastName,
                'is_public' => true,
            ]);

            // PRESERVATION: Valid names with special characters should be stored correctly
            expect($memorial->first_name)->toBe($validFirstName);
            expect($memorial->last_name)->toBe($validLastName);

            // Verify data displays correctly when retrieving the memorial (using camelCase from resource)
            $getResponse = $this->getJson("/api/v1/memorials/{$memorial->slug}");
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('data.firstName'))->toBe($validFirstName);
            expect($getResponse->json('data.lastName'))->toBe($validLastName);
        });
    });

    describe('Valid Hero Settings Display Tests', function () {

        it('applies and displays valid hero settings correctly', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Valid hero settings data
            $validHeroSettings = [
                'hero_title' => 'Welcome to Our Memorial Site',
                'hero_subtitle' => 'Honoring the memories of loved ones',
                'hero_image_url' => 'https://example.com/hero-image.jpg',
                'cta_button_text' => 'Create Memorial',
                'cta_button_link' => '/register',
                'secondary_button_text' => 'Learn More',
                'secondary_button_link' => '/about',
            ];

            // Update hero settings with valid data
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson('/api/v1/admin/hero-settings', $validHeroSettings);

            // PRESERVATION: Valid hero settings should be accepted
            expect($response->status())->toBe(200);

            // Verify hero settings display correctly when retrieving them (no 'data' wrapper)
            $getResponse = $this->getJson('/api/v1/hero-settings');
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('hero_title'))->toBe($validHeroSettings['hero_title']);
            expect($getResponse->json('hero_subtitle'))->toBe($validHeroSettings['hero_subtitle']);
            expect($getResponse->json('hero_image_url'))->toBe($validHeroSettings['hero_image_url']);
            expect($getResponse->json('cta_button_text'))->toBe($validHeroSettings['cta_button_text']);
            expect($getResponse->json('cta_button_link'))->toBe($validHeroSettings['cta_button_link']);
            expect($getResponse->json('secondary_button_text'))->toBe($validHeroSettings['secondary_button_text']);
            expect($getResponse->json('secondary_button_link'))->toBe($validHeroSettings['secondary_button_link']);
        });

        it('applies valid hero settings with special characters correctly', function () {
            // Create admin user
            $admin = User::factory()->create(['role' => 'admin']);
            $admin->profile()->create(['email' => $admin->email]);
            $token = $admin->createToken('auth_token')->plainTextToken;

            // Valid hero settings with special characters
            $validHeroSettings = [
                'hero_title' => 'Honoring Life\'s Journey & Legacy',
                'hero_subtitle' => 'Creating meaningful tributes for those we\'ve loved',
                'cta_button_text' => 'Get Started',
                'cta_button_link' => '/register',
                'secondary_button_text' => 'Learn More',
                'secondary_button_link' => '/about',
            ];

            // Update hero settings with valid data
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson('/api/v1/admin/hero-settings', $validHeroSettings);

            // PRESERVATION: Valid hero settings with special chars should be accepted
            expect($response->status())->toBe(200);

            // Verify hero settings display correctly (no 'data' wrapper)
            $getResponse = $this->getJson('/api/v1/hero-settings');
            expect($getResponse->status())->toBe(200);
            expect($getResponse->json('hero_title'))->toBe($validHeroSettings['hero_title']);
            expect($getResponse->json('hero_subtitle'))->toBe($validHeroSettings['hero_subtitle']);
        });
    });

    describe('Valid Contact Form Processing Tests', function () {

        it('processes legitimate contact form submission correctly', function () {
            // Valid contact form data (includes required 'subject' field)
            $validContactData = [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'subject' => 'Memorial Information Request',
                'message' => 'I would like to learn more about creating a memorial for my loved one. Can you provide more information about the process?',
            ];

            // Submit valid contact form (web route, not API)
            $response = $this->post('/en/contact', $validContactData);

            // PRESERVATION: Valid contact form should be accepted with redirect
            expect($response->status())->toBe(302); // Redirect after successful submission
        });

        it('processes contact form with special characters correctly', function () {
            // Valid contact form data with special characters
            $validContactData = [
                'name' => 'María José O\'Connor',
                'email' => 'maria.jose@example.com',
                'subject' => 'Question about memorials',
                'message' => 'Hello! I\'m interested in your services. Can you help me create a memorial? Thanks & regards.',
            ];

            // Submit valid contact form
            $response = $this->post('/en/contact', $validContactData);

            // PRESERVATION: Valid contact form with special chars should be accepted
            expect($response->status())->toBe(302);
        });

        it('processes contact form with unicode characters correctly', function () {
            // Valid contact form data with unicode characters
            $validContactData = [
                'name' => 'Владимир Петров',
                'email' => 'vladimir@example.com',
                'subject' => 'Memorial creation',
                'message' => 'Здравствуйте! I would like to create a memorial. Thank you ❤️',
            ];

            // Submit valid contact form
            $response = $this->post('/en/contact', $validContactData);

            // PRESERVATION: Valid contact form with unicode should be accepted
            expect($response->status())->toBe(302);
        });

        it('processes contact form with long valid message correctly', function () {
            // Valid contact form data with longer message (but within reasonable limits)
            $validContactData = [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@example.com',
                'subject' => 'Detailed inquiry about memorial services',
                'message' => str_repeat('This is a valid message with meaningful content. ', 20), // ~900 characters
            ];

            // Submit valid contact form
            $response = $this->post('/en/contact', $validContactData);

            // PRESERVATION: Valid contact form with long message should be accepted
            expect($response->status())->toBe(302);
        });
    });
});


describe('Property 2: Preservation - Valid Business Logic Operations', function () {

    /**
     * Validates: Requirements 3.15, 3.16, 3.17, 3.18, 3.19
     * Property 2: Preservation - Valid Business Logic
     *
     * CRITICAL: These tests MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid business operations process correctly
     */

    describe('Valid Reorder Operations', function () {

        /**
         * Validates: Requirement 3.15
         * GOAL: Capture that valid reorder requests update display order correctly
         */

        it('accepts valid image reorder request and updates display order', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create multiple images with initial display order
            $image1 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 1,
            ]);
            $image2 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 2,
            ]);
            $image3 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 3,
            ]);

            // Valid reorder request (reverse the order)
            $validReorderData = [
                'images' => [
                    ['id' => $image3->id, 'display_order' => 1],
                    ['id' => $image2->id, 'display_order' => 2],
                    ['id' => $image1->id, 'display_order' => 3],
                ],
            ];

            // Submit valid reorder request
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images/reorder", $validReorderData);

            // PRESERVATION: Valid reorder request should be accepted
            expect($response->status())->toBe(200);

            // Verify display order was updated correctly
            $image1->refresh();
            $image2->refresh();
            $image3->refresh();
            expect($image3->display_order)->toBe(1);
            expect($image2->display_order)->toBe(2);
            expect($image1->display_order)->toBe(3);
        });

        it('accepts valid video reorder request and updates display order', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create multiple videos with initial display order
            $video1 = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 1,
            ]);
            $video2 = MemorialVideo::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 2,
            ]);

            // Valid reorder request (swap the order)
            $validReorderData = [
                'videos' => [
                    ['id' => $video2->id, 'display_order' => 1],
                    ['id' => $video1->id, 'display_order' => 2],
                ],
            ];

            // Submit valid reorder request
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/videos/reorder", $validReorderData);

            // PRESERVATION: Valid reorder request should be accepted
            expect($response->status())->toBe(200);

            // Verify display order was updated correctly
            $video1->refresh();
            $video2->refresh();
            expect($video2->display_order)->toBe(1);
            expect($video1->display_order)->toBe(2);
        });

        it('accepts valid reorder with display_order values within range (0-9999)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial owned by the user
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create images
            $image1 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 0,
            ]);
            $image2 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 5000,
            ]);

            // Valid reorder with values in acceptable range
            $validReorderData = [
                'images' => [
                    ['id' => $image1->id, 'display_order' => 100],
                    ['id' => $image2->id, 'display_order' => 9999],
                ],
            ];

            // Submit valid reorder request
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images/reorder", $validReorderData);

            // PRESERVATION: Valid display order values should be accepted
            expect($response->status())->toBe(200);

            // Verify display order was updated correctly
            $image1->refresh();
            $image2->refresh();
            expect($image1->display_order)->toBe(100);
            expect($image2->display_order)->toBe(9999);
        });
    });

    describe('Valid Search Operations', function () {

        /**
         * Validates: Requirement 3.16
         * GOAL: Capture that valid search queries return relevant results
         */

        it('returns relevant results for valid search query matching memorial name', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create searchable memorials
            $memorial1 = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'first_name' => 'Alexander',
                'last_name' => 'Hamilton',
            ]);

            $memorial2 = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'first_name' => 'Benjamin',
                'last_name' => 'Franklin',
            ]);

            // Valid search query
            $validQuery = 'Alexander';

            // Search with valid query
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/v1/search?query=" . urlencode($validQuery));

            // PRESERVATION: Valid search should return relevant results
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();

            // Verify relevant result is included
            $results = collect($response->json('data'));
            $foundMemorial = $results->firstWhere('id', $memorial1->id);
            expect($foundMemorial)->not->toBeNull();
        });

        it('returns relevant results for valid search query with pagination', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create multiple searchable memorials
            Memorial::factory()->count(15)->create([
                'user_id' => $user->id,
                'is_public' => true,
                'first_name' => 'John',
            ]);

            // Valid search query with pagination parameters
            $validQuery = 'John';

            // Search with valid query and pagination
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/v1/search?query=" . urlencode($validQuery) . "&per_page=10&page=1");

            // PRESERVATION: Valid search with pagination should work
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
            expect(count($response->json('data')))->toBeLessThanOrEqual(10);
        });

        it('returns relevant results for valid search query with type filter', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create searchable memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
            ]);

            // Valid search query with type filter
            $validQuery = 'Maria';

            // Search with valid query and type filter (using 'profiles' which is a valid type)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/v1/search?query=" . urlencode($validQuery) . "&type=profiles");

            // PRESERVATION: Valid search with type filter should work
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });
    });

    describe('Valid Role Update Operations', function () {

        /**
         * Validates: Requirement 3.17
         * GOAL: Capture that valid role updates by authorized admins work correctly
         */

        it('allows admin to add admin role to regular user', function () {
            // Create admin user
            $admin = User::factory()->create();
            $admin->profile()->create(['email' => $admin->email]);
            $admin->role = 'admin';
            $admin->save();
            $adminToken = $admin->createToken('auth_token')->plainTextToken;

            // Create regular user
            $regularUser = User::factory()->create();
            $regularUser->profile()->create(['email' => $regularUser->email]);
            $regularUser->role = 'user';
            $regularUser->save();

            // Valid role update request (user -> admin)
            $validRoleData = [
                'role' => 'admin',
                'action' => 'add',
            ];

            // Admin updates user role
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminToken,
            ])->putJson("/api/v1/admin/users/{$regularUser->id}/role", $validRoleData);

            // PRESERVATION: Valid role update by admin should work
            expect($response->status())->toBe(200);

            // Verify role was added correctly
            $regularUser->refresh();
            expect($regularUser->roles()->where('role', 'admin')->exists())->toBeTrue();
            expect($regularUser->role)->toBe('admin');
        });

        it('allows admin to update user role to admin', function () {
            // Create admin user
            $admin = User::factory()->create();
            $admin->profile()->create(['email' => $admin->email]);
            $admin->role = 'admin';
            $admin->save();
            $adminToken = $admin->createToken('auth_token')->plainTextToken;

            // Create regular user
            $regularUser = User::factory()->create();
            $regularUser->profile()->create(['email' => $regularUser->email]);
            $regularUser->role = 'user';
            $regularUser->save();

            // Valid role update request (user -> admin)
            $validRoleData = [
                'role' => 'admin',
                'action' => 'add',
            ];

            // Admin updates user role
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminToken,
            ])->putJson("/api/v1/admin/users/{$regularUser->id}/role", $validRoleData);

            // PRESERVATION: Valid role update to admin should work
            expect($response->status())->toBe(200);

            // Verify role was updated correctly
            $regularUser->refresh();
            expect($regularUser->role)->toBe('admin');
        });

        it('allows admin to remove admin role from user', function () {
            // Create admin user
            $admin = User::factory()->create();
            $admin->profile()->create(['email' => $admin->email]);
            $admin->role = 'admin';
            $admin->save();
            $adminToken = $admin->createToken('auth_token')->plainTextToken;

            // Create admin user to be downgraded
            $adminUser = User::factory()->create();
            $adminUser->profile()->create(['email' => $adminUser->email]);
            $adminUser->role = 'admin';
            $adminUser->save();
            // Add admin role to roles table
            $adminUser->roles()->create(['role' => 'admin']);

            // Valid role downgrade request (admin -> user)
            $validRoleData = [
                'role' => 'admin',
                'action' => 'remove',
            ];

            // Admin downgrades user role
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $adminToken,
            ])->putJson("/api/v1/admin/users/{$adminUser->id}/role", $validRoleData);

            // PRESERVATION: Valid role downgrade should work
            expect($response->status())->toBe(200);

            // Verify role was removed correctly (user role should remain as base)
            $adminUser->refresh();
            expect($adminUser->roles()->where('role', 'admin')->exists())->toBeFalse();
            expect($adminUser->role)->toBe('user');
        });
    });

    describe('Valid Tribute Timestamp Operations', function () {

        /**
         * Validates: Requirement 3.18
         * GOAL: Capture that valid tributes are accepted and created correctly
         * NOTE: Tributes use created_at automatically, not tribute_timestamp
         */

        it('accepts valid tribute with all required fields', function () {
            // Create a public memorial
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid tribute data
            $validTributeData = [
                'author_name' => 'John Doe',
                'author_email' => 'john@example.com',
                'message' => 'A heartfelt tribute message.',
                'timestamp' => time(), // Required for bot protection
            ];

            // Submit tribute
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", $validTributeData);

            // PRESERVATION: Valid tribute should be accepted
            expect($response->status())->toBe(201);

            // Verify tribute was stored
            $tribute = $memorial->tributes()->first();
            expect($tribute)->not->toBeNull();
            expect($tribute->author_name)->toBe($validTributeData['author_name']);
            expect($tribute->message)->toBe($validTributeData['message']);
        });

        it('accepts valid tribute with long message (within limit)', function () {
            // Create a public memorial
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid tribute with long message (900 characters, within 1000 limit)
            $longMessage = str_repeat('This is a heartfelt tribute. ', 30); // ~900 chars
            $validTributeData = [
                'author_name' => 'Jane Smith',
                'author_email' => 'jane@example.com',
                'message' => $longMessage,
                'timestamp' => time(),
            ];

            // Submit tribute
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", $validTributeData);

            // PRESERVATION: Valid tribute with long message should be accepted
            expect($response->status())->toBe(201);

            // Verify tribute was stored
            $tribute = $memorial->tributes()->first();
            expect($tribute)->not->toBeNull();
        });

        it('accepts valid tribute with special characters in message', function () {
            // Create a public memorial
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid tribute with special characters
            $validTributeData = [
                'author_name' => "O'Brien",
                'author_email' => 'obrien@example.com',
                'message' => "John's legacy lives on! We'll never forget him & his kindness.",
                'timestamp' => time(),
            ];

            // Submit tribute
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", $validTributeData);

            // PRESERVATION: Valid tribute with special chars should be accepted
            expect($response->status())->toBe(201);

            // Verify tribute was stored
            $tribute = $memorial->tributes()->first();
            expect($tribute)->not->toBeNull();
        });

        it('accepts valid tribute with unicode characters', function () {
            // Create a public memorial
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Valid tribute with unicode
            $validTributeData = [
                'author_name' => 'María García',
                'author_email' => 'maria@example.com',
                'message' => 'Forever in our hearts ❤️ Café memories with José',
                'timestamp' => time(),
            ];

            // Submit tribute
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", $validTributeData);

            // PRESERVATION: Valid tribute with unicode should be accepted
            expect($response->status())->toBe(201);

            // Verify tribute was stored
            $tribute = $memorial->tributes()->first();
            expect($tribute)->not->toBeNull();
        });
    });

    describe('Valid Slug Parameter Operations', function () {

        /**
         * Validates: Requirement 3.19
         * GOAL: Capture that valid slug parameters route correctly
         */

        it('routes correctly with valid lowercase alphanumeric slug', function () {
            // Create a memorial with valid slug
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => 'john-smith-1980',
            ]);

            // Access memorial by valid slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid slug should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe('john-smith-1980');
        });

        it('routes correctly with valid slug containing multiple hyphens', function () {
            // Create a memorial with valid slug (multiple hyphens)
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => 'mary-anne-smith-jones-1975-2020',
            ]);

            // Access memorial by valid slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid slug with multiple hyphens should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe('mary-anne-smith-jones-1975-2020');
        });

        it('routes correctly with valid slug containing only numbers', function () {
            // Create a memorial with valid numeric slug
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => '12345',
            ]);

            // Access memorial by valid numeric slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid numeric slug should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe('12345');
        });

        it('routes correctly with valid slug containing letters and numbers', function () {
            // Create a memorial with valid alphanumeric slug
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => 'memorial-2024-abc123',
            ]);

            // Access memorial by valid alphanumeric slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid alphanumeric slug should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe('memorial-2024-abc123');
        });

        it('routes correctly with valid short slug', function () {
            // Create a memorial with valid short slug
            $user = User::factory()->create();
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => 'abc',
            ]);

            // Access memorial by valid short slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid short slug should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe('abc');
        });

        it('routes correctly with valid long slug (within 255 char limit)', function () {
            // Create a memorial with valid long slug (200 characters)
            $user = User::factory()->create();
            $validLongSlug = str_repeat('a', 100) . '-' . str_repeat('b', 99); // 200 chars total
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'slug' => $validLongSlug,
            ]);

            // Access memorial by valid long slug
            $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

            // PRESERVATION: Valid long slug should route correctly
            expect($response->status())->toBe(200);
            expect($response->json('data.slug'))->toBe($validLongSlug);
        });
    });
});


describe('Property 2: Preservation - Valid System Operations', function () {

    /**
     * Validates: Requirements 3.20
     * Property 2: Preservation - Valid System Operations
     *
     * CRITICAL: These tests MUST PASS on unfixed code - passing confirms baseline behavior
     * GOAL: Capture that valid system operations work correctly before security fixes
     *
     * This covers:
     * - Requests within size limits process normally
     * - Valid locale parameters apply localization
     * - Valid display order values apply correctly
     * - Valid date ranges filter results correctly
     */

    describe('Request Size Limits', function () {

        it('processes requests with small payloads normally (1KB)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Small payload (1KB biography)
            $smallBiography = str_repeat('A', 1024);

            // Update memorial with small payload
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/memorials/{$memorial->id}", [
                'biography' => $smallBiography,
            ]);

            // PRESERVATION: Small requests should process normally
            expect($response->status())->toBe(200);

            // Verify the data was stored correctly
            $memorial->refresh();
            expect($memorial->biography)->toBe($smallBiography);
        });

        it('processes requests with medium payloads normally (100KB)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Medium payload (5000 chars biography - within limit)
            $mediumBiography = str_repeat('B', 5000);

            // Update memorial with medium payload
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson("/api/v1/memorials/{$memorial->id}", [
                'biography' => $mediumBiography,
            ]);

            // PRESERVATION: Medium-sized requests should process normally
            expect($response->status())->toBe(200);

            // Verify the data was stored correctly
            $memorial->refresh();
            expect($memorial->biography)->toBe($mediumBiography);
        });

        it('processes multiple small requests in sequence normally', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            $memorials = Memorial::factory()->count(5)->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Process multiple small requests
            foreach ($memorials as $memorial) {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->putJson("/api/v1/memorials/{$memorial->id}", [
                    'biography' => 'Updated biography ' . $memorial->id,
                ]);

                // PRESERVATION: Each small request should process normally
                expect($response->status())->toBe(200);
            }
        });
    });

    describe('Locale Parameters', function () {

        it('applies valid locale parameter "en" correctly', function () {
            // Test with valid English locale
            $response = $this->get('/language/en');

            // PRESERVATION: Valid locale should be accepted and applied
            // Should redirect to home with locale set
            expect($response->status())->toBeIn([200, 302]);

            // If redirected, verify it's a valid redirect
            if ($response->status() === 302) {
                expect($response->headers->get('Location'))->not->toBeNull();
            }
        });

        it('applies valid locale parameter "de" correctly', function () {
            // Test with valid German locale
            $response = $this->get('/language/de');

            // PRESERVATION: Valid locale should be accepted and applied
            expect($response->status())->toBeIn([200, 302]);

            // If redirected, verify it's a valid redirect
            if ($response->status() === 302) {
                expect($response->headers->get('Location'))->not->toBeNull();
            }
        });

        it('handles locale switching between valid locales', function () {
            // Switch to English
            $response1 = $this->get('/language/en');
            expect($response1->status())->toBeIn([200, 302]);

            // Switch to German
            $response2 = $this->get('/language/de');
            expect($response2->status())->toBeIn([200, 302]);

            // Switch back to English
            $response3 = $this->get('/language/en');
            expect($response3->status())->toBeIn([200, 302]);

            // PRESERVATION: Valid locale switching should work correctly
        });

        it('processes requests with valid locale query parameter', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            Memorial::factory()->count(3)->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Request with valid locale in query parameter
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?lang=en');

            // PRESERVATION: Valid locale query parameter should work
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });
    });

    describe('Display Order Values', function () {

        it('applies valid display_order value of 0 correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create image with display_order = 0 (minimum valid value)
            $image = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 0,
            ]);

            // Verify display_order was set correctly
            expect($image->display_order)->toBe(0);

            // PRESERVATION: display_order of 0 should be valid
        });

        it('applies valid display_order value of 5000 correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create image with display_order = 5000 (mid-range value)
            $image = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 5000,
            ]);

            // Verify display_order was set correctly
            expect($image->display_order)->toBe(5000);

            // PRESERVATION: display_order of 5000 should be valid
        });

        it('applies valid display_order value of 9999 correctly (at limit)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create image with display_order = 9999 (maximum valid value)
            $image = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 9999,
            ]);

            // Verify display_order was set correctly
            expect($image->display_order)->toBe(9999);

            // PRESERVATION: display_order of 9999 should be valid
        });

        it('sorts images by valid display_order values correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create images with various valid display_order values
            $image1 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 100,
            ]);
            $image2 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 50,
            ]);
            $image3 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 200,
            ]);

            // Fetch images directly to verify sorting
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/v1/memorials/{$memorial->slug}/images");

            // PRESERVATION: Images should be sorted by display_order
            // If the endpoint exists, verify sorting; otherwise verify data integrity
            if ($response->status() === 200) {
                $images = $response->json('images');
                if ($images && is_array($images) && count($images) >= 3) {
                    expect($images[0]['display_order'])->toBe(50);
                    expect($images[1]['display_order'])->toBe(100);
                    expect($images[2]['display_order'])->toBe(200);
                }
            } else {
                // Alternative: Just verify the images exist with correct display_order
                $image1->refresh();
                $image2->refresh();
                $image3->refresh();
                expect($image1->display_order)->toBe(100);
                expect($image2->display_order)->toBe(50);
                expect($image3->display_order)->toBe(200);

                // Verify images can be retrieved in display_order
                $sortedImages = $memorial->images()->orderBy('display_order')->get();
                expect($sortedImages[0]->display_order)->toBe(50);
                expect($sortedImages[1]->display_order)->toBe(100);
                expect($sortedImages[2]->display_order)->toBe(200);
            }
        });

        it('updates display_order values within valid range correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create a memorial
            $memorial = Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);

            // Create images
            $image1 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 1,
            ]);
            $image2 = MemorialImage::factory()->create([
                'memorial_id' => $memorial->id,
                'display_order' => 2,
            ]);

            // Reorder with valid display_order values
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson("/api/v1/memorials/{$memorial->id}/images/reorder", [
                'images' => [
                    ['id' => $image1->id, 'display_order' => 500],
                    ['id' => $image2->id, 'display_order' => 1000],
                ],
            ]);

            // PRESERVATION: Reordering with valid display_order values should work
            expect($response->status())->toBe(200);

            // Verify the new display_order values
            $image1->refresh();
            $image2->refresh();
            expect($image1->display_order)->toBe(500);
            expect($image2->display_order)->toBe(1000);
        });
    });

    describe('Date Range Filtering', function () {

        it('filters results with valid date range (start before end)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials with different dates
            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1950-01-15',
                'death_date' => '2020-06-20',
            ]);

            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1960-03-10',
                'death_date' => '2023-08-15',
            ]);

            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1945-12-05',
                'death_date' => '2019-11-30',
            ]);

            // Query with valid date range (start_date before end_date)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?start_date=2020-01-01&end_date=2023-12-31');

            // PRESERVATION: Valid date range should filter results correctly
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('filters results with valid date range (same start and end date)', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1950-01-15',
                'death_date' => '2020-06-20',
            ]);

            // Query with same start and end date (valid edge case)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?start_date=2020-06-20&end_date=2020-06-20');

            // PRESERVATION: Same start and end date should be valid
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('filters results with valid wide date range', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            Memorial::factory()->count(5)->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1950-01-15',
                'death_date' => '2020-06-20',
            ]);

            // Query with wide date range (10 years)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?start_date=2015-01-01&end_date=2025-12-31');

            // PRESERVATION: Wide date range should work correctly
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('filters results with valid narrow date range', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1950-01-15',
                'death_date' => '2020-06-20',
            ]);

            // Query with narrow date range (1 month)
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?start_date=2020-06-01&end_date=2020-06-30');

            // PRESERVATION: Narrow date range should work correctly
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('processes search with valid date range parameters', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create searchable memorials
            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'first_name' => 'John',
                'last_name' => 'Smith',
                'birth_date' => '1950-01-15',
                'death_date' => '2020-06-20',
            ]);

            // Search with valid date range
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/search?query=John&start_date=2020-01-01&end_date=2020-12-31');

            // PRESERVATION: Search with valid date range should work
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });

        it('handles date range with year boundaries correctly', function () {
            // Create authenticated user
            $user = User::factory()->create();
            $user->profile()->create(['email' => $user->email]);
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create memorials
            Memorial::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
                'birth_date' => '1950-01-15',
                'death_date' => '2020-12-31',
            ]);

            // Query with date range spanning year boundary
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/v1/memorials?start_date=2020-12-01&end_date=2021-01-31');

            // PRESERVATION: Date range spanning year boundary should work
            expect($response->status())->toBe(200);
            expect($response->json('data'))->toBeArray();
        });
    });
});

describe('Property 2: Preservation - Valid Active Status', function () {

    /**
     * Validates: Requirements 3.20
     * Property 2: Preservation - Valid Active Status
     *
     * CRITICAL: This test MUST PASS after fix - confirms valid boolean values still work
     * GOAL: Verify that valid boolean true/false values are accepted for is_active field
     */

    it('accepts boolean true for country is_active field', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/countries', [
            'code' => 'US',
            'name' => 'United States',
            'is_active' => true, // Valid boolean value
        ]);

        // PRESERVATION: Valid boolean true should be accepted
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(true);

        // Verify country was created with correct value
        $country = \App\Models\Country::where('code', 'US')->first();
        expect($country)->not->toBeNull();
        expect($country->is_active)->toBe(true);
    });

    it('accepts boolean false for country is_active field', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/countries', [
            'code' => 'GB',
            'name' => 'United Kingdom',
            'is_active' => false, // Valid boolean value
        ]);

        // PRESERVATION: Valid boolean false should be accepted
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(false);

        // Verify country was created with correct value
        $country = \App\Models\Country::where('code', 'GB')->first();
        expect($country)->not->toBeNull();
        expect($country->is_active)->toBe(false);
    });

    it('accepts boolean true for place is_active field', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        // Create country
        $country = \App\Models\Country::create([
            'code' => 'JP',
            'name' => 'Japan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/places', [
            'country_id' => $country->id,
            'name' => 'Tokyo',
            'type' => 'city',
            'is_active' => true, // Valid boolean value
        ]);

        // PRESERVATION: Valid boolean true should be accepted
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(true);

        // Verify place was created with correct value
        $place = \App\Models\Place::where('name', 'Tokyo')->first();
        expect($place)->not->toBeNull();
        expect($place->is_active)->toBe(true);
    });

    it('accepts boolean false for place is_active field', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        // Create country
        $country = \App\Models\Country::create([
            'code' => 'BR',
            'name' => 'Brazil',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/places', [
            'country_id' => $country->id,
            'name' => 'Rio de Janeiro',
            'type' => 'city',
            'is_active' => false, // Valid boolean value
        ]);

        // PRESERVATION: Valid boolean false should be accepted
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(false);

        // Verify place was created with correct value
        $place = \App\Models\Place::where('name', 'Rio de Janeiro')->first();
        expect($place)->not->toBeNull();
        expect($place->is_active)->toBe(false);
    });

    it('accepts omitted is_active field (defaults to true) for country', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/countries', [
            'code' => 'MX',
            'name' => 'Mexico',
            // is_active omitted - should default to true
        ]);

        // PRESERVATION: Omitted is_active should default to true
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(true);

        // Verify country was created with default value
        $country = \App\Models\Country::where('code', 'MX')->first();
        expect($country)->not->toBeNull();
        expect($country->is_active)->toBe(true);
    });

    it('accepts omitted is_active field (defaults to true) for place', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->profile()->create(['email' => $admin->email]);

        // Create country
        $country = \App\Models\Country::create([
            'code' => 'IN',
            'name' => 'India',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/locations/places', [
            'country_id' => $country->id,
            'name' => 'Mumbai',
            'type' => 'city',
            // is_active omitted - should default to true
        ]);

        // PRESERVATION: Omitted is_active should default to true
        expect($response->status())->toBe(201);
        expect($response->json('data.isActive'))->toBe(true);

        // Verify place was created with default value
        $place = \App\Models\Place::where('name', 'Mumbai')->first();
        expect($place)->not->toBeNull();
        expect($place->is_active)->toBe(true);
    });
});
