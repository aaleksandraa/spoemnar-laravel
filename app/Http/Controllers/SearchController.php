<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\SearchService;
use App\Support\LocaleResolver;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search memorials by name
     *
     * @param SearchRequest $request
     * @return JsonResponse
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $query = (string) $request->input('query', $request->input('q', ''));
        $perPage = $request->input('per_page', 15);
        $locale = $request->input('locale');
        if (!is_string($locale) || !LocaleResolver::isSupported($locale)) {
            $locale = app()->getLocale();
        }

        $user = $request->user();

        // Perform search
        $results = $this->searchService->searchMemorials($query, $user, $perPage, $locale);

        return response()->json($results);
    }
}
