<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Registration Validation Bug Condition Exploration Test
 *
 * **CRITICAL**: This test MUST FAIL on unfixed code - failure confirms the bug exists
 * **DO NOT attempt to fix the test or the code when it fails**
 * **NOTE**: This test encodes the expected behavior - it will validate the fix when it passes after implementation
 *
 * **Validates: Property 1 (Fault Condition) - Frontend Validation Matches Backend**
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 *
 * This test surfaces counterexamples that demonstrate the bug exists by checking:
 * 1. Frontend HTML validation (minlength attribute)
 * 2. Password strength checker logic
 * 3. Error display logic
 */
class RegistrationValidationBugConditionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that frontend HTML validation requires minimum 12 characters
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS - minlength="8" instead of minlength="12"
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - minlength="12" matches backend
     *
     * **Validates: Requirements 1.1, 2.1**
     */
    public function test_frontend_html_validation_requires_12_characters(): void
    {
        // Act: Render the registration page
        $response = $this->get(route('register', ['locale' => 'en']));

        // Assert: HTML should have minlength="12" for password fields
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check password field has minlength="12"
        $this->assertMatchesRegularExpression(
            '/<input[^>]*id="password"[^>]*minlength="12"[^>]*>/',
            $content,
            'Password field should have minlength="12" to match backend validation'
        );

        // Check password_confirmation field has minlength="12"
        $this->assertMatchesRegularExpression(
            '/<input[^>]*id="password_confirmation"[^>]*minlength="12"[^>]*>/',
            $content,
            'Password confirmation field should have minlength="12" to match backend validation'
        );
    }

    /**
     * Test that password strength checker uses correct length requirement (>= 12)
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS - strength checker uses >= 8 instead of >= 12
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - strength checker uses >= 12
     *
     * **Validates: Requirements 1.4, 2.4**
     */
    public function test_password_strength_checker_uses_correct_length(): void
    {
        // Act: Render the registration page
        $response = $this->get(route('register', ['locale' => 'en']));

        // Assert: JavaScript should check for length >= 12
        $response->assertStatus(200);

        $content = $response->getContent();

        // The strength checker should use value.length >= 12, not >= 8
        $this->assertStringNotContainsString(
            'value.length >= 8',
            $content,
            'Password strength checker should not use >= 8 (should be >= 12)'
        );

        $this->assertStringContainsString(
            'value.length >= 12',
            $content,
            'Password strength checker should use >= 12 to match backend validation'
        );
    }

    /**
     * Test that password strength checker validates broad special characters
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS - uses /[@$!%*?&]/ restricted set
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - uses /[^A-Za-z0-9]/ broad set
     *
     * **Validates: Requirements 1.5, 2.5**
     */
    public function test_password_strength_checker_validates_correct_special_characters(): void
    {
        // Act: Render the registration page
        $response = $this->get(route('register', ['locale' => 'en']));

        // Assert: JavaScript should check for broad special characters
        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringContainsString(
            '/[^A-Za-z0-9]/',
            $content,
            'Password strength checker should accept broad non-alphanumeric special characters'
        );

        $this->assertStringNotContainsString(
            '/[@$!%*?&]/',
            $content,
            'Password strength checker should not be limited to a small special-character allowlist'
        );
    }

    /**
     * Test that backend rejects password with 8-11 characters
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES (backend correctly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend still correctly rejects)
     *
     * This confirms the backend validation is correct and the bug is in the frontend.
     *
     * **Validates: Requirements 1.1**
     */
    public function test_backend_rejects_password_with_8_to_11_characters(): void
    {
        // Arrange: Password with 8 characters that would pass frontend validation on unfixed code
        $payload = [
            'email' => 'test@example.com',
            'password' => 'Pass123!',  // 8 characters - passes unfixed frontend, fails backend
            'password_confirmation' => 'Pass123!',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should reject with 422 validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $errors = $response->json('errors.password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('12 characters', implode(' ', $errors));
    }

    /**
     * Test that backend rejects password without uppercase letter
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES (backend correctly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend still correctly rejects)
     *
     * **Validates: Requirements 1.2**
     */
    public function test_backend_rejects_password_without_uppercase(): void
    {
        // Arrange: Password without uppercase
        $payload = [
            'email' => 'test@example.com',
            'password' => 'password1234!',  // 13 characters, no uppercase
            'password_confirmation' => 'password1234!',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should reject with 422 validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $errors = $response->json('errors.password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('uppercase', implode(' ', $errors));
    }

    /**
     * Test that backend rejects password without lowercase letter
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES (backend correctly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend still correctly rejects)
     *
     * **Validates: Requirements 1.2**
     */
    public function test_backend_rejects_password_without_lowercase(): void
    {
        // Arrange: Password without lowercase
        $payload = [
            'email' => 'test@example.com',
            'password' => 'PASSWORD1234!',  // 13 characters, no lowercase
            'password_confirmation' => 'PASSWORD1234!',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should reject with 422 validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $errors = $response->json('errors.password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('lowercase', implode(' ', $errors));
    }

    /**
     * Test that backend rejects password without digit
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES (backend correctly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend still correctly rejects)
     *
     * **Validates: Requirements 1.2**
     */
    public function test_backend_rejects_password_without_digit(): void
    {
        // Arrange: Password without digit
        $payload = [
            'email' => 'test@example.com',
            'password' => 'Password!@#$',  // 12 characters, no digit
            'password_confirmation' => 'Password!@#$',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should reject with 422 validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $errors = $response->json('errors.password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('digit', implode(' ', $errors));
    }

    /**
     * Test that backend rejects password without any special character
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES (backend correctly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend still correctly rejects)
     *
     * **Validates: Requirements 1.2**
     */
    public function test_backend_rejects_password_without_required_special_character(): void
    {
        // Arrange: Password without special character
        $payload = [
            'email' => 'test@example.com',
            'password' => 'Password1234',  // 12 characters, no special char
            'password_confirmation' => 'Password1234',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should reject with 422 validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $errors = $response->json('errors.password');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('special character', implode(' ', $errors));
    }

    /**
     * Test that backend accepts broader special characters
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS (backend incorrectly rejects)
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES (backend accepts broader characters)
     *
     * **Validates: Requirements 1.2, 1.5**
     */
    public function test_backend_accepts_broader_special_characters(): void
    {
        // Arrange: Password with broader special characters
        $payload = [
            'email' => 'test@example.com',
            'password' => "XcdC5>Z'iaz)zke@",
            'password_confirmation' => "XcdC5>Z'iaz)zke@",
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Backend should accept the password
        $response->assertStatus(201);
    }

    /**
     * Test that error display shows individual validation errors clearly
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test FAILS - errors are concatenated with join(' ')
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - errors are displayed separately
     *
     * **Validates: Requirements 1.3, 2.3**
     */
    public function test_error_display_logic_does_not_concatenate_errors(): void
    {
        // Act: Render the registration page
        $response = $this->get(route('register', ['locale' => 'en']));

        // Assert: JavaScript should not concatenate errors with join(' ')
        $response->assertStatus(200);

        $content = $response->getContent();

        // The error display should not use Object.values(data.errors).flat().join(' ')
        $this->assertStringNotContainsString(
            "Object.values(data.errors).flat().join(' ')",
            $content,
            'Error display should not concatenate all errors into one string'
        );

        // Should display errors in a structured way (list, separate lines, etc.)
        // After fix, there should be logic to display each error separately
        $this->assertStringContainsString(
            'data.errors',
            $content,
            'Error handling should still process data.errors from backend'
        );
    }
}
