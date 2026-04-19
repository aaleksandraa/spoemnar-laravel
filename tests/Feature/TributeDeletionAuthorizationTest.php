<?php

use App\Models\Memorial;
use App\Models\Tribute;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a memorial owner to delete a tribute left by someone else', function () {
    $owner = User::factory()->create();
    $owner->profile()->create(['email' => $owner->email]);
    $ownerToken = $owner->createToken('auth_token')->plainTextToken;

    $memorial = Memorial::factory()->create([
        'user_id' => $owner->id,
    ]);

    $tribute = Tribute::factory()->create([
        'memorial_id' => $memorial->id,
        'author_name' => 'Another Visitor',
        'author_email' => 'visitor@example.com',
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$ownerToken,
    ])->deleteJson("/api/v1/tributes/{$tribute->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('tributes', [
        'id' => $tribute->id,
    ]);
});

it('forbids a non-owner from deleting a tribute on another users memorial', function () {
    $owner = User::factory()->create();
    $owner->profile()->create(['email' => $owner->email]);

    $otherUser = User::factory()->create();
    $otherUser->profile()->create(['email' => $otherUser->email]);
    $otherUserToken = $otherUser->createToken('auth_token')->plainTextToken;

    $memorial = Memorial::factory()->create([
        'user_id' => $owner->id,
    ]);

    $tribute = Tribute::factory()->create([
        'memorial_id' => $memorial->id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$otherUserToken,
    ])->deleteJson("/api/v1/tributes/{$tribute->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('tributes', [
        'id' => $tribute->id,
    ]);
});

it('allows an admin to delete a tribute on another users memorial', function () {
    $owner = User::factory()->create();
    $owner->profile()->create(['email' => $owner->email]);

    $admin = User::factory()->create();
    $admin->profile()->create(['email' => $admin->email]);
    UserRole::create([
        'user_id' => $admin->id,
        'role' => 'admin',
    ]);
    $adminToken = $admin->createToken('auth_token')->plainTextToken;

    $memorial = Memorial::factory()->create([
        'user_id' => $owner->id,
    ]);

    $tribute = Tribute::factory()->create([
        'memorial_id' => $memorial->id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$adminToken,
    ])->deleteJson("/api/v1/tributes/{$tribute->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('tributes', [
        'id' => $tribute->id,
    ]);
});
