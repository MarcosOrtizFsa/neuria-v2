<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAutomationTaskRequest;
use App\Http\Requests\UpdateAutomationTaskRequest;
use App\Models\AutomationTask;
use App\Models\Organization;
use App\Services\AuditService;
use App\Services\Automation\AutomationWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AutomationTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AutomationTask::class);
        $filters = $request->validate(['status' => ['nullable', 'string', 'max:40'], 'q' => ['nullable', 'string', 'max:100']]);
        $tasks = AutomationTask::query()->with(['organization:id,name', 'quotes' => fn ($query) => $query->latest()->limit(1), 'runs' => fn ($query) => $query->latest()->limit(1)])
            ->when($request->user()->role?->code === 'CLIENTE', fn ($query) => $query->where('organization_id', $request->user()->organization_id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.automations.index', compact('tasks', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        Gate::authorize('create', AutomationTask::class);
        $organizations = $this->organizations($request);

        return view('admin.automations.create', compact('organizations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAutomationTaskRequest $request, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $organizationId = $this->organizationId($request);
        try {
            $task = $workflow->create($request->validated(), $request->user(), $organizationId);
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['rate' => $exception->getMessage()]);
        }
        $audit->record($request, 'CREAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $task);

        return redirect()->route('admin.piloto.show', $task)->with('success', 'Tarea preparada. Revisá la cotización antes de activarla.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, AutomationTask $automationTask): View
    {
        $this->ensureVisible($request, $automationTask);
        Gate::authorize('view', $automationTask);
        $automationTask->load(['organization', 'creator', 'versions' => fn ($query) => $query->latest('version'), 'quotes.rate', 'runs' => fn ($query) => $query->with(['result.sources', 'usageAttempts', 'ledgerEntries'])->latest()->limit(30)]);
        $currentVersion = $automationTask->versions->firstWhere('version', $automationTask->current_version);
        $currentQuote = $automationTask->quotes->where('automation_task_version_id', $currentVersion?->id)->sortByDesc('id')->first();

        return view('admin.automations.show', compact('automationTask', 'currentVersion', 'currentQuote'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, AutomationTask $automationTask): View
    {
        $this->ensureVisible($request, $automationTask);
        Gate::authorize('update', $automationTask);
        $configuration = $automationTask->currentVersion()?->configuration ?? [];

        return view('admin.automations.edit', compact('automationTask', 'configuration'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAutomationTaskRequest $request, AutomationTask $automationTask, AutomationWorkflowService $workflow, AuditService $audit): RedirectResponse
    {
        $this->ensureVisible($request, $automationTask);
        try {
            $workflow->update($automationTask, $request->validated(), $request->user());
        } catch (\DomainException $exception) {
            return back()->withInput()->withErrors(['automation' => $exception->getMessage()]);
        }
        $audit->record($request, 'EDITAR_AUTOMATIZACION', 'PILOTO_AUTOMATICO', $automationTask);

        return redirect()->route('admin.piloto.show', $automationTask)->with('success', 'Nueva versión creada. Debe confirmar nuevamente el precio y la programación.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(): void
    {
        abort(405);
    }

    private function organizationId(Request $request): int
    {
        return $request->user()->role?->code === 'CLIENTE' ? (int) $request->user()->organization_id : (int) $request->integer('organization_id');
    }

    private function organizations(Request $request)
    {
        return Organization::query()->where('is_active', true)->when($request->user()->role?->code === 'CLIENTE', fn ($query) => $query->whereKey($request->user()->organization_id))->orderBy('name')->get(['id', 'name']);
    }

    private function ensureVisible(Request $request, AutomationTask $task): void
    {
        if ($request->user()->role?->code === 'CLIENTE' && $request->user()->organization_id !== $task->organization_id) {
            abort(404);
        }
    }
}
