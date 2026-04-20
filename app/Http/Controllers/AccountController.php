<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountRequest;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('profile', 'roles'),
        ]);
    }

    public function update(UpdateAccountRequest $request): JsonResponse
    {
        $user = $request->user()->loadMissing('profile', 'roles');
        $validated = $request->validated();
        $profile = $this->resolveProfile($request);

        $email = isset($validated['email'])
            ? mb_strtolower(trim((string) $validated['email']))
            : (string) $user->email;
        $fullName = array_key_exists('full_name', $validated)
            ? $validated['full_name']
            : $profile->full_name;
        $preferredLocale = array_key_exists('preferred_locale', $validated)
            ? $validated['preferred_locale']
            : $profile->preferred_locale;
        $passwordChanged = filled($validated['new_password'] ?? null);

        DB::transaction(function () use ($user, $profile, $email, $fullName, $preferredLocale, $validated): void {
            if ($email !== '' && $email !== $user->email) {
                $user->email = $email;
            }

            if (filled($validated['new_password'] ?? null)) {
                $user->password = (string) $validated['new_password'];
            }

            $user->save();

            $profile->forceFill([
                'email' => $email !== '' ? $email : $user->email,
                'full_name' => $fullName,
                'preferred_locale' => $preferredLocale,
            ])->save();
        });

        if ($passwordChanged) {
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken) {
                $request->user()->tokens()
                    ->whereKeyNot($currentToken->id)
                    ->delete();
            }
        }

        return response()->json([
            'message' => __('ui.account.save_success'),
            'user' => $request->user()->fresh()->load('profile', 'roles'),
        ]);
    }

    private function resolveProfile(Request $request): Profile
    {
        $user = $request->user()->loadMissing('profile');

        if ($user->profile) {
            return $user->profile;
        }

        return $user->profile()->create([
            'email' => $user->email,
        ]);
    }
}
