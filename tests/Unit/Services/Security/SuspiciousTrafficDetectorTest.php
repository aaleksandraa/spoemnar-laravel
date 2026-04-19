<?php

namespace Tests\Unit\Services\Security;

use App\Services\Security\SuspiciousTrafficDetector;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Tests\TestCase;

class SuspiciousTrafficDetectorTest extends TestCase
{
    private function createDetector(Request $request, array $config = []): SuspiciousTrafficDetector
    {
        $configRepository = $this->createMock(ConfigRepository::class);
        $configRepository->method('get')->willReturnCallback(function ($key, $default = null) use ($config) {
            return $config[$key] ?? $default;
        });

        return new SuspiciousTrafficDetector($request, $configRepository);
    }

    public function test_suppresses_analytics_for_empty_user_agent(): void
    {
        $request = Request::create('/bs', 'GET');
        $request->headers->set('User-Agent', '');

        $detector = $this->createDetector($request, [
            'security.analytics_bot_filter.enabled' => true,
            'security.analytics_bot_filter.signatures' => ['bot'],
        ]);

        $this->assertTrue($detector->shouldSuppressAnalytics());
    }

    public function test_suppresses_analytics_for_headless_browser_signature(): void
    {
        $request = Request::create('/bs', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 HeadlessChrome/124.0.0.0 Safari/537.36');

        $detector = $this->createDetector($request, [
            'security.analytics_bot_filter.enabled' => true,
            'security.analytics_bot_filter.signatures' => ['headlesschrome', 'bot'],
        ]);

        $this->assertTrue($detector->shouldSuppressAnalytics());
    }

    public function test_allows_normal_browser_traffic(): void
    {
        $request = Request::create('/bs', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36');

        $detector = $this->createDetector($request, [
            'security.analytics_bot_filter.enabled' => true,
            'security.analytics_bot_filter.signatures' => ['headlesschrome', 'bot', 'curl/'],
        ]);

        $this->assertFalse($detector->shouldSuppressAnalytics());
    }
}
