<x-layouts.app title="Editar tarea">
    <div class="mb-4"><a href="{{ route('admin.piloto.show', $automationTask) }}" class="text-decoration-none">← Volver</a><h1 class="h3 mt-2">Editar {{ $automationTask->name }}</h1><div class="alert alert-warning">Los cambios crean una versión nueva, detienen la programación vigente y requieren aceptar una nueva cotización.</div></div>
    <form method="POST" action="{{ route('admin.piloto.update', $automationTask) }}">@csrf @method('PUT')
        @include('admin.automations._form', ['configuration' => $configuration])
        <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('admin.piloto.show', $automationTask) }}">Cancelar</a><button class="btn btn-primary">Guardar nueva versión</button></div>
    </form>
</x-layouts.app>
