<x-layouts.app :title="$organization->exists ? 'Editar organización' : 'Nueva organización'">
    <h1 class="h3">{{ $organization->exists ? 'Editar' : 'Nueva' }} organización</h1>
    <form class="card card-body" method="POST" action="{{ $organization->exists ? route('admin.organizaciones.update', $organization) : route('admin.organizaciones.store') }}">
        @csrf
        @if($organization->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nombre <x-form-help text="Nombre legal o institucional de la organización. Ejemplo: Instituto Superior Félix Atilio Cabrera." /></label>
                <input class="form-control" name="name" value="{{ old('name', $organization->name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nombre comercial <x-form-help text="Nombre corto o marca con la que se presenta públicamente. Ejemplo: ISFD." /></label>
                <input class="form-control" name="trade_name" value="{{ old('trade_name', $organization->trade_name) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Correo <x-form-help text="Correo principal de contacto administrativo de la organización." /></label>
                <input class="form-control" type="email" name="contact_email" value="{{ old('contact_email', $organization->contact_email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Teléfono <x-form-help text="Teléfono de contacto, preferentemente con código de área. Ejemplo: +54 370 4000000." /></label>
                <input class="form-control" name="phone" value="{{ old('phone', $organization->phone) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Observaciones internas <x-form-help text="Notas privadas para el equipo de NEURIA. No se muestran al cliente ni se envían a la IA." /></label>
                <textarea class="form-control" name="internal_notes">{{ old('internal_notes', $organization->internal_notes) }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Estado <x-form-help text="Una organización inactiva no podrá operar normalmente con sus usuarios, medios y credenciales." /></label>
                <select class="form-select" name="is_active"><option value="1">Activa</option><option value="0" @selected(!$organization->is_active && $organization->exists)>Inactiva</option></select>
            </div>
        </div>
        <div class="mt-3"><button class="btn btn-primary">Guardar</button><a class="btn btn-link" href="{{ route('admin.organizaciones.index') }}">Cancelar</a></div>
    </form>
</x-layouts.app>
