<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('USUARIOS.VER');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || ($user->hasPermission('USUARIOS.VER') && $this->canManageRole($user, $model));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('USUARIOS.ALTA');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->hasPermission('USUARIOS.MODIFICAR') && $this->canManageRole($user, $model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->hasPermission('USUARIOS.BAJA') && $user->id !== $model->id && $this->canManageRole($user, $model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    private function canManageRole(User $actor, User $target): bool
    {
        return $actor->isRoot() || ($actor->role?->level() ?? 0) > ($target->role?->level() ?? 0);
    }
}
