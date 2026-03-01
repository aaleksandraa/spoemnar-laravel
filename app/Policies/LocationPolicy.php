<?php

namespace App\Policies;

use App\Models\User;

class LocationPolicy
{
    /**
     * Determine if the user can import locations.
     * Only admin users are allowed to perform bulk imports.
     */
    public function import(User $user): bool
    {
        // Check if user has admin role
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }
}
