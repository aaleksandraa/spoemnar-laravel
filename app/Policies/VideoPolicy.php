<?php

namespace App\Policies;

use App\Models\MemorialVideo;
use App\Models\User;

class VideoPolicy
{
    /**
     * Determine if the user can reorder the video.
     */
    public function reorder(User $user, MemorialVideo $video): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $video->memorial->user_id) {
            return true;
        }

        // Admin users can reorder any videos
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can update the video.
     */
    public function update(User $user, MemorialVideo $video): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $video->memorial->user_id) {
            return true;
        }

        // Admin users can update any videos
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can delete the video.
     */
    public function delete(User $user, MemorialVideo $video): bool
    {
        // User must own the parent memorial
        if ((string) $user->id === (string) $video->memorial->user_id) {
            return true;
        }

        // Admin users can delete any videos
        if ($user->roles()->where('role', 'admin')->exists() || (string) $user->role === 'admin') {
            return true;
        }

        return false;
    }
}
