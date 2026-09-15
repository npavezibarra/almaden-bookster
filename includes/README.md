# Directorio de Lógica PHP Backend (`includes/`)

Este directorio concentra la lógica de negocio de WordPress del plugin, organizada de forma modular para evitar archivos desordenados o sobredimensionados.

## Estructura de Subcarpetas

### 1. 📂 [cpt/](cpt) (Custom Post Types)
*   **[cpt.php](cpt/cpt.php)**: Registra los tipos de contenido personalizados `almaden-books` y `book_chapter`.

### 2. 📂 [integrations/](integrations) (Plugins Externos)
*   **[learni-integration.php](integrations/learni-integration.php)**: Helper principal y llamadas API/metadatos de la integración con el plugin Learni.
*   **[learni-integration-actions.php](integrations/learni-integration-actions.php)**: Hooks de administración y formularios/callbacks para guardar quizzes.

### 3. 📂 [io/](io) (Input/Output & Cloud Services)
*   **`book-import-export.php`**: Copias de seguridad y clonación del contenido del libro (JSON / ZIP).
*   **[epub-export.php](io/epub-export.php)**: Exportación de libros al formato estándar ePub.
*   **[gdrive-client.php](io/gdrive-client.php)**: Cliente de comunicación OAuth2 con Google Drive API para almacenar respaldos.

### 4. 📂 [admin/](admin) (Configuraciones de Administración)
*   **[admin-pages.php](admin/admin-pages.php)**: Subpagina de rutas internas del plugin, incluida la configuracion del creador de libros.
*   **[admin-fonts.php](admin/admin-fonts.php)**: Lógica de instalación, descarga de archivos `.ttf` y guardado local de tipografías de Google Fonts.
*   **[admin-settings.php](admin/admin-settings.php)**: Guardado de credenciales del cliente de Google Drive.

### 4b. 📂 [frontend/](frontend) (Routing y paginas publicas)
*   **[pages.php](frontend/pages.php)**: Configuracion de slugs, URLs y sincronizacion de la pagina interna del creador.
*   **[access-control.php](frontend/access-control.php)**: Utilidades de compra y permisos para el catálogo público, la ficha individual y los hooks de lectura.
*   **[shell-access.php](frontend/shell-access.php)**: Permisos de las app pages internas del shell. Si un usuario anónimo entra a una pantalla privada como `Taller`, la lógica lo redirige a la URL pública con el modal de login abierto en lugar de mostrar la pantalla de acceso denegado.
*   **[reading-stats.php](frontend/reading-stats.php)**: Sincronizacion y agregados para la pantalla `My Reading Stats`.

### 4c. 📂 [database/](database) (Esquema SQL compartido)
*   **[schema.php](database/schema.php)**: Helpers comunes para verificar tablas, ejecutar `dbDelta()` y centralizar el instalador de esquema del plugin.

### 4d. 📂 [publishers/](publishers) (Editoriales)
*   **[publishers.php](publishers/publishers.php)**: Creación de las tablas base para editoriales y membresías, más helpers para persistir `publisher_id` en libros y para renderizar la ruta pública `/editorial/{slug}`.
*   **[permissions.php](publishers/permissions.php)**: Reglas de membresía y validación de acceso para editoriales y libros.
*   **[settings.php](publishers/settings.php)**: Panel público `/editorial/{slug}/ajustes` con persistencia de configuración avanzada en JSON.
*   **[onboarding.php](publishers/onboarding.php)**: Landing pública `/crear-editorial`, wizard de alta, creación de cuenta y redirección al taller.
*   **[tour.php](publishers/tour.php)**: Estado del onboarding editorial, checklist inicial y handlers para completar la guía del taller.

### 4e. 📂 [books/](books) (Relaciones editoriales de libro)
*   **[book-authors.php](books/book-authors.php)**: Tabla de relación libro-usuario, orden de autores, sincronizacion con metadatos legacy y helpers de permisos por autor.
*   **[book-authors-hooks.php](books/book-authors-hooks.php)**: Migracion inicial y sincronizacion automatica cuando se guarda un libro.

### 4f. 📂 [payments/](payments) (WooCommerce)
*   **[woocommerce-integration.php](payments/woocommerce-integration.php)**: Bootstrap compatible de la integración.
*   **`woocommerce-relation.php`**: Persistencia de relaciones libro-producto.
*   **`woocommerce-products.php`**: Productos, variaciones y enlaces de compra.
*   **`woocommerce-access.php`**: Navegación y autorización de lectura.
*   **`woocommerce-hooks.php`**: Hooks de producto, carrito, checkout y confirmación.
*   **`woocommerce-provider.php`**: Registro del adaptador WooCommerce.

### 4g. 📂 [progress/](progress) (Quizzes y avance)
*   **[quiz-progress.php](progress/quiz-progress.php)**: Persistencia de intentos, cálculo del avance del libro por sesión y reset habilitado solo cuando todos los quizzes están completos.

### 5. 📂 [reader/](reader) (Lógica de Lectura)
*   **[highlights.php](reader/highlights.php)**: Registro de resaltados de texto y permisos de acceso del lector.
*   **[highlight-comments.php](reader/highlight-comments.php)**: Lógica de comentarios sociales y notas en los textos resaltados.

### 6. 📂 [helpers/](helpers) (Utilidades Comunes)
*   **[cover-thumbnail.php](helpers/cover-thumbnail.php)**: Generación dinámica del marcado HTML/CSS de las miniaturas de portada.
*   **[crypto.php](helpers/crypto.php)**: Métodos de encriptación y desencriptación para credenciales seguras (Google Drive).
*   **[editor-data-loader.php](helpers/editor-data-loader.php)**: Carga de metadatos iniciales requeridos por el editor web.

---

## Otras Estructuras

*   📂 **[ajax/](ajax)**: Procesamiento de peticiones AJAX asíncronas desde el editor.
*   📂 **[templates/](templates)**: Configuración JSON por defecto del editor de libros.

* frontend.php
