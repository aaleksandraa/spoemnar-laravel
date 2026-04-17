<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationSecurityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (glob(storage_path('logs/security*.log')) ?: [] as $path) {
            if (is_string($path) && file_exists($path)) {
                @unlink($path);
            }
        }
    }

    public function test_registration_rejects_disposable_email_domain_and_logs_suspicious_attempt(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'email' => 'bot@mailinator.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $logPath = storage_path('logs/security-'.now()->format('Y-m-d').'.log');
        $this->assertFileExists($logPath);

        $logContents = file_get_contents($logPath);
        $this->assertIsString($logContents);
        $this->assertStringContainsString('suspicious_registration', $logContents);
        $this->assertStringContainsString('disposable_email_domain', $logContents);
        $this->assertStringContainsString('mailinator.com', $logContents);
    }

    public function test_admin_can_fetch_suspicious_registration_log_entries(): void
    {
        $this->postJson('/api/v1/register', [
            'email' => 'review@getnada.com',
            'password' => 'StrongPass123!',
            'password_confirmation' => 'StrongPass123!',
        ])->assertStatus(422);

        $admin = User::create([
            'email' => 'admin@example.com',
            'password' => bcrypt('StrongPass123!'),
            'role' => 'admin',
        ]);
        $admin->profile()->create(['email' => 'admin@example.com']);
        UserRole::create([
            'user_id' => $admin->id,
            'role' => 'admin',
        ]);

        $token = $admin->createToken('admin-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/admin/security/suspicious-registrations');

        $response->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.reason', 'disposable_email_domain')
            ->assertJsonPath('data.0.email', 'review@getnada.com')
            ->assertJsonPath('data.0.emailDomain', 'getnada.com');
    }
}
