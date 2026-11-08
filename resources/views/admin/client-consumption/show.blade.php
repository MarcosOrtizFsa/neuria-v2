<x-layouts.app title="Detalle de solicitud">
    @php
        $totalTokens = (int) $newsRequest->usages->sum('total_tokens');
        $inputTokens = (int) $newsRequest->usages->sum('input_tokens');
        $outputTokens = (int) $newsRequest->usages->sum('output_tokens');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a class="text-decoration-none small" href="{{ route('admin.solicitudes-consumos.index') }}">&larr; Volver a solicitudes</a>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <h1 class="h3 mb-0">{{ $newsRequest->title }}</h1>
                <span class="badge text-bg-{{ $newsRequest->status === 'COMPLETADA' ? 'success' : ($newsRequest->status === 'ERROR' ? 'danger' : 'secondary') }}">{{ str_replace('_', ' ', $newsRequest->status) }}</span>
            </div>
            <p class="text-secondary mb-0">Referencia para soporte: <code class="fw-semibold">{{ $newsRequest->reference_code }}</code></p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Tokens de la solicitud</span><strong class="client-metric-value">{{ number_format($totalTokens, 0, ',', '.') }}</strong><small class="text-secondary">consumo total de IA</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Tokens de entrada</span><strong class="client-metric-value">{{ number_format($inputTokens, 0, ',', '.') }}</strong><small class="text-secondary">instrucciones y contexto</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Tokens de salida</span><strong class="client-metric-value">{{ number_format($outputTokens, 0, ',', '.') }}</strong><small class="text-secondary">investigación y redacción</small></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Consultas API</span><strong class="client-metric-value">{{ number_format($newsRequest->apiUsageLogs->count(), 0, ',', '.') }}</strong><small class="text-secondary">interacciones técnicas</small></div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Solicitud</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-md-3">Fecha</dt><dd class="col-md-9">{{ $newsRequest->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}</dd>
                <dt class="col-md-3">Progreso</dt><dd class="col-md-9">{{ $newsRequest->progress }}%</dd>
                <dt class="col-md-3">Descripción</dt><dd class="col-md-9">{{ $newsRequest->description ?: 'Sin descripción adicional.' }}</dd>
                @if($newsRequest->specific_instructions)<dt class="col-md-3">Instrucciones</dt><dd class="col-md-9">{{ $newsRequest->specific_instructions }}</dd>@endif
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Contenidos generados</div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Medio / canal</th><th>Contenido</th><th>Estado</th><th></th></tr></thead><tbody>
            @forelse($newsRequest->destinations as $destination)
                <tr>
                    <td>{{ $destination->mediaOutlet->name }}<br><small class="text-secondary">{{ $destination->mediaChannel->name }} · {{ $destination->mediaChannel->type }}</small></td>
                    <td>{{ $destination->draft?->title ?? 'Aún no disponible' }}</td>
                    <td><span class="badge text-bg-secondary">{{ $destination->status }}</span></td>
                    <td class="text-end">@if($destination->draft)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.contenidos.show', $destination->draft) }}">Ver contenido</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-secondary py-4">No hay destinos visibles.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    <div class="card mb-4">
        <div class="card-header">Consumo de inteligencia artificial</div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha</th><th>Operación</th><th>Modelo</th><th>Entrada</th><th>Salida</th><th>Total</th></tr></thead><tbody>
            @forelse($newsRequest->usages as $usage)
                <tr><td>{{ $usage->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s') }}</td><td>{{ str_replace('_', ' ', $usage->operation) }}</td><td><code>{{ $usage->model }}</code></td><td>{{ number_format((int) $usage->input_tokens, 0, ',', '.') }}</td><td>{{ number_format((int) $usage->output_tokens, 0, ',', '.') }}</td><td><strong>{{ number_format((int) $usage->total_tokens, 0, ',', '.') }}</strong></td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">Aún no se registró consumo de IA.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>

    <div class="card">
        <div class="card-header">Actividad técnica API</div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha</th><th>Endpoint</th><th>HTTP</th><th>Credencial</th><th>Registros</th><th>Duración</th></tr></thead><tbody>
            @forelse($newsRequest->apiUsageLogs as $log)
                <tr><td>{{ $log->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s') }}</td><td><code>{{ $log->endpoint }}</code></td><td><span class="badge text-bg-{{ $log->status_code < 400 ? 'success' : 'danger' }}">{{ $log->status_code }}</span></td><td>{{ $log->credential?->name ?? '—' }}</td><td>{{ $log->records_returned }}</td><td>{{ $log->duration_ms }} ms</td></tr>
            @empty
                <tr><td colspan="6" class="text-center text-secondary py-4">No hay actividad API vinculada.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</x-layouts.app>
