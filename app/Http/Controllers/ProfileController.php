<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display the specified user's profile
     */
    public function show(User $user): JsonResponse
    {
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found',
            ], 404);
        }

        // Authorization check: verify user owns profile or has admin role
        $this->authorize('view', $profile);

        return response()->json([
            'profile' => $profile,
        ]);
    }

    /**
     * Update the specified user's profile
     */
    public function update(UpdateProfileRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found',
            ], 404);
        }

        // Authorization check: verify user owns profile or has admin role
        $this->authorize('update', $profile);

        $profile->update($validated);

        return response()->json([
            'profile' => $profile->fresh(),
        ]);
    }

    /**
     * Remove the specified user's profile and account
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $profile = $user->profile;

        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found',
            ], 404);
        }

        // Authorization check: verify user owns profile or has admin role
        $this->authorize('delete', $profile);

        // Delete user (cascade will handle profile and memorials)
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ], 204);
    }
}
