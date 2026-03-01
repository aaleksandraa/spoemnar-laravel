<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\IpBlocker;
use App\Models\BlockedIp;
use App\Repositories\BlockedIpRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IpBlockerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_allows_non_blocked_ip()
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = app(IpBlocker::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(200);
    }

    public function test_blocks_exact_ip_match()
    {
        // Block an IP
        BlockedIp::create([
            'ip_address' => '192.168.1.100',
            'reason' => 'Test block',
            'blocked_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $middleware = app(IpBlocker::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(403);
        expect($response->getData()->message)->toBe('Forbidden');
    }

    public function test_blocks_cidr_range()
    {
        // Block a CIDR range
        BlockedIp::create([
            'ip_address' => '192.168.1.0/24',
            'reason' => 'Test CIDR block',
            'blocked_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.50');

        $middleware = app(IpBlocker::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(403);
    }

    public function test_allows_whitelisted_ip()
    {
        // Block an IP
        BlockedIp::create([
            'ip_address' => '127.0.0.1',
            'reason' => 'Test block',
            'blocked_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        // Add to whitelist in config
        config(['security.bot_protection.whitelist' => ['127.0.0.1']]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $middleware = app(IpBlocker::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(200);
    }

    public function test_does_not_block_expired_ip()
    {
        // Block an IP with expired date
        BlockedIp::create([
            'ip_address' => '192.168.1.200',
            'reason' => 'Test block',
            'blocked_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
        ]);

        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.200');

        $middleware = app(IpBlocker::class);
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->status())->toBe(200);
    }
}
