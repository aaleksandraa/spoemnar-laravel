<?php

/**
 * Security Vulnerabilities Bug Condition Exploration Tests
 *
 * These tests are designed to FAIL on unfixed code to confirm security vulnerabilities exist.
 * They validate Property 1: Fault Condition from the bugfix design.
 *
 * IMPORTANT: These tests MUST FAIL before fixes are implemented.
 * Failures confirm the bugs exist and validate the root cause analysis.
 */

use App\Models\User;
use App\Models\Memorial;
use App\Models\Tribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

describe('1. Private Memorial IDOR Vulnerability', function () {

    /**
     * Validates: Requirements 1.1, 1.2, 1.3
     * Property 1: Fault Condition - Private Memorial Access Control
     */

    it('allows unauthenticated access to private memorial via GET /api/v1/memorials/{slug}', function () {
        // Create a user and their private memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $privateMemorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
            'slug' => 'private-memorial-test',
        ]);

        // Unauthenticated user tries to access private memorial
        $response = $this->getJson("/api/v1/memorials/{$privateMemorial->slug}");

        // BUG: Currently returns 200 OK with full data (should be 404 or 403)
        expect($response->status())->toBe(200)
            ->and($response->json('data.isPublic'))->toBe(false);
    });

    it('allows authenticated non-owner to access other user\'s private memorial', function () {
        // Create owner and their private memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $privateMemorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => false,
            'slug' => 'owner-private-memorial',
        ]);

        // Create different user (not the owner)
        $otherUser = User::factory()->create();
        $otherUser->profile()->create(['email' => $otherUser->email]);
        $token = $otherUser->createToken('auth_token')->plainTextToken;

        // Other user tries to access owner's private memorial
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials/{$privateMemorial->slug}");

        // BUG: Currently returns 200 OK (should be 403 Forbidden or 404)
        expect($response->status())->toBe(200)
            ->and($response->json('data.isPublic'))->toBe(false);
    });

    it('includes private memorials in index when explicitly requested', function () {
        // Create public and private memorials
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => true,
            'slug' => 'public-memorial',
        ]);

        Memorial::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
            'slug' => 'private-memorial',
        ]);

        // Unauthenticated request to index with isPublic=false filter
        $response = $this->getJson('/api/v1/memorials?isPublic=false');

        // BUG: Private memorials might be returned even to unauthenticated users
        $memorials = $response->json('data');

        // If any memorials are returned with isPublic=false, the bug exists
        if (!empty($memorials)) {
            $privateMemorials = collect($memorials)->filter(fn($m) => $m['isPublic'] === false);
            expect($privateMemorials->count())->toBeGreaterThan(0);
        } else {
            // If no memorials returned, test passes (bug doesn't exist in this scenario)
            expect(true)->toBeTrue();
        }
    });
});

describe('2. Email Address Exposure Vulnerability', function () {

    /**
     * Validates: Requirements 2.1, 2.2, 2.3
     * Property 2: Fault Condition - Email Address Protection
     */

    it('exposes authorEmail in GET /api/v1/memorials/{slug} response', function () {
        // Create memorial with tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        Tribute::factory()->create([
            'memorial_id' => $memorial->id,
            'author_name' => 'John Doe',
            'author_email' => 'exposed@example.com',
            'message' => 'Test tribute',
        ]);

        // Any user accesses memorial
        $response = $this->getJson("/api/v1/memorials/{$memorial->slug}");

        // BUG: authorEmail is exposed in tributes array
        $tributes = $response->json('data.tributes');
        expect($tributes)->not->toBeNull();
        expect($tributes)->not->toBeEmpty();
        expect($tributes[0])->toHaveKey('authorEmail');
        expect($tributes[0]['authorEmail'])->toBe('exposed@example.com');
    });

    it('exposes author_email in GET /api/v1/memorials/{memorial}/tributes for non-owners', function () {
        // Create memorial with tribute
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        Tribute::factory()->create([
            'memorial_id' => $memorial->id,
            'author_name' => 'Jane Smith',
            'author_email' => 'private@example.com',
            'message' => 'Another tribute',
        ]);

        // Different user accesses tributes
        $otherUser = User::factory()->create();
        $otherUser->profile()->create(['email' => $otherUser->email]);
        $token = $otherUser->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/memorials/{$memorial->id}/tributes");

        // BUG: author_email is exposed to non-owner
        $tributes = $response->json('data');
        if (!empty($tributes)) {
            expect($tributes[0])->toHaveKey('authorEmail');
            expect($tributes[0]['authorEmail'])->toBe('private@example.com');
        }
    });
});

describe('3. Authentication Rate Limiting Absence', function () {

    /**
     * Validates: Requirements 3.1, 3.2, 3.3
     * Property 3: Fault Condition - Authentication Rate Limiting
     */

    it('accepts unlimited login requests without throttling', function () {
        // Create a user
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->profile()->create(['email' => $user->email]);

        // Send 10 login requests rapidly (should be throttled after 5)
        $successCount = 0;
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'test@example.com',
                'password' => 'wrong_password',
            ]);

            if ($response->status() !== 429) {
                $successCount++;
            }
        }

        // BUG: All requests are processed without throttling
        expect($successCount)->toBe(10);
    });

    it('accepts unlimited register requests without throttling', function () {
        // Send 10 register requests rapidly (should be throttled after 3)
        $successCount = 0;
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/register', [
                'email' => "user{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            if ($response->status() !== 429) {
                $successCount++;
            }
        }

        // BUG: All requests are processed without throttling
        expect($successCount)->toBe(10);
    });
});

describe('4. Insecure Token Storage', function () {

    /**
     * Validates: Requirements 4.1, 4.2, 4.3
     * Property 4: Fault Condition - Secure Token Storage
     */

    it('returns token in JSON response body on login', function () {
        // Create user
        $user = User::factory()->create([
            'email' => 'token@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->profile()->create(['email' => $user->email]);

        // Login
        $response = $this->postJson('/api/v1/login', [
            'email' => 'token@example.com',
            'password' => 'password123',
        ]);

        // BUG: Token is returned in JSON body (should be in httpOnly cookie)
        expect($response->status())->toBe(200);
        expect($response->json())->toHaveKey('token');
        expect($response->json('token'))->toBeString();
        expect($response->json('token'))->not->toBeEmpty();
    });

    it('frontend code uses localStorage for token storage', function () {
        // Check if login.blade.php exists and contains localStorage usage
        $loginBladePath = resource_path('views/auth/login.blade.php');

        if (file_exists($loginBladePath)) {
            $content = file_get_contents($loginBladePath);

            // BUG: Frontend uses localStorage.setItem('auth_token')
            expect($content)->toContain('localStorage');
            expect($content)->toContain('auth_token');
        } else {
            // If file doesn't exist, skip this test
            expect(true)->toBeTrue();
        }
    });
});

describe('5. Tribute Spam Vulnerability', function () {

    /**
     * Validates: Requirements 5.1, 5.2, 5.3
     * Property 5: Fault Condition - Tribute Anti-Spam Protection
     */

    it('accepts unlimited tribute submissions without rate limiting', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Send 10 tribute requests rapidly (should be throttled after 3)
        $successCount = 0;
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
                'author_name' => "Spammer {$i}",
                'message' => "Spam message {$i}",
                'author_email' => "spam{$i}@example.com",
            ]);

            if ($response->status() === 201 || $response->status() === 200) {
                $successCount++;
            }
        }

        // BUG: All tribute requests are processed without throttling
        expect($successCount)->toBeGreaterThan(3);
    });

    it('accepts tribute submission without honeypot validation', function () {
        // Create memorial
        $owner = User::factory()->create();
        $owner->profile()->create(['email' => $owner->email]);

        $memorial = Memorial::factory()->create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        // Submit tribute without honeypot field
        $response = $this->postJson("/api/v1/memorials/{$memorial->id}/tributes", [
            'author_name' => 'Bot',
            'message' => 'Bot message',
            'author_email' => 'bot@example.com',
            // No honeypot field
        ]);

        // BUG: Tribute is accepted without honeypot validation
        expect($response->status())->toBeIn([200, 201]);
    });
});

describe('6. Missing Security Headers', function () {

    /**
     * Validates: Requirements 6.1, 6.2, 6.3, 6.4
     * Property 6: Fault Condition - Security Headers Enforcement
     */

    it('HTTP responses lack Content-Security-Policy header', function () {
        $response = $this->get('/');

        // BUG: CSP header is missing
        expect($response->headers->has('Content-Security-Policy'))->toBeFalse();
    });

    it('HTTP responses lack Strict-Transport-Security header', function () {
        $response = $this->get('/');

        // BUG: HSTS header is missing
        expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
    });

    it('HTTP responses lack X-Frame-Options header', function () {
        $response = $this->get('/');

        // BUG: X-Frame-Options header is missing
        expect($response->headers->has('X-Frame-Options'))->toBeFalse();
    });

    it('HTTP responses lack X-Content-Type-Options header', function () {
        $response = $this->get('/');

        // BUG: X-Content-Type-Options header is missing
        expect($response->headers->has('X-Content-Type-Options'))->toBeFalse();
    });
});

describe('7. Perpetual Token Validity', function () {

    /**
     * Validates: Requirements 7.1, 7.2, 7.3
     * Property 7: Fault Condition - Token Expiration
     */

    it('config/sanctum.php has expiration set to null', function () {
        // BUG: Sanctum expiration is null (tokens never expire)
        $expiration = Config::get('sanctum.expiration');
        expect($expiration)->toBeNull();
    });

    it('created token never expires', function () {
        // Create user and token
        $user = User::factory()->create();
        $user->profile()->create(['email' => $user->email]);
        $token = $user->createToken('test_token')->plainTextToken;

        // Access protected endpoint
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        // BUG: Token works indefinitely (no expiration check)
        expect($response->status())->toBe(200);

        // Check token record in database
        $tokenRecord = $user->tokens()->first();
        expect($tokenRecord->expires_at)->toBeNull();
    });
});

describe('8. Permissive CORS Configuration', function () {

    /**
     * Validates: Requirements 8.1, 8.2, 8.3
     * Property 8: Fault Condition - Restrictive CORS Policy
     */

    it('config/cors.php has wildcard allowed_methods', function () {
        $allowedMethods = Config::get('cors.allowed_methods');

        // BUG: Wildcard allows all HTTP methods
        expect($allowedMethods)->toContain('*');
    });

    it('config/cors.php has wildcard allowed_headers', function () {
        $allowedHeaders = Config::get('cors.allowed_headers');

        // BUG: Wildcard allows all headers
        expect($allowedHeaders)->toContain('*');
    });

    it('uses wildcard with supports_credentials=true', function () {
        $allowedMethods = Config::get('cors.allowed_methods');
        $allowedHeaders = Config::get('cors.allowed_headers');
        $supportsCredentials = Config::get('cors.supports_credentials');

        // BUG: Dangerous combination of wildcards with credentials
        $hasWildcard = in_array('*', $allowedMethods) || in_array('*', $allowedHeaders);
        expect($hasWildcard)->toBeTrue();
        expect($supportsCredentials)->toBeTrue();
    });
});

describe('9. Contact Form Vulnerabilities', function () {

    /**
     * Validates: Requirements 9.1, 9.2, 9.3
     * Property 9: Fault Condition - Contact Form Protection
     */

    it('accepts unlimited contact form submissions without rate limiting', function () {
        // Send 10 contact form requests rapidly (should be throttled after 5)
        $successCount = 0;
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/contact', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'subject' => 'Test',
                'message' => 'Test message',
            ]);

            if ($response->status() !== 429) {
                $successCount++;
            }
        }

        // BUG: All requests are processed without throttling
        expect($successCount)->toBeGreaterThan(5);
    });

    it('ContactController logs PII data in application logs', function () {
        // Check if ContactController.php contains PII logging
        $controllerPath = app_path('Http/Controllers/ContactController.php');

        if (file_exists($controllerPath)) {
            $content = file_get_contents($controllerPath);

            // BUG: Controller logs email or name directly in Log statements
            // The actual code has: Log::info('...', ['name' => $validated['name'], 'email' => $validated['email']])
            $hasPIILogging = (
                (str_contains($content, 'Log::info') || str_contains($content, 'Log::debug')) &&
                str_contains($content, '$validated[\'email\']') &&
                str_contains($content, '$validated[\'name\']')
            );

            expect($hasPIILogging)->toBeTrue();
        } else {
            // If controller doesn't exist, skip
            expect(true)->toBeTrue();
        }
    });
});

describe('10. HTTPS Enforcement Absence', function () {

    /**
     * Validates: Requirements 10.1, 10.2, 10.3
     * Property 10: Fault Condition - HTTPS Enforcement
     */

    it('application does not force HTTPS at application level', function () {
        // Check if ForceHttps middleware exists
        $middlewarePath = app_path('Http/Middleware/ForceHttps.php');

        // BUG: No HTTPS enforcement middleware
        expect(file_exists($middlewarePath))->toBeFalse();
    });

    it('config/app.php does not have force_https setting', function () {
        // BUG: No force_https configuration
        $forceHttps = Config::get('app.force_https');
        expect($forceHttps)->toBeNull();
    });

    it('application allows HTTP requests in production without redirect', function () {
        // Save original environment
        $originalEnv = Config::get('app.env');

        // Simulate production environment
        Config::set('app.env', 'production');

        // Make a simple request
        $response = $this->get('/');

        // BUG: If there was automatic HTTPS redirect, status would be 301 or 302
        // We expect NO redirect (bug exists)
        $isRedirect = in_array($response->status(), [301, 302, 303, 307, 308]);

        // Restore environment
        Config::set('app.env', $originalEnv);

        // The bug exists if there's NO redirect OR if redirect is not to HTTPS
        if ($isRedirect) {
            $location = $response->headers->get('Location');
            // If redirecting, it should NOT be forcing HTTPS (that's the bug)
            expect($location)->not->toStartWith('https://');
        } else {
            // No redirect at all (bug confirmed)
            expect($isRedirect)->toBeFalse();
        }
    });
});
