<?php

namespace Neuria\Connector\Contracts;

interface ContentRepository
{
    public function exists(string $neuriaId): bool;

    /** Guarda contenido nuevo como borrador local. Nunca debe sobrescribir una edición local existente. */
    public function importDraft(array $content): void;

    /** Registra suspensiones, reactivaciones o retiros para que el CMS decida cómo aplicarlos. */
    public function recordChange(array $change): void;
}
