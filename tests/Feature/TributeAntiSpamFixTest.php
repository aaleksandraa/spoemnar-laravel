<?php

use App\Models\Memorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Validates: Requirements 2.15, 2.16, 2.17
 * Property 5: Expected Behavior - Tribute Anti-Spam Protection
 *
 * These tests verify that the tribute anti-spam protection is working correctly:
 * - Rate limiting: 3 requests per 10 minutes
 * - Honeypot validation: Empty honeypot field required
 * - Timestamp validation: Timestamp must be within last hour
 */

describe('Tribute Anti-Spam Protection (Fixed)', function () {

    it('allows first 3 valid tribute submissions and rate limits the 4th', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $currentTime = time();

        // First 3 requests should succeed
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
                'author_name' => "User {$i}",
                'author_email' => "user{$i}@example.com",
                'message' => "Message {$i}",
                'honeypot' => '', // Empty honeypot (valid)
                'timestamp' => $currentTime, // Current timestamp (valid)
            ]);

            expect($response->status())->toBe(201);
        }

        // 4th request should be rate limited
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'User 4',
            'author_email' => 'user4@example.com',
            'message' => 'Message 4',
            'honeypot' => '',
            'timestamp' => $currentTime,
        ]);

        expect($response->status())->toBe(429); // Too Many Requests
    });

    it('rejects tribute submission with filled honeypot', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute with filled honeypot (bot behavior)
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'Bot',
            'author_email' => 'bot@example.com',
            'message' => 'Bot message',
            'honeypot' => 'I am a bot', // Filled honeypot (invalid)
            'timestamp' => time(),
        ]);

        // Should be rejected with validation error
        expect($response->status())->toBe(422);
        expect($response->json('errors.honeypot'))->not->toBeNull();
    });

    it('rejects tribute submission with old timestamp', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute with timestamp older than 1 hour
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'User',
            'author_email' => 'user@example.com',
            'message' => 'Message',
            'honeypot' => '',
            'timestamp' => time() - 7200, // 2 hours old (invalid)
        ]);

        // Should be rejected with validation error
        expect($response->status())->toBe(422);
        expect($response->json('errors.timestamp'))->not->toBeNull();
    });

    it('allows tribute submission without honeypot field (field is optional)', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute without honeypot field (should be allowed)
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'User',
            'author_email' => 'user@example.com',
            'message' => 'Message',
            // No honeypot field - this is OK
            'timestamp' => time(),
        ]);

        // Should be accepted (honeypot is optional)
        expect($response->status())->toBe(201);
    });

    it('rejects tribute submission without timestamp field', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute without timestamp field
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'User',
            'author_email' => 'user@example.com',
            'message' => 'Message',
            'honeypot' => '',
            // No timestamp field
        ]);

        // Should be rejected with validation error
        expect($response->status())->toBe(422);
        expect($response->json('errors.timestamp'))->not->toBeNull();
    });
});
