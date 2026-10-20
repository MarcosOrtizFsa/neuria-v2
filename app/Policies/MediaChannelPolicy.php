<?php

namespace App\Policies;

use App\Models\MediaChannel;
use App\Models\User;

class MediaChannelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaChannel $mediaChannel): bool
    {
        return $user->can('view', $mediaChannel->mediaOutlet);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaChannel $mediaChannel): bool
    {
        return $user->can('manageChannels', $mediaChannel->mediaOutlet);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaChannel $mediaChannel): bool
    {
        return $user->can('manageChannels', $mediaChannel->mediaOutlet);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaChannel $mediaChannel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaChannel $mediaChannel): bool
    {
        return false;
    }
}
