<x-layouts.app title="Nueva tarea automática">
    <div class="mb-4"><a href="{{ route('admin.piloto.index') }}" class="text-decoration-none">← Volver</a><h1 class="h3 mt-2">Nueva tarea de Piloto Automático</h1><p class="text-secondary">Describí el pedido para que NEURIA lo interprete o completá manualmente. Ninguna opción activa la tarea sin tu confirmación.</p></div>
    <div class="card card-body mb-4 border-primary">
        <h2 class="h5">Creación asistida con IA</h2>
        <form method="POST" action="{{ route('admin.piloto.interpret') }}">@csrf
            @if($organizations->count() > 1)<div class="mb-3"><label class="form-label" for="interpret_organization_id">Organización</label><select class="form-select" id="interpret_organization_id" name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}">{{ $organization->name }}</option>@endforeach</select></div>@elseif($organizations->isNotEmpty())<input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">@endif
            <label class="form-label" for="request_text">Contanos qué necesitás</label><textarea class="form-control" id="request_text" name="request_text" rows="6" required placeholder="De lunes a viernes a las 12, buscá las últimas noticias sobre...">{{ old('request_text') }}</textarea>
            <div class="d-flex justify-content-between align-items-center mt-3"><small class="text-secondary">Interpretar puede utilizar OpenAI, pero no programa ejecuciones ni cargos.</small><button class="btn btn-primary">Interpretar y preparar ficha</button></div>
        </form>
    </div>
    <div class="d-flex align-items-center gap-3 mb-3"><hr class="flex-grow-1"><strong>o configurar manualmente</strong><hr class="flex-grow-1"></div>
    <form method="POST" action="{{ route('admin.piloto.store') }}">@csrf
        @if($organizations->count() > 1)<div class="card card-body mb-4"><label class="form-label" for="organization_id">Organización *</label><select class="form-select" id="organization_id" name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}">{{ $organization->name }}</option>@endforeach</select></div>@elseif($organizations->isNotEmpty())<input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">@endif
        @include('admin.automations._form')
        <div class="d-flex justify-content-end"><button class="btn btn-primary">Preparar cotización</button></div>
    </form>
</x-layouts.app>
