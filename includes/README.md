# Directorio de Lógica PHP Backend (`includes/`)

Este directorio concentra la logica de negocio de WordPress: CPTs, AJAX, frontend publico, exportadores, integraciones y helpers compartidos.

## Leer primero

Si vas a tocar quizzes, empieza por:

1. [learni-integration.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/learni-integration.php)
2. [frontend.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php)
3. [ajax/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/README.md)

## Estructura clave

*   **[cpt.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/cpt.php)**: registra `almaden-books` y `book_chapter`.
*   **[frontend.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php)**: rutas publicas, shortcodes y carga del quiz builder.
*   **[learni-integration.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/learni-integration.php)**: puente con Learni, quiz por libro, quiz por capitulo, persistencia y helpers de metadatos.
*   **[editor-data-loader.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/editor-data-loader.php)**: carga inicial del editor de libros.
*   **[book-import-export.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/book-import-export.php)**: respaldo y clonacion JSON/ZIP.
*   **[epub-export.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/epub-export.php)**: exportador EPUB.
*   **[gdrive-client.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/gdrive-client.php)**: integracion con Google Drive.
*   **[highlights.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/highlights.php)** y **[highlight-comments.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/highlight-comments.php)**: anotaciones del reader.

## Estructura AJAX

*   [includes/ajax/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/)
