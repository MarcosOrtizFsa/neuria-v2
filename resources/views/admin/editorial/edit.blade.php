@php
    $identityFields = [
        'audience' => ['¿A qué público está dirigido tu medio?', 'Describí lectores, intereses y nivel de conocimiento. Ejemplo: docentes y estudiantes de nivel superior interesados en tecnología educativa.'],
        'focus' => ['Enfoque editorial', 'Indicá desde qué perspectiva aborda los temas. Ejemplo: educativo, institucional y orientado a la aplicación práctica.'],
        'tone' => ['Tono de comunicación', 'Definí cómo debe sonar la redacción. Ejemplo: claro, cercano, didáctico y profesional.'],
        'style' => ['Estilo periodístico', 'Explicá la forma de narrar. Ejemplo: informativo, con datos verificables, párrafos breves y lenguaje accesible.'],
        'preferred_vocabulary' => ['Vocabulario preferido', 'Palabras y expresiones que representan al medio. Ejemplo: comunidad educativa, formación docente, innovación responsable.'],
        'avoid_expressions' => ['Expresiones a evitar', 'Términos, fórmulas o tonos que la IA no debe usar. Ejemplo: sensacionalismo, tecnicismos sin explicar o afirmaciones absolutas.'],
    ];
    $productionFields = [
        'preferred_length' => ['Extensión preferida', 'Longitud habitual de las notas. Ejemplo: entre 700 y 1.000 palabras.'],
        'title_structure' => ['Estructura del título', 'Modelo de títulos esperado. Ejemplo: directos, informativos y de hasta 90 caracteres, sin exageraciones.'],
        'summary_style' => ['Estilo de bajada', 'Indicá cómo resumir la nota. Ejemplo: uno o dos párrafos breves que aporten el dato principal y el contexto.'],
        'body_structure' => ['Organización del cuerpo', 'Orden deseado. Ejemplo: introducción, antecedentes, desarrollo con subtítulos y cierre contextual.'],
        'quote_preferences' => ['Preferencias de citas', 'Definí cómo tratar declaraciones. Ejemplo: usar solo citas verificables y atribuir siempre la fuente.'],
        'output_format' => ['Formato de salida', 'Formato técnico/editorial esperado. Ejemplo: texto plano con párrafos separados y sin etiquetas HTML.'],
    ];
    $restrictionFields = [
        'preferred_topics' => ['Temáticas preferidas', 'Temas prioritarios para el medio. Ejemplo: educación, formación docente, tecnología e inteligencia artificial.'],
        'excluded_topics' => ['Temáticas excluidas', 'Temas que no deben producirse para este medio. Escribí uno por línea o separados claramente.'],
        'undesired_approaches' => ['Enfoques no deseados', 'Perspectivas que deben evitarse. Ejemplo: publicidad encubierta, alarmismo o afirmaciones sin respaldo.'],
        'writing_rules' => ['Reglas específicas', 'Normas obligatorias de redacción. Ejemplo: explicar siglas, verificar fechas y diferenciar hechos de opiniones.'],
    ];
@endphp

<x-layouts.app title="Línea editorial">
    <h1 class="h3">Línea editorial de {{ $mediaOutlet->name }}</h1>
    <p class="text-secondary">Estas preferencias adaptan la redacción, pero nunca reemplazan las reglas de veracidad y seguridad de NEURIA.</p>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.medios.editorial.update', $mediaOutlet) }}">
        @csrf @method('PUT')
        <div class="accordion" id="editor">
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity">Identidad editorial</button></h2>
                <div id="identity" class="accordion-collapse collapse show">
                    <div class="accordion-body row g-3">
                        <div class="col-md-6"><label class="form-label">Nombre del perfil <x-form-help text="Nombre interno para reconocer esta configuración editorial. Ejemplo: Perfil editorial ISFD." /></label><input class="form-control" name="name" value="{{ old('name', $profile->name) }}" required></div>
                        <div class="col-md-6"><label class="form-label">Idioma <x-form-help text="Código de idioma y región de la redacción. Ejemplo: es-AR para español de Argentina." /></label><input class="form-control" name="language" value="{{ old('language', $profile->language) }}" required></div>
                        @foreach($identityFields as $key => [$label, $help])
                            <div class="col-md-6"><label class="form-label">{{ $label }} <x-form-help :text="$help" /></label><textarea class="form-control" name="identity[{{ $key }}]">{{ old('identity.'.$key, $profile->identity[$key] ?? '') }}</textarea></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#production">Producción de contenidos</button></h2>
                <div id="production" class="accordion-collapse collapse">
                    <div class="accordion-body row g-3">
                        @foreach($productionFields as $key => [$label, $help])
                            <div class="col-md-6"><label class="form-label">{{ $label }} <x-form-help :text="$help" /></label><textarea class="form-control" name="production[{{ $key }}]">{{ old('production.'.$key, $profile->production[$key] ?? '') }}</textarea></div>
                        @endforeach
                        <div class="col-12 form-check ms-2"><input type="hidden" name="production[use_subtitles]" value="0"><input class="form-check-input" type="checkbox" name="production[use_subtitles]" value="1" @checked($profile->production['use_subtitles'] ?? false)><label class="form-check-label">Usar subtítulos <x-form-help text="Permite organizar notas extensas en secciones breves y fáciles de leer." /></label></div>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sources">Fuentes y restricciones</button></h2>
                <div id="sources" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <label class="form-label">Fuentes preferidas <x-form-help text="Medios, organismos y sitios que deben priorizarse. Escribí nombres o URLs, una por línea. Ejemplo: UNESCO, argentina.gob.ar." /></label><textarea class="form-control mb-3" name="sources_config[preferred]">{{ old('sources_config.preferred', $profile->sources_config['preferred'] ?? '') }}</textarea>
                        <label class="form-label">Fuentes restringidas <x-form-help text="Sitios o fuentes que no deben utilizarse. Escribí nombres o dominios, una por línea." /></label><textarea class="form-control mb-3" name="sources_config[restricted]">{{ old('sources_config.restricted', $profile->sources_config['restricted'] ?? '') }}</textarea>
                        @foreach($restrictionFields as $key => [$label, $help])
                            <label class="form-label">{{ $label }} <x-form-help :text="$help" /></label><textarea class="form-control mb-3" name="restrictions[{{ $key }}]">{{ old('restrictions.'.$key, $profile->restrictions[$key] ?? '') }}</textarea>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-body mt-3">
            <label class="form-label">Instrucciones adicionales autorizadas <x-form-help text="Indicaciones permanentes que complementan todos los campos anteriores. No incluyas contraseñas, tokens ni datos sensibles." /></label><textarea class="form-control mb-3" name="additional_instructions">{{ old('additional_instructions', $profile->additional_instructions) }}</textarea>
            <label class="form-label">Motivo del cambio <x-form-help text="Breve explicación para el historial de versiones. Ejemplo: Ajuste del tono y de las fuentes preferidas." /></label><input class="form-control" name="change_note">
        </div>
        <button class="btn btn-primary mt-3">Guardar y crear versión</button>
    </form>
</x-layouts.app>
