<?php

use App\Models\Memorial;
use App\Models\Tribute;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 35: Admin role verification
// Validates: Requirements 8.1

it('verifies admin role before granting access to admin panel', function () {
    // Test with admin user
    $adminUser = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $adminUser->profile()->create(['email' => $adminUser->email]);
    UserRole::create(['user_id' => $adminUser->id, 'role' => 'admin']);

    $adminToken = $adminUser->createToken('auth_token')->plainTextToken;
    $adminResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->getJson('/api/v1/admin/dashboard');

    $adminResponse->assertStatus(200);
    $adminResponse->assertJsonStructure([
        'total_users',
        'total_memorials',
        'public_memorials',
        'private_memorials',
    ]);

    // Explicitly logout/clear auth state
    auth()->guard('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();

    // Test with non-admin user
    $regularUser = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $regularUser->profile()->create(['email' => $regularUser->email]);
    UserRole::create(['user_id' => $regularUser->id, 'role' => 'user']);

    $regularToken = $regularUser->createToken('auth_token')->plainTextToken;
    $regularResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $regularToken,
    ])->getJson('/api/v1/admin/dashboard');

    $regularResponse->assertStatus(403);
    $regularResponse->assertJson([
        'message' => 'Forbidden. You do not have permission to access this resource.',
    ]);

    // Explicitly logout/clear auth state
    auth()->guard('sanctum')->forgetUser();
    $this->app['auth']->forgetGuards();

    // Test with user with no roles
    $noRoleUser = User::create([
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password123'),
    ]);
    $noRoleUser->profile()->create(['email' => $noRoleUser->email]);

    $noRoleToken = $noRoleUser->createToken('auth_token')->plainTextToken;
    $noRoleResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $noRoleToken,
    ])->getJson('/api/v1/admin/dashboard');

    $noRoleResponse->assertStatus(403);
    $noRoleResponse->assertJson([
        'message' => 'Forbidden. You do not have permission to access this resource.',
    ]);
})->repeat(100);

// Feature: laravel-migration, Property 36: Admin sees all memorials
// Validates: Requirements 8.2

it('allows admin to see all memorials regardless of is_public status', function () {
    // Create admin user
    $admin = User::create([
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    // Create regular user
    $regularUser = User::create([
        'email' => 'user@test.com',
        'password' => Hash::make('password123'),
    ]);
    $regularUser->profile()->create(['email' => $regularUser->email]);

    // Create mix of public and private memorials
    $publicCount = rand(2, 5);
    $privateCount = rand(2, 5);

    for ($i = 0; $i < $publicCount; $i++) {
        Memorial::create([
            'user_id' => $regularUser->id,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'death_date' => fake()->date(),
            'slug' => fake()->unique()->slug(),
            'is_public' => true,
        ]);
    }

    for ($i = 0; $i < $privateCount; $i++) {
        Memorial::create([
            'user_id' => $regularUser->id,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date(),
            'death_date' => fake()->date(),
            'slug' => fake()->unique()->slug(),
            'is_public' => false,
        ]);
    }

    // Admin should see all memorials
    $token = $admin->createToken('auth_token')->plainTextToken;
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
    ])->getJson('/api/v1/admin/memorials');

    $response->assertStatus(200);

    // Should return all memorials (public + private)
    $totalMemorials = $publicCount + $privateCount;
    expect($response->json('total'))->toBe($totalMemorials);

    // Verify response contains both public and private memorials
    $memorials = $response->json('data');
    $publicInResponse = collect($memorials)->where('is_public', true)->count();
    $privateInResponse = collect($memorials)->where('is_public', false)->count();

    expect($publicInResponse)->toBe($publicCount);
    expect($privateInResponse)->toBe($privateCount);
})->repeat(100);

// Feature: laravel-migration, Property 37: Admin can delete any resource
// Validates: Requirements 8.3

it('allows admin to delete any memorial or tribute regardless of ownership', function () {
    // Create admin user
    $admin = User::create([
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    // Create regular user who owns resources
    $owner = User::create([
        'email' => 'owner@test.com',
        'password' => Hash::make('password123'),
    ]);
    $owner->profile()->create(['email' => $owner->email]);

    // Create memorial owned by regular user
    $memorial = Memorial::create([
        'user_id' => $owner->id,
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'birth_date' => fake()->date(),
        'death_date' => fake()->date(),
        'slug' => fake()->unique()->slug(),
        'is_public' => true,
    ]);

    // Create tribute on the memorial
    $tribute = Tribute::create([
        'memorial_id' => $memorial->id,
        'author_name' => fake()->name(),
        'author_email' => fake()->safeEmail(),
        'message' => fake()->paragraph(),
    ]);

    $adminToken = $admin->createToken('auth_token')->plainTextToken;

    // Admin should be able to delete memorial (not owned by admin)
    $deleteMemorialResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->deleteJson("/api/v1/admin/memorials/{$memorial->id}");

    $deleteMemorialResponse->assertStatus(200);
    $deleteMemorialResponse->assertJson([
        'message' => 'Memorial deleted successfully.',
    ]);

    // Memorial should be deleted from database
    $this->assertDatabaseMissing('memorials', ['id' => $memorial->id]);

    // Create another memorial and tribute for tribute deletion test
    $memorial2 = Memorial::create([
        'user_id' => $owner->id,
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'birth_date' => fake()->date(),
        'death_date' => fake()->date(),
        'slug' => fake()->unique()->slug(),
        'is_public' => true,
    ]);

    $tribute2 = Tribute::create([
        'memorial_id' => $memorial2->id,
        'author_name' => fake()->name(),
        'author_email' => fake()->safeEmail(),
        'message' => fake()->paragraph(),
    ]);

    // Admin should be able to delete tribute
    $deleteTributeResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->deleteJson("/api/v1/admin/tributes/{$tribute2->id}");

    $deleteTributeResponse->assertStatus(200);
    $deleteTributeResponse->assertJson([
        'message' => 'Tribute deleted successfully.',
    ]);

    // Tribute should be deleted from database
    $this->assertDatabaseMissing('tributes', ['id' => $tribute2->id]);
})->repeat(100);

// Feature: laravel-migration, Property 38: Admin can manage user roles
// Validates: Requirements 8.4

it('allows admin to add or remove user roles', function () {
    // Create admin user
    $admin = User::create([
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
    ]);
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);

    // Create target user
    $targetUser = User::create([
        'email' => 'target@test.com',
        'password' => Hash::make('password123'),
    ]);
    $targetUser->profile()->create(['email' => $targetUser->email]);

    $adminToken = $admin->createToken('auth_token')->plainTextToken;

    // Test adding admin role
    $addRoleResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->putJson("/api/v1/admin/users/{$targetUser->id}/role", [
        'role' => 'admin',
        'action' => 'add',
    ]);

    $addRoleResponse->assertStatus(200);
    $addRoleResponse->assertJson([
        'message' => 'Role added successfully.',
    ]);

    // Verify role was added to database
    $this->assertDatabaseHas('user_roles', [
        'user_id' => $targetUser->id,
        'role' => 'admin',
    ]);

    // Test removing admin role
    $removeRoleResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->putJson("/api/v1/admin/users/{$targetUser->id}/role", [
        'role' => 'admin',
        'action' => 'remove',
    ]);

    $removeRoleResponse->assertStatus(200);
    $removeRoleResponse->assertJson([
        'message' => 'Role removed successfully.',
    ]);

    // Verify role was removed from database
    $this->assertDatabaseMissing('user_roles', [
        'user_id' => $targetUser->id,
        'role' => 'admin',
    ]);

    // Test adding user role
    $addUserRoleResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->putJson("/api/v1/admin/users/{$targetUser->id}/role", [
        'role' => 'user',
        'action' => 'add',
    ]);

    $addUserRoleResponse->assertStatus(200);

    // Verify user role was added
    $this->assertDatabaseHas('user_roles', [
        'user_id' => $targetUser->id,
        'role' => 'user',
    ]);

    // Test idempotency - adding same role twice should not cause error
    $addAgainResponse = $this->withHeaders([
        'Authorization' => 'Bearer ' . $adminToken,
    ])->putJson("/api/v1/admin/users/{$targetUser->id}/role", [
        'role' => 'user',
        'action' => 'add',
    ]);

    $addAgainResponse->assertStatus(200);

    // Should still have only one user role record
    $roleCount = UserRole::where('user_id', $targetUser->id)
        ->where('role', 'user')
        ->count();
    expect($roleCount)->toBe(1);
})->repeat(100);
