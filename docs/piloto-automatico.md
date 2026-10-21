# Piloto Automático

El módulo permite que cada cliente describa en lenguaje natural una investigación periodística recurrente. NEURIA interpreta el pedido, muestra una configuración editable y una cotización, y solo crea la automatización cuando el usuario la confirma expresamente.

## Alcance inicial

- Investigación de noticias mediante OpenAI y búsqueda web restringida a dominios autorizados.
- Resultado dentro del panel de NEURIA. El envío directo a web, correo o redes queda indicado como una capacidad futura.
- Frecuencia diaria, semanal o por días seleccionados, con hora y zona horaria IANA.
- Borradores, versiones inmutables, cotizaciones versionadas, pausado, archivado y ejecución manual.
- Presupuesto mensual, límites diarios y de concurrencia por organización.
- Registro separado de consumo técnico, cargo comercial y fuentes.

Las tarifas sembradas por `AutomationDemoSeeder` son demostrativas. No representan precios comerciales. Antes de habilitar el servicio pago en producción deben cargarse tarifas vigentes y límites acordados con cada cliente.

## Configuración

Variables principales:

```dotenv
OPENAI_API_KEY=
AUTOMATION_OPENAI_MODEL=gpt-5.4
AUTOMATION_DEFAULT_TIMEZONE=America/Argentina/Buenos_Aires
AUTOMATION_RESEARCH_OVERLAP_MINUTES=15
AUTOMATION_LATE_TOLERANCE_MINUTES=180
AUTOMATION_QUOTE_VALID_DAYS=7
AUTOMATION_INTERPRET_RATE_LIMIT=5
AUTOMATION_RUN_NOW_RATE_LIMIT=5
```

Preparación local:

```bash
php artisan migrate
php artisan db:seed --class=ModuleSeeder
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=AutomationDemoSeeder
```

## Procesos permanentes

El scheduler reclama vencimientos en forma idempotente y despacha trabajos a la cola:

```bash
php artisan schedule:work
php artisan queue:work --tries=1 --timeout=300
```

En producción, ejecutar `php artisan schedule:run` cada minuto mediante cron y mantener `queue:work` bajo un supervisor de procesos. Los trabajos no reintentan automáticamente una respuesta ambigua del proveedor; quedan identificados para conciliación y evitan un doble cargo.

## Prueba de punta a punta

1. Ingresar como cliente o administrador y abrir **Piloto Automático**.
2. Describir una tarea, por ejemplo: investigar cada lunes a las 09:00 novedades de un tema en dominios concretos y redactar entre 2.000 y 3.000 caracteres.
3. Interpretar el pedido, completar faltantes y revisar la configuración normalizada.
4. Guardar el borrador y revisar la cotización proyectada a 30 días.
5. Confirmar la activación. Guardar o interpretar nunca activa por sí solo.
6. Usar **Ejecutar ahora** o esperar al horario programado.
7. Verificar resultado, fuentes, advertencias y cargo en el detalle de la automatización.

Las pruebas automatizadas sustituyen el proveedor de OpenAI y no consumen crédito. La prueba manual con una clave real sí puede generar consumo del proveedor.

## Despliegue

Ejecutar migraciones y seeders de permisos/tarifas, compilar los recursos, reiniciar trabajadores de cola y limpiar la caché de configuración. La aplicación debe conservar una única instancia del scheduler o usar el mecanismo de exclusión configurado. La clave de OpenAI nunca debe almacenarse en la base de datos ni mostrarse en la interfaz.
