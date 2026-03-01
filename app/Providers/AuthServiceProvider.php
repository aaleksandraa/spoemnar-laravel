<?php

namespace App\Providers;

use App\Models\Memorial;
use App\Policies\MemorialPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Memorial::class => MemorialPolicy::class,
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Tribute::class => \App\Policies\TributePolicy::class,
        \App\Models\Profile::class => \App\Policies\ProfilePolicy::class,
        \App\Models\MemorialImage::class => \App\Policies\ImagePolicy::class,
        \App\Models\MemorialVideo::class => \App\Policies\VideoPolicy::class,
        \App\Models\Country::class => \App\Policies\LocationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Admin users bypass all authorization checks
        Gate::before(function ($user, $ability) {
            if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
                return true;
            }
        });
    }
}
