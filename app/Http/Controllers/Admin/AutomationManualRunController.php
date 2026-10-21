<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RunAutomationNowRequest;
use App\Models\AutomationTask;
use App\Services\AuditService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;

class AutomationManualRunController extends Controller
{
    public function store(RunAutomationNowRequest $request, AutomationTask $automationTask, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        try {
            $run = $workflow->runNow($automationTask, $request->validated('request_id'));
        } catch (\DomainException $exception) {
            return back()->withErrors(['run' => $exception->getMessage()]);
        }
        $audit->record($request, 'EJECUTAR_AUTOMATIZACION_AHORA', 'PILOTO_AUTOMATICO', $automationTask, ['run_id' => $run->id]);

        return back()->with('success', 'Ejecución enviada a la cola. Esta prueba utiliza la tarifa aceptada.');
    }
}
