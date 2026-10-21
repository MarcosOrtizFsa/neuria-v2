<?php

namespace App\Policies;

use App\Models\AutomationTask;
use App\Models\User;

class AutomationTaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return ($user->role?->code === 'CLIENTE' && $user->organization_id !== null) || $user->hasPermission('PILOTO_AUTOMATICO.VER');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AutomationTask $automationTask): bool
    {
        return $this->owns($user, $automationTask) || ($user->role?->code !== 'CLIENTE' && $user->hasPermission('PILOTO_AUTOMATICO.VER'));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return ($user->role?->code === 'CLIENTE' && $user->organization_id !== null) || $user->hasPermission('PILOTO_AUTOMATICO.ALTA');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AutomationTask $automationTask): bool
    {
        return $this->view($user, $automationTask) && $automationTask->status !== 'ARCHIVADA';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AutomationTask $automationTask): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AutomationTask $automationTask): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AutomationTask $automationTask): bool
    {
        return false;
    }

    public function activate(User $user, AutomationTask $automationTask): bool
    {
        return $this->view($user, $automationTask) && in_array($automationTask->status, ['PENDIENTE_CONFIRMACION', 'PAUSADA'], true);
    }

    public function pause(User $user, AutomationTask $automationTask): bool
    {
        return $this->view($user, $automationTask) && $automationTask->status === 'ACTIVA';
    }

    public function archive(User $user, AutomationTask $automationTask): bool
    {
        return $this->view($user, $automationTask) && $automationTask->status !== 'ARCHIVADA';
    }

    public function runNow(User $user, AutomationTask $automationTask): bool
    {
        return $this->view($user, $automationTask) && in_array($automationTask->status, ['ACTIVA', 'PAUSADA'], true);
    }

    private function owns(User $user, AutomationTask $automationTask): bool
    {
        return $user->organization_id !== null && $user->organization_id === $automationTask->organization_id;
    }
}
