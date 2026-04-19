<?php

namespace App\Services\Analytics;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use App\Services\Security\SuspiciousTrafficDetector;

class GA4Service
{
    public function __construct(
        private ConfigRepository $config,
        private GTMService $gtmService,
        private SuspiciousTrafficDetector $suspiciousTrafficDetector
    ) {}

    /**
     * Return configured GA4 measurement ID.
     */
    public function getMeasurementId(): ?string
    {
        $measurementId = trim((string) $this->config->get('analytics.ga4.measurement_id', ''));

        return $measurementId !== '' ? $measurementId : null;
    }

    /**
     * Check if GA4 integration is enabled.
     */
    public function isEnabled(): bool
    {
        if ($this->config->get('app.env') === 'local') {
            return false;
        }

        if ($this->suspiciousTrafficDetector->shouldSuppressAnalytics()) {
            return false;
        }

        if (!$this->config->get('analytics.ga4.enabled', false)) {
            return false;
        }

        return $this->getMeasurementId() !== null;
    }

    /**
     * Check if direct GA4 script should load.
     *
     * Direct GA4 is suppressed when GTM is active to avoid duplicate tracking.
     */
    public function shouldLoadDirectScript(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!$this->config->get('analytics.ga4.use_direct_script', true)) {
            return false;
        }

        return !$this->gtmService->isEnabled();
    }

    /**
     * Determine whether GA4 should send an automatic initial page view.
     */
    public function shouldSendInitialPageView(): bool
    {
        return (bool) $this->config->get('analytics.ga4.send_page_view', false);
    }
}
