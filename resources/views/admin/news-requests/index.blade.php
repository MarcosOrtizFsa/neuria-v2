<x-layouts.app title="Agencia de Noticias">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h3 mb-1">Solicitudes periodísticas</h1><p class="text-secondary mb-0">Investigación central y borradores personalizados por destino.</p></div>
        @can('create', App\Models\NewsRequest::class)<a class="btn btn-primary" href="{{ route('admin.agencia.create') }}">Nueva solicitud</a>@endcan
    </div>
    <form class="card card-body mb-4" method="GET">
        <div class="row g-3 align-items-end">
            <div class="col-md-6"><label class="form-label" for="q">Buscar</label><input class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tema o descripción"></div>
            <div class="col-md-4"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option>@foreach(['PENDIENTE','INVESTIGANDO','INVESTIGACION_COMPLETA','GENERANDO','COMPLETADA','PARCIALMENTE_COMPLETADA','ERROR'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_',' ',$status) }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
        </div>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>ID</th><th>Tema</th><th>Solicitante</th><th>Estado</th><th>Destinos</th><th>Borradores</th><th>Progreso</th><th>Actualizada</th><th></th></tr></thead><tbody>
        @forelse($requests as $item)<tr><td>#{{ $item->id }}</td><td><strong>{{ $item->title }}</strong></td><td>{{ $item->requester->name }} {{ $item->requester->last_name }}</td><td><span class="badge text-bg-{{ $item->status === 'COMPLETADA' ? 'success' : ($item->status === 'ERROR' ? 'danger' : 'secondary') }}">{{ str_replace('_',' ',$item->status) }}</span></td><td>{{ $item->destinations_count }}</td><td>{{ $item->drafts_count }}</td><td style="min-width:140px"><div class="progress" role="progressbar" aria-label="Progreso" aria-valuenow="{{ $item->progress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $item->progress }}%">{{ $item->progress }}%</div></div></td><td>{{ $item->updated_at->format('d/m/Y H:i') }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.agencia.show',$item) }}">Ver</a></td></tr>
        @empty<tr><td colspan="9" class="text-center text-secondary py-5">Todavía no hay solicitudes.</td></tr>@endforelse
    </tbody></table></div></div>
    <div class="mt-3">{{ $requests->links() }}</div>
</x-layouts.app>
