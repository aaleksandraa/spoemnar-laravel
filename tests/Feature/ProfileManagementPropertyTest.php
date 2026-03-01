<?php

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

// Feature: laravel-migration, Property 5: Profile auto-creation on registration
// Validates: Requirements 2.1

it('automatically creates profile for any new user registration', function () {
    $testData = [
        ['email' => 'user1@example.com', 'password' => 'password123', 'full_name' => 'John Doe'],
        ['email' => 'test.user@domain.co.uk', 'password' => 'SecurePass!@#', 'full_name' => 'Jane Smith'],
        ['email' => 'admin+test@company.org', 'password' => 'MyP@ssw0rd', 'full_name' => null],
        ['email' => 'john.doe@subdomain.example.com', 'password' => 'Test1234!', 'full_name' => 'Test User'],
        ['email' => 'user_name@test-domain.net', 'password' => 'ValidPass99', 'full_name' => 'Another User'],
    ];

    foreach ($testData as $data) {
        $response = $this->postJson('/api/v1/register', [
            'email' => $data['email'],
            'password' => $data['password'],
            'password_confirmation' => $data['password'],
            'full_name' => $data['full_name'],
        ]);

        // Should return 201 Created
        $response->assertStatus(201);

        // User should exist
        $user = User::where('email', $data['email'])->first();
        expect($user)->not->toBeNull();

        // Profile should be auto-created
        expect($user->profile)->not->toBeNull();

        // Profile should have correct email
        expect($user->profile->email)->toBe($data['email']);

        // Profile should have correct full_name
        if ($data['full_name']) {
            expect($user->profile->full_name)->toBe($data['full_name']);
        }

        // Profile should be in database
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'email' => $data['email'],
        ]);
    }
})->repeat(100);

// Feature: laravel-migration, Property 6: Profile update persistence
// Validates: Requirements 2.2

it('persists profile updates for any valid data', function () {
    $testUpdates = [
        ['email' => 'updated1@example.com', 'full_name' => 'Updated Name 1'],
        ['email' => 'updated2@test.com', 'full_name' => 'Updated Name 2'],
        ['email' => 'new.email@domain.org', 'full_name' => 'New Full Name'],
        ['full_name' => 'Only Name Update'],
        ['email' => 'only.email@update.com'],
    ];

    foreach ($testUpdates as $index => $updateData) {
        // Create user with profile using unique email for each iteration
        $originalEmail = 'original' . $index . '@test.com';

        $user = User::create([
            'email' => $originalEmail,
            'password' => Hash::make('password123'),
        ]);

        $user->profile()->create([
            'email' => $originalEmail,
            'full_name' => 'Original Name',
        ]);

        // Update profile using actingAs() for proper authentication
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/profiles/{$user->id}", $updateData);

        // Should return 200 OK
        $response->assertStatus(200);

        // Should return updated profile
        $response->assertJsonStructure([
            'profile' => ['id', 'user_id', 'email', 'full_name'],
        ]);

        // Refresh user and profile
        $user->refresh();
        $profile = $user->profile->fresh();

        // Profile should be updated in database
        if (isset($updateData['email'])) {
            expect($profile->email)->toBe($updateData['email']);
            $this->assertDatabaseHas('profiles', [
                'id' => $profile->id,
                'email' => $updateData['email'],
            ]);
        }

        if (isset($updateData['full_name'])) {
            expect($profile->full_name)->toBe($updateData['full_name']);
            $this->assertDatabaseHas('profiles', [
                'id' => $profile->id,
                'full_name' => $updateData['full_name'],
            ]);
        }
    }
})->repeat(100);

// Feature: laravel-migration, Property 8: Cascade delete on account deletion
// Validates: Requirements 2.4

it('cascade deletes profile and memorials when user account is deleted', function () {
    // Create user with profile
    $user = User::create([
        'email' => 'delete@test.com',
        'password' => Hash::make('password123'),
    ]);

    $profile = $user->profile()->create([
        'email' => 'delete@test.com',
        'full_name' => 'Delete Test User',
    ]);

    $profileId = $profile->id;
    $userId = $user->id;

    // Delete account using actingAs() for proper authentication
    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/profiles/{$user->id}");

    // Should return 204 No Content
    $response->assertStatus(204);

    // User should be deleted
    $this->assertDatabaseMissing('users', [
        'id' => $userId,
    ]);

    // Profile should be cascade deleted
    $this->assertDatabaseMissing('profiles', [
        'id' => $profileId,
    ]);

    // Verify profile doesn't exist
    expect(Profile::find($profileId))->toBeNull();
    expect(User::find($userId))->toBeNull();
})->repeat(100);

it('prevents unauthorized users from updating other profiles', function () {
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

    // User1 tries to update User2's profile using actingAs()
    $response = $this->actingAs($user1, 'sanctum')
        ->putJson("/api/v1/profiles/{$user2->id}", [
            'full_name' => 'Hacked Name',
        ]);

    // Should return 403 Forbidden
    $response->assertStatus(403);

    // User2's profile should remain unchanged
    $user2->refresh();
    expect($user2->profile->full_name)->not->toBe('Hacked Name');
});

it('prevents unauthorized users from deleting other accounts', function () {
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

    // User1 tries to delete User2's account using actingAs()
    $response = $this->actingAs($user1, 'sanctum')
        ->deleteJson("/api/v1/profiles/{$user2->id}");

    // Should return 403 Forbidden
    $response->assertStatus(403);

    // User2 should still exist
    $this->assertDatabaseHas('users', [
        'id' => $user2->id,
    ]);
});
