<?php

use App\Models\User;
use App\Models\Memorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 9: Memorial required fields validation
// Validates: Requirements 3.1

it('rejects memorial creation when required fields are missing', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    $testCases = [
        ['data' => ['last_name' => 'Doe', 'birth_date' => '1950-01-01', 'death_date' => '2020-01-01'], 'missing' => 'first_name'],
        ['data' => ['first_name' => 'John', 'birth_date' => '1950-01-01', 'death_date' => '2020-01-01'], 'missing' => 'last_name'],
        ['data' => ['first_name' => 'John', 'last_name' => 'Doe', 'death_date' => '2020-01-01'], 'missing' => 'birth_date'],
        ['data' => ['first_name' => 'John', 'last_name' => 'Doe', 'birth_date' => '1950-01-01'], 'missing' => 'death_date'],
    ];

    foreach ($testCases as $testCase) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', $testCase['data']);

        // Should return 422 Unprocessable Entity
        $response->assertStatus(422);

        // Should have validation error for missing field
        $response->assertJsonValidationErrors([$testCase['missing']]);
    }
})->repeat(100);

// Feature: laravel-migration, Property 11: Optional fields acceptance
// Validates: Requirements 3.3

it('successfully creates memorial when optional fields are omitted', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    $testCases = [
        ['first_name' => 'John', 'last_name' => 'Doe', 'birth_date' => '1950-01-01', 'death_date' => '2020-01-01'],
        ['first_name' => 'Jane', 'last_name' => 'Smith', 'birth_date' => '1960-05-15', 'death_date' => '2021-03-20', 'birth_place' => 'New York'],
        ['first_name' => 'Bob', 'last_name' => 'Johnson', 'birth_date' => '1945-12-25', 'death_date' => '2019-11-10', 'death_place' => 'London'],
        ['first_name' => 'Alice', 'last_name' => 'Williams', 'birth_date' => '1955-07-04', 'death_date' => '2022-02-14', 'biography' => 'A wonderful person'],
        ['first_name' => 'Charlie', 'last_name' => 'Brown', 'birth_date' => '1940-03-30', 'death_date' => '2018-09-05', 'profile_image_url' => 'https://example.com/image.jpg'],
    ];

    foreach ($testCases as $data) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', $data);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return memorial data
        $response->assertJsonStructure([
            'memorial' => ['id', 'first_name', 'last_name', 'birth_date', 'death_date', 'slug'],
        ]);

        // Memorial should exist in database
        $this->assertDatabaseHas('memorials', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);
    }
})->repeat(100);

// Feature: laravel-migration, Property 12: Public visibility control
// Validates: Requirements 3.4

it('excludes private memorials from public search results', function () {
    // Create two users
    $user1 = User::create([
        'email' => 'user1@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user1->profile()->create(['email' => 'user1@test.com']);

    $user2 = User::create([
        'email' => 'user2@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user2->profile()->create(['email' => 'user2@test.com']);

    // Create public memorial
    $publicMemorial = Memorial::create([
        'user_id' => $user1->id,
        'first_name' => 'Public',
        'last_name' => 'Person',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'public.person',
        'is_public' => true,
    ]);

    // Create private memorial
    $privateMemorial = Memorial::create([
        'user_id' => $user1->id,
        'first_name' => 'Private',
        'last_name' => 'Person',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'private.person',
        'is_public' => false,
    ]);

    // Unauthenticated request should only see public memorials
    $response = $this->getJson('/api/v1/memorials');
    $response->assertStatus(200);

    $memorials = $response->json('data');
    $slugs = array_column($memorials, 'slug');

    expect($slugs)->toContain('public.person');
    expect($slugs)->not->toContain('private.person');
})->repeat(100);

// Feature: laravel-migration, Property 13: Updated timestamp on modification
// Validates: Requirements 3.5

it('updates timestamp when memorial is modified', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    $originalUpdatedAt = $memorial->updated_at;

    // Wait a moment to ensure timestamp difference
    sleep(1);

    // Update memorial
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->putJson('/api/v1/memorials/' . $memorial->id, [
        'biography' => 'Updated biography',
    ]);

    $response->assertStatus(200);

    // Refresh memorial from database
    $memorial->refresh();

    // Updated timestamp should be different
    expect($memorial->updated_at->timestamp)->toBeGreaterThan($originalUpdatedAt->timestamp);
})->repeat(100);

// Feature: laravel-migration, Property 14: Cascade delete memorial resources
// Validates: Requirements 3.6

it('deletes all associated resources when memorial is deleted', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create memorial
    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Create associated resources
    $memorial->images()->create([
        'image_url' => 'https://example.com/image1.jpg',
        'display_order' => 1,
    ]);

    $memorial->videos()->create([
        'youtube_url' => 'https://youtube.com/watch?v=abc123',
        'title' => 'Video 1',
        'display_order' => 1,
    ]);

    $memorial->tributes()->create([
        'author_name' => 'Friend',
        'author_email' => 'friend@example.com',
        'message' => 'Rest in peace',
    ]);

    $memorialId = $memorial->id;

    // Delete memorial
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->deleteJson('/api/v1/memorials/' . $memorial->id);

    $response->assertStatus(204);

    // Memorial should be deleted
    $this->assertDatabaseMissing('memorials', ['id' => $memorialId]);

    // Associated resources should be deleted (cascade)
    $this->assertDatabaseMissing('memorial_images', ['memorial_id' => $memorialId]);
    $this->assertDatabaseMissing('memorial_videos', ['memorial_id' => $memorialId]);
    $this->assertDatabaseMissing('tributes', ['memorial_id' => $memorialId]);
})->repeat(100);

// Feature: laravel-migration, Property 47: Date format validation
// Validates: Requirements 11.3

it('rejects memorial creation with invalid date formats', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    $invalidDateFormats = [
        '01-01-1950',      // MM-DD-YYYY
        '1950/01/01',      // YYYY/MM/DD
        '01.01.1950',      // DD.MM.YYYY
        '1950-1-1',        // Missing leading zeros
        '1950-13-01',      // Invalid month
        '1950-01-32',      // Invalid day
        'not-a-date',      // Invalid string
        '1950-02-30',      // Invalid date (Feb 30)
    ];

    foreach ($invalidDateFormats as $invalidDate) {
        // Test invalid birth_date
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => $invalidDate,
            'death_date' => '2020-01-01',
        ]);

        // Should return 422 Unprocessable Entity
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['birth_date']);

        // Test invalid death_date
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '1950-01-01',
            'death_date' => $invalidDate,
        ]);

        // Should return 422 Unprocessable Entity
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['death_date']);
    }

    // Test valid date format (YYYY-MM-DD) should succeed
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->postJson('/api/v1/memorials', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-12-31',
    ]);

    $response->assertStatus(201);
})->repeat(100);

// Feature: laravel-migration, Property 48: Death date after birth date
// Validates: Requirements 11.4

it('rejects memorial creation when death date is before or equal to birth date', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    $invalidDateCombinations = [
        ['birth_date' => '2020-01-01', 'death_date' => '2020-01-01'], // Same date
        ['birth_date' => '2020-01-01', 'death_date' => '2019-12-31'], // Death before birth
        ['birth_date' => '2020-06-15', 'death_date' => '2020-06-14'], // Death one day before
        ['birth_date' => '2020-12-31', 'death_date' => '2020-01-01'], // Death much earlier
        ['birth_date' => '1950-01-01', 'death_date' => '1949-12-31'], // Death in previous year
    ];

    foreach ($invalidDateCombinations as $dates) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => $dates['birth_date'],
            'death_date' => $dates['death_date'],
        ]);

        // Should return 422 Unprocessable Entity
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['death_date']);
    }

    // Test valid date combination (death after birth) should succeed
    $validDateCombinations = [
        ['birth_date' => '1950-01-01', 'death_date' => '2020-01-01'], // 70 years later
        ['birth_date' => '2020-01-01', 'death_date' => '2020-01-02'], // One day later
        ['birth_date' => '1980-06-15', 'death_date' => '2021-12-25'], // Many years later
    ];

    foreach ($validDateCombinations as $dates) {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/memorials', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => $dates['birth_date'],
            'death_date' => $dates['death_date'],
        ]);

        $response->assertStatus(201);
    }
})->repeat(100);
