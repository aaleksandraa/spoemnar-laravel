<?php

namespace App\Policies;

use App\Models\Tribute;
use App\Models\User;

class TributePolicy
{
    /**
     * Determine if the user can delete the tribute.
     *
     * Admins can delete any tribute for moderation purposes.
     * Memorial owners can delete tributes on their memorials.
     */
    public function delete(User $user, Tribute $tribute): bool
    {
        // Allow if user is admin (check both roles table and legacy role column)
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        // Allow if user is memorial owner
        return (string) $user->id === (string) $tribute->memorial->user_id;
    }
}
