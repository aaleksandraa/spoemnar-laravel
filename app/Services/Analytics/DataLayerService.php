<?php

namespace App\Services\Analytics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataLayerService
{
    public function __construct(
        private Request $request
    ) {}

    /**
     * Get initial data layer state for current page
     */
    public function getInitialState(): array
    {
        $locale = app()->getLocale();

        return [
            'page_type' => $this->getPageType(),
            'locale' => $locale,
            'region' => $this->getRegion($locale),
            'user_type' => $this->getUserType(),
            'page_path' => $this->request->path(),
            'page_title' => '', // Will be set by JavaScript from document.title
        ];
    }

    /**
     * Get page type from current route
     */
    public function getPageType(): string
    {
        $routeName = $this->request->route()?->getName();

        if (!$routeName) {
            return 'unknown';
        }

        // Map route names to page types
        return match (true) {
            $routeName === 'home' => 'home',
            str_starts_with($routeName, 'memorials.') => 'memorial',
            str_starts_with($routeName, 'search') => 'search',
            str_starts_with($routeName, 'contact') => 'contact',
            str_starts_with($routeName, 'about') => 'about',
            str_starts_with($routeName, 'privacy') => 'privacy',
            str_starts_with($routeName, 'terms') => 'terms',
            str_starts_with($routeName, 'cookie') => 'cookie_settings',
            default => 'other',
        };
    }

    /**
     * Get user type (guest, registered)
     */
    public function getUserType(): string
    {
        return Auth::check() ? 'registered' : 'guest';
    }

    /**
     * Get region from locale
     */
    public function getRegion(string $locale): string
    {
        return match ($locale) {
            'bs' => 'BA', // Bosnia and Herzegovina
            'sr' => 'RS', // Serbia
            'hr' => 'HR', // Croatia
            'de' => 'DE', // Germany
            'en' => 'US', // United States (default for English)
            'it' => 'IT', // Italy
            default => 'US',
        };
    }
}
