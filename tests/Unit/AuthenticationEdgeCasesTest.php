<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test registration with existing email
     * Requirements: 1.1
     */
    public function test_registration_with_existing_email_fails(): void
    {
        // Create an existing user
        $existingUser = User::create([
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Create profile for existing user
        $existingUser->profile()->create([
            'email' => 'existing@example.com',
        ]);

        // Attempt to register with the same email
        $response = $this->postJson('/api/v1/register', [
            'email' => 'existing@example.com',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        // Should return 422 Unprocessable Entity (validation error)
        $response->assertStatus(422);

        // Should have validation error for email field
        $response->assertJsonValidationErrors(['email']);

        // Should contain error message about email already being registered
        $response->assertJsonFragment([
            'message' => 'This email address is already registered.'
        ]);

        // Verify only one user exists with this email
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Test login with incorrect password
     * Requirements: 1.2
     */
    public function test_login_with_incorrect_password_fails(): void
    {
        // Create a user with known password
        $user = User::create([
            'email' => 'user@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        // Create profile
        $user->profile()->create([
            'email' => 'user@example.com',
        ]);

        // Attempt login with incorrect password
        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should return 422 Unprocessable Entity (validation error)
        $response->assertStatus(422);

        // Should contain error message
        $response->assertJson([
            'message' => 'The provided credentials are incorrect.'
        ]);

        // Should have validation error for email field
        $response->assertJsonValidationErrors(['email']);

        // Should not return a token
        $response->assertJsonMissing(['token']);
    }

    /**
     * Test login with non-existent email
     * Requirements: 1.2
     */
    public function test_login_with_nonexistent_email_fails(): void
    {
        // Attempt login with email that doesn't exist
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'somepassword',
        ]);

        // Should return 422 Unprocessable Entity (validation error)
        $response->assertStatus(422);

        // Should contain error message
        $response->assertJson([
            'message' => 'The provided credentials are incorrect.'
        ]);

        // Should have validation error for email field
        $response->assertJsonValidationErrors(['email']);

        // Should not return a token
        $response->assertJsonMissing(['token']);
    }

    /**
     * Test access to protected resource without token
     * Requirements: 1.4
     */
    public function test_access_protected_resource_without_token_returns_401(): void
    {
        // Attempt to access protected endpoint without token
        $response = $this->getJson('/api/v1/me');

        // Should return 401 Unauthorized
        $response->assertStatus(401);

        // Should contain error message
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }

    /**
     * Test access to protected resource with invalid token
     * Requirements: 1.4
     */
    public function test_access_protected_resource_with_invalid_token_returns_401(): void
    {
        // Attempt to access protected endpoint with invalid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token_string',
        ])->getJson('/api/v1/me');

        // Should return 401 Unauthorized
        $response->assertStatus(401);

        // Should contain error message
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }

    /**
     * Test access to protected resource with malformed authorization header
     * Requirements: 1.4
     */
    public function test_access_protected_resource_with_malformed_header_returns_401(): void
    {
        // Test with missing "Bearer" prefix
        $response = $this->withHeaders([
            'Authorization' => 'some_token_without_bearer',
        ])->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);

        // Test with empty token after "Bearer"
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ',
        ])->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }

    /**
     * Test logout endpoint without token
     * Requirements: 1.4
     */
    public function test_logout_without_token_returns_401(): void
    {
        // Attempt to logout without token
        $response = $this->postJson('/api/v1/logout');

        // Should return 401 Unauthorized
        $response->assertStatus(401);

        // Should contain error message
        $response->assertJson([
            'message' => 'Unauthenticated.'
        ]);
    }
}
