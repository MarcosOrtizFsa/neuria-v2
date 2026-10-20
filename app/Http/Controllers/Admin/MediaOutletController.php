<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaOutletRequest;
use App\Http\Requests\UpdateMediaOutletRequest;
use App\Models\MediaOutlet;
use App\Models\Organization;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MediaOutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', MediaOutlet::class);
        $sort = in_array($request->string('sort')->toString(), ['name', 'type', 'status', 'created_at'], true) ? $request->string('sort')->toString() : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $query = MediaOutlet::query()->with(['organization:id,name'])->withCount('channels');
        if ($request->user()->role?->code === 'CLIENTE') {
            $query->where('organization_id', $request->user()->organization_id);
        }
        $mediaOutlets = $query->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))->when($request->integer('organization_id'), fn ($q, $id) => $q->where('organization_id', $id))->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))->orderBy($sort, $direction)->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.media.index', ['mediaOutlets' => $mediaOutlets, 'organizations' => Organization::orderBy('name')->get(['id', 'name'])]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', MediaOutlet::class);

        return view('admin.media.form', ['mediaOutlet' => new MediaOutlet, 'organizations' => Organization::where('is_active', true)->orderBy('name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMediaOutletRequest $request, AuditService $audit): RedirectResponse
    {
        $mediaOutlet = MediaOutlet::create($request->validated());
        $audit->record($request, 'CREAR', 'MEDIOS', $mediaOutlet);

        return redirect()->route('admin.medios.show', $mediaOutlet)->with('success', 'Medio creado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('view', $mediaOutlet);
        $mediaOutlet->load(['organization', 'channels', 'editorialProfile.versions.user']);
        if ($request->user()->can('manageCredentials', $mediaOutlet)) {
            $mediaOutlet->load('apiCredentials');
        }

        return view('admin.media.show', compact('mediaOutlet'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MediaOutlet $mediaOutlet): View
    {
        Gate::authorize('update', $mediaOutlet);

        return view('admin.media.form', ['mediaOutlet' => $mediaOutlet, 'organizations' => Organization::where('is_active', true)->orderBy('name')->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMediaOutletRequest $request, MediaOutlet $mediaOutlet, AuditService $audit): RedirectResponse
    {
        $mediaOutlet->update($request->validated());
        $audit->record($request, 'MODIFICAR', 'MEDIOS', $mediaOutlet);

        return redirect()->route('admin.medios.show', $mediaOutlet)->with('success', 'Medio actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, MediaOutlet $mediaOutlet, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $mediaOutlet);
        $mediaOutlet->update(['status' => 'INACTIVO']);
        $audit->record($request, 'DESACTIVAR', 'MEDIOS', $mediaOutlet);

        return back()->with('success', 'Medio desactivado.');
    }
}
