<x-layouts.app title="Solicitudes y consumos">
    @php
        $month = $report['month'];
        $stats = $report['stats'];
        $commercial = $report['commercial'];
        $money = fn (int $minor): string => $commercial['currency'].' '.number_format($minor / 100, 2, ',', '.');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><span class="client-eyebrow">Consumo globalizado</span><h1 class="h3 mb-1">Solicitudes y consumos</h1><p class="text-secondary mb-0">Cada fila corresponde a una producción, independientemente de cuántas veces fue consultada por API.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('admin.api-consumos.index') }}">Ver actividad técnica API</a>
    </div>

    <form class="card card-body mb-4" method="GET"><div class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label" for="month">Período</label><input class="form-control" id="month" name="month" type="month" value="{{ $month->format('Y-m') }}" max="{{ now('America/Argentina/Buenos_Aires')->format('Y-m') }}"></div>
        <div class="col-md-4"><label class="form-label" for="q">Referencia o tema</label><input class="form-control" id="q" name="q" value="{{ $report['filters']['q'] ?? '' }}" placeholder="Ejemplo: NR-A1B2C3D4E5F6"></div>
        <div class="col-md-3"><label class="form-label" for="status">Estado</label><select class="form-select" id="status" name="status"><option value="">Todos</option>@foreach(['PENDIENTE','INVESTIGANDO','INVESTIGACION_COMPLETA','GENERANDO','COMPLETADA','PARCIALMENTE_COMPLETADA','ERROR','CANCELADA'] as $status)<option value="{{ $status }}" @selected(($report['filters']['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filtrar</button><a class="btn btn-outline-secondary" href="{{ route('admin.solicitudes-consumos.index') }}">Limpiar</a></div>
    </div></form>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Solicitudes</span><strong class="client-metric-value">{{ number_format($stats['requests'], 0, ',', '.') }}</strong><small class="text-secondary">producciones del período</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Tokens técnicos</span><strong class="client-metric-value">{{ number_format($stats['tokens'], 0, ',', '.') }}</strong><small class="text-secondary">investigación y redacción</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Consultas API</span><strong class="client-metric-value">{{ number_format($stats['api_calls'], 0, ',', '.') }}</strong><small class="text-secondary">actividad técnica relacionada</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Contenidos obtenidos</span><strong class="client-metric-value">{{ number_format($stats['contents'], 0, ',', '.') }}</strong><small class="text-secondary">resultados generados</small></div></div></div>
    </div>

    <div class="card mb-4"><div class="card-body"><div class="row g-3 align-items-center">
        <div class="col-lg-4"><h2 class="h5 mb-1">Contratado y consumido</h2><p class="text-secondary small mb-0">Los tokens son una métrica técnica; la liquidación comercial se registra por ejecuciones válidas.</p></div>
        <div class="col-6 col-lg-2"><span class="d-block small text-secondary">Plan mensual</span><strong>{{ $commercial['monthly_limit_minor'] !== null ? $money($commercial['monthly_limit_minor']) : 'No configurado' }}</strong></div>
        <div class="col-6 col-lg-2"><span class="d-block small text-secondary">Liquidado</span><strong>{{ $money($commercial['settled_minor']) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="d-block small text-secondary">Reservado</span><strong>{{ $money($commercial['reserved_minor']) }}</strong></div>
        <div class="col-6 col-lg-2"><span class="d-block small text-secondary">Disponible</span><strong>{{ $commercial['available_minor'] !== null ? $money($commercial['available_minor']) : '—' }}</strong></div>
    </div></div></div>

    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Referencia</th><th>Fecha / tema</th><th>Estado</th><th>Tokens de la solicitud</th><th>Consultas API</th><th>Contenidos</th><th></th></tr></thead><tbody>
        @forelse($report['requests'] as $item)
            <tr><td><code class="fw-semibold">{{ $item->reference_code }}</code></td><td><small class="text-secondary">{{ $item->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}</small><br><strong>{{ $item->title }}</strong></td><td><span class="badge text-bg-{{ $item->status === 'COMPLETADA' ? 'success' : ($item->status === 'ERROR' ? 'danger' : 'secondary') }}">{{ str_replace('_', ' ', $item->status) }}</span><div class="small text-secondary mt-1">{{ $item->progress }}%</div></td><td><strong>{{ number_format((int) $item->total_tokens, 0, ',', '.') }}</strong><br><small class="text-secondary">E {{ number_format((int) $item->input_tokens, 0, ',', '.') }} · S {{ number_format((int) $item->output_tokens, 0, ',', '.') }}</small></td><td>{{ number_format($item->api_calls_count, 0, ',', '.') }}</td><td>{{ number_format($item->contents_count, 0, ',', '.') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.solicitudes-consumos.show', $item->reference_code) }}">Revisar</a></td></tr>
        @empty
            <tr><td colspan="7" class="text-center text-secondary py-5">No existen solicitudes para el período y filtros elegidos.</td></tr>
        @endforelse
    </tbody></table></div>@if($report['requests']->hasPages())<div class="card-footer">{{ $report['requests']->links() }}</div>@endif</div>
</x-layouts.app>
