<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class SanitizationService
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();

        // Very restrictive configuration - no HTML tags allowed
        $config->set('HTML.Allowed', '');

        // Disable cache for simplicity (can be enabled in production)
        $config->set('Cache.DefinitionImpl', null);

        // Convert newlines to <br> tags (but they will be stripped anyway)
        $config->set('AutoFormat.AutoParagraph', false);

        // Remove all attributes
        $config->set('HTML.AllowedAttributes', '');

        // Suppress warnings from HTMLPurifier
        $config->set('Core.CollectErrors', false);

        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Sanitize HTML content by removing all HTML tags and malicious content
     *
     * @param string|null $html
     * @return string|null
     */
    public function sanitizeHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Suppress warnings during purification
        $previousErrorReporting = error_reporting();
        error_reporting($previousErrorReporting & ~E_USER_WARNING & ~E_WARNING);

        try {
            // Use HTMLPurifier to sanitize the content
            $sanitized = $this->purifier->purify($html);

            // Trim whitespace
            $sanitized = trim($sanitized);

            // Decode HTML entities to preserve plain text characters
            // HTMLPurifier encodes entities like & to &amp; even when no HTML is allowed
            // We decode them back to preserve the original plain text
            $sanitized = html_entity_decode($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $sanitized;
        } finally {
            // Restore error reporting
            error_reporting($previousErrorReporting);
        }
    }

    /**
     * Sanitize plain text by escaping special characters
     *
     * @param string|null $text
     * @return string|null
     */
    public function sanitizeText(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
