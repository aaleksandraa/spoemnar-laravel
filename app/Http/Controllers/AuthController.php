<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Mail\PasswordResetRequestMail;
use App\Mail\RegistrationWelcomeMail;
use App\Support\LocaleResolver;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $preferredLocale = $this->resolvePreferredLocale((string) $request->input('locale', ''));

        try {
            ['user' => $user, 'token' => $token] = DB::transaction(function () use ($validated): array {
                $user = User::create([
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role' => 'user',
                ]);

                // Create profile automatically.
                $user->profile()->create([
                    'email' => $validated['email'],
                    'full_name' => $validated['full_name'] ?? null,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                ];
            });
        } catch (\Throwable $exception) {
            Log::error('Registration transactional step failed.', [
                'email' => $validated['email'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        $fullName = trim((string) ($validated['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string) $user->email;
        }

        try {
            Mail::to($user->email)->send(new RegistrationWelcomeMail(
                fullName: $fullName,
                mailLocale: $preferredLocale,
                dashboardUrl: route('dashboard', ['locale' => $preferredLocale]),
            ));
        } catch (\Throwable $exception) {
            Log::warning('Registration welcome email failed.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'user' => $user->load('profile', 'roles'),
            'token' => $token,
        ], 201)->cookie('auth_token', $token, 60 * 24 * 60, '/', null, true, true, false, 'strict');
    }

    /**
     * Login user and generate JWT token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            // Fire the Failed event for security logging
            event(new \Illuminate\Auth\Events\Failed('web', $user, $validated));

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('profile', 'roles'),
            'token' => $token,
        ])->cookie('auth_token', $token, 60 * 24 * 60, '/', null, true, true, false, 'strict');
    }

    /**
     * Send password reset email if account exists.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $preferredLocale = $this->resolvePreferredLocale((string) ($validated['locale'] ?? ''));
        $email = mb_strtolower(trim((string) $validated['email']));

        $user = User::with('profile')
            ->where('email', $email)
            ->first();

        if ($user) {
            try {
                $token = Password::broker()->createToken($user);
                $resetUrl = route('password.reset.form', [
                    'locale' => $preferredLocale,
                    'token' => $token,
                    'email' => $user->email,
                ]);

                $fullName = trim((string) ($user->profile?->full_name ?? ''));
                if ($fullName === '') {
                    $fullName = (string) $user->email;
                }

                Mail::to($user->email)->send(new PasswordResetRequestMail(
                    fullName: $fullName,
                    mailLocale: $preferredLocale,
                    resetUrl: $resetUrl,
                ));
            } catch (\Throwable $exception) {
                Log::warning('Password reset email failed.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => __('ui.auth.reset_link_sent'),
        ]);
    }

    /**
     * Reset password by token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $status = Password::broker()->reset(
            [
                'email' => mb_strtolower(trim((string) $validated['email'])),
                'password' => $validated['password'],
                'password_confirmation' => (string) $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke old API tokens after password reset.
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [$this->passwordResetStatusMessage($status)],
            ]);
        }

        return response()->json([
            'message' => __('ui.auth.password_reset_success'),
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        // Clear auth_token cookie using Cookie::forget()
        $cookie = cookie()->forget('auth_token', '/', null);

        return response()->json([
            'message' => 'Successfully logged out',
        ])->withCookie($cookie);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        UserRole::firstOrCreate([
            'user_id' => $request->user()->id,
            'role' => 'user',
        ]);

        return response()->json([
            'user' => $request->user()->load('profile', 'roles'),
        ]);
    }

    /**
     * Refresh authentication token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->load('profile', 'roles'),
            'token' => $token,
        ])->cookie('auth_token', $token, 60 * 24 * 60, '/', null, true, true, false, 'strict');
    }

    private function resolvePreferredLocale(string $candidate): string
    {
        $normalized = LocaleResolver::normalizeLocale($candidate);

        return LocaleResolver::isSupported($normalized) ? $normalized : app()->getLocale();
    }

    private function passwordResetStatusMessage(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => __('ui.auth.password_reset_invalid_user'),
            Password::INVALID_TOKEN => __('ui.auth.password_reset_invalid_token'),
            default => __('ui.auth.password_reset_failed'),
        };
    }
}
