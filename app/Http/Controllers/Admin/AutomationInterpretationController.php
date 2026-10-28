<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AutomationProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\InterpretAutomationRequest;
use App\Services\AuditService;
use App\Services\Automation\AutomationConfigurationService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;

class AutomationInterpretationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(InterpretAutomationRequest $request, AutomationProvider $provider, AutomationConfigurationService $configuration, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        try {
            $interpretation = $configuration->validateInterpretation($provider->interpret($request->validated('request_text')));
        } catch (\RuntimeException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['request_text' => 'No se pudo interpretar el pedido en este momento. Podés usar el formulario manual.']);
        }
        if ($interpretation['missing_fields'] !== []) {
            return back()->withInput()->withErrors(['request_text' => 'Falta información indispensable: '.implode(', ', $interpretation['missing_fields']).'. No se creó ni activó ninguna tarea.']);
        }
        $data = $configuration->interpretationToForm($interpretation);
        $data['domains_text'] = implode("\n", $data['domains']);
        $data['organization_id'] = $request->validated('organization_id');
        $data['media_channel_id'] = $request->validated('media_channel_id');
        $organizationId = $request->user()->role?->code === 'CLIENTE' ? (int) $request->user()->organization_id : (int) $request->validated('organization_id');
        try {
            $task = $workflow->create($data, $request->user(), $organizationId, $request->validated('request_text'));
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['request_text' => $exception->getMessage()]);
        }
        $audit->record($request, 'INTERPRETAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $task);

        return redirect()->route('admin.piloto.show', $task)->with('success', 'Pedido interpretado. Revisá todos los campos y la cotización; todavía no está activo.');
    }
}
