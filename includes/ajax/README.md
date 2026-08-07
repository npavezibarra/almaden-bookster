# Controladores AJAX (`includes/ajax/`)

Este directorio contiene los manejadores de peticiones AJAX y endpoints del lado del servidor para interactuar de forma asíncrona con la base de datos de WordPress.

## Archivos y Responsabilidades

*   **[ajax-save-book.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-save-book.php)**: 
    Recibe los datos serializados del libro y capítulos desde el editor. Realiza actualizaciones de ordenación, crea nuevos capítulos en la base de datos de WordPress, guarda el contenido Markdown y resuelve la concordancia entre IDs temporales cliente y definitivos del servidor.
*   **`ajax-credits-json.php` / `ajax-credits-persistence.php`**:
    Decodifican y persisten la configuración estructurada de créditos, manteniendo compatibilidad entre JSON proveniente de peticiones y valores recuperados desde `post_meta`.
*   **[ajax-settings.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings.php)**: 
    Manejadores AJAX primarios para guardar (`almaden_save_book_settings`) y obtener las configuraciones físicas de un libro.
*   **[ajax-settings-helper.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings-helper.php)**: 
    Lógica de obtención y definición del gran arreglo asociativo con los valores por defecto de maquetación (márgenes, tipografías de cabecera, pies de página, notas, etc.).
*   **[ajax-settings-templates.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings-templates.php)**: 
    Endpoints para la gestión de plantillas de maquetación. Permite listar presets guardados, guardarlos como archivos JSON en la carpeta de configuraciones o eliminarlos.
*   **[ajax-typst-pdf.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-typst-pdf.php)**:
    Endpoint autenticado que recompone el libro con Typst. Reinyecta las
    plantillas persistidas desde `_almaden_page_templates`, adjunta la
    configuración de portada, compila el PDF y expone headers de diagnóstico
    para geometría, flujo, apertura y resultados de plantillas.
*   **[ajax-cover.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-cover.php)**: 
    Guarda y recupera la configuración de capas, fondos y solapas del editor de portadas de libros.
*   **[ajax-publish.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-publish.php)**: 
    Manejador AJAX para cambiar el estado de publicación pública de un libro (hacer visible/oculto en la estantería pública).
*   **[ajax-user-prefs.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-user-prefs.php)**: 
    Permite guardar preferencias específicas del usuario del editor (como el zoom, atajos, etc.) en las preferencias de perfil de WordPress.
