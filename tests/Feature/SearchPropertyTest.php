<?php

use App\Models\User;
use App\Models\Memorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 30: Name search returns matching memorials
// Validates: Requirements 7.1

it('returns memorials matching search term in first_name or last_name', function () {
    // Create authenticated user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create memorials with various names
    $testCases = [
        ['first_name' => 'John', 'last_name' => 'Smith', 'search' => 'john', 'should_match' => true],
        ['first_name' => 'Jane', 'last_name' => 'Doe', 'search' => 'jane', 'should_match' => true],
        ['first_name' => 'Bob', 'last_name' => 'Johnson', 'search' => 'johnson', 'should_match' => true],
        ['first_name' => 'Alice', 'last_name' => 'Williams', 'search' => 'alice', 'should_match' => true],
        ['first_name' => 'Charlie', 'last_name' => 'Brown', 'search' => 'charlie', 'should_match' => true],
        ['first_name' => 'David', 'last_name' => 'Miller', 'search' => 'miller', 'should_match' => true],
        ['first_name' => 'Emma', 'last_name' => 'Davis', 'search' => 'emma', 'should_match' => true],
        ['first_name' => 'Frank', 'last_name' => 'Wilson', 'search' => 'frank', 'should_match' => true],
    ];

    foreach ($testCases as $index => $testCase) {
        $memorial = Memorial::create([
            'user_id' => $user->id,
            'first_name' => $testCase['first_name'],
            'last_name' => $testCase['last_name'],
            'birth_date' => '1950-01-01',
            'death_date' => '2020-01-01',
            'slug' => strtolower($testCase['first_name']) . '.' . strtolower($testCase['last_name']) . '.' . $index,
            'is_public' => true,
        ]);

        // Search by the search term (case-insensitive)
        $response = $this->getJson('/api/v1/search?query=' . $testCase['search']);
        $response->assertStatus(200);

        $memorials = $response->json('data');
        $slugs = array_column($memorials, 'slug');

        if ($testCase['should_match']) {
            expect($slugs)->toContain($memorial->slug);
        }
    }

    // Test partial matching
    Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Jonathan',
        'last_name' => 'Smithson',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'jonathan.smithson',
        'is_public' => true,
    ]);

    // Search for "smith" should match "Smithson"
    $response = $this->getJson('/api/v1/search?query=smith');
    $response->assertStatus(200);
    $memorials = $response->json('data');
    $names = array_map(fn($m) => $m['last_name'], $memorials);
    expect($names)->toContain('Smithson');

    // Test case-insensitive search
    $response = $this->getJson('/api/v1/search?query=JOHN');
    $response->assertStatus(200);
    $memorials = $response->json('data');
    $firstNames = array_map(fn($m) => $m['first_name'], $memorials);
    expect($firstNames)->toContain('John');
    expect($firstNames)->toContain('Jonathan');
})->repeat(100);

// Feature: laravel-migration, Property 31: Public-only search for unauthenticated users
// Validates: Requirements 7.2

it('returns only public memorials for unauthenticated users', function () {
    // Create user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create public memorial
    $publicMemorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Public',
        'last_name' => 'Person',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'public.person',
        'is_public' => true,
    ]);

    // Create private memorial
    $privateMemorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Private',
        'last_name' => 'Person',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'private.person',
        'is_public' => false,
    ]);

    // Unauthenticated search should only return public memorials
    $response = $this->getJson('/api/v1/search?query=person');
    $response->assertStatus(200);

    $memorials = $response->json('data');
    $slugs = array_column($memorials, 'slug');

    // Should contain public memorial
    expect($slugs)->toContain('public.person');

    // Should NOT contain private memorial
    expect($slugs)->not->toContain('private.person');

    // All returned memorials should have is_public = true
    foreach ($memorials as $memorial) {
        expect($memorial['is_public'])->toBeTrue();
    }
})->repeat(100);

// Feature: laravel-migration, Property 32: User sees all own memorials
// Validates: Requirements 7.3

it('returns all own memorials for authenticated users regardless of public status', function () {
    // Create user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);
    $token = $user->createToken('auth_token')->plainTextToken;

    // Create public memorial
    $publicMemorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Public',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'public.memorial',
        'is_public' => true,
    ]);

    // Create private memorial
    $privateMemorial = Memorial::create([
        'user_id' => $user->id,
        'first_name' => 'Private',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'private.memorial',
        'is_public' => false,
    ]);

    // Create another user's private memorial
    $otherUser = User::create([
        'email' => 'other@test.com',
        'password' => Hash::make('password123'),
    ]);
    $otherUser->profile()->create(['email' => 'other@test.com']);

    $otherPrivateMemorial = Memorial::create([
        'user_id' => $otherUser->id,
        'first_name' => 'Other',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'other.memorial',
        'is_public' => false,
    ]);

    // Authenticated search should return both public and private memorials for the user
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/search?query=memorial');

    $response->assertStatus(200);

    $memorials = $response->json('data');
    $slugs = array_column($memorials, 'slug');

    // Should contain both own memorials (public and private)
    expect($slugs)->toContain('public.memorial');
    expect($slugs)->toContain('private.memorial');

    // Should NOT contain other user's private memorial
    expect($slugs)->not->toContain('other.memorial');
})->repeat(100);

// Feature: laravel-migration, Property 33: Search pagination
// Validates: Requirements 7.4

it('returns paginated search results with correct structure', function () {
    // Create user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create multiple memorials
    for ($i = 1; $i <= 25; $i++) {
        Memorial::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Person' . $i,
            'birth_date' => '1950-01-01',
            'death_date' => '2020-01-01',
            'slug' => 'test.person' . $i,
            'is_public' => true,
        ]);
    }

    // Test default pagination (15 per page)
    $response = $this->getJson('/api/v1/search?query=test');
    $response->assertStatus(200);

    // Check pagination structure
    $response->assertJsonStructure([
        'current_page',
        'data',
        'first_page_url',
        'from',
        'last_page',
        'last_page_url',
        'links',
        'next_page_url',
        'path',
        'per_page',
        'prev_page_url',
        'to',
        'total',
    ]);

    $json = $response->json();

    // Should have 15 results on first page (default per_page)
    expect($json['data'])->toHaveCount(15);
    expect($json['per_page'])->toBe(15);
    expect($json['current_page'])->toBe(1);
    expect($json['total'])->toBe(25);
    expect($json['last_page'])->toBe(2);

    // Test custom per_page
    $response = $this->getJson('/api/v1/search?query=test&per_page=10');
    $response->assertStatus(200);

    $json = $response->json();
    expect($json['data'])->toHaveCount(10);
    expect($json['per_page'])->toBe(10);
    expect($json['total'])->toBe(25);
    expect($json['last_page'])->toBe(3);

    // Test page 2
    $response = $this->getJson('/api/v1/search?query=test&per_page=10&page=2');
    $response->assertStatus(200);

    $json = $response->json();
    expect($json['data'])->toHaveCount(10);
    expect($json['current_page'])->toBe(2);

    // Test last page
    $response = $this->getJson('/api/v1/search?query=test&per_page=10&page=3');
    $response->assertStatus(200);

    $json = $response->json();
    expect($json['data'])->toHaveCount(5); // Remaining 5 items
    expect($json['current_page'])->toBe(3);
})->repeat(100);

// Feature: laravel-migration, Property 34: Search result sorting
// Validates: Requirements 7.5

it('sorts search results by created_at in descending order', function () {
    // Create user
    $user = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $user->profile()->create(['email' => 'user@test.com']);

    // Create memorials with different timestamps using DB::table to ensure timestamps are set correctly
    $baseTime = now();

    DB::table('memorials')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'first_name' => 'First',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'first.memorial',
        'is_public' => true,
        'created_at' => $baseTime->copy()->subDays(10),
        'updated_at' => $baseTime->copy()->subDays(10),
    ]);

    DB::table('memorials')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'first_name' => 'Second',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'second.memorial',
        'is_public' => true,
        'created_at' => $baseTime->copy()->subDays(5),
        'updated_at' => $baseTime->copy()->subDays(5),
    ]);

    DB::table('memorials')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'first_name' => 'Third',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'third.memorial',
        'is_public' => true,
        'created_at' => $baseTime->copy()->subDays(1),
        'updated_at' => $baseTime->copy()->subDays(1),
    ]);

    DB::table('memorials')->insert([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'first_name' => 'Fourth',
        'last_name' => 'Memorial',
        'birth_date' => '1950-01-01',
        'death_date' => '2020-01-01',
        'slug' => 'fourth.memorial',
        'is_public' => true,
        'created_at' => $baseTime,
        'updated_at' => $baseTime,
    ]);

    // Search for memorials
    $response = $this->getJson('/api/v1/search?query=memorial');
    $response->assertStatus(200);

    $memorials = $response->json('data');

    // Should be sorted by created_at DESC (newest first)
    expect($memorials[0]['slug'])->toBe('fourth.memorial');
    expect($memorials[1]['slug'])->toBe('third.memorial');
    expect($memorials[2]['slug'])->toBe('second.memorial');
    expect($memorials[3]['slug'])->toBe('first.memorial');

    // Verify timestamps are in descending order
    $timestamps = array_map(fn($m) => strtotime($m['created_at']), $memorials);
    for ($i = 0; $i < count($timestamps) - 1; $i++) {
        expect($timestamps[$i])->toBeGreaterThanOrEqual($timestamps[$i + 1]);
    }
})->repeat(100);
