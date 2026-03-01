<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 1: Registration creates user account
// Validates: Requirements 1.1

it('creates user account for any valid email and password combination', function () {
    $testData = [
        ['email' => 'user1@example.com', 'password' => 'password123'],
        ['email' => 'test.user@domain.co.uk', 'password' => 'SecurePass!@#'],
        ['email' => 'admin+test@company.org', 'password' => 'MyP@ssw0rd'],
        ['email' => 'john.doe@subdomain.example.com', 'password' => 'Test1234!'],
        ['email' => 'user_name@test-domain.net', 'password' => 'ValidPass99'],
    ];

    foreach ($testData as $data) {
        $response = $this->postJson('/api/v1/register', [
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password'],
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // Should return user and token
        $response->assertJsonStructure([
            'user' => ['id', 'email', 'profile'],
            'token',
        ]);

        // User should exist in database
        $this->assertDatabaseHas('users', [
            'email' => $data['email'],
        ]);

        // Password should be hashed
        $user = User::where('email', $data['email'])->first();
        expect($user)->not->toBeNull();
        expect(Hash::check($data['password'], $user->password))->toBeTrue();

        // Profile should be auto-created
        expect($user->profile)->not->toBeNull();
        expect($user->profile->email)->toBe($data['email']);
    }
})->repeat(100);

// Feature: laravel-migration, Property 2: Login returns JWT token
// Validates: Requirements 1.2

it('returns JWT token for any registered user with valid credentials', function () {
    $testUsers = [
        ['email' => 'user1@test.com', 'password' => 'password123'],
        ['email' => 'user2@test.com', 'password' => 'SecurePass!@#'],
        ['email' => 'user3@test.com', 'password' => 'MyP@ssw0rd'],
        ['email' => 'user4@test.com', 'password' => 'Test1234!'],
        ['email' => 'user5@test.com', 'password' => 'ValidPass99'],
    ];

    foreach ($testUsers as $userData) {
        // Create user
        $user = User::create([
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
        ]);

        // Create profile
        $user->profile()->create([
            'email' => $userData['email'],
        ]);

        // Attempt login
        $response = $this->postJson('/api/v1/login', [
            'email' => $userData['email'],
            'password' => $userData['password'],
        ]);

        // Should return 200 OK
        $response->assertStatus(200);

        // Should return user and token
        $response->assertJsonStructure([
            'user' => ['id', 'email', 'profile'],
            'token',
        ]);

        // Token should be a non-empty string
        $token = $response->json('token');
        expect($token)->toBeString();
        expect($token)->not->toBeEmpty();

        // Token should be valid (contains pipe separator for Sanctum tokens)
        expect($token)->toContain('|');
    }
})->repeat(100);

// Feature: laravel-migration, Property 3: Valid token authenticates user
// Validates: Requirements 1.3

it('authenticates user with valid JWT token for any protected resource', function () {
    // Create user
    $user = User::create([
        'email' => 'auth@test.com',
        'password' => Hash::make('password123'),
    ]);

    // Create profile
    $user->profile()->create([
        'email' => 'auth@test.com',
    ]);

    // Generate token
    $token = $user->createToken('auth_token')->plainTextToken;

    // Access protected endpoint with token
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/me');

    // Should return 200 OK
    $response->assertStatus(200);

    // Should return authenticated user data
    $response->assertJsonStructure([
        'user' => ['id', 'email', 'profile'],
    ]);

    // Should return correct user
    $response->assertJson([
        'user' => [
            'email' => 'auth@test.com',
        ],
    ]);
})->repeat(100);

it('rejects access to protected resources without valid token', function () {
    $testCases = [
        ['header' => null, 'description' => 'no token'],
        ['header' => 'Bearer invalid_token', 'description' => 'invalid token'],
        ['header' => 'Bearer ', 'description' => 'empty token'],
        ['header' => 'InvalidFormat token123', 'description' => 'wrong format'],
    ];

    foreach ($testCases as $testCase) {
        $headers = $testCase['header'] ? ['Authorization' => $testCase['header']] : [];

        $response = $this->withHeaders($headers)->getJson('/api/v1/me');

        // Should return 401 Unauthorized
        $response->assertStatus(401);
    }
});
