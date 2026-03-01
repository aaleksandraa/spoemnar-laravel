<?php

use App\Models\User;
use App\Models\Memorial;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 25: Tribute required fields validation
// Validates: Requirements 6.1

it('rejects tribute submission missing required fields for any memorial', function () {
    // Create memorial
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Test missing author_name
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_email' => 'visitor@example.com',
        'message' => 'Rest in peace',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_name']);

    // Test missing author_email
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Jane Smith',
        'message' => 'Rest in peace',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_email']);

    // Test missing message
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Jane Smith',
        'author_email' => 'visitor@example.com',
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['message']);

    // Test all fields missing
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", []);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['author_name', 'author_email', 'message']);
})->repeat(100);

// Feature: laravel-migration, Property 26: Email format validation
// Validates: Requirements 6.2

it('validates author_email format for any tribute submission', function () {
    // Create memorial
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Test valid email formats
    $validEmails = [
        'user@example.com',
        'test.user@example.com',
        'user+tag@example.co.uk',
        'user_name@example-domain.com',
    ];

    foreach ($validEmails as $email) {
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'Jane Smith',
            'author_email' => $email,
            'message' => 'Rest in peace',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'tribute' => ['id', 'author_email', 'memorial_id'],
        ]);
    }

    // Test invalid email formats
    $invalidEmails = [
        'not-an-email',
        '@example.com',
        'user@',
        'user space@example.com',
        '',
    ];

    foreach ($invalidEmails as $email) {
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'Jane Smith',
            'author_email' => $email,
            'message' => 'Rest in peace',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['author_email']);
    }
})->repeat(100);

// Feature: laravel-migration, Property 27: Tribute timestamp auto-creation
// Validates: Requirements 6.3

it('automatically sets created_at timestamp for any newly created tribute', function () {
    // Create memorial
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Create tribute
    $beforeCreation = now();

    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Jane Smith',
        'author_email' => 'visitor@example.com',
        'message' => 'Rest in peace',
    ]);

    $afterCreation = now();

    $response->assertStatus(201);

    $tributeId = $response->json('tribute.id');
    $tribute = Tribute::find($tributeId);

    // Verify created_at is set
    expect($tribute->created_at)->not->toBeNull();

    // Verify created_at is between before and after timestamps
    expect($tribute->created_at->timestamp)->toBeGreaterThanOrEqual($beforeCreation->timestamp);
    expect($tribute->created_at->timestamp)->toBeLessThanOrEqual($afterCreation->timestamp);
})->repeat(100);

// Feature: laravel-migration, Property 28: Owner can delete tributes
// Validates: Requirements 6.4

it('allows memorial owner to delete any tribute on their memorial', function () {
    // Create memorial owner
    $owner = User::create([
        'email' => 'owner@test.com',
        'password' => Hash::make('password123'),
    ]);
    $owner->profile()->create(['email' => 'owner@test.com']);
    $ownerToken = $owner->createToken('auth_token')->plainTextToken;

    $memorial = Memorial::create([
        'user_id' => $owner->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Create tribute
    $tribute = Tribute::create([
        'memorial_id' => $memorial->id,
        'author_name' => 'Jane Smith',
        'author_email' => 'visitor@example.com',
        'message' => 'Rest in peace',
    ]);

    // Verify tribute exists
    $this->assertDatabaseHas('tributes', ['id' => $tribute->id]);

    // Owner deletes tribute
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $ownerToken,
    ])->deleteJson("/api/v1/tributes/{$tribute->id}");

    $response->assertStatus(204);

    // Verify tribute is deleted
    $this->assertDatabaseMissing('tributes', ['id' => $tribute->id]);
})->repeat(100);

// Feature: laravel-migration, Property 29: Unauthenticated tribute creation
// Validates: Requirements 6.5

it('allows unauthenticated users to create tributes for any memorial', function () {
    // Create memorial
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    $memorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'john.doe',
        'is_public' => true,
    ]);

    // Create tribute WITHOUT authentication
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Anonymous Visitor',
        'author_email' => 'anonymous@example.com',
        'message' => 'You will be missed',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'tribute' => [
            'id',
            'memorial_id',
            'author_name',
            'author_email',
            'message',
            'created_at',
        ],
    ]);

    // Verify tribute was created in database
    $tributeId = $response->json('tribute.id');
    $this->assertDatabaseHas('tributes', [
        'id' => $tributeId,
        'memorial_id' => $memorial->id,
        'author_name' => 'Anonymous Visitor',
        'author_email' => 'anonymous@example.com',
        'message' => 'You will be missed',
    ]);
})->repeat(100);

