@php($values = $configuration ?? [])
<div class="card card-body mb-4">
    <h2 class="h5">Configuración de la tarea</h2>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label" for="name">Nombre *</label><input class="form-control" id="name" name="name" required maxlength="150" value="{{ old('name', $values['name'] ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label" for="language">Idioma</label><input class="form-control" id="language" name="language" value="{{ old('language', $values['language'] ?? 'es-AR') }}"></div>
        <div class="col-12"><label class="form-label" for="topic">Tema a investigar *</label><textarea class="form-control" id="topic" name="topic" rows="3" required>{{ old('topic', $values['topic'] ?? '') }}</textarea></div>
        <div class="col-md-6"><label class="form-label" for="instructions">Instrucciones</label><textarea class="form-control" id="instructions" name="instructions" rows="4">{{ old('instructions', $values['instructions'] ?? '') }}</textarea></div>
        <div class="col-md-6"><label class="form-label" for="editorial_approach">Enfoque editorial</label><textarea class="form-control" id="editorial_approach" name="editorial_approach" rows="4">{{ old('editorial_approach', $values['editorial_approach'] ?? '') }}</textarea></div>
        <div class="col-12"><label class="form-label" for="domains_text">Dominios permitidos, uno por línea *</label><textarea class="form-control" id="domains_text" name="domains_text" rows="3" required placeholder="argentina.gob.ar&#10;indec.gob.ar">{{ old('domains_text', isset($values['domains']) ? implode("\n", $values['domains']) : '') }}</textarea><div class="form-text">La IA solo podrá buscar dentro de estos dominios. No escribas una fuente genérica como “tal medio”.</div></div>
    </div>
</div>
<div class="card card-body mb-4">
    <h2 class="h5">Programación</h2>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="frequency">Frecuencia *</label><select class="form-select" id="frequency" name="frequency" required>@foreach(['DAILY'=>'Todos los días','SELECTED_DAYS'=>'Días elegidos','WEEKLY'=>'Semanal'] as $key=>$label)<option value="{{ $key }}" @selected(old('frequency', $values['frequency'] ?? 'SELECTED_DAYS') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="time">Hora local *</label><input class="form-control" type="time" id="time" name="time" required value="{{ old('time', $values['time'] ?? '12:00') }}"></div>
        <div class="col-md-4"><label class="form-label" for="timezone">Zona horaria *</label><input class="form-control" id="timezone" name="timezone" required value="{{ old('timezone', $values['timezone'] ?? 'America/Argentina/Buenos_Aires') }}"></div>
        <div class="col-12"><span class="form-label d-block">Días</span>@foreach([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'] as $day=>$label)<label class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="days[]" value="{{ $day }}" @checked(in_array($day, old('days', $values['days'] ?? [1,2,3,4,5])))><span class="form-check-label">{{ $label }}</span></label>@endforeach</div>
        <div class="col-md-3"><label class="form-label" for="min_characters">Mínimo de caracteres</label><input class="form-control" type="number" id="min_characters" name="min_characters" min="200" value="{{ old('min_characters', $values['min_characters'] ?? 700) }}"></div>
        <div class="col-md-3"><label class="form-label" for="max_characters">Máximo de caracteres</label><input class="form-control" type="number" id="max_characters" name="max_characters" min="200" value="{{ old('max_characters', $values['max_characters'] ?? 1000) }}"></div>
        <div class="col-md-3"><label class="form-label">Destino</label><input class="form-control" value="Panel de NEURIA" disabled><div class="form-text">Publicación externa: fuera de esta versión.</div></div>
        <div class="col-md-3"><label class="form-label" for="no_news_policy">Sin novedades</label><select class="form-select" id="no_news_policy" name="no_news_policy"><option value="NO_CONTENT">Registrar sin novedades, sin inventar nota</option></select></div>
    </div>
</div>
