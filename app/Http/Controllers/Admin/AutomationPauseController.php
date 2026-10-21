<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Services\AuditService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AutomationPauseController extends Controller
{
    public function store(Request $request, AutomationTask $automationTask, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        if ($request->user()->role?->code === 'CLIENTE' && $request->user()->organization_id !== $automationTask->organization_id) {
            abort(404);
        }
        Gate::authorize('pause', $automationTask);
        $workflow->pause($automationTask);
        $audit->record($request, 'PAUSAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $automationTask);

        return back()->with('success', 'Tarea pausada. No se crearán nuevas ejecuciones programadas.');
    }
}
