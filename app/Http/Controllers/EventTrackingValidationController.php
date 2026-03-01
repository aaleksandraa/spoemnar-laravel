<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EventTrackingValidationController extends Controller
{
    /**
     * Show the event tracking validation page.
     * Only accessible in non-production environments.
     */
    public function index()
    {
        // Only allow in non-production environments
        if (config('app.env') === 'production') {
            abort(404);
        }

        $events = $this->getEventDefinitions();

        return view('analytics.validation', compact('events'));
    }

    /**
     * Get definitions for all 12 event types
     */
    private function getEventDefinitions(): array
    {
        return [
            [
                'name' => 'page_view',
                'description' => 'Tracks when a user views a page',
                'parameters' => [
                    'page_path' => '/test-page',
                    'page_title' => 'Test Page',
                    'page_locale' => 'en',
                    'page_type' => 'test',
                ],
            ],
            [
                'name' => 'view_memorial',
                'description' => 'Tracks when a user views a memorial profile',
                'parameters' => [
                    'memorial_id' => '123',
                    'memorial_slug' => 'john-doe',
                    'locale' => 'en',
                    'is_public' => true,
                ],
            ],
            [
                'name' => 'search',
                'description' => 'Tracks when a user performs a search',
                'parameters' => [
                    'search_term' => 'test query',
                    'results_count' => 5,
                    'locale' => 'en',
                ],
            ],
            [
                'name' => 'form_submit',
                'description' => 'Tracks when a user submits a form',
                'parameters' => [
                    'form_type' => 'contact',
                    'locale' => 'en',
                    'success' => true,
                    'error_type' => null,
                ],
            ],
            [
                'name' => 'sign_up',
                'description' => 'Tracks when a user completes registration',
                'parameters' => [
                    'locale' => 'en',
                    'registration_method' => 'email',
                ],
            ],
            [
                'name' => 'create_memorial',
                'description' => 'Tracks when a user creates a memorial',
                'parameters' => [
                    'locale' => 'en',
                    'is_public' => true,
                ],
            ],
            [
                'name' => 'upload_media',
                'description' => 'Tracks when a user uploads media',
                'parameters' => [
                    'media_type' => 'image',
                    'memorial_id' => '123',
                    'file_size_kb' => 1024,
                ],
            ],
            [
                'name' => 'submit_tribute',
                'description' => 'Tracks when a user submits a tribute',
                'parameters' => [
                    'memorial_id' => '123',
                    'locale' => 'en',
                    'tribute_type' => 'text',
                ],
            ],
            [
                'name' => 'navigation_click',
                'description' => 'Tracks when a user clicks a navigation link',
                'parameters' => [
                    'menu_item' => 'Home',
                    'destination_url' => 'https://example.com',
                    'locale' => 'en',
                ],
            ],
            [
                'name' => 'outbound_click',
                'description' => 'Tracks when a user clicks an external link',
                'parameters' => [
                    'link_url' => 'https://external.com',
                    'link_text' => 'External Link',
                    'page_location' => 'https://example.com/page',
                ],
            ],
            [
                'name' => 'file_download',
                'description' => 'Tracks when a user downloads a file',
                'parameters' => [
                    'file_type' => 'document',
                    'file_name' => 'example.pdf',
                    'file_extension' => 'pdf',
                ],
            ],
            [
                'name' => 'error_event',
                'description' => 'Tracks JavaScript errors',
                'parameters' => [
                    'error_type' => 'TypeError',
                    'error_message' => 'Test error message',
                    'page_url' => 'https://example.com/page',
                    'user_agent' => 'Mozilla/5.0...',
                ],
            ],
        ];
    }
}
