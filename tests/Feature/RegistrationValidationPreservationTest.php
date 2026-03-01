<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Registration Validation Preservation Property Tests
 *
 * These tests validate that existing legitimate functionality is preserved
 * when validation fixes are implemented. They test on UNFIXED code and should PASS.
 *
 * IMPORTANT: These tests MUST PASS before and after fixes are implemented.
 * They ensure we don't break existing functionality while fixing validation bugs.
 *
 * **Validates: Property 2 (Preservation) - Valid Password Registration and Non-Password Validation**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 */
class RegistrationValidationPreservationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that valid password (12+ chars with all requirements) successfully registers user
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - valid passwords work correctly
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - valid passwords still work correctly
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_valid_password_registration_succeeds(): void
    {
        Mail::fake();

        // Arrange: Valid password that meets all backend requirements
        $payload = [
            'email' => 'newuser@example.com',
            'password' => 'MyStr0ng!Pass2024',  // 17 chars, uppercase, lowercase, digit, special char
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration should succeed
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'user' => [
                'id',
                'email',
                'profile',
                'roles',
            ],
        ]);

        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        // Verify profile was created
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->profile);
        $this->assertEquals('Test User', $user->profile->full_name);

        // Verify auth token cookie is set
        $cookies = $response->headers->getCookies();
        $authCookie = collect($cookies)->first(fn($cookie) => $cookie->getName() === 'auth_token');
        $this->assertNotNull($authCookie, 'Auth token cookie should be set');
        $this->assertNotEmpty($authCookie->getValue());
        $this->assertTrue($authCookie->isHttpOnly(), 'Auth token should be httpOnly');
    }

    /**
     * Test that duplicate email returns appropriate error message
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - duplicate email validation works
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - duplicate email validation still works
     *
     * **Validates: Requirements 3.2**
     */
    public function test_duplicate_email_returns_error(): void
    {
        // Arrange: Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);
        $existingUser->profile()->create(['email' => $existingUser->email]);

        // Attempt to register with same email
        $payload = [
            'email' => 'existing@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Another User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $errors = $response->json('errors.email');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('already registered', implode(' ', $errors));
    }

    /**
     * Test that password mismatch returns appropriate error message
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - password mismatch validation works
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - password mismatch validation still works
     *
     * **Validates: Requirements 3.3**
     */
    public function test_password_mismatch_returns_error(): void
    {
        // Arrange: Passwords that don't match
        $payload = [
            'email' => 'test@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'DifferentP@ss123',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password_confirmation');

        $errors = $response->json('errors.password_confirmation');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('does not match', implode(' ', $errors));
    }

    /**
     * Test that invalid email format returns appropriate error message
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - email format validation works
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - email format validation still works
     *
     * **Validates: Requirements 3.5**
     */
    public function test_invalid_email_format_returns_error(): void
    {
        // Arrange: Invalid email format
        $payload = [
            'email' => 'not-an-email',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Should return validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');

        $errors = $response->json('errors.email');
        $this->assertIsArray($errors);
        $this->assertStringContainsString('valid email', implode(' ', $errors));
    }

    /**
     * Test that successful registration sends welcome email
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - welcome email is sent
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - welcome email still sent
     *
     * **Validates: Requirements 3.4**
     */
    public function test_successful_registration_sends_welcome_email(): void
    {
        Mail::fake();

        // Arrange: Valid registration data
        $payload = [
            'email' => 'welcome@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Welcome User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration succeeds
        $response->assertStatus(201);

        // Verify welcome email was sent
        Mail::assertSent(\App\Mail\RegistrationWelcomeMail::class, function ($mail) {
            return $mail->hasTo('welcome@example.com');
        });
    }

    /**
     * Test that various valid password formats all work correctly
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - all valid passwords work
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - all valid passwords still work
     *
     * **Validates: Requirements 3.1**
     *
     * @dataProvider validPasswordProvider
     */
    public function test_various_valid_passwords_succeed(string $password): void
    {
        Mail::fake();

        // Arrange: Valid registration with different password formats
        $email = 'test' . uniqid() . '@example.com';
        $payload = [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'full_name' => 'Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration should succeed
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    /**
     * Data provider for valid passwords
     * All passwords meet backend requirements: 12+ chars, uppercase, lowercase, digit, special char from @$!%*?&
     */
    public static function validPasswordProvider(): array
    {
        return [
            'minimum valid password' => ['MyP@ssw0rd12'],  // Exactly 12 chars
            'long password' => ['ThisIsAVeryL0ng!Password2024'],  // 28 chars
            'all special chars' => ['P@ssw0rd!2024'],  // @ and !
            'dollar sign' => ['MyP$ssw0rd123'],  // $
            'percent sign' => ['MyP%ssw0rd123'],  // %
            'asterisk' => ['MyP*ssw0rd123'],  // *
            'question mark' => ['MyP?ssw0rd123'],  // ?
            'ampersand' => ['MyP&ssw0rd123'],  // &
            'multiple special chars' => ['P@ss!w0rd$2024'],  // Multiple special chars
            'numbers at end' => ['Password!@$123'],  // Numbers at end
            'numbers at start' => ['123Password!@$'],  // Numbers at start
            'mixed case throughout' => ['PaSsWoRd!123'],  // Mixed case
        ];
    }

    /**
     * Test that missing required fields return appropriate errors
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - required field validation works
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - required field validation still works
     *
     * **Validates: Requirements 3.1, 3.2, 3.3**
     */
    public function test_missing_required_fields_return_errors(): void
    {
        // Arrange: Empty payload
        $payload = [];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Should return validation errors for all required fields
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password', 'password_confirmation']);
    }

    /**
     * Test that full_name is optional
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - full_name is optional
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - full_name still optional
     *
     * **Validates: Requirements 3.1**
     */
    public function test_full_name_is_optional(): void
    {
        Mail::fake();

        // Arrange: Valid registration without full_name
        $payload = [
            'email' => 'noname@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration should succeed
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'noname@example.com']);
    }

    /**
     * Test that locale is optional and defaults appropriately
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - locale is optional
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - locale still optional
     *
     * **Validates: Requirements 3.1**
     */
    public function test_locale_is_optional(): void
    {
        Mail::fake();

        // Arrange: Valid registration without locale
        $payload = [
            'email' => 'nolocale@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Test User',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration should succeed
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'nolocale@example.com']);
    }

    /**
     * Test that user roles are created correctly
     *
     * **EXPECTED OUTCOME ON UNFIXED CODE**: Test PASSES - user role creation works
     * **EXPECTED OUTCOME ON FIXED CODE**: Test PASSES - user role creation still works
     *
     * **Validates: Requirements 3.1, 3.4**
     */
    public function test_user_role_is_created_on_registration(): void
    {
        Mail::fake();

        // Arrange: Valid registration data
        $payload = [
            'email' => 'roletest@example.com',
            'password' => 'MyStr0ng!Pass2024',
            'password_confirmation' => 'MyStr0ng!Pass2024',
            'full_name' => 'Role Test User',
            'locale' => 'en',
        ];

        // Act: Submit registration
        $response = $this->postJson('/api/v1/register', $payload);

        // Assert: Registration succeeds
        $response->assertStatus(201);

        // Verify user role was created
        $user = User::where('email', 'roletest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles()->where('role', 'user')->exists());
    }
}
