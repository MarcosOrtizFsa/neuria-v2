<?php

namespace App\Policies;

use App\Models\MediaOutlet;
use App\Models\User;

class MediaOutletPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('MEDIOS.VER') || ($user->role?->code === 'CLIENTE' && $user->organization_id !== null);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $this->owns($user, $mediaOutlet) || $user->hasPermission('MEDIOS.VER');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('MEDIOS.ALTA');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('MEDIOS.MODIFICAR');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('MEDIOS.BAJA');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MediaOutlet $mediaOutlet): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MediaOutlet $mediaOutlet): bool
    {
        return false;
    }

    public function manageChannels(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $user->hasPermission('MEDIOS.GESTIONAR_CANALES') && ($user->role?->code !== 'CLIENTE' || $this->owns($user, $mediaOutlet));
    }

    public function manageCredentials(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('MEDIOS.GESTIONAR_CREDENCIALES');
    }

    public function editEditorial(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $this->owns($user, $mediaOutlet) || $user->hasPermission('LINEA_EDITORIAL.MODIFICAR');
    }

    private function owns(User $user, MediaOutlet $mediaOutlet): bool
    {
        return $user->organization_id !== null && $user->organization_id === $mediaOutlet->organization_id;
    }
}
