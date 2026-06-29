# Directorio de Lógica PHP Backend (`includes/`)

Este directorio contiene las clases y archivos PHP con las reglas de negocio de WordPress (CPTs, AJAX, cifrado, exportadores, y clientes de servicios).

## Estructura de Módulos AJAX
*   Manejadores AJAX principales: [includes/ajax/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/)

## Archivos y Responsabilidades

*   **[cpt.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/cpt.php)**: 
    Registra los tipos de contenido personalizados (Custom Post Types) del plugin: `almaden-books` (libros) y `book_chapter` (capítulos).
*   **[crypto.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/crypto.php)**: 
    Cifrado y descifrado simétrico de datos sensibles (como la clave privada de Google Drive) usando OpenSSL y la sal única de seguridad de WordPress (`SECURE_AUTH_KEY`).
*   **[editor-data-loader.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/editor-data-loader.php)**: 
    Cargador inicial de datos. Recupera el libro, sus capítulos, las configuraciones de maquetación de la base de datos y la lista de fuentes CDN preparadas para inyectar en el visualizador.
*   **[admin-fonts.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/admin-fonts.php)**: 
    Lógica de base de datos e instalación local de metadatos de fuentes tipográficas para la biblioteca del editor.
*   **[admin-settings.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/admin-settings.php)**: 
    Manejador AJAX y registro de los ajustes generales del plugin (ej: credenciales del servicio de sincronización con Google Drive).
*   **[gdrive-client.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/gdrive-client.php)**: 
    Cliente de integración con Google Drive API para subir archivos compilados y portadas.
*   **[epub-export.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/epub-export.php)**: 
    Exportador de eBooks. Construye paquetes zip con formato `.epub` estándar, compilando el manifest, la tabla de contenidos, los archivos HTML de capítulos y los metadatos de autoría.
*   **[book-import-export.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/book-import-export.php)**: 
    Sistema de respaldos y clonación de libros. Exporta y carga archivos JSON/ZIP estructurados con toda la metadata del proyecto.
*   **[cover-thumbnail.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/cover-thumbnail.php)**: 
    Procesamiento y generación de miniaturas (thumbnails) a partir de los datos de capas del editor de portadas.
*   **[frontend.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php)**: 
    Gestión de plantillas del lado del cliente y filtros del shortcode de visualización de libros públicos.
*   **[highlights.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/highlights.php)**: 
    Controlador AJAX y consultas de la base de datos para guardar, listar y eliminar subrayados (highlights) realizados en el eBook Reader.
*   **[highlight-comments.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/highlight-comments.php)**: 
    Controlador AJAX y base de datos para registrar, listar y eliminar comentarios vinculados a los subrayados (highlights) del eBook Reader.
