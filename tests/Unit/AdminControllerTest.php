<?php

namespace Tests\Unit;

use App\Models\Memorial;
use App\Models\Profile;
use App\Models\Tribute;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that non-admin users cannot access admin endpoints.
     */
    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Test that admin users can access admin dashboard.
     */
    public function test_admin_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        UserRole::create(['user_id' => $user->id, 'role' => 'admin']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_users',
                'total_memorials',
                'public_memorials',
                'private_memorials',
            ]);
    }

    /**
     * Test that admin can see all memorials.
     */
    public function test_admin_can_see_all_memorials(): void
    {
        $user = User::factory()->create();
        UserRole::create(['user_id' => $user->id, 'role' => 'admin']);
        $token = $user->createToken('test-token')->plainTextToken;

        // Create some memorials
        $profile = Profile::factory()->create();
        Memorial::factory()->count(3)->create(['user_id' => $profile->user_id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/admin/memorials');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    /**
     * Test that admin can update user roles.
     */
    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create();
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $targetUser = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/v1/admin/users/' . $targetUser->id . '/role', [
            'role' => 'admin',
            'action' => 'add',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Role added successfully.',
            ]);

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $targetUser->id,
            'role' => 'admin',
        ]);
    }

    /**
     * Test that admin can delete any memorial.
     */
    public function test_admin_can_delete_any_memorial(): void
    {
        $admin = User::factory()->create();
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $profile = Profile::factory()->create();
        $memorial = Memorial::factory()->create(['user_id' => $profile->user_id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/admin/memorials/' . $memorial->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Memorial deleted successfully.',
            ]);

        $this->assertDatabaseMissing('memorials', [
            'id' => $memorial->id,
        ]);
    }

    /**
     * Test that admin can delete any tribute.
     */
    public function test_admin_can_delete_any_tribute(): void
    {
        $admin = User::factory()->create();
        UserRole::create(['user_id' => $admin->id, 'role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $profile = Profile::factory()->create();
        $memorial = Memorial::factory()->create(['user_id' => $profile->user_id]);
        $tribute = Tribute::factory()->create(['memorial_id' => $memorial->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/admin/tributes/' . $tribute->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Tribute deleted successfully.',
            ]);

        $this->assertDatabaseMissing('tributes', [
            'id' => $tribute->id,
        ]);
    }
}
