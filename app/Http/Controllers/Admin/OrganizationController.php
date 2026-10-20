<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Organization::class);
        $organizations = Organization::query()->withCount('users')->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.organizations.index', compact('organizations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Organization::class);

        return view('admin.organizations.form', ['organization' => new Organization]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrganizationRequest $request, AuditService $audit): RedirectResponse
    {
        $organization = Organization::create($request->validated());
        $audit->record($request, 'CREAR', 'ORGANIZACIONES', $organization);

        return redirect()->route('admin.organizaciones.index')->with('success', 'Organización creada.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Organization $organization): View
    {
        Gate::authorize('view', $organization);

        return view('admin.organizations.show', ['organization' => $organization->load('users.role')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('admin.organizations.form', compact('organization'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $organization);
        $organization->update($request->validated());
        $audit->record($request, 'MODIFICAR', 'ORGANIZACIONES', $organization);

        return redirect()->route('admin.organizaciones.index')->with('success', 'Organización actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Organization $organization, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $organization);
        $organization->update(['is_active' => false]);
        $audit->record($request, 'DESACTIVAR', 'ORGANIZACIONES', $organization);

        return back()->with('success', 'Organización desactivada.');
    }
}
