<?php

namespace App\Jobs;

use App\Services\Automation\AutomationExecutionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExecuteAutomationRun implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $runId) {}

    /**
     * Execute the job.
     */
    public function handle(AutomationExecutionService $execution): void
    {
        $execution->execute($this->runId);
    }
}
