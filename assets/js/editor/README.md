# Directorio Content Editor (`assets/js/editor/`)

Este directorio contiene los controladores e interactividad de la interfaz del editor de libros principal.

## Archivos y Funcionalidades

- **`editor-core.js`**: Inicialización central de eventos, recuperación de datos y autosave periódico.
- **`editor-ui.js`**: Control visual de la interfaz (cambios de tema, modo de pantalla dividida, toasts).
- **`editor-toolbar.js`**: Acciones de formateo de texto del editor de markdown y subida de imágenes.
- **`editor-chapters.js`**: Operaciones CRUD y ordenamiento interactivo (Drag and Drop) de la barra lateral de capítulos.
- **`editor-virtualization.js`**: Renderizado perezoso de hojas PDF basado en IntersectionObserver.
- **`editor-settings-tabs.js`**: Controladores visuales de navegación de pestañas. Implementa guardas de *nullish coalescing* (`??`) para preservar rigurosamente configuraciones en cero (ej. paddings/márgenes).
- **`editor-settings-fields.js`**: Lógica condicional de la UI para habilitar/deshabilitar opciones, así como la integración de selectores de color.
- **`editor-settings-credits.js`**: Lógica encargada de inyectar dinámicamente y administrar en tiempo real los inputs para la página de Créditos (reemplazando el antiguo `tab-credits.php`).
- **`editor-settings-templates.js`**: Controlador AJAX de UI y lógicas para aplicar y guardar plantillas personalizadas de ajustes.
- **`editor-settings-api.js`**: Peticiones de guardado global y silencioso de los ajustes vía AJAX. Extrae, purifica y serializa con precisión valores decimales (incluso si tienen coma).
- **`editor-chapter-settings.js`**: Modal de edición de propiedades a nivel de capítulo individual.
- **`editor-markdown.js`**: Parseador liviano de markdown y renderizador de bloques en HTML.
