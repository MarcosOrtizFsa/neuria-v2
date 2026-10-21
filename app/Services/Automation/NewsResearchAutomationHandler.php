<?php

namespace App\Services\Automation;

use App\Contracts\AutomationHandler;
use App\Contracts\AutomationProvider;
use App\Models\AutomationRun;

class NewsResearchAutomationHandler implements AutomationHandler
{
    public function __construct(private readonly AutomationProvider $provider) {}

    public function type(): string
    {
        return 'NEWS_RESEARCH_AND_WRITE';
    }

    public function execute(AutomationRun $run): array
    {
        return $this->provider->generate($run->version->configuration, $run->window_from, $run->window_to);
    }
}
