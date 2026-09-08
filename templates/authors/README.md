# Authors Templates (`templates/authors/`)

## Responsabilidad

Renderiza el directorio y las paginas publicas de autores, separando wrappers de app page y contenido compatible con paginas publicas.

## Archivos principales

- `authors-app.php`: wrapper app para la experiencia de autores.
- `authors-page.php`: contenido del directorio/listado de autores.
- `author-app.php`: wrapper app para perfil individual.
- `author-page.php`: contenido del perfil publico de autor.

## Flujo de entrada

Las rutas de `includes/authors/` seleccionan estos templates segun listado o perfil individual. El shell y la navegacion compartida deben mantenerse consistentes con las reglas frontend del plugin.

## Reglas locales

- Cada contenedor principal debe tener un ID unico.
- Mantener separada la estructura de pagina del contenido parcial.
- No introducir logica de mutacion en templates.
- Las paginas publicas no deben exigir login.

## Al modificar aqui

Revisar `includes/authors/README.md`, estilos en `assets/css/authors/` y JS en `assets/js/authors/` si aplica. Validar directorio, perfil individual y comportamiento anonimo.

## Archivos relacionados

- `includes/authors/`
- `assets/js/authors/`
- `assets/css/authors/`
- `templates/shell/`
