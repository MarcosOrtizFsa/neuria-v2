<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $users = User::query()->with(['role:id,name,code', 'organization:id,name'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nested) => $nested->where('name', 'like', '%'.$request->string('search').'%')->orWhere('last_name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->when($request->integer('role_id'), fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->orderBy('last_name')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderByDesc('id')->get(['id', 'name'])]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.form', ['user' => new User, 'roles' => $this->assignableRoles(request()->user()), 'organizations' => Organization::where('is_active', true)->orderBy('name')->get()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, AuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureRoleCanBeAssigned($request->user(), (int) $data['role_id']);
        $user = User::create($data);
        $audit->record($request, 'CREAR', 'USUARIOS', $user);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('admin.users.show', ['user' => $user->load(['role', 'organization', 'permissions.module'])]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.form', ['user' => $user, 'roles' => $this->assignableRoles(request()->user(), $user), 'organizations' => Organization::where('is_active', true)->orderBy('name')->get()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $user);
        $data = $request->validated();
        $this->ensureRoleCanBeAssigned($request->user(), (int) $data['role_id']);
        if (empty($data['password'])) {
            $data = Arr::except($data, ['password']);
        }
        if ($user->isRoot() && ! $data['is_active'] && User::where('role_id', $user->role_id)->where('is_active', true)->count() === 1) {
            throw ValidationException::withMessages(['is_active' => 'No se puede desactivar el último ROOT activo.']);
        }
        $user->update($data);
        $audit->record($request, 'MODIFICAR', 'USUARIOS', $user);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user, AuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $user);
        if ($user->isRoot() && User::where('role_id', $user->role_id)->where('is_active', true)->count() === 1) {
            throw ValidationException::withMessages(['user' => 'No se puede desactivar el último ROOT activo.']);
        }
        $user->update(['is_active' => false]);
        $audit->record($request, 'DESACTIVAR', 'USUARIOS', $user);

        return back()->with('success', 'Usuario desactivado.');
    }

    private function assignableRoles(User $actor, ?User $target = null)
    {
        return Role::query()->when(! $actor->isRoot(), fn ($query) => $query->whereNotIn('code', ['ROOT'])->whereRaw("CASE code WHEN 'GERENTE' THEN 40 WHEN 'ADMINISTRADOR' THEN 30 WHEN 'OPERADOR' THEN 20 WHEN 'CLIENTE' THEN 10 ELSE 0 END < ?", [$actor->role?->level() ?? 0]))->orderByDesc('id')->get();
    }

    private function ensureRoleCanBeAssigned(User $actor, int $roleId): void
    {
        $role = Role::findOrFail($roleId);
        abort_unless($actor->isRoot() || $role->level() < ($actor->role?->level() ?? 0), 403);
    }
}
