<?php

namespace App\Services\Analytics;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class GTMService
{
    public function __construct(
        private ConfigRepository $config
    ) {}

    /**
     * Get GTM container ID for current environment
     */
    public function getContainerId(): ?string
    {
        // Don't load GTM in development environment
        if ($this->config->get('app.env') === 'local') {
            return null;
        }

        return $this->config->get('analytics.gtm.container_id');
    }

    /**
     * Check if GTM should be loaded
     */
    public function isEnabled(): bool
    {
        // GTM is disabled in development environment
        if ($this->config->get('app.env') === 'local') {
            return false;
        }

        // Check if analytics is enabled and container ID is configured
        return $this->config->get('analytics.gtm.enabled', false)
            && !empty($this->getContainerId());
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return $this->config->get('analytics.gtm.debug_mode', false);
    }

    /**
     * Get GTM head script HTML
     */
    public function getHeadScript(?string $nonce = null): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $containerId = $this->getContainerId();
        $nonceAttr = $nonce ? " nonce=\"{$nonce}\"" : '';

        return <<<HTML
<!-- Google Tag Manager -->
<script{$nonceAttr}>
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$containerId}');
</script>
<!-- End Google Tag Manager -->
HTML;
    }

    /**
     * Get GTM body noscript HTML
     */
    public function getBodyNoScript(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $containerId = $this->getContainerId();

        return <<<HTML
<!-- Google Tag Manager (noscript) -->
<noscript>
<iframe src="https://www.googletagmanager.com/ns.html?id={$containerId}"
height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;
    }

    /**
     * Get CSP directives for GTM
     */
    public function getCspDirectives(): array
    {
        return $this->config->get('analytics.csp', [
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
        ]);
    }
}
