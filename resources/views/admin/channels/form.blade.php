<x-layouts.app title="Canal">
    <h1 class="h3">{{ $mediaChannel->exists ? 'Editar' : 'Nuevo' }} canal</h1>
    <form class="card card-body" method="POST" action="{{ $mediaChannel->exists ? route('admin.medios.canales.update', [$mediaOutlet, $mediaChannel]) : route('admin.medios.canales.store', $mediaOutlet) }}">
        @csrf
        @if($mediaChannel->exists) @method('PUT') @endif
        <label class="form-label">Nombre <x-form-help text="Nombre reconocible del destino. Ejemplo: ISFD.COM.AR, Facebook ISFD o Instagram ISFD." /></label>
        <input class="form-control mb-3" name="name" value="{{ old('name', $mediaChannel->name) }}" required>
        <label class="form-label">Tipo <x-form-help text="Plataforma donde se publicará el contenido: sitio web, Facebook, Instagram, LinkedIn, X u otra." /></label>
        <select class="form-select mb-3" name="type">@foreach(['WEB','FACEBOOK','INSTAGRAM','LINKEDIN','X','OTRO'] as $type)<option @selected(old('type', $mediaChannel->type) === $type)>{{ $type }}</option>@endforeach</select>
        <label class="form-label">URL <x-form-help text="Dirección pública exacta del sitio, página o perfil. Ejemplo: https://isfd.com.ar." /></label>
        <input class="form-control mb-3" type="url" name="url" value="{{ old('url', $mediaChannel->url) }}">
        <label class="form-label">Identificador externo <x-form-help text="Código usado por el sistema cliente para reconocer este canal. Ejemplo: isfd. Debe coincidir con su integración cuando corresponda." /></label>
        <input class="form-control mb-3" name="external_identifier" value="{{ old('external_identifier', $mediaChannel->external_identifier) }}">
        <label class="form-label">Estado <x-form-help text="Solo los canales ACTIVOS pueden recibir nuevas producciones de NEURIA." /></label>
        <select class="form-select mb-3" name="status"><option>ACTIVO</option><option @selected($mediaChannel->status === 'INACTIVO')>INACTIVO</option></select>
        <h2 class="h5 mt-3">Preferencias editoriales adicionales</h2>
        <p class="text-secondary">Complementan el perfil general del medio únicamente para este canal.</p>
        <label class="form-label">Extensión <x-form-help text="Longitud deseada. Ejemplo: entre 700 y 1.000 palabras, o hasta 1.500 caracteres para redes." /></label>
        <input class="form-control mb-3" name="editorial_overrides[preferred_length]" value="{{ old('editorial_overrides.preferred_length', $mediaChannel->editorial_overrides['preferred_length'] ?? '') }}">
        <label class="form-label">Tono específico <x-form-help text="Tono propio de este canal. Ejemplo: didáctico, claro y profesional." /></label>
        <textarea class="form-control mb-3" name="editorial_overrides[tone]">{{ old('editorial_overrides.tone', $mediaChannel->editorial_overrides['tone'] ?? '') }}</textarea>
        <label class="form-label">Formato de salida <x-form-help text="Estructura esperada. Ejemplo: título, bajada, cuerpo con subtítulos y cierre; o publicación breve para red social." /></label>
        <input class="form-control mb-3" name="editorial_overrides[output_format]" value="{{ old('editorial_overrides.output_format', $mediaChannel->editorial_overrides['output_format'] ?? '') }}">
        <button class="btn btn-primary">Guardar</button>
    </form>
</x-layouts.app>
