<?php

namespace App\Policies;

use App\Models\MemorialImage;
use App\Models\User;

class ImagePolicy
{
    /**
     * Determine if the user can reorder the image.
     */
    public function reorder(User $user, MemorialImage $image): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $image->memorial->user_id) {
            return true;
        }

        // Admin users can reorder any images
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update the image.
     */
    public function update(User $user, MemorialImage $image): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $image->memorial->user_id) {
            return true;
        }

        // Admin users can update any images
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the image.
     */
    public function delete(User $user, MemorialImage $image): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $image->memorial->user_id) {
            return true;
        }

        // Admin users can delete any images
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }
}
