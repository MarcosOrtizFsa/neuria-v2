<x-layouts.app title="Centro del cliente">
    @if($dashboard['organization'] === null)
        <div class="alert alert-warning mb-0">Tu usuario todavía no tiene una organización asignada. Contactá al administrador de NEURIA.</div>
    @else
        @php
            $month = $dashboard['month'];
            $monthly = $dashboard['monthly'];
            $commercial = $monthly['commercial'];
            $money = fn (int $minor): string => $commercial['currency'].' '.number_format($minor / 100, 2, ',', '.');
            $runLabels = ['COMPLETADA' => 'Completada', 'SIN_NOVEDADES' => 'Sin novedades', 'ERROR' => 'Error', 'INVALIDA' => 'Inválida', 'BLOQUEADA' => 'Bloqueada', 'ESTADO_DESCONOCIDO' => 'A conciliar', 'PENDIENTE' => 'Pendiente', 'PROCESANDO' => 'Procesando'];
            $movementLabels = ['RESERVA' => 'Reserva', 'LIQUIDACION' => 'Liquidación', 'LIBERACION' => 'Liberación'];
        @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <span class="client-eyebrow">Centro de servicios agénticos</span>
                <h1 class="h2 mb-1">Hola, {{ $user->name }}</h1>
                <p class="text-secondary mb-0">{{ $dashboard['organization']->name }} · Estado de servicios, automatizaciones y consumos.</p>
            </div>
            <form method="GET" action="{{ route('panel') }}" class="card card-body py-2 px-3 client-period-filter">
                <label class="form-label small text-secondary mb-1" for="month">Período</label>
                <div class="input-group">
                    <input class="form-control" id="month" name="month" type="month" value="{{ $month->format('Y-m') }}" max="{{ now('America/Argentina/Buenos_Aires')->format('Y-m') }}">
                    <button class="btn btn-primary">Ver</button>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Solicitudes de noticias</span><strong class="client-metric-value">{{ number_format($monthly['news_requests'], 0, ',', '.') }}</strong><small class="text-secondary">durante {{ $month->locale('es')->translatedFormat('F Y') }}</small></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Contenidos generados</span><strong class="client-metric-value">{{ number_format($monthly['contents_generated'], 0, ',', '.') }}</strong><small class="text-secondary">{{ $monthly['contents_available'] }} disponibles actualmente</small></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="client-metric-label">Ejecuciones agénticas</span><strong class="client-metric-value">{{ number_format($monthly['automation']['runs'], 0, ',', '.') }}</strong><small class="text-secondary">{{ $monthly['automation']['completed'] }} resultados válidos</small></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="card metric-card h-100 border-primary-subtle"><div class="card-body"><span class="client-metric-label">Consumo comercial</span><strong class="client-metric-value fs-3">{{ $money($commercial['settled_minor']) }}</strong><small class="text-secondary">liquidado en el período</small></div></div></div>
        </div>

        <div class="mb-3"><h2 class="h4 mb-1">Mis servicios</h2><p class="text-secondary mb-0">Accesos y estado actual de la organización.</p></div>
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xxl-3"><div class="card client-service-card h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between"><span class="client-service-icon">01</span><span class="badge text-bg-{{ $dashboard['services']['media']['active'] > 0 ? 'success' : 'secondary' }}">{{ $dashboard['services']['media']['active'] > 0 ? 'Activo' : 'Sin configurar' }}</span></div><h3 class="h5 mt-3">Medios y canales</h3><p class="text-secondary">{{ $dashboard['services']['media']['active'] }} medios activos y {{ $dashboard['services']['media']['channels'] }} canales configurados.</p><a class="btn btn-outline-primary mt-auto" href="{{ route('admin.medios.index') }}">Administrar medios</a></div></div></div>
            <div class="col-md-6 col-xxl-3"><div class="card client-service-card h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between"><span class="client-service-icon">02</span><span class="badge text-bg-{{ $dashboard['services']['api']['enabled'] ? 'success' : 'secondary' }}">{{ $dashboard['services']['api']['enabled'] ? 'Activo' : 'Sin credencial' }}</span></div><h3 class="h5 mt-3">Distribución por API</h3><p class="text-secondary">{{ $dashboard['services']['api']['active_credentials'] }} credenciales activas y {{ $monthly['api']['records'] }} registros entregados este mes.</p><a class="btn btn-outline-primary mt-auto" href="{{ route('admin.api-consumos.index', ['from' => $month->format('Y-m-d'), 'to' => $month->endOfMonth()->format('Y-m-d')]) }}">Ver actividad API</a></div></div></div>
            <div class="col-md-6 col-xxl-3"><div class="card client-service-card h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between"><span class="client-service-icon">03</span><span class="badge text-bg-{{ $dashboard['services']['automation']['active'] > 0 ? 'success' : 'secondary' }}">{{ $dashboard['services']['automation']['active'] }} activas</span></div><h3 class="h5 mt-3">Piloto Automático</h3><p class="text-secondary">{{ $dashboard['services']['automation']['total'] }} tareas, {{ $dashboard['services']['automation']['paused'] }} pausadas. Próxima: {{ $dashboard['services']['automation']['next_run_at'] ? \Illuminate\Support\Carbon::parse($dashboard['services']['automation']['next_run_at'])->setTimezone('America/Argentina/Buenos_Aires')->format('d/m H:i') : 'sin programar' }}.</p><a class="btn btn-primary mt-auto" href="{{ route('admin.piloto.index') }}">Configurar agentes</a></div></div></div>
            <div class="col-md-6 col-xxl-3"><div class="card client-service-card h-100"><div class="card-body d-flex flex-column"><div class="d-flex justify-content-between"><span class="client-service-icon">04</span><span class="badge text-bg-primary">{{ $dashboard['services']['content']['available'] }} disponibles</span></div><h3 class="h5 mt-3">Contenidos</h3><p class="text-secondary">Producciones creadas por NEURIA y disponibles para tus canales y sistemas conectados.</p><a class="btn btn-outline-primary mt-auto" href="{{ route('admin.contenidos.index') }}">Ver contenidos</a></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7"><div class="card h-100"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Consumo mensual</h2></div><div class="card-body"><div class="row g-3">
                @foreach([
                    ['Solicitudes API', $monthly['api']['requests'], $monthly['api']['errors'].' errores'],
                    ['Registros entregados', $monthly['api']['records'], $monthly['api']['average_ms'].' ms promedio'],
                    ['Eventos de distribución', $monthly['distribution_events'], 'altas y cambios disponibles'],
                    ['Tokens de IA', $monthly['ai_tokens'], 'investigación y redacción'],
                    ['Sin novedades', $monthly['automation']['no_news'], 'sin cargo comercial'],
                    ['Incidencias', $monthly['automation']['incidents'], 'errores, bloqueos o salidas inválidas'],
                ] as [$label, $value, $detail])
                    <div class="col-sm-6"><div class="client-consumption-item"><span>{{ $label }}</span><strong>{{ number_format($value, 0, ',', '.') }}</strong><small>{{ $detail }}</small></div></div>
                @endforeach
            </div></div></div></div>
            <div class="col-xl-5"><div class="card h-100"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Presupuesto y movimientos</h2></div><div class="card-body">
                <dl class="row client-money-summary mb-3"><dt class="col-7">Reservado</dt><dd class="col-5 text-end">{{ $money($commercial['reserved_minor']) }}</dd><dt class="col-7">Liquidado</dt><dd class="col-5 text-end fw-bold">{{ $money($commercial['settled_minor']) }}</dd><dt class="col-7">Liberado</dt><dd class="col-5 text-end">{{ $money($commercial['released_minor']) }}</dd></dl>
                @if($commercial['monthly_limit_minor'] !== null)
                    @php($usedPercent = $commercial['monthly_limit_minor'] > 0 ? min(100, (int) round(($commercial['settled_minor'] / $commercial['monthly_limit_minor']) * 100)) : 0)
                    <div class="d-flex justify-content-between small mb-1"><span>Límite mensual</span><strong>{{ $money($commercial['monthly_limit_minor']) }}</strong></div>
                    <div class="progress mb-2" role="progressbar" aria-label="Presupuesto utilizado" aria-valuenow="{{ $usedPercent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $usedPercent }}%">{{ $usedPercent }}%</div></div>
                    <small class="text-secondary">Disponible: {{ $money($commercial['available_minor']) }}</small>
                @else
                    <div class="alert alert-light border mb-0">No existe una fotografía histórica del presupuesto para este período. Los cargos liquidados sí corresponden al mes seleccionado.</div>
                @endif
            </div></div></div>
        </div>

        <div class="row g-4">
            <div class="col-xl-7"><div class="card"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Ejecuciones recientes</h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Fecha</th><th>Tarea</th><th>Estado</th><th>Resultado</th></tr></thead><tbody>
                @forelse($dashboard['recentRuns'] as $run)<tr><td>{{ $run->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m H:i') }}</td><td><a href="{{ route('admin.piloto.show', $run->automation_task_id) }}">{{ $run->task?->name }}</a><br><small class="text-secondary">{{ $run->trigger }}</small></td><td><span class="badge text-bg-{{ $run->status === 'COMPLETADA' ? 'success' : (in_array($run->status, ['ERROR','INVALIDA','BLOQUEADA','ESTADO_DESCONOCIDO']) ? 'danger' : 'secondary') }}">{{ $runLabels[$run->status] ?? str_replace('_', ' ', $run->status) }}</span></td><td>{{ $run->result?->title ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="text-center text-secondary py-4">No hubo ejecuciones en este período.</td></tr>@endforelse
            </tbody></table></div></div></div>
            <div class="col-xl-5"><div class="card"><div class="card-header bg-white py-3"><h2 class="h5 mb-0">Movimientos comerciales</h2></div><div class="list-group list-group-flush">
                @forelse($dashboard['recentMovements'] as $movement)<div class="list-group-item py-3"><div class="d-flex justify-content-between gap-3"><div><strong>{{ $movementLabels[$movement->type] ?? $movement->type }}</strong><div class="small text-secondary">{{ $movement->run?->task?->name }} · {{ $movement->created_at->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') }}</div></div><span class="fw-semibold text-nowrap">{{ $movement->currency }} {{ number_format($movement->amount_minor / 100, 2, ',', '.') }}</span></div></div>@empty<div class="list-group-item text-center text-secondary py-4">No existen movimientos en este período.</div>@endforelse
            </div></div></div>
        </div>
    @endif
</x-layouts.app>
