# Books Backend (`includes/books/`)

## Responsabilidad

Gestiona relaciones editoriales del libro, especialmente la asociacion libro-autor y su sincronizacion con metadatos legacy.

## Archivos principales

- `book-authors.php`: tabla de relacion libro-usuario, orden de autores, helpers de permisos y sincronizacion base.
- `book-authors-hooks.php`: migracion inicial y sincronizacion automatica cuando se guarda un libro.

## Flujo de entrada

El bootstrap del plugin registra estos helpers para que otros dominios consulten autoria, permisos y relaciones desde libros, autores, editoriales y pantallas de administracion.

## Reglas locales

- Mantener compatibilidad con metadatos legacy mientras existan consumidores antiguos.
- No cambiar el contrato de permisos sin revisar editoriales, dashboard, editor y paginas publicas.
- Las migraciones deben ser idempotentes.

## Al modificar aqui

Validar creacion/guardado de libro, autores asociados y cualquier vista que muestre autoria publica. Si se cambia esquema o sincronizacion, revisar hooks de activacion.

## Archivos relacionados

- `includes/authors/`
- `includes/publishers/`
- `templates/authors/`
- `templates/ebook/`
