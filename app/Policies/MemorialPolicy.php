<?php

namespace App\Policies;

use App\Models\Memorial;
use App\Models\User;

class MemorialPolicy
{
    /**
     * Determine if the given memorial can be viewed by the user.
     */
    public function view(?User $user, Memorial $memorial): bool
    {
        // Public memorials can be viewed by anyone
        if ($memorial->is_public) {
            return true;
        }

        // Private memorials can only be viewed by the owner or admin
        if ($user) {
            // Check if user is the owner
            if ((string) $user->id === (string) $memorial->user_id) {
                return true;
            }

            // Check if user is an admin (check both roles table and legacy role column)
            if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user can view any memorials.
     * This is used for index filtering.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view the memorial list (but filtering will be applied)
        return true;
    }
}
