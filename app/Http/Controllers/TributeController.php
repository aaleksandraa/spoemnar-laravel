<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTributeRequest;
use App\Models\Memorial;
use App\Models\Tribute;
use App\Services\TributeService;
use App\Services\SanitizationService;
use App\Support\LocaleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TributeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private SanitizationService $sanitizationService,
        private TributeService $tributeService,
    ) {}

    /**
     * Get all tributes for a memorial
     *
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function index(Memorial $memorial): JsonResponse
    {
        // Authorize access to the memorial
        $this->authorize('view', $memorial);

        $tributes = $memorial->tributes()
            ->orderBy('created_at', 'desc')
            ->get();

        return \App\Http\Resources\TributeResource::collection($tributes)->response();
    }

    /**
     * Store a new tribute (public endpoint - no authentication required)
     *
     * @param StoreTributeRequest $request
     * @param Memorial $memorial
     * @return JsonResponse
     */
    public function store(StoreTributeRequest $request, Memorial $memorial): JsonResponse
    {
        // Create tribute record (created_at will be set automatically)
        // Use validated() to ensure only validated fields are used
        $validated = $request->validated();

        $tribute = $this->tributeService->createForMemorial(
            memorial: $memorial,
            authorName: $validated['author_name'],
            authorEmail: $validated['author_email'],
            message: $this->sanitizationService->sanitizeHtml($validated['message']),
            mailLocale: LocaleResolver::detectFromRequest($request),
        );

        return response()->json([
            'tribute' => new \App\Http\Resources\TributeResource($tribute)
        ], 201);
    }

    /**
     * Delete a tribute (memorial owner or admin can delete)
     *
     * @param Request $request
     * @param Tribute $tribute
     * @return JsonResponse
     */
    public function destroy(Request $request, Tribute $tribute): JsonResponse
    {
        // Use policy authorization - allows memorial owner or admin
        $this->authorize('delete', $tribute);

        // Delete tribute record
        $tribute->delete();

        return response()->json(null, 204);
    }
}
