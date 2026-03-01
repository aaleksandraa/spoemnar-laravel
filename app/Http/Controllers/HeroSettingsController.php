<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHeroSettingsRequest;
use App\Models\HeroSettings;
use Illuminate\Http\JsonResponse;

class HeroSettingsController extends Controller
{
    /**
     * Display the hero settings (public endpoint).
     *
     * @return JsonResponse
     */
    public function show(): JsonResponse
    {
        $settings = HeroSettings::get();

        return response()->json($settings);
    }

    /**
     * Update the hero settings (admin only).
     *
     * @param UpdateHeroSettingsRequest $request
     * @return JsonResponse
     */
    public function update(UpdateHeroSettingsRequest $request): JsonResponse
    {
        $settings = HeroSettings::get();
        $settings->update($request->validated());

        return response()->json($settings);
    }
}
