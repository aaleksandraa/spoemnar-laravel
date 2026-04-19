<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\GA4Service;
use App\Services\Security\SuspiciousTrafficDetector;
use Tests\TestCase;

class GA4ServiceTest extends TestCase
{
    public function test_is_enabled_returns_false_in_local_environment(): void
    {
        config([
            'app.env' => 'local',
            'analytics.ga4.enabled' => true,
            'analytics.ga4.measurement_id' => 'G-TEST123',
        ]);

        $service = app(GA4Service::class);

        $this->assertFalse($service->isEnabled());
    }

    public function test_is_enabled_returns_false_without_measurement_id(): void
    {
        config([
            'app.env' => 'production',
            'analytics.ga4.enabled' => true,
            'analytics.ga4.measurement_id' => '',
        ]);

        $service = app(GA4Service::class);

        $this->assertFalse($service->isEnabled());
    }

    public function test_should_load_direct_script_returns_true_when_ga4_enabled_and_gtm_disabled(): void
    {
        config([
            'app.env' => 'production',
            'analytics.ga4.enabled' => true,
            'analytics.ga4.measurement_id' => 'G-TEST123',
            'analytics.ga4.use_direct_script' => true,
            'analytics.gtm.enabled' => false,
            'analytics.gtm.container_id' => '',
        ]);

        $service = app(GA4Service::class);

        $this->assertTrue($service->shouldLoadDirectScript());
    }

    public function test_should_load_direct_script_returns_false_when_gtm_is_enabled(): void
    {
        config([
            'app.env' => 'production',
            'analytics.ga4.enabled' => true,
            'analytics.ga4.measurement_id' => 'G-TEST123',
            'analytics.ga4.use_direct_script' => true,
            'analytics.gtm.enabled' => true,
            'analytics.gtm.container_id' => 'GTM-TEST123',
        ]);

        $service = app(GA4Service::class);

        $this->assertFalse($service->shouldLoadDirectScript());
    }

    public function test_is_enabled_returns_false_for_suspicious_traffic(): void
    {
        $detector = $this->createMock(SuspiciousTrafficDetector::class);
        $detector->method('shouldSuppressAnalytics')->willReturn(true);
        $this->app->instance(SuspiciousTrafficDetector::class, $detector);

        config([
            'app.env' => 'production',
            'analytics.ga4.enabled' => true,
            'analytics.ga4.measurement_id' => 'G-TEST123',
        ]);

        $service = app(GA4Service::class);

        $this->assertFalse($service->isEnabled());
    }
}
