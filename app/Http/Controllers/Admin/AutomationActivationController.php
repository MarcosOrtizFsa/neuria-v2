<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAutomationTaskRequest;
use App\Models\AutomationQuote;
use App\Models\AutomationTask;
use App\Services\AuditService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;

class AutomationActivationController extends Controller
{
    public function store(ActivateAutomationTaskRequest $request, AutomationTask $automationTask, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $quote = AutomationQuote::query()->where('automation_task_id', $automationTask->id)->findOrFail($request->integer('quote_id'));
        try {
            $workflow->activate($automationTask, $quote);
        } catch (\DomainException $exception) {
            return back()->withErrors(['activation' => $exception->getMessage()]);
        }
        $audit->record($request, 'ACTIVAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $automationTask, ['quote_id' => $quote->id]);

        return back()->with('success', 'Piloto Automático activado. El servidor ejecutará la tarea en el próximo vencimiento.');
    }
}
