<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationTask;
use App\Services\AuditService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AutomationArchiveController extends Controller
{
    public function store(Request $request, AutomationTask $automationTask, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        if ($request->user()->role?->code === 'CLIENTE' && $request->user()->organization_id !== $automationTask->organization_id) {
            abort(404);
        }
        Gate::authorize('archive', $automationTask);
        $workflow->archive($automationTask);
        $audit->record($request, 'ARCHIVAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $automationTask);

        return redirect()->route('admin.piloto.index')->with('success', 'Tarea archivada. Se conservan resultados, consumos y movimientos económicos.');
    }
}
