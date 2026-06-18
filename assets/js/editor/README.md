# Directorio Content Editor (`assets/js/editor/`)

Este directorio contiene los controladores e interactividad de la interfaz del editor de libros principal.

## Archivos y Funcionalidades

- **`editor-core.js`**: Inicialización central de eventos, recuperación de datos y autosave periódico.
- **`editor-ui.js`**: Control visual de la interfaz (cambios de tema, modo de pantalla dividida, toasts).
- **`editor-toolbar.js`**: Acciones de formateo de texto del editor de markdown y subida de imágenes.
- **`editor-chapters.js`**: Operaciones CRUD y ordenamiento interactivo (Drag and Drop) de la barra lateral de capítulos.
- **`editor-virtualization.js`**: Renderizado perezoso de hojas PDF basado en IntersectionObserver.
- **`editor-settings-ui.js`**: Controladores visuales de pestañas y sliders en el panel de maquetación del libro.
- **`editor-settings-api.js`**: Peticiones de guardado de ajustes vía AJAX.
- **`editor-chapter-settings.js`**: Modal de edición de propiedades a nivel de capítulo individual.
- **`editor-markdown.js`**: Parseador liviano de markdown y renderizador de bloques en HTML.
