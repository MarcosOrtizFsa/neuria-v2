<?php

namespace App\Policies;

use App\Models\GeneratedDraft;
use App\Models\User;

class GeneratedDraftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('CONTENIDOS.VER') || ($user->role?->code === 'CLIENTE' && $user->organization_id !== null);
    }

    public function view(User $user, GeneratedDraft $draft): bool
    {
        return $this->owns($user, $draft) || ($user->role?->code !== 'CLIENTE' && $user->hasPermission('CONTENIDOS.VER'));
    }

    public function suspend(User $user, GeneratedDraft $draft): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('CONTENIDOS.MODIFICAR') && $draft->status === 'DISPONIBLE';
    }

    public function reactivate(User $user, GeneratedDraft $draft): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('CONTENIDOS.MODIFICAR') && $draft->status === 'SUSPENDIDO';
    }

    public function retire(User $user, GeneratedDraft $draft): bool
    {
        return $user->role?->code !== 'CLIENTE' && $user->hasPermission('CONTENIDOS.BAJA') && in_array($draft->status, ['DISPONIBLE', 'SUSPENDIDO'], true);
    }

    private function owns(User $user, GeneratedDraft $draft): bool
    {
        return $user->organization_id !== null && $user->organization_id === $draft->destination?->mediaOutlet?->organization_id;
    }
}
