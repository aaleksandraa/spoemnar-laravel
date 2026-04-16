<?php

namespace Tests\Feature\SEO;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchConsoleVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_console_file_verification_returns_expected_content(): void
    {
        config([
            'seo.search_console.file_token' => 'abc123token',
            'seo.search_console.file_content' => 'google-site-verification: googleabc123token.html',
        ]);

        $response = $this->get('/googleabc123token.html');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSeeText('google-site-verification: googleabc123token.html');
    }

    public function test_search_console_file_verification_uses_default_content_when_not_configured(): void
    {
        config([
            'seo.search_console.file_token' => 'xyz987token',
            'seo.search_console.file_content' => '',
        ]);

        $response = $this->get('/googlexyz987token.html');

        $response->assertOk();
        $response->assertSeeText('google-site-verification: googlexyz987token.html');
    }

    public function test_search_console_file_verification_returns_404_for_invalid_token(): void
    {
        config([
            'seo.search_console.file_token' => 'valid-token',
            'seo.search_console.file_content' => 'google-site-verification: googlevalid-token.html',
        ]);

        $this->get('/googleinvalid-token.html')->assertNotFound();
    }
}
