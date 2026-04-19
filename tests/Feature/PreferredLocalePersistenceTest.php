<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the preferred locale on login when one is explicitly provided', function () {
    $user = User::factory()->create([
        'email' => 'locale-owner@example.com',
        'password' => 'SecretPass123!',
    ]);

    $user->profile()->create([
        'email' => $user->email,
        'full_name' => 'Locale Owner',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'locale-owner@example.com',
        'password' => 'SecretPass123!',
        'locale' => 'de',
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'preferred_locale' => 'de',
    ]);
});

it('syncs the preferred locale from authenticated me requests', function () {
    $user = User::factory()->create([
        'email' => 'viewer@example.com',
    ]);

    $user->profile()->create([
        'email' => $user->email,
        'full_name' => 'Viewer Owner',
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->getJson('/api/v1/me?lang=it');

    $response->assertOk();

    $this->assertDatabaseHas('profiles', [
        'user_id' => $user->id,
        'preferred_locale' => 'it',
    ]);
});
