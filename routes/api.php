<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HeroSettingsController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MemorialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TributeController;
use App\Http\Controllers\VideoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => 'v1',
            'timestamp' => now()->toIso8601String()
        ]);
    });

    // Authentication routes
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset-link');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset-submit');

    // Public memorial routes (with optional authentication for access control)
    Route::get('/memorials', [MemorialController::class, 'index'])->middleware('optional.auth');
    Route::get('/memorials/{slug}', [MemorialController::class, 'show'])->middleware(['optional.auth', 'validate.slug']);

    // Public location routes
    Route::get('/locations/countries', [LocationController::class, 'countries']);
    Route::get('/locations/countries/{country}/places', [LocationController::class, 'countryPlaces']);

    // Public tribute routes
    Route::get('/memorials/{memorial}/tributes', [TributeController::class, 'index']);
    Route::post('/memorials/{memorial}/tributes', [TributeController::class, 'store'])->middleware('throttle:3,10');

    // Search route (public)
    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:search');

    // Hero settings route (public)
    Route::get('/hero-settings', [HeroSettingsController::class, 'show']);

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // Profile routes
        Route::get('/profiles/{user}', [ProfileController::class, 'show']);
        Route::put('/profiles/{user}', [ProfileController::class, 'update']);
        Route::delete('/profiles/{user}', [ProfileController::class, 'destroy']);

        // Memorial routes (authenticated)
        Route::post('/memorials', [MemorialController::class, 'store']);
        Route::put('/memorials/{memorial}', [MemorialController::class, 'update']);
        Route::delete('/memorials/{memorial}', [MemorialController::class, 'destroy']);

        // Image routes
        Route::post('/memorials/{memorial}/profile-image', [ImageController::class, 'storeProfileImage']);
        Route::post('/memorials/{memorial}/images', [ImageController::class, 'store']);
        Route::put('/images/{image}', [ImageController::class, 'update']);
        Route::delete('/images/{image}', [ImageController::class, 'destroy']);
        Route::post('/memorials/{memorial}/images/reorder', [ImageController::class, 'reorder']);

        // Video routes
        Route::post('/memorials/{memorial}/videos', [VideoController::class, 'store']);
        Route::put('/videos/{video}', [VideoController::class, 'update']);
        Route::delete('/videos/{video}', [VideoController::class, 'destroy']);
        Route::post('/memorials/{memorial}/videos/reorder', [VideoController::class, 'reorder']);

        // Tribute routes (authenticated - for deletion only)
        Route::delete('/tributes/{tribute}', [TributeController::class, 'destroy']);

        // Admin routes (require 'admin' role)
        Route::middleware('check.role:admin')->prefix('admin')->group(function () {
            // Dashboard
            Route::get('/dashboard', [AdminController::class, 'dashboard']);

            // Memorials management
            Route::get('/memorials', [AdminController::class, 'memorials']);
            Route::delete('/memorials/{memorial}', [AdminController::class, 'deleteMemorial']);

            // Users management
            Route::get('/users', [AdminController::class, 'users']);
            Route::put('/users/{user}/role', [AdminController::class, 'updateUserRole']);
            Route::delete('/users/{user}', [AdminController::class, 'deleteUser']);

            // Tributes management
            Route::delete('/tributes/{tribute}', [AdminController::class, 'deleteTribute']);

            // Hero settings management
            Route::put('/hero-settings', [HeroSettingsController::class, 'update']);

            // App settings management (feature toggles)
            Route::get('/settings', [AdminController::class, 'settings']);
            Route::put('/settings/{settingKey}', [AdminController::class, 'updateSetting']);

            // SEO health check
            Route::get('/seo/locale-health', [AdminController::class, 'localeSeoHealth']);

            // Locations management
            Route::get('/locations/countries', [LocationController::class, 'adminCountries']);
            Route::get('/locations/places', [LocationController::class, 'adminPlaces']);
            Route::post('/locations/countries', [LocationController::class, 'storeCountry']);
            Route::post('/locations/places', [LocationController::class, 'storePlace']);
            Route::post('/locations/import', [LocationController::class, 'import']);
        });
    });
});
