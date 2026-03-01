<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\BotAttackDetector;
use App\Services\Security\ExploitPatternMatcher;
use App\Services\Security\RateLimiter;
use App\Services\Security\SecurityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BotAttackDetectorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['security.bot_protection.enabled' => true]);
    }

    public function test_allows_legitimate_requests()
    {
        $request = Request::create('/about', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(BotAttackDetector::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(200);
    }

    public function test_blocks_exploit_pattern_requests()
    {
        $request = Request::create('/wp-login.php', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(BotAttackDetector::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(403);
        expect($response->getData()->message)->toBe('Forbidden');
    }

    public function test_blocks_non_existent_php_files()
    {
        $request = Request::create('/malicious-script.php', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(BotAttackDetector::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(403);
    }

    public function test_blocks_path_traversal_attempts()
    {
        $request = Request::create('/../etc/passwd', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(BotAttackDetector::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(403);
    }

    public function test_returns_429_when_rate_limited()
    {
        $ip = '192.168.1.1';

        // Simulate rate limit exceeded by making multiple malicious requests
        for ($i = 0; $i < 12; $i++) {
            $request = Request::create('/wp-login.php', 'GET');
            $request->server->set('REMOTE_ADDR', $ip);

            $middleware = app(BotAttackDetector::class);
            $response = $middleware->handle($request, function ($req) {
                return response()->json(['success' => true]);
            });

            // First request should be 403, subsequent requests after rate limit should be 429
            if ($response->status() === 429) {
                expect($response->getData()->message)->toContain('Too many requests');
                break;
            } else {
                expect($response->status())->toBe(403);
            }
        }

        // Verify we got a 429 at some point
        expect($response->status())->toBe(429);
    }

    public function test_bypasses_when_bot_protection_disabled()
    {
        config(['security.bot_protection.enabled' => false]);

        $request = Request::create('/wp-login.php', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(BotAttackDetector::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(200);
    }
}
