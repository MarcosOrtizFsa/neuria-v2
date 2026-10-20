<?php

namespace App\Policies;

use App\Models\NewsRequest;
use App\Models\User;

class NewsRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('AGENCIA_NOTICIAS.VER');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NewsRequest $newsRequest): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('AGENCIA_NOTICIAS.ALTA');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NewsRequest $newsRequest): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('AGENCIA_NOTICIAS.MODIFICAR');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NewsRequest $newsRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, NewsRequest $newsRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, NewsRequest $newsRequest): bool
    {
        return false;
    }

    public function retry(User $user, NewsRequest $newsRequest): bool
    {
        return $this->update($user, $newsRequest) && in_array($newsRequest->status, ['ERROR', 'PARCIALMENTE_COMPLETADA'], true);
    }
}
