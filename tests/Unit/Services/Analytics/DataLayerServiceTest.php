<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\DataLayerService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class DataLayerServiceTest extends TestCase
{
    private function createService(string $routeName = null, string $path = '/', string $locale = 'en'): DataLayerService
    {
        $request = $this->createMock(Request::class);

        if ($routeName !== null) {
            $route = $this->createMock(Route::class);
            $route->expects($this->any())
                ->method('getName')
                ->willReturn($routeName);

            $request->expects($this->any())
                ->method('route')
                ->willReturn($route);
        } else {
            $request->expects($this->any())
                ->method('route')
                ->willReturn(null);
        }

        $request->expects($this->any())
            ->method('path')
            ->willReturn($path);

        app()->setLocale($locale);

        return new DataLayerService($request);
    }

    public function test_get_initial_state_returns_correct_page_context(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $service = $this->createService('home', '/', 'en');
        $state = $service->getInitialState();

        $this->assertIsArray($state);
        $this->assertArrayHasKey('page_type', $state);
        $this->assertArrayHasKey('locale', $state);
        $this->assertArrayHasKey('region', $state);
        $this->assertArrayHasKey('user_type', $state);
        $this->assertArrayHasKey('page_path', $state);
        $this->assertArrayHasKey('page_title', $state);

        $this->assertEquals('home', $state['page_type']);
        $this->assertEquals('en', $state['locale']);
        $this->assertEquals('US', $state['region']);
        $this->assertEquals('guest', $state['user_type']);
        $this->assertEquals('/', $state['page_path']);
    }

    public function test_get_page_type_identifies_home_page(): void
    {
        $service = $this->createService('home');
        $this->assertEquals('home', $service->getPageType());
    }

    public function test_get_page_type_identifies_memorial_pages(): void
    {
        $service = $this->createService('memorials.show');
        $this->assertEquals('memorial', $service->getPageType());

        $service = $this->createService('memorials.index');
        $this->assertEquals('memorial', $service->getPageType());

        $service = $this->createService('memorials.create');
        $this->assertEquals('memorial', $service->getPageType());
    }

    public function test_get_page_type_identifies_search_page(): void
    {
        $service = $this->createService('search');
        $this->assertEquals('search', $service->getPageType());

        $service = $this->createService('search.results');
        $this->assertEquals('search', $service->getPageType());
    }

    public function test_get_page_type_identifies_contact_page(): void
    {
        $service = $this->createService('contact');
        $this->assertEquals('contact', $service->getPageType());

        $service = $this->createService('contact.submit');
        $this->assertEquals('contact', $service->getPageType());
    }

    public function test_get_page_type_identifies_about_page(): void
    {
        $service = $this->createService('about');
        $this->assertEquals('about', $service->getPageType());
    }

    public function test_get_page_type_identifies_privacy_page(): void
    {
        $service = $this->createService('privacy');
        $this->assertEquals('privacy', $service->getPageType());

        $service = $this->createService('privacy.policy');
        $this->assertEquals('privacy', $service->getPageType());
    }

    public function test_get_page_type_identifies_terms_page(): void
    {
        $service = $this->createService('terms');
        $this->assertEquals('terms', $service->getPageType());

        $service = $this->createService('terms.service');
        $this->assertEquals('terms', $service->getPageType());
    }

    public function test_get_page_type_identifies_cookie_settings_page(): void
    {
        $service = $this->createService('cookie.settings');
        $this->assertEquals('cookie_settings', $service->getPageType());

        $service = $this->createService('cookies');
        $this->assertEquals('cookie_settings', $service->getPageType());
    }

    public function test_get_page_type_returns_other_for_unmatched_routes(): void
    {
        $service = $this->createService('some.random.route');
        $this->assertEquals('other', $service->getPageType());
    }

    public function test_get_page_type_returns_unknown_when_no_route(): void
    {
        $service = $this->createService(null);
        $this->assertEquals('unknown', $service->getPageType());
    }

    public function test_get_user_type_returns_guest_when_not_authenticated(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $service = $this->createService('home');
        $this->assertEquals('guest', $service->getUserType());
    }

    public function test_get_user_type_returns_registered_when_authenticated(): void
    {
        Auth::shouldReceive('check')->andReturn(true);

        $service = $this->createService('home');
        $this->assertEquals('registered', $service->getUserType());
    }

    public function test_get_region_maps_bosnian_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'bs');
        $this->assertEquals('BA', $service->getRegion('bs'));
    }

    public function test_get_region_maps_serbian_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'sr');
        $this->assertEquals('RS', $service->getRegion('sr'));
    }

    public function test_get_region_maps_croatian_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'hr');
        $this->assertEquals('HR', $service->getRegion('hr'));
    }

    public function test_get_region_maps_german_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'de');
        $this->assertEquals('DE', $service->getRegion('de'));
    }

    public function test_get_region_maps_english_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'en');
        $this->assertEquals('US', $service->getRegion('en'));
    }

    public function test_get_region_maps_italian_locale_correctly(): void
    {
        $service = $this->createService('home', '/', 'it');
        $this->assertEquals('IT', $service->getRegion('it'));
    }

    public function test_get_region_returns_default_for_unknown_locale(): void
    {
        $service = $this->createService('home', '/', 'fr');
        $this->assertEquals('US', $service->getRegion('fr'));
    }

    public function test_get_initial_state_uses_current_locale(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $service = $this->createService('home', '/', 'de');
        $state = $service->getInitialState();

        $this->assertEquals('de', $state['locale']);
        $this->assertEquals('DE', $state['region']);
    }

    public function test_get_initial_state_includes_correct_path(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $service = $this->createService('memorials.show', 'memorials/john-doe', 'en');
        $state = $service->getInitialState();

        $this->assertEquals('memorials/john-doe', $state['page_path']);
    }

    public function test_get_initial_state_reflects_authenticated_user(): void
    {
        Auth::shouldReceive('check')->andReturn(true);

        $service = $this->createService('home', '/', 'en');
        $state = $service->getInitialState();

        $this->assertEquals('registered', $state['user_type']);
    }
}
