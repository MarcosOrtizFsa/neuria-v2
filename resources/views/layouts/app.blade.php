<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? 'NEURIA' }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="admin-shell d-lg-flex">
    <aside class="sidebar p-3 text-white">
        <a href="{{ route('panel') }}" class="h3 text-white text-decoration-none">NEURIA</a>
        <nav class="nav flex-column gap-1 mt-4">
            <a class="nav-link" href="{{ route('panel') }}">Inicio</a>
            @if(auth()->user()->hasPermission('USUARIOS.VER'))
                <a class="nav-link" href="{{ route('admin.usuarios.index') }}">Usuarios</a>
                <a class="nav-link" href="{{ route('admin.organizaciones.index') }}">Organizaciones</a>
            @endif
            @can('viewAny', App\Models\MediaOutlet::class)
                <a class="nav-link" href="{{ route('admin.medios.index') }}">{{ auth()->user()->role?->code === 'CLIENTE' ? 'Mis medios' : 'Medios' }}</a>
            @endcan
            @can('viewAny', App\Models\NewsRequest::class)
                <a class="nav-link" href="{{ route('admin.agencia.index') }}">Agencia de Noticias</a>
            @endcan
            @can('viewAny', App\Models\GeneratedDraft::class)
                <a class="nav-link" href="{{ route('admin.contenidos.index') }}">Contenidos</a>
            @endcan
            @can('viewAny', App\Models\AutomationTask::class)
                <a class="nav-link" href="{{ route('admin.piloto.index') }}">Piloto Automático</a>
            @endcan
            @if(auth()->user()->role?->code === 'CLIENTE' || auth()->user()->hasPermission('API_CONSUMOS.VER'))
                <a class="nav-link" href="{{ route('admin.api-consumos.index') }}">API y Consumos</a>
            @endif
            @foreach(['Portal público', 'Configuración'] as $future)
                <span class="nav-link opacity-50">{{ $future }} <small>(próximamente)</small></span>
            @endforeach
        </nav>
    </aside>
    <main class="flex-grow-1">
        <header class="bg-white border-bottom px-4 py-3 d-flex justify-content-between">
            <strong>{{ $title ?? 'Panel' }}</strong>
            <div>{{ auth()->user()->name }} · {{ auth()->user()->role?->name }} <a href="{{ route('profile.edit') }}" class="ms-3">Perfil</a><form class="d-inline ms-2" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-outline-danger">Salir</button></form></div>
        </header>
        <div class="container-fluid p-4">
            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="alert alert-danger"><strong>Revisá los datos:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
