<?php

namespace App\Services;

use App\Models\GeneratedDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContentAvailabilityService
{
    /** @return list<string> */
    public function validationErrors(GeneratedDraft $draft): array
    {
        $draft->loadMissing(['destination.mediaOutlet', 'destination.mediaChannel', 'research.newsRequest', 'research.sources', 'editorialProfileVersion']);
        $errors = [];

        if (! $draft->destination || $draft->destination->media_outlet_id !== $draft->destination->mediaChannel?->media_outlet_id) {
            $errors[] = 'La asociación entre medio y canal no es válida.';
        }
        if (! $draft->research || $draft->research->news_request_id !== $draft->destination?->news_request_id || $draft->research->status !== 'COMPLETADA') {
            $errors[] = 'La investigación de origen no existe o no está completa.';
        }
        if (! $draft->editorial_profile_version_id || ! $draft->editorialProfileVersion) {
            $errors[] = 'No se conservó la versión editorial utilizada.';
        }
        if (blank($draft->title) || blank($draft->body) || blank($draft->summary)) {
            $errors[] = 'Faltan campos obligatorios del contenido.';
        }
        if ($draft->research?->newsRequest?->web_search_enabled && $draft->research->sources->isEmpty()) {
            $errors[] = 'La investigación web no contiene fuentes registradas.';
        }
        if (! is_array($draft->tags) || ! is_array($draft->source_references) || ! is_array($draft->editorial_warnings)) {
            $errors[] = 'La estructura de metadatos no es válida.';
        }

        return $errors;
    }

    public function validateAndMakeAvailable(GeneratedDraft $draft): bool
    {
        return DB::transaction(function () use ($draft): bool {
            $draft = GeneratedDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if (in_array($draft->status, ['SUSPENDIDO', 'RETIRADO'], true)) {
                return false;
            }

            $errors = $this->validationErrors($draft);
            if ($errors !== []) {
                $this->transition($draft, 'ERROR', null, implode(' ', $errors));
                $draft->update(['technical_error' => implode("\n", $errors)]);

                return false;
            }

            $draft->update(['generated_at' => $draft->generated_at ?? now(), 'available_at' => now(), 'suspended_at' => null, 'technical_error' => null]);
            $this->transition($draft, 'DISPONIBLE', null, 'Validación técnica completada automáticamente.');

            return true;
        });
    }

    public function suspend(GeneratedDraft $draft, User $user, ?string $reason): void
    {
        $this->changeAdministrativeStatus($draft, $user, 'SUSPENDIDO', ['DISPONIBLE'], $reason, ['suspended_at' => now()]);
    }

    public function reactivate(GeneratedDraft $draft, User $user, ?string $reason): void
    {
        if ($this->validationErrors($draft) !== []) {
            throw new \DomainException('El contenido ya no supera la validación técnica.');
        }
        $this->changeAdministrativeStatus($draft, $user, 'DISPONIBLE', ['SUSPENDIDO'], $reason, ['available_at' => now(), 'suspended_at' => null]);
    }

    public function retire(GeneratedDraft $draft, User $user, ?string $reason): void
    {
        $this->changeAdministrativeStatus($draft, $user, 'RETIRADO', ['DISPONIBLE', 'SUSPENDIDO'], $reason, ['retired_at' => now()]);
    }

    private function changeAdministrativeStatus(GeneratedDraft $draft, User $user, string $to, array $allowedFrom, ?string $reason, array $attributes): void
    {
        DB::transaction(function () use ($draft, $user, $to, $allowedFrom, $reason, $attributes): void {
            $draft = GeneratedDraft::query()->lockForUpdate()->findOrFail($draft->id);
            if (! in_array($draft->status, $allowedFrom, true)) {
                throw new \DomainException("No se puede cambiar el contenido de {$draft->status} a {$to}.");
            }
            $draft->update($attributes);
            $this->transition($draft, $to, $user, $reason);
        });
    }

    private function transition(GeneratedDraft $draft, string $to, ?User $user, ?string $reason): void
    {
        $from = $draft->status;
        if ($from === $to) {
            return;
        }
        $draft->update(['status' => $to]);
        $draft->statusHistory()->create(['user_id' => $user?->id, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason]);
    }
}
