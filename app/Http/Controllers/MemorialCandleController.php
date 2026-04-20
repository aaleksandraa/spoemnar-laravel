<?php

namespace App\Http\Controllers;

use App\Exceptions\MemorialCandleException;
use App\Models\Memorial;
use App\Models\User;
use App\Policies\MemorialPolicy;
use App\Services\MemorialCandleService;
use App\Support\LocaleResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemorialCandleController extends Controller
{
    public function __construct(
        private readonly MemorialCandleService $memorialCandleService,
    ) {}

    public function show(Request $request, Memorial $memorial): JsonResponse
    {
        $locale = $this->resolveRequestLocale($request);
        app()->setLocale($locale);

        if (!(new MemorialPolicy())->view($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Memorial not found',
            ], 404);
        }

        return response()->json([
            'data' => $this->memorialCandleService->summary(
                memorial: $memorial,
                viewer: $request->user(),
                request: $request,
                locale: $locale,
            ),
        ]);
    }

    public function store(Request $request, Memorial $memorial): JsonResponse
    {
        $locale = $this->resolveRequestLocale($request);
        app()->setLocale($locale);
        $validated = $request->validate([
            'locale' => ['nullable', 'string'],
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        if (!(new MemorialPolicy())->view($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Memorial not found',
            ], 404);
        }

        try {
            $this->memorialCandleService->light(
                memorial: $memorial,
                viewer: $request->user(),
                request: $request,
                locale: $locale,
                message: $validated['message'] ?? null,
            );
        } catch (MemorialCandleException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'message' => __('ui.memorial.candle.messages.lit_success'),
            'data' => $this->memorialCandleService->summary(
                memorial: $memorial,
                viewer: $request->user(),
                request: $request,
                locale: $locale,
            ),
        ], 201);
    }

    public function upsertFamily(Request $request, Memorial $memorial): JsonResponse
    {
        $locale = $this->resolveRequestLocale($request);
        app()->setLocale($locale);
        $validated = $request->validate([
            'locale' => ['nullable', 'string'],
            'message' => ['nullable', 'string', 'max:320'],
            'is_premium' => ['nullable', 'boolean'],
        ]);

        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->memorialCandleService->upsertFamilyCandle(
                memorial: $memorial,
                actor: $request->user(),
                message: $validated['message'] ?? null,
                isPremium: (bool) ($validated['is_premium'] ?? false),
                locale: $locale,
            );
        } catch (MemorialCandleException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'message' => __('ui.memorial.candle.messages.family_saved'),
            'data' => $this->memorialCandleService->summary(
                memorial: $memorial,
                viewer: $request->user(),
                request: $request,
                locale: $locale,
            ),
        ]);
    }

    public function destroyFamily(Request $request, Memorial $memorial): JsonResponse
    {
        $locale = $this->resolveRequestLocale($request);
        app()->setLocale($locale);

        if (!$this->canManageMemorial($request->user(), $memorial)) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->memorialCandleService->removeFamilyCandle(
                memorial: $memorial,
                locale: $locale,
            );
        } catch (MemorialCandleException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        return response()->json([
            'message' => __('ui.memorial.candle.messages.family_deleted'),
            'data' => $this->memorialCandleService->summary(
                memorial: $memorial,
                viewer: $request->user(),
                request: $request,
                locale: $locale,
            ),
        ]);
    }

    private function resolveRequestLocale(Request $request): string
    {
        $explicitLocale = LocaleResolver::normalizeLocale((string) ($request->input('locale') ?: ''));

        if (LocaleResolver::isSupported($explicitLocale)) {
            return $explicitLocale;
        }

        return LocaleResolver::detectFromRequest($request);
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
