<?php

namespace App\Services\Automation;

use App\Contracts\AutomationHandler;

class AutomationHandlerRegistry
{
    /** @param iterable<AutomationHandler> $handlers */
    public function __construct(private readonly iterable $handlers) {}

    public function for(string $type): AutomationHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->type() === $type) {
                return $handler;
            }
        }

        throw new \DomainException("No existe un manejador habilitado para {$type}.");
    }
}
