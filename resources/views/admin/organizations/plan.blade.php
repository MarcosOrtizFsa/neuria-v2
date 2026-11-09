<x-layouts.app title="Plan de la organización">
    @php
        $monthlyLimit = $budget ? number_format($budget->monthly_limit_minor / 100, 2, '.', '') : '0.00';
        $money = fn (int $minor): string => ($budget?->currency ?? 'ARS').' '.number_format($minor / 100, 2, ',', '.');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <a class="text-decoration-none small" href="{{ route('admin.organizaciones.show', $organization) }}">&larr; Volver a la organización</a>
            <h1 class="h3 mt-2 mb-1">Plan mensual de {{ $organization->name }}</h1>
            <p class="text-secondary mb-0">Define el presupuesto y los límites operativos de los servicios agénticos.</p>
        </div>
    </div>

    @if($budget)
        <div class="row g-3 mb-4">
            <div class="col-md-4"><div class="card card-body h-100"><span class="small text-secondary">Liquidado este mes</span><strong class="h5 mb-0">{{ $money($budget->settled_minor) }}</strong></div></div>
            <div class="col-md-4"><div class="card card-body h-100"><span class="small text-secondary">Reservado</span><strong class="h5 mb-0">{{ $money($budget->reserved_minor) }}</strong></div></div>
            <div class="col-md-4"><div class="card card-body h-100"><span class="small text-secondary">Período vigente</span><strong class="h5 mb-0">{{ $budget->period_start->format('m/Y') }}</strong></div></div>
        </div>
    @endif

    <form class="card card-body" method="POST" action="{{ route('admin.organizaciones.plan.update', $organization) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="currency">Moneda <x-form-help text="Moneda utilizada para cotizaciones, reservas y liquidaciones del cliente." /></label>
                <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency" required>
                    @foreach(['ARS' => 'Pesos argentinos (ARS)', 'USD' => 'Dólares estadounidenses (USD)'] as $code => $label)
                        <option value="{{ $code }}" @selected(old('currency', $budget?->currency ?? 'ARS') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-8">
                <label class="form-label" for="monthly_limit">Importe máximo mensual <x-form-help text="Tope comercial disponible durante el mes. Ejemplo: 100000.00 representa cien mil pesos." /></label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input class="form-control @error('monthly_limit') is-invalid @enderror" id="monthly_limit" name="monthly_limit" inputmode="decimal" value="{{ old('monthly_limit', $monthlyLimit) }}" placeholder="100000.00" required>
                    @error('monthly_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-text">Puede modificar el límite sin perder lo ya consumido o reservado durante el período actual.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="active_task_limit">Tareas activas permitidas <x-form-help text="Cantidad máxima de tareas de Piloto Automático que la organización puede mantener activas al mismo tiempo." /></label>
                <input class="form-control @error('active_task_limit') is-invalid @enderror" id="active_task_limit" name="active_task_limit" type="number" min="0" max="10000" value="{{ old('active_task_limit', $budget?->active_task_limit ?? 10) }}" required>
                @error('active_task_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="daily_run_limit">Ejecuciones diarias <x-form-help text="Máximo de ejecuciones automáticas o manuales que puede realizar la organización por día." /></label>
                <input class="form-control @error('daily_run_limit') is-invalid @enderror" id="daily_run_limit" name="daily_run_limit" type="number" min="0" max="10000" value="{{ old('daily_run_limit', $budget?->daily_run_limit ?? 20) }}" required>
                @error('daily_run_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label class="form-label" for="concurrency_limit">Procesos simultáneos <x-form-help text="Cantidad máxima de trabajos que pueden procesarse simultáneamente para esta organización." /></label>
                <input class="form-control @error('concurrency_limit') is-invalid @enderror" id="concurrency_limit" name="concurrency_limit" type="number" min="1" max="100" value="{{ old('concurrency_limit', $budget?->concurrency_limit ?? 2) }}" required>
                @error('concurrency_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">Guardar plan</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.organizaciones.show', $organization) }}">Cancelar</a>
        </div>
    </form>
</x-layouts.app>
