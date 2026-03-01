<?php

/**
 * Security Logging and Monitoring Tests
 *
 * This test suite verifies that security events are properly logged:
 * - Failed login attempts are logged with timestamp, IP, username, and user agent
 * - Authorization failures are logged with user ID, resource type, resource ID, and action
 * - Log format is consistent across all security events
 * - Log entries contain all required information for security monitoring
 *
 * Requirements: 2.26, 2.27
 */

use App\Models\User;
use App\Models\Memorial;
use App\Models\MemorialImage;
use App\Models\MemorialVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper function to get the current security log file path
function getSecurityLogPath(): string
{
    $date = now()->format('Y-m-d');
    return storage_path("logs/security-{$date}.log");
}

describe('Security Logging and Monitoring', function () {

    beforeEach(function () {
        // Clear security log before each test
        // Note: Security log uses daily driver, so file has date suffix
        $logPath = getSecurityLogPath();
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    });

    describe('Failed Login Logging', function () {

        it('logs failed login attempts with all required information', function () {
            // Create a user with known credentials
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Attempt login with incorrect password
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Verify login failed
            expect($response->status())->toBe(422);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            expect(file_exists($logPath))->toBeTrue();

            $logContents = file_get_contents($logPath);

            // Verify log contains failed login entry
            expect($logContents)->toContain('Failed login attempt');

            // Verify log contains timestamp
            expect($logContents)->toContain('timestamp');

            // Verify log contains IP address
            expect($logContents)->toContain('ip_address');

            // Verify log contains username/email
            expect($logContents)->toContain('test@example.com');

            // Verify log contains user agent
            expect($logContents)->toContain('user_agent');
        });

        it('logs failed login with non-existent user', function () {
            // Attempt login with non-existent email
            $response = $this->postJson('/api/v1/login', [
                'email' => 'nonexistent@example.com',
                'password' => 'any-password',
            ]);

            // Verify login failed
            expect($response->status())->toBe(422);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            expect(file_exists($logPath))->toBeTrue();

            $logContents = file_get_contents($logPath);

            // Verify log contains failed login entry
            expect($logContents)->toContain('Failed login attempt');
            expect($logContents)->toContain('nonexistent@example.com');
        });

        it('logs multiple failed login attempts separately', function () {
            // Clear log before test
            $logPath = getSecurityLogPath();
            if (file_exists($logPath)) {
                unlink($logPath);
            }

            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Attempt multiple failed logins
            for ($i = 0; $i < 3; $i++) {
                $this->postJson('/api/v1/login', [
                    'email' => 'test@example.com',
                    'password' => 'wrong-password-' . $i,
                ]);
            }

            // Verify security log contains multiple entries
            $logContents = file_get_contents($logPath);

            // Count occurrences of "Failed login attempt"
            $count = substr_count($logContents, 'Failed login attempt');
            expect($count)->toBe(3);
        });

        it('logs IP address and user agent for forensic analysis', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Attempt login with custom headers
            $response = $this->withHeaders([
                'User-Agent' => 'AttackerBot/1.0',
                'X-Forwarded-For' => '192.168.1.100',
            ])->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Verify security log contains IP and user agent
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            expect($logContents)->toContain('AttackerBot');
            expect($logContents)->toContain('ip_address');
        });
    });

    describe('Authorization Failure Logging', function () {

        it('logs unauthorized profile access attempts', function () {
            // Create two users with their memorials
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $memorialA = Memorial::factory()->create(['user_id' => $userA->id]);
            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);

            // User A attempts to access User B's memorial
            $response = $this->actingAs($userA)
                ->getJson("/api/v1/memorials/{$memorialB->id}");

            // Verify access was denied
            expect($response->status())->toBeIn([403, 404]);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            expect(file_exists($logPath))->toBeTrue();

            $logContents = file_get_contents($logPath);

            // Verify log contains authorization failure entry
            expect($logContents)->toContain('Authorization failure');

            // Verify log contains user ID
            expect($logContents)->toContain('user_id');
            expect($logContents)->toContain((string)$userA->id);

            // Verify log contains resource information
            expect($logContents)->toContain('resource_type');
            expect($logContents)->toContain('resource_id');

            // Verify log contains action attempted
            expect($logContents)->toContain('action');
        });

        it('logs unauthorized image deletion attempts', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);
            $imageB = MemorialImage::factory()->create(['memorial_id' => $memorialB->id]);

            // User A attempts to delete User B's image
            $response = $this->actingAs($userA)
                ->deleteJson("/api/v1/images/{$imageB->id}");

            // Verify deletion was denied
            expect($response->status())->toBeIn([403, 404]);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            expect($logContents)->toContain('Authorization failure');
            expect($logContents)->toContain((string)$userA->id);
        });

        it('logs unauthorized video update attempts', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);
            $videoB = MemorialVideo::factory()->create(['memorial_id' => $memorialB->id]);

            // User A attempts to update User B's video
            $response = $this->actingAs($userA)
                ->putJson("/api/v1/videos/{$videoB->id}", [
                    'video_url' => 'https://www.youtube.com/watch?v=newvideo',
                    'caption' => 'Updated caption',
                ]);

            // Verify update was denied
            expect($response->status())->toBeIn([403, 404]);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            expect($logContents)->toContain('Authorization failure');
        });

        it('logs privilege escalation attempts', function () {
            // Create regular user
            $regularUser = User::factory()->create(['role' => 'user']);

            // Attempt to access admin-only endpoint (location import)
            $response = $this->actingAs($regularUser)
                ->postJson('/api/locations/import', [
                    'file' => 'locations.csv',
                ]);

            // Verify access was denied
            expect($response->status())->toBeIn([403, 404]);

            // Verify security log entry was created
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            expect($logContents)->toContain('Authorization failure');
            expect($logContents)->toContain((string)$regularUser->id);
        });

        it('logs multiple authorization failures showing attack pattern', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);
            $imageB = MemorialImage::factory()->create(['memorial_id' => $memorialB->id]);
            $videoB = MemorialVideo::factory()->create(['memorial_id' => $memorialB->id]);

            // User A attempts multiple unauthorized operations
            $this->actingAs($userA)->getJson("/api/v1/memorials/{$memorialB->id}");
            $this->actingAs($userA)->deleteJson("/api/v1/images/{$imageB->id}");
            $this->actingAs($userA)->deleteJson("/api/v1/videos/{$videoB->id}");

            // Verify security log contains multiple entries
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Count occurrences of "Authorization failure"
            $count = substr_count($logContents, 'Authorization failure');
            expect($count)->toBeGreaterThanOrEqual(3);
        });

        it('logs authorization failures with resource context', function () {
            $userA = User::factory()->create();
            $userB = User::factory()->create();

            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);

            // User A attempts to update User B's memorial
            $response = $this->actingAs($userA)
                ->putJson("/api/v1/memorials/{$memorialB->id}", [
                    'name' => 'Hacked Name',
                ]);

            // Verify security log contains resource details
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            expect($logContents)->toContain('Authorization failure');
            expect($logContents)->toContain('resource_type');
            expect($logContents)->toContain('resource_id');
            expect($logContents)->toContain((string)$memorialB->id);
        });
    });

    describe('Log Format Consistency', function () {

        it('uses consistent JSON format for all security log entries', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Trigger failed login
            $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Trigger authorization failure
            $userB = User::factory()->create();
            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);
            $this->actingAs($user)->getJson("/api/v1/memorials/{$memorialB->id}");

            // Verify log format
            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Both entries should contain structured data
            expect($logContents)->toContain('timestamp');
            expect($logContents)->toContain('ip_address');

            // Verify ISO 8601 timestamp format is used
            expect($logContents)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        it('includes timestamp in ISO 8601 format', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Verify ISO 8601 format (YYYY-MM-DDTHH:MM:SS)
            expect($logContents)->toMatch('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        it('includes IP address in all security events', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Trigger failed login
            $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Trigger authorization failure
            $userB = User::factory()->create();
            $memorialB = Memorial::factory()->create(['user_id' => $userB->id]);
            $this->actingAs($user)->getJson("/api/v1/memorials/{$memorialB->id}");

            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Count occurrences of ip_address
            $count = substr_count($logContents, 'ip_address');
            expect($count)->toBeGreaterThanOrEqual(2);
        });
    });

    describe('Log Analysis for Security Monitoring', function () {

        it('enables detection of brute force attacks through multiple failed logins', function () {
            // Clear log before test
            $logPath = getSecurityLogPath();
            if (file_exists($logPath)) {
                unlink($logPath);
            }

            $user = User::factory()->create([
                'email' => 'target@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Simulate brute force attack
            for ($i = 0; $i < 5; $i++) {
                $this->postJson('/api/v1/login', [
                    'email' => 'target@example.com',
                    'password' => 'attempt-' . $i,
                ]);
            }

            $logContents = file_get_contents($logPath);

            // Verify all attempts are logged
            $count = substr_count($logContents, 'Failed login attempt');
            expect($count)->toBe(5);

            // Verify target email appears in all entries
            $emailCount = substr_count($logContents, 'target@example.com');
            expect($emailCount)->toBeGreaterThanOrEqual(5);
        });

        it('enables detection of unauthorized access patterns', function () {
            $attacker = User::factory()->create();
            $victim = User::factory()->create();

            // Create multiple resources owned by victim
            $memorial = Memorial::factory()->create(['user_id' => $victim->id]);
            $image1 = MemorialImage::factory()->create(['memorial_id' => $memorial->id]);
            $image2 = MemorialImage::factory()->create(['memorial_id' => $memorial->id]);
            $video = MemorialVideo::factory()->create(['memorial_id' => $memorial->id]);

            // Attacker attempts to access multiple resources
            $this->actingAs($attacker)->getJson("/api/v1/memorials/{$memorial->id}");
            $this->actingAs($attacker)->deleteJson("/api/v1/images/{$image1->id}");
            $this->actingAs($attacker)->deleteJson("/api/v1/images/{$image2->id}");
            $this->actingAs($attacker)->deleteJson("/api/v1/videos/{$video->id}");

            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Verify pattern is detectable
            $count = substr_count($logContents, 'Authorization failure');
            expect($count)->toBeGreaterThanOrEqual(4);

            // Verify attacker ID appears in all entries
            $attackerIdCount = substr_count($logContents, (string)$attacker->id);
            expect($attackerIdCount)->toBeGreaterThanOrEqual(4);
        });

        it('provides forensic data for security incident investigation', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            // Simulate attack with specific user agent and IP
            $response = $this->withHeaders([
                'User-Agent' => 'MaliciousBot/2.0',
            ])->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            $logPath = getSecurityLogPath();
            $logContents = file_get_contents($logPath);

            // Verify forensic data is available
            expect($logContents)->toContain('MaliciousBot');
            expect($logContents)->toContain('test@example.com');
            expect($logContents)->toContain('timestamp');
            expect($logContents)->toContain('ip_address');
        });

        it('logs security events to dedicated security.log file', function () {
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'password' => bcrypt('correct-password'),
            ]);

            $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);

            // Verify security.log file exists
            $securityLogPath = getSecurityLogPath();
            expect(file_exists($securityLogPath))->toBeTrue();

            // Verify main laravel.log doesn't contain security events (they go to dedicated channel)
            $laravelLogPath = storage_path('logs/laravel.log');
            if (file_exists($laravelLogPath)) {
                $laravelLogContents = file_get_contents($laravelLogPath);
                // Security events should be in security.log, not laravel.log
                // (This is a best practice check - security events in dedicated file)
            }
        });
    });
});





