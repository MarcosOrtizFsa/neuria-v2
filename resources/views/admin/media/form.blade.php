<x-layouts.app :title="$mediaOutlet->exists ? 'Editar medio' : 'Nuevo medio'">
    <h1 class="h3">{{ $mediaOutlet->exists ? 'Editar' : 'Nuevo' }} medio</h1>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form class="card card-body" method="POST" action="{{ $mediaOutlet->exists ? route('admin.medios.update', $mediaOutlet) : route('admin.medios.store') }}">
        @csrf
        @if($mediaOutlet->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Organización <x-form-help text="Entidad propietaria o responsable del medio." /></label><select class="form-select" name="organization_id" required>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(old('organization_id', $mediaOutlet->organization_id) == $organization->id)>{{ $organization->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Nombre <x-form-help text="Nombre con el que NEURIA identificará al medio. Ejemplo: ISFD Noticias." /></label><input class="form-control" name="name" value="{{ old('name', $mediaOutlet->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">Nombre comercial <x-form-help text="Marca pública o denominación abreviada del medio. Ejemplo: ISFD." /></label><input class="form-control" name="trade_name" value="{{ old('trade_name', $mediaOutlet->trade_name) }}"></div>
            <div class="col-md-6"><label class="form-label">Identificador público <x-form-help text="Código estable usado por la API. Usá letras, números, guiones o guion bajo, sin espacios. Ejemplo: isfd." /></label><input class="form-control" name="public_identifier" value="{{ old('public_identifier', $mediaOutlet->public_identifier) }}" required></div>
            <div class="col-md-6"><label class="form-label">URL principal <x-form-help text="Dirección pública del sitio principal del medio. Ejemplo: https://isfd.com.ar." /></label><input class="form-control" type="url" name="main_url" value="{{ old('main_url', $mediaOutlet->main_url) }}"></div>
            <div class="col-md-3"><label class="form-label">Tipo <x-form-help text="Clasificación general del medio para organizar clientes y servicios." /></label><select class="form-select" name="type">@foreach(['PORTAL_NOTICIAS','MEDIO_DIGITAL','MEDIO_INSTITUCIONAL','AGENCIA_NOTICIAS','OTRO'] as $type)<option @selected(old('type', $mediaOutlet->type) === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Estado <x-form-help text="ACTIVO permite operar y consumir la API; INACTIVO o SUSPENDIDO bloquean el acceso del medio." /></label><select class="form-select" name="status">@foreach(['ACTIVO','INACTIVO','SUSPENDIDO'] as $status)<option @selected(old('status', $mediaOutlet->status) === $status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Correo <x-form-help text="Correo del responsable editorial o técnico del medio." /></label><input class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $mediaOutlet->contact_email) }}"></div>
            <div class="col-md-6"><label class="form-label">Teléfono <x-form-help text="Teléfono del contacto responsable, preferentemente con código de área." /></label><input class="form-control" name="contact_phone" value="{{ old('contact_phone', $mediaOutlet->contact_phone) }}"></div>
            <div class="col-12"><label class="form-label">Descripción <x-form-help text="Resumen de identidad, audiencia y propósito del medio. La IA puede usarlo como contexto editorial." /></label><textarea class="form-control" name="description">{{ old('description', $mediaOutlet->description) }}</textarea></div>
            <div class="col-12"><label class="form-label">Observaciones internas <x-form-help text="Notas privadas para la administración de NEURIA. No se distribuyen por la API." /></label><textarea class="form-control" name="internal_notes">{{ old('internal_notes', $mediaOutlet->internal_notes) }}</textarea></div>
        </div>
        <button class="btn btn-primary mt-3">Guardar</button>
    </form>
</x-layouts.app>
