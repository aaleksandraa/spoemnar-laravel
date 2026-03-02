<?php

namespace App\Http\Controllers;

use App\Models\Memorial;
use App\Models\User;
use App\Http\Requests\StoreMemorialRequest;
use App\Http\Requests\UpdateMemorialRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\MemorialResource;
use App\Services\SanitizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemorialController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private SanitizationService $sanitizationService
    ) {}

    /**
     * Display a listing of memorials with pagination
     */
    public function index(PaginationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = $validated['perPage'] ?? $validated['per_page'] ?? 15;
        $isPublic = $request->input('isPublic');
        $mineOnly = filter_var($request->input('mine'), FILTER_VALIDATE_BOOLEAN);

        $query = Memorial::query();

        $user = $request->user();
        $isAdmin = $user && ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin');

        if ($mineOnly) {
            if (!$user) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('user_id', $user->id);
            }

            if ($isPublic !== null) {
                $query->where('is_public', filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));
            }

            $memorials = $query
                ->with(['birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $transformedData = MemorialResource::collection($memorials->items())->toArray($request);

            return response()->json([
                'data' => $transformedData,
                'current_page' => $memorials->currentPage(),
                'last_page' => $memorials->lastPage(),
                'per_page' => $memorials->perPage(),
                'total' => $memorials->total(),
                'from' => $memorials->firstItem(),
                'to' => $memorials->lastItem(),
                'first_page_url' => $memorials->url(1),
                'last_page_url' => $memorials->url($memorials->lastPage()),
                'next_page_url' => $memorials->nextPageUrl(),
                'prev_page_url' => $memorials->previousPageUrl(),
                'path' => $memorials->path(),
                'links' => $memorials->linkCollection()->toArray(),
            ]);
        }

        // Admin users can see all memorials
        if ($isAdmin) {
            // Filter by public status if specified
            if ($isPublic !== null) {
                $query->where('is_public', filter_var($isPublic, FILTER_VALIDATE_BOOLEAN));
            }
        } else {
            // Non-admin users: show public memorials OR their own private memorials
            if ($user) {
                // Authenticated non-admin: public memorials OR own memorials
                $query->where(function ($q) use ($user) {
                    $q->where('is_public', true)
                      ->orWhere('user_id', $user->id);
                });
            } else {
                // Unauthenticated: only public memorials
                $query->where('is_public', true);
            }
        }

        $memorials = $query
            ->with(['birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Transform the paginated response to match frontend expectations
        // Convert resource collection to array to avoid type mismatch
        $transformedData = MemorialResource::collection($memorials->items())->toArray($request);

        return response()->json([
            'data' => $transformedData,
            'current_page' => $memorials->currentPage(),
            'last_page' => $memorials->lastPage(),
            'per_page' => $memorials->perPage(),
            'total' => $memorials->total(),
            'from' => $memorials->firstItem(),
            'to' => $memorials->lastItem(),
            'first_page_url' => $memorials->url(1),
            'last_page_url' => $memorials->url($memorials->lastPage()),
            'next_page_url' => $memorials->nextPageUrl(),
            'prev_page_url' => $memorials->previousPageUrl(),
            'path' => $memorials->path(),
            'links' => $memorials->linkCollection()->toArray(),
        ]);
    }

    /**
     * Store a newly created memorial
     */
    public function store(StoreMemorialRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Sanitize biography to prevent XSS
        if (isset($validated['biography'])) {
            $validated['biography'] = $this->sanitizationService->sanitizeHtml($validated['biography']);
        }

        // Generate slug from first_name and last_name
        $baseSlug = Memorial::generateSlug($validated['first_name'], $validated['last_name']);

        // Ensure slug uniqueness by appending numeric suffix if needed
        $slug = $baseSlug;
        $counter = 1;

        while (Memorial::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $validated['slug'] = $slug;
        $validated['user_id'] = $request->user()->id;

        $memorial = Memorial::create($validated);
        $memorial->load(['birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity']);

        return response()->json([
            'data' => new MemorialResource($memorial),
        ], 201);
    }

    /**
     * Display the specified memorial by slug
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $memorial = Memorial::with(['birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity'])
            ->where('slug', $slug)
            ->first();

        if (!$memorial) {
            return response()->json([
                'message' => 'Memorial not found',
            ], 404);
        }

        // Check authorization using the policy
        $user = $request->user();
        $policy = new \App\Policies\MemorialPolicy();

        if (!$policy->view($user, $memorial)) {
            return response()->json([
                'message' => 'Memorial not found',
            ], 404);
        }

        // Load relationships
        $memorial->load(['images', 'videos', 'tributes', 'birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity']);

        return response()->json([
            'data' => new MemorialResource($memorial),
        ]);
    }

    /**
     * Update the specified memorial
     */
    public function update(UpdateMemorialRequest $request, Memorial $memorial): JsonResponse
    {
        // Check authorization - owner or admin can update memorial
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validated();

        // Sanitize biography to prevent XSS
        if (isset($validated['biography'])) {
            $validated['biography'] = $this->sanitizationService->sanitizeHtml($validated['biography']);
        }

        // If first_name or last_name changed, regenerate slug
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $memorial->first_name;
            $lastName = $validated['last_name'] ?? $memorial->last_name;

            $baseSlug = Memorial::generateSlug($firstName, $lastName);

            // Ensure slug uniqueness (excluding current memorial)
            $slug = $baseSlug;
            $counter = 1;

            while (Memorial::where('slug', $slug)->where('id', '!=', $memorial->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $validated['slug'] = $slug;
        }

        $memorial->update($validated);

        return response()->json([
            'data' => new MemorialResource($memorial->fresh(['birthCountry', 'deathCountry', 'birthPlaceEntity', 'deathPlaceEntity'])),
        ]);
    }

    /**
     * Remove the specified memorial
     */
    public function destroy(Request $request, Memorial $memorial): JsonResponse
    {
        // Check authorization - owner or admin can delete memorial
        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $memorial->delete();

        return response()->json([
            'message' => 'Memorial deleted successfully',
        ], 204);
    }

    private function canManageMemorial(?User $user, Memorial $memorial): bool
    {
        if (!$user) {
            return false;
        }

        if ((string) $user->id === (string) $memorial->user_id) {
            return true;
        }

        return $user->roles()->where('role', 'admin')->exists()
            || (string) $user->role === 'admin';
    }
}
