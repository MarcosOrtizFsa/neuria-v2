# Conector PHP para CMS

Conector de referencia, independiente del framework del sitio cliente. Consume los flujos `contents` y `changes`, importa cada contenido una sola vez como borrador y conserva cursores de sincronización.

Variables requeridas: `NEURIA_API_URL=https://neuria...` y `NEURIA_API_TOKEN=...`. El token debe permanecer fuera del repositorio. Adapte `ContentRepository` y `CursorStore` a la base de datos del CMS; el ejemplo JSON es solo demostrativo.

El CMS conserva dos capas: el objeto original recibido de NEURIA y su versión editorial local. Una nueva sincronización nunca debe sobrescribir la versión local. Los eventos de suspensión y retiro deben marcarse para revisión del editor.
