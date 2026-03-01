<?php

namespace App\Http\Controllers;

use App\Services\SEO\SitemapService;
use App\Support\LocaleResolver;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private SitemapService $sitemapService
    ) {}

    /**
     * Return sitemap index
     *
     * @return Response
     */
    public function index(): Response
    {
        $xml = $this->sitemapService->generateSitemapIndex();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Return locale-specific sitemap
     *
     * @param string $locale
     * @return Response
     */
    public function show(string $locale): Response
    {
        // Validate locale
        if (!in_array($locale, LocaleResolver::supportedLocales(), true)) {
            abort(404);
        }

        $xml = $this->sitemapService->generateSitemap($locale);

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
