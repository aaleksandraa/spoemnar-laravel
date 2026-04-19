<?php

namespace App\Services\Security;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;

class SuspiciousTrafficDetector
{
    public function __construct(
        private Request $request,
        private ConfigRepository $config
    ) {}

    public function shouldSuppressAnalytics(): bool
    {
        if (!$this->config->get('security.analytics_bot_filter.enabled', true)) {
            return false;
        }

        if ($this->request->isMethod('HEAD')) {
            return true;
        }

        $userAgent = mb_strtolower(trim((string) $this->request->userAgent()));
        if ($userAgent === '') {
            return true;
        }

        $signatures = $this->config->get('security.analytics_bot_filter.signatures', []);
        if (!is_array($signatures)) {
            return false;
        }

        foreach ($signatures as $signature) {
            $needle = mb_strtolower(trim((string) $signature));
            if ($needle !== '' && str_contains($userAgent, $needle)) {
                return true;
            }
        }

        return false;
    }
}
