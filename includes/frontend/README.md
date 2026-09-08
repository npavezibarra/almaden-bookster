# Frontend Runtime (`includes/frontend/`)

## Responsabilidad

Esta carpeta contiene rutas, paginas canonicas, shell visual, menus, visibilidad, permisos y utilidades de acceso para las superficies frontend de Almaden Bookster.

## Archivos principales

- `pages.php`: punto de entrada para registrar configuracion, helpers y sincronizacion de paginas.
- `pages-settings.php`: definicion de paginas canonicas, slugs y opciones persistidas.
- `pages-sync.php`: creacion y actualizacion de paginas WordPress instalables.
- `pages-helpers.php`: utilidades comunes para resolver slugs, URLs y page IDs.
- `pages-menus*.php`: sincronizacion, reglas, limpieza y bloques de menus.
- `pages-visibility.php`: visibilidad de paginas publicas, privadas e internas.
- `app-shell.php`: shell visual compartido de app pages.
- `shell-access.php`: control de acceso para pantallas privadas del shell.
- `access-control.php`: permisos de compra y acceso a lectura.
- `user-access-manager.php`: operaciones de acceso de usuario.
- `reading-stats.php`: agregados de lectura, highlights, quizzes y actividad.

## Flujo de entrada

El bootstrap principal del plugin carga `pages.php` y archivos relacionados. Las app pages suelen resolver su render desde hooks de WordPress, reglas de pagina canonica o loaders frontend.

## Reglas locales

- Toda pagina Almaden Shell nueva debe agregarse a configuracion, sincronizacion e instalacion.
- Las paginas publicas no deben exigir login.
- Las pantallas privadas deben validar acceso en el loader y no solo ocultarse en menus.
- Si una pagina debe conservar el layout del tema, usar `the_content` en lugar de reemplazar toda la respuesta.

## Al modificar aqui

Revisar `AGENT_GUIDELINES.md`, el README raiz, `templates/shell/README.md` y el README del template afectado. Validar al menos la URL canonica, anonimo/logueado cuando aplique y menus compartidos.

## Archivos relacionados

- `templates/shell/`
- `templates/bookshelf/`
- `templates/ebook/`
- `templates/dashboard/`
- `includes/payments/`
