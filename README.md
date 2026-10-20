# NEURIA 2.0

Plataforma SaaS multiempresa desarrollada con Laravel 13, PHP 8.5, MySQL, Blade, Bootstrap 5.3 y Vite.

## Desarrollo local

```powershell
C:\Users\BANGHÓ\.config\herd-lite\bin\php.exe artisan migrate
C:\Users\BANGHÓ\.config\herd-lite\bin\php.exe artisan db:seed
C:\Users\BANGHÓ\.config\herd-lite\bin\php.exe artisan serve
npm run dev
```

El seeder del usuario inicial requiere `ROOT_NAME`, `ROOT_LAST_NAME`, `ROOT_EMAIL` y `ROOT_PASSWORD` en `.env`. Las credenciales privadas nunca deben incorporarse al repositorio.

## Módulos actuales

- Portal público, autenticación y panel privado.
- Usuarios, organizaciones, roles y permisos granulares.
- Medios asociados a organizaciones.
- Canales de distribución por medio.
- Credenciales API revocables con token almacenado mediante hash.
- Perfiles editoriales estructurados e historial de versiones.
- Auditoría de operaciones administrativas.

Las rutas administrativas están agrupadas bajo `/admin`. El módulo MEDIOS utiliza `/admin/medios`; sus canales, credenciales y perfil editorial son recursos subordinados al medio.

## Permisos de Fase 3

- `MEDIOS.VER`, `MEDIOS.ALTA`, `MEDIOS.MODIFICAR`, `MEDIOS.BAJA` y `MEDIOS.CONFIGURAR`.
- `MEDIOS.GESTIONAR_CANALES` y `MEDIOS.GESTIONAR_CREDENCIALES`.
- `LINEA_EDITORIAL.VER`, `LINEA_EDITORIAL.MODIFICAR`, `LINEA_EDITORIAL.CONFIGURAR`.
- `LINEA_EDITORIAL.VER_VERSIONES` y `LINEA_EDITORIAL.RESTAURAR_VERSION`.

ROOT posee acceso global. Los permisos sensibles nuevos no se conceden automáticamente a otros roles. Una denegación individual prevalece sobre el permiso heredado del rol.

## Credenciales API

Cada credencial pertenece a un medio y admite los alcances `content:read` y `content:import`. El token completo se entrega únicamente en la respuesta de creación, con cabeceras que impiden su almacenamiento en caché. La base conserva solamente su hash SHA-256, identificador público y prefijo. Revocar una credencial no elimina su historial.

La API de distribución de contenidos todavía no está implementada.

## Línea editorial y clientes

Cada medio posee como máximo un perfil vigente y múltiples versiones históricas. Guardar o restaurar genera una versión nueva y conserva las anteriores. Las preferencias del cliente están separadas de las instrucciones internas de NEURIA.

Los clientes solo pueden consultar medios de su organización y modificar una lista explícita de preferencias autorizadas. No pueden administrar credenciales, organizaciones, permisos ni instrucciones internas.

## Pruebas

```powershell
C:\Users\BANGHÓ\.config\herd-lite\bin\php.exe artisan test
```

Las pruebas cubren autenticación, permisos, aislamiento entre organizaciones, medios, canales, credenciales, auditoría y versionado editorial.

## Funcionalidades futuras

Quedan fuera de esta fase la generación mediante IA, la API de entrega de noticias, publicación en redes, facturación, suscripciones y automatizaciones editoriales.

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
