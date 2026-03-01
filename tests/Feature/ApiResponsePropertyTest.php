<?php

use App\Models\User;
use App\Models\Profile;
use App\Models\Memorial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 43: JSON responses with status codes
test('successful GET requests return 200 with JSON', function () {
    $response = $this->getJson('/api/v1/memorials');
    expect($response->status())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
})->repeat(10);

test('successful POST requests return 201 with JSON', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'birth_date' => '1950-01-01',
            'death_date' => '2020-01-01',
            'is_public' => true,
        ]);
    expect($response->status())->toBe(201)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
})->repeat(10);

test('unauthorized requests return 401 with JSON', function () {
    $response = $this->postJson('/api/v1/memorials', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'birth_date' => '1960-01-01',
        'death_date' => '2021-01-01',
    ]);
    expect($response->status())->toBe(401)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
})->repeat(10);

test('not found requests return 404 with JSON', function () {
    $response = $this->getJson('/api/v1/memorials/non-existent-slug');
    expect($response->status())->toBe(404)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
})->repeat(10);

test('validation errors return 422 with JSON', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'first_name' => 'Test',
            // Missing required fields
        ]);
    expect($response->status())->toBe(422)
        ->and($response->headers->get('Content-Type'))->toContain('application/json');
})->repeat(10);

// Feature: laravel-migration, Property 44: Structured error responses
test('validation error responses contain message and errors fields', function () {
    $user = User::factory()->create();
    Profile::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'first_name' => 'Test',
            // Missing required fields
        ]);

    expect($response->status())->toBe(422)
        ->and($response->json())->toHaveKey('message')
        ->and($response->json())->toHaveKey('errors')
        ->and($response->json('message'))->toBeString();
})->repeat(10);

test('not found error responses contain message field', function () {
    $response = $this->getJson('/api/v1/memorials/non-existent-slug');

    expect($response->status())->toBe(404)
        ->and($response->json())->toHaveKey('message')
        ->and($response->json('message'))->toBeString();
})->repeat(10);

test('unauthorized error responses contain message field', function () {
    $response = $this->postJson('/api/v1/memorials', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
    ]);

    expect($response->status())->toBe(401)
        ->and($response->json())->toHaveKey('message')
        ->and($response->json('message'))->toBeString();
})->repeat(10);

// Feature: laravel-migration, Property 45: Validation errors return 422
test('validation errors return 422 status with detailed errors', function () {
    $user = User::factory()->create();

    // Test memorial validation
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/memorials', [
            'first_name' => 'Test',
            // Missing last_name, birth_date, death_date
        ]);

    expect($response->status())->toBe(422)
        ->and($response->json())->toHaveKey('message')
        ->and($response->json())->toHaveKey('errors')
        ->and($response->json('errors'))->toBeArray()
        ->and($response->json('errors'))->toHaveKey('last_name')
        ->and($response->json('errors'))->toHaveKey('birth_date')
        ->and($response->json('errors'))->toHaveKey('death_date');

    // Test tribute validation
    $memorial = Memorial::factory()->create(['user_id' => $user->id]);
    $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
        'author_name' => 'Test',
        // Missing author_email and message
    ]);

    expect($response->status())->toBe(422)
        ->and($response->json())->toHaveKey('errors')
        ->and($response->json('errors'))->toHaveKey('author_email')
        ->and($response->json('errors'))->toHaveKey('message');

    // Test profile validation
    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/v1/profiles/{$user->id}", [
            'email' => 'invalid-email', // Invalid email format
        ]);

    expect($response->status())->toBe(422)
        ->and($response->json())->toHaveKey('errors')
        ->and($response->json('errors'))->toHaveKey('email');
})->repeat(10);
