<?php

namespace App\Console\Commands;

use App\Services\Automation\AutomationDispatchService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('automations:dispatch-due')]
#[Description('Crea y despacha ejecuciones vencidas de Piloto Automático')]
class DispatchDueAutomations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AutomationDispatchService $dispatcher): int
    {
        $result = $dispatcher->dispatchDue();
        $this->info("Creadas: {$result['created']}; omitidas: {$result['omitted']}; recuperadas: {$result['recovered']}.");

        return self::SUCCESS;
    }
}
