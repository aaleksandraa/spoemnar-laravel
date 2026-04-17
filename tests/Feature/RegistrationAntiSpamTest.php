<?php

namespace Tests\Feature;

use App\Support\RegistrationFormProtection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_accepts_valid_signed_form_submission(): void
    {
        $payload = array_merge([
            'email' => 'antispam@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], $this->registrationSecurityPayload());

        $response = $this->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'antispam@example.com');
    }

    public function test_registration_rejects_filled_honeypot_field(): void
    {
        $payload = array_merge([
            'email' => 'bot@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], $this->registrationSecurityPayload([
            'company' => 'Spam Bot LLC',
        ]));

        $response = $this->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company']);
    }

    public function test_registration_rejects_invalid_form_signature(): void
    {
        $payload = array_merge([
            'email' => 'invalid-signature@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], $this->registrationSecurityPayload([
            'form_signature' => str_repeat('a', 64),
        ]));

        $response = $this->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['form_signature']);
    }

    public function test_registration_rejects_forms_submitted_too_quickly(): void
    {
        $renderedAt = now()->subSecond()->timestamp;
        $payload = array_merge([
            'email' => 'too-fast@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ], $this->registrationSecurityPayload([
            'form_rendered_at' => $renderedAt,
            'form_signature' => RegistrationFormProtection::sign($renderedAt, '127.0.0.1'),
        ]));

        $response = $this->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['form_rendered_at']);
    }

    public function test_registration_is_throttled_after_repeated_attempts_from_same_ip(): void
    {
        $ipAddress = '203.0.113.77';

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $renderedAt = now()->subSeconds(5)->timestamp;
            $payload = array_merge([
                'email' => "throttle{$attempt}@example.com",
                'password' => 'StrongPass123!',
                'password_confirmation' => 'StrongPass123!',
                'company' => '',
            ], RegistrationFormProtection::issue($ipAddress, $renderedAt));

            $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
                ->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload)
                ->assertCreated();
        }

        $renderedAt = now()->subSeconds(5)->timestamp;
        $payload = array_merge([
            'email' => 'throttle3@example.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
            'company' => '',
        ], RegistrationFormProtection::issue($ipAddress, $renderedAt));

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ipAddress])
            ->postJsonWithoutRegistrationSecurity('/api/v1/register', $payload);

        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too many registration attempts. Please try again later.',
            ]);
    }
}
