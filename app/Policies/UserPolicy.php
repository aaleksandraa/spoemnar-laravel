<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can delete the target user.
     *
     * Prevents admin from deleting themselves to avoid losing administrative access.
     * Only admins can delete users.
     */
    public function delete(User $authUser, User $targetUser): bool
    {
        // Prevent admin from deleting themselves
        if ((string) $authUser->id === (string) $targetUser->id) {
            return false;
        }

        // Only admins can delete users
        // Check both the roles relation and legacy role column
        return $authUser->roles()->where('role', 'admin')->exists()
            || ((string) $authUser->role === 'admin');
    }

    /**
     * Determine whether the user can force delete the target user.
     *
     * Same logic as delete - prevents self-deletion.
     */
    public function forceDelete(User $authUser, User $targetUser): bool
    {
        // Prevent admin from deleting themselves
        if ((string) $authUser->id === (string) $targetUser->id) {
            return false;
        }

        // Only admins can force delete users
        return $authUser->roles()->where('role', 'admin')->exists()
            || ((string) $authUser->role === 'admin');
    }
}
