# API de distribución NEURIA v1

Base: `https://<host>/api/v1`. Todas las peticiones requieren `Accept: application/json` y `Authorization: Bearer <token>`. La credencial debe estar activa, pertenecer a un medio y contar con `content:read`. En producción se exige HTTPS.

## Endpoints

- `GET /contents`: contenidos actualmente disponibles para el medio autenticado.
- `GET /contents/{uuid}`: detalle de un contenido propio y disponible.
- `GET /changes`: eventos incrementales `CONTENT_AVAILABLE`, `CONTENT_SUSPENDED`, `CONTENT_REACTIVATED` y `CONTENT_WITHDRAWN`.
- `GET /me`: identidad del medio, organización, credencial y permisos.

`/contents` acepta `channel_id`, `available_from`, `id`, `cursor` y `limit` (1–100). `/changes` acepta `channel_id`, `cursor` y `limit`. Cada lista devuelve `data` y `meta` con `next_cursor`, `limit` y `has_more`. Los cursores son opacos: el cliente debe almacenarlos y devolverlos sin interpretarlos.

## Errores

Los errores usan `{ "error": { "code": "...", "message": "..." } }` cuando los genera la capa de acceso. Códigos principales: `401 UNAUTHENTICATED`, `403 INSUFFICIENT_SCOPE`, `404` para contenido inexistente o ajeno, `422` para filtros/cursores inválidos y `429 RATE_LIMIT_EXCEEDED`.

## Sincronización recomendada

1. Consultar `/contents` hasta que `has_more` sea falso e importar cada `id` de manera idempotente como borrador.
2. Consultar `/changes` con su cursor independiente para reflejar suspensiones, reactivaciones y retiros.
3. Confirmar el cursor local solo después de persistir completamente la página.
4. Ante `429` o `5xx`, reintentar con espera progresiva. No reintentar indefinidamente `401`, `403` o `422`.

NEURIA registra endpoint, credencial, estado HTTP, cantidad y duración; nunca guarda ni devuelve el token completo. Consulte `integrations/php-cms` para el conector de referencia.
