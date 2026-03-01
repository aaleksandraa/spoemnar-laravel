<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Configure rate limiting
            RateLimiter::for('password-reset-link', function (Request $request) {
                return Limit::perHour(3)->by($request->input('email') ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Too many password reset attempts. Please try again later.'
                        ], 429);
                    });
            });

            RateLimiter::for('password-reset-submit', function (Request $request) {
                return Limit::perHour(5)->by($request->input('email') ?: $request->ip())
                    ->response(function () {
                        return response()->json([
                            'message' => 'Too many password reset submissions. Please try again later.'
                        ], 429);
                    });
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.custom' => \App\Http\Middleware\Authenticate::class,
            'check.role' => \App\Http\Middleware\CheckRole::class,
            'optional.auth' => \App\Http\Middleware\OptionalAuth::class,
            'validate.slug' => \App\Http\Middleware\ValidateSlugFormat::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Enable CORS for API routes
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Add security headers globally
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Force HTTPS in production
        $middleware->append(\App\Http\Middleware\ForceHttps::class);

        // Enforce global request size limit (10MB)
        $middleware->append(\App\Http\Middleware\EnforceRequestSizeLimit::class);

        // Assign unique request ID for debugging and auditing
        $middleware->append(\App\Http\Middleware\AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle validation exceptions
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
        });

        // Handle model not found exceptions
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found'
                ], 404);
            }
        });

        // Handle authentication exceptions
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated'
                ], 401);
            }
        });

        // Handle authorization exceptions
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden'
                ], 403);
            }
        });

        // Handle custom exceptions
        $exceptions->render(function (\App\Exceptions\MemorialNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 404);
            }
        });

        $exceptions->render(function (\App\Exceptions\UnauthorizedAccessException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 403);
            }
        });

        $exceptions->render(function (\App\Exceptions\InvalidSlugException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\ImageUploadException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }
        });

        $exceptions->render(function (\App\Exceptions\InvalidYouTubeUrlException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }
        });

        // Catch-all handler for any unhandled exceptions
        // This must be the LAST handler - it only catches exceptions not handled above
        $exceptions->render(function (\Throwable $e, $request) {
            // Skip if this is a specific exception type already handled above
            if ($e instanceof \Illuminate\Validation\ValidationException ||
                $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ||
                $e instanceof \Illuminate\Auth\AuthenticationException ||
                $e instanceof \Illuminate\Auth\Access\AuthorizationException ||
                $e instanceof \App\Exceptions\MemorialNotFoundException ||
                $e instanceof \App\Exceptions\UnauthorizedAccessException ||
                $e instanceof \App\Exceptions\InvalidSlugException ||
                $e instanceof \App\Exceptions\ImageUploadException ||
                $e instanceof \App\Exceptions\InvalidYouTubeUrlException) {
                // Let the specific handlers above handle these
                return null;
            }

            // Log full error details internally for debugging
            \Illuminate\Support\Facades\Log::error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
            ]);

            // Return generic error messages in production, detailed in development
            $isProduction = app()->environment('production');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $isProduction
                        ? 'An error occurred while processing your request. Please try again later.'
                        : $e->getMessage(),
                    'exception' => $isProduction ? null : get_class($e),
                    'file' => $isProduction ? null : $e->getFile(),
                    'line' => $isProduction ? null : $e->getLine(),
                    'trace' => $isProduction ? null : explode("\n", $e->getTraceAsString()),
                ], 500);
            }

            // For non-JSON requests (HTML)
            if ($isProduction) {
                return response()->view('errors.500', [], 500);
            }

            // In development, let Laravel show the detailed error page
            return null;
        });
    })->create();
