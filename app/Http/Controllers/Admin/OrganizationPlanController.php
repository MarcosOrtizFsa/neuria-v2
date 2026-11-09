<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationPlanRequest;
use App\Models\Organization;
use App\Services\AuditService;
use App\Services\OrganizationPlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationPlanController extends Controller
{
    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('admin.organizations.plan', [
            'organization' => $organization,
            'budget' => $organization->automationBudget,
        ]);
    }

    public function update(
        UpdateOrganizationPlanRequest $request,
        Organization $organization,
        OrganizationPlanService $plans,
        AuditService $audit,
    ): RedirectResponse {
        $budget = $plans->update($organization, [
            'currency' => $request->validated('currency'),
            'monthly_limit_minor' => $request->monthlyLimitMinor(),
            'active_task_limit' => (int) $request->validated('active_task_limit'),
            'daily_run_limit' => (int) $request->validated('daily_run_limit'),
            'concurrency_limit' => (int) $request->validated('concurrency_limit'),
        ]);
        $audit->record($request, 'CONFIGURAR_PLAN', 'ORGANIZACIONES', $organization, [
            'automation_budget_id' => $budget->id,
            'currency' => $budget->currency,
            'monthly_limit_minor' => $budget->monthly_limit_minor,
        ]);

        return redirect()
            ->route('admin.organizaciones.show', $organization)
            ->with('success', 'Plan mensual actualizado.');
    }
}
