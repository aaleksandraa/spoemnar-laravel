<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Services\SearchService;
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
        $query = $request->input('query');
        $perPage = $request->input('per_page', 15);

        // Check if user is authenticated
        $publicOnly = !$request->user();

        // Perform search
        $results = $this->searchService->searchMemorials($query, $publicOnly, $perPage);

        return response()->json($results);
    }
}
