# Blog Post Module (`modules/blog-post/`)

## Responsabilidad

Modulo de publicaciones tipo blog dentro de Almaden Bookster, con editor, templates publicos, archivo y estilos propios.

## Archivos principales

- `init.php`: bootstrap del modulo.
- `includes/class-blog-post-editor.php`: logica del editor de posts.
- `includes/class-blog-post-template.php`: resolucion de templates del modulo.
- `templates/blog-post/`: wrappers y partials para archivo, single, comentarios y app.
- `assets/js/blog-post-editor.js`: comportamiento del editor.
- `assets/css/*.css`: estilos de editor, archivo y vista publica.

## Flujo de entrada

El plugin carga `init.php` cuando el modulo esta disponible. Desde ahi se registran assets, templates y logica especifica.

## Reglas locales

- Mantener el modulo encapsulado; no mover logica de blog al core salvo que sea realmente compartida.
- Separar editor, template publico y estilos.
- Respetar la regla global de 500 lineas por archivo.

## Al modificar aqui

Revisar los templates y assets del modulo completo. Validar vista de archivo, vista single y editor cuando el cambio toque presentacion o datos.

## Archivos relacionados

- `includes/frontend/`
- `templates/shell/`
