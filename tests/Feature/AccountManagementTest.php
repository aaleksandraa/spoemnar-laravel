<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createAccountTestUser(
    string $email = 'owner@example.com',
    string $password = 'CurrentPass1!',
    string $fullName = 'Owner Person',
    string $preferredLocale = 'bs',
): User {
    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make($password),
    ]);

    $user->profile()->create([
        'email' => $email,
        'full_name' => $fullName,
        'preferred_locale' => $preferredLocale,
    ]);

    return $user;
}

it('returns the authenticated account with profile details', function () {
    $user = createAccountTestUser();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/account');

    $response->assertOk();
    $response->assertJsonPath('user.email', 'owner@example.com');
    $response->assertJsonPath('user.profile.full_name', 'Owner Person');
    $response->assertJsonPath('user.profile.preferred_locale', 'bs');
});

it('updates account profile details without requiring the current password for non-sensitive changes', function () {
    $user = createAccountTestUser();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->putJson('/api/v1/account', [
        'email' => 'owner@example.com',
        'full_name' => 'Updated Owner',
        'preferred_locale' => 'de',
        'locale' => 'bs',
    ]);

    $response->assertOk();
    $response->assertJsonPath('user.profile.full_name', 'Updated Owner');
    $response->assertJsonPath('user.profile.preferred_locale', 'de');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => 'owner@example.com',
    ]);

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'full_name' => 'Updated Owner',
        'preferred_locale' => 'de',
    ]);
});

it('updates email and password and keeps profile email in sync when the current password is correct', function () {
    $user = createAccountTestUser();
    $user->createToken('secondary_token');
    $activeToken = $user->createToken('active_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$activeToken,
    ])->putJson('/api/v1/account', [
        'email' => 'updated-owner@example.com',
        'full_name' => 'Updated Owner',
        'preferred_locale' => 'it',
        'current_password' => 'CurrentPass1!',
        'new_password' => 'NewSecretPass2!',
        'new_password_confirmation' => 'NewSecretPass2!',
        'locale' => 'en',
    ]);

    $response->assertOk();
    $response->assertJsonPath('user.email', 'updated-owner@example.com');
    $response->assertJsonPath('user.profile.email', 'updated-owner@example.com');
    $response->assertJsonPath('user.profile.preferred_locale', 'it');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => 'updated-owner@example.com',
    ]);

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'email' => 'updated-owner@example.com',
        'full_name' => 'Updated Owner',
        'preferred_locale' => 'it',
    ]);

    expect(Hash::check('NewSecretPass2!', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->tokens()->count())->toBe(1);
});

it('rejects sensitive account changes when the current password is invalid', function () {
    $user = createAccountTestUser();
    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->putJson('/api/v1/account', [
        'email' => 'changed@example.com',
        'current_password' => 'WrongPassword1!',
        'locale' => 'bs',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'email' => 'owner@example.com',
    ]);
});
