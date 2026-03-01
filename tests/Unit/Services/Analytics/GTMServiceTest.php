<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\GTMService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Tests\TestCase;

class GTMServiceTest extends TestCase
{
    private function createService(array $config = []): GTMService
    {
        $configRepository = $this->createMock(ConfigRepository::class);
        $configRepository->method('get')->willReturnCallback(function ($key, $default = null) use ($config) {
            return $config[$key] ?? $default;
        });

        return new GTMService($configRepository);
    }

    public function test_get_container_id_returns_null_in_local_environment(): void
    {
        $service = $this->createService([
            'app.env' => 'local',
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $this->assertNull($service->getContainerId());
    }

    public function test_get_container_id_returns_id_in_staging_environment(): void
    {
        $service = $this->createService([
            'app.env' => 'staging',
            'analytics.gtm.container_id' => 'GTM-STAGING',
        ]);

        $this->assertEquals('GTM-STAGING', $service->getContainerId());
    }

    public function test_get_container_id_returns_id_in_production_environment(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.container_id' => 'GTM-PROD',
        ]);

        $this->assertEquals('GTM-PROD', $service->getContainerId());
    }

    public function test_is_enabled_returns_false_in_local_environment(): void
    {
        $service = $this->createService([
            'app.env' => 'local',
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $this->assertFalse($service->isEnabled());
    }

    public function test_is_enabled_returns_false_when_disabled(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.enabled' => false,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $this->assertFalse($service->isEnabled());
    }

    public function test_is_enabled_returns_true_when_enabled_with_container_id(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $this->assertTrue($service->isEnabled());
    }

    public function test_is_debug_mode_returns_correct_value(): void
    {
        $service = $this->createService([
            'analytics.gtm.debug_mode' => true,
        ]);

        $this->assertTrue($service->isDebugMode());

        $service = $this->createService([
            'analytics.gtm.debug_mode' => false,
        ]);

        $this->assertFalse($service->isDebugMode());
    }

    public function test_get_head_script_returns_empty_when_disabled(): void
    {
        $service = $this->createService([
            'app.env' => 'local',
            'analytics.gtm.enabled' => false,
        ]);

        $this->assertEquals('', $service->getHeadScript());
    }

    public function test_get_head_script_returns_gtm_code_when_enabled(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $script = $service->getHeadScript();

        $this->assertStringContainsString('Google Tag Manager', $script);
        $this->assertStringContainsString('GTM-TEST123', $script);
        $this->assertStringContainsString('www.googletagmanager.com/gtm.js', $script);
    }

    public function test_get_head_script_includes_nonce_when_provided(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $script = $service->getHeadScript('test-nonce-123');

        $this->assertStringContainsString('nonce="test-nonce-123"', $script);
    }

    public function test_get_body_no_script_returns_empty_when_disabled(): void
    {
        $service = $this->createService([
            'app.env' => 'local',
            'analytics.gtm.enabled' => false,
        ]);

        $this->assertEquals('', $service->getBodyNoScript());
    }

    public function test_get_body_no_script_returns_iframe_when_enabled(): void
    {
        $service = $this->createService([
            'app.env' => 'production',
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $noscript = $service->getBodyNoScript();

        $this->assertStringContainsString('<noscript>', $noscript);
        $this->assertStringContainsString('<iframe', $noscript);
        $this->assertStringContainsString('GTM-TEST123', $noscript);
        $this->assertStringContainsString('www.googletagmanager.com/ns.html', $noscript);
    }

    public function test_get_csp_directives_returns_correct_domains(): void
    {
        $service = $this->createService([
            'analytics.csp' => [
                'script_src' => [
                    'https://www.googletagmanager.com',
                    'https://www.google-analytics.com',
                ],
                'connect_src' => [
                    'https://www.google-analytics.com',
                    'https://analytics.google.com',
                    'https://stats.g.doubleclick.net',
                ],
                'img_src' => [
                    'https://www.google-analytics.com',
                    'https://www.googletagmanager.com',
                ],
            ],
        ]);

        $directives = $service->getCspDirectives();

        $this->assertArrayHasKey('script_src', $directives);
        $this->assertArrayHasKey('connect_src', $directives);
        $this->assertArrayHasKey('img_src', $directives);
        $this->assertContains('https://www.googletagmanager.com', $directives['script_src']);
        $this->assertContains('https://www.google-analytics.com', $directives['connect_src']);
    }
}
