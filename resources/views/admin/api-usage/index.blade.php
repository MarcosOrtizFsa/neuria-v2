<x-layouts.app title="API y Consumos">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">API y Consumos</h1><p class="text-secondary mb-0">Actividad de distribución sin exposición de tokens.</p></div><span class="badge text-bg-success">{{ $activeCredentials }} credenciales activas</span></div>
    <form class="card card-body mb-4" method="GET"><div class="row g-3">
        <div class="col-md-3"><label class="form-label">Medio</label><select name="media_outlet_id" class="form-select"><option value="">Todos</option>@foreach($media as $item)<option value="{{ $item->id }}" @selected(($filters['media_outlet_id'] ?? null)==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Endpoint</label><input class="form-control" name="endpoint" value="{{ $filters['endpoint'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label">Estado HTTP</label><input class="form-control" type="number" name="status_code" value="{{ $filters['status_code'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label">Desde</label><input class="form-control" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="col-md-2"><label class="form-label">Hasta</label><input class="form-control" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
        <div class="col-12"><button class="btn btn-primary">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('admin.api-consumos.index') }}">Limpiar</a></div>
    </div></form>
    <div class="row g-3 mb-4">@foreach(['Solicitudes'=>$stats['requests'],'Registros entregados'=>$stats['records'],'Errores'=>$stats['errors'],'Promedio (ms)'=>$stats['average_ms']] as $label=>$value)<div class="col-md-3"><div class="card card-body"><small class="text-secondary">{{ $label }}</small><strong class="fs-3">{{ number_format($value, 0, ',', '.') }}</strong></div></div>@endforeach</div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Fecha</th><th>Medio / credencial</th><th>Endpoint</th><th>HTTP</th><th>Registros</th><th>Duración</th></tr></thead><tbody>
    @forelse($logs as $log)<tr><td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $log->mediaOutlet?->name ?? 'No identificada' }}<br><small>{{ $log->credential?->name }} {{ $log->credential?->token_prefix }}</small></td><td><code>{{ $log->endpoint }}</code></td><td><span class="badge {{ $log->status_code < 400 ? 'text-bg-success' : 'text-bg-danger' }}">{{ $log->status_code }}</span></td><td>{{ $log->records_returned }}</td><td>{{ $log->duration_ms }} ms</td></tr>@empty<tr><td colspan="6" class="text-center py-4 text-secondary">No hay consumos para los filtros seleccionados.</td></tr>@endforelse
    </tbody></table></div>@if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif</div>
</x-layouts.app>
