<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    /**
     * Determine if the user can view the profile.
     */
    public function view(User $user, Profile $profile): bool
    {
        // User can view their own profile or admin can view any profile
        return $user->id === $profile->user_id || $user->role === 'admin';
    }

    /**
     * Determine if the user can update the profile.
     */
    public function update(User $user, Profile $profile): bool
    {
        // User can update their own profile or admin can update any profile
        return $user->id === $profile->user_id || $user->role === 'admin';
    }

    /**
     * Determine if the user can delete the profile.
     */
    public function delete(User $user, Profile $profile): bool
    {
        // User can delete their own profile or admin can delete any profile
        return $user->id === $profile->user_id || $user->role === 'admin';
    }
}
