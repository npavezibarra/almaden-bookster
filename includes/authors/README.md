# Authors Backend (`includes/authors/`)

## Responsabilidad

Gestiona el dominio de autores: rutas publicas, metadatos, configuracion, persistencia y mutaciones de perfil.

## Archivos principales

- `authors.php`: bootstrap del subsistema de autores.
- `authors-routes.php`: rutas y resolucion de vistas publicas de autores.
- `authors-meta.php`: metadatos y lectura de datos de perfil.
- `authors-settings.php`: configuracion del dominio de autores.
- `authors-mutations.php`: cambios persistentes sobre perfiles o datos de autor.

## Flujo de entrada

El plugin carga el bootstrap de autores desde el backend principal. Las rutas publicas renderizan templates en `templates/authors/` y pueden depender de assets especificos.

## Reglas locales

- Mantener separada la lectura de datos de las mutaciones.
- Validar permisos antes de escribir metadatos de autor.
- No duplicar reglas de presentacion que ya vivan en templates o assets.

## Al modificar aqui

Leer tambien `templates/authors/README.md` y, si hay interaccion frontend, `assets/js/authors/README.md` cuando exista. Probar perfil/directorio publico y flujo autenticado si se toca edicion.

## Archivos relacionados

- `templates/authors/`
- `assets/js/authors/`
- `assets/css/authors/`
- `includes/books/`
