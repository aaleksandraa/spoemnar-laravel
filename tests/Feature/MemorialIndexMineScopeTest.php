<?php

namespace Tests\Feature;

use App\Models\Memorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemorialIndexMineScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_mine_scope_returns_only_authenticated_users_memorials(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $owner->profile()->create(['email' => $owner->email]);
        $otherUser->profile()->create(['email' => $otherUser->email]);

        $ownerPublic = Memorial::factory()->create([
            'user_id' => $owner->id,
            'slug' => 'owner.public',
            'is_public' => true,
        ]);
        $ownerPrivate = Memorial::factory()->create([
            'user_id' => $owner->id,
            'slug' => 'owner.private',
            'is_public' => false,
        ]);
        Memorial::factory()->create([
            'user_id' => $otherUser->id,
            'slug' => 'other.public',
            'is_public' => true,
        ]);
        Memorial::factory()->create([
            'user_id' => $otherUser->id,
            'slug' => 'other.private',
            'is_public' => false,
        ]);

        $token = $owner->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/memorials?mine=1&per_page=100');

        $response->assertStatus(200);

        $slugs = collect($response->json('data'))->pluck('slug')->values()->all();
        sort($slugs);

        $expected = [$ownerPrivate->slug, $ownerPublic->slug];
        sort($expected);

        $this->assertSame($expected, $slugs);
    }

    public function test_mine_scope_returns_empty_list_for_unauthenticated_requests(): void
    {
        Memorial::factory()->create(['is_public' => true, 'slug' => 'public.one']);
        Memorial::factory()->create(['is_public' => true, 'slug' => 'public.two']);

        $response = $this->getJson('/api/v1/memorials?mine=1&per_page=100');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
        $response->assertJsonPath('total', 0);
    }
}

