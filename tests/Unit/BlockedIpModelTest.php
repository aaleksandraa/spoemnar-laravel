<?php

use App\Models\BlockedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('can create a blocked IP', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.1',
        'reason' => 'Malicious activity',
        'blocked_at' => now(),
        'is_auto_blocked' => false,
        'malicious_request_count' => 5,
    ]);

    expect($blockedIp)->toBeInstanceOf(BlockedIp::class)
        ->and($blockedIp->ip_address)->toBe('192.168.1.1')
        ->and($blockedIp->reason)->toBe('Malicious activity')
        ->and($blockedIp->is_auto_blocked)->toBeFalse()
        ->and($blockedIp->malicious_request_count)->toBe(5);
});

test('isExpired returns true for expired blocks', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.2',
        'reason' => 'Test',
        'blocked_at' => now()->subHours(2),
        'expires_at' => now()->subHour(),
    ]);

    expect($blockedIp->isExpired())->toBeTrue();
});

test('isExpired returns false for active blocks', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.3',
        'reason' => 'Test',
        'blocked_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    expect($blockedIp->isExpired())->toBeFalse();
});

test('isExpired returns false for permanent blocks', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.4',
        'reason' => 'Test',
        'blocked_at' => now(),
        'expires_at' => null,
    ]);

    expect($blockedIp->isExpired())->toBeFalse();
});

test('isPermanent returns true when expires_at is null', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.5',
        'reason' => 'Test',
        'blocked_at' => now(),
        'expires_at' => null,
    ]);

    expect($blockedIp->isPermanent())->toBeTrue();
});

test('isPermanent returns false when expires_at is set', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.6',
        'reason' => 'Test',
        'blocked_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    expect($blockedIp->isPermanent())->toBeFalse();
});

test('active scope returns only active blocks', function () {
    // Create permanent block
    BlockedIp::create([
        'ip_address' => '192.168.1.7',
        'reason' => 'Permanent',
        'blocked_at' => now(),
        'expires_at' => null,
    ]);

    // Create active temporary block
    BlockedIp::create([
        'ip_address' => '192.168.1.8',
        'reason' => 'Active',
        'blocked_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    // Create expired block
    BlockedIp::create([
        'ip_address' => '192.168.1.9',
        'reason' => 'Expired',
        'blocked_at' => now()->subHours(2),
        'expires_at' => now()->subHour(),
    ]);

    $activeBlocks = BlockedIp::active()->get();

    expect($activeBlocks)->toHaveCount(2)
        ->and($activeBlocks->pluck('ip_address')->toArray())
        ->toContain('192.168.1.7', '192.168.1.8')
        ->not->toContain('192.168.1.9');
});

test('expired scope returns only expired blocks', function () {
    // Create permanent block
    BlockedIp::create([
        'ip_address' => '192.168.1.10',
        'reason' => 'Permanent',
        'blocked_at' => now(),
        'expires_at' => null,
    ]);

    // Create active temporary block
    BlockedIp::create([
        'ip_address' => '192.168.1.11',
        'reason' => 'Active',
        'blocked_at' => now(),
        'expires_at' => now()->addHour(),
    ]);

    // Create expired block
    BlockedIp::create([
        'ip_address' => '192.168.1.12',
        'reason' => 'Expired',
        'blocked_at' => now()->subHours(2),
        'expires_at' => now()->subHour(),
    ]);

    $expiredBlocks = BlockedIp::expired()->get();

    expect($expiredBlocks)->toHaveCount(1)
        ->and($expiredBlocks->first()->ip_address)->toBe('192.168.1.12');
});

test('supports IPv6 addresses', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        'reason' => 'Test IPv6',
        'blocked_at' => now(),
    ]);

    expect($blockedIp->ip_address)->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
});

test('casts blocked_at and expires_at to datetime', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.13',
        'reason' => 'Test',
        'blocked_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    expect($blockedIp->blocked_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($blockedIp->expires_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('casts is_auto_blocked to boolean', function () {
    $blockedIp = BlockedIp::create([
        'ip_address' => '192.168.1.14',
        'reason' => 'Test',
        'blocked_at' => now(),
        'is_auto_blocked' => true,
    ]);

    expect($blockedIp->is_auto_blocked)->toBeTrue()
        ->and($blockedIp->is_auto_blocked)->toBeBool();
});
