# Directorio JS (`assets/js/`) - Arquitectura Frontend Reorganizada

Este directorio contiene todo el código de JavaScript de la aplicación. Siguiendo el principio de **Modularidad Extrema** (con un límite estricto de <500 líneas por archivo), los archivos se agrupan en subcarpetas funcionales:

## Estructura de Directorios

```
assets/js/
├── admin/          # Panel de administración de WordPress y Taller
├── editor/         # Interfaz de edición del libro (Content Editor)
├── pdf/            # Motor de renderizado PDF (Virtual Pagination Engine)
├── reader/         # Lector público de eBooks (Web Reader)
├── cover/          # Editor de Portadas (Bookster Cover Editor)
├── quiz-builder/   # Creador de evaluaciones interactivo (Quiz Builder)
└── almaden-shortcodes.js # Procesamiento de shortcodes comunes
```

---

## 🔗 Subcarpetas del Frontend
*   **Taller y Admin**: [assets/js/admin/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/admin/)
*   **Editor de Contenido**: [assets/js/editor/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/)
*   **Motor PDF**: [assets/js/pdf/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/)
*   **Lector eBook**: [assets/js/reader/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/)
*   **Diseñador de Portadas**: [assets/js/cover/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/)
*   **Creador de Quizzes**: [assets/js/quiz-builder/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/)
*   **Shortcodes Comunes**: [almaden-shortcodes.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/almaden-shortcodes.js)

---

## 1. Módulos de Administración ([admin/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/admin/))

*   **[admin-fonts-page.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/admin/admin-fonts-page.js)**: 
    Interactividad para la pantalla de configuración en el WP Admin Dashboard, concretamente para buscar, instalar, probar Google Drive y desinstalar archivos tipográficos.
*   **[booklist-ui.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/admin/booklist-ui.js)**: 
    Controla el dashboard o taller de listado de libros (crear nuevo libro, duplicar, eliminar, publicar en el Ebook Store).

---

## 2. Componentes del Editor ([editor/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/))

*   **[editor-core.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-core.js)**: 
    El cerebro de la aplicación. Maneja la inicialización principal (`window.onload`), la gestión del estado global (`bookState`), y la declaración del `initEventListeners` central.
*   **[editor-ui.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js)**: 
    Controla de forma exclusiva la interfaz de usuario. Maneja cambios de tema visual (claro, sepia, oscuro), los modos de vista, la barra lateral de capítulos, y el sistema de notificaciones (Toasts).
*   **[editor-toolbar.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js)**: 
    Lógica de la barra de formato superior. Inserta textos envueltos (negrita, cursiva), procesa imágenes (Uploader Media), imágenes de paridad y altera tamaños/fuentes de texto en el editor.
*   **[editor-chapters.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters.js)**: 
    Controla el panel lateral izquierdo. Maneja la creación, eliminación, reordenamiento (drag and drop) de capítulos, y el cambio del capítulo "activo".
*   **[editor-virtualization.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-virtualization.js)**: 
    Optimiza el rendimiento inicializando la Virtualización del PDF en el DOM vía IntersectionObserver, limitando los elementos inyectados a lo visible.
*   **[editor-settings-tabs.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-tabs.js)**: 
    Maneja el control visual de pestañas. Implementa coalescencia nula (`??`) para conservar configuraciones estables (e.g., permite márgenes exactamente en 0).
*   **[editor-settings-fields.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-fields.js)**: 
    Maneja la lógica condicional que muestra/oculta campos, además de la integración de color pickers y selección de imágenes del PDF.
*   **[editor-settings-credits.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-credits.js)**: 
    Controla dinámicamente la UI del editor para crear y modificar en tiempo real la página de créditos personalizada, prescindiendo del archivo estático PHP antiguo.
*   **[editor-settings-templates.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-templates.js)**: 
    Manejo y guardado de plantillas de ajustes y conexión UI/AJAX para cargarlas.
*   **[editor-settings-api.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-api.js)**: 
    Peticiones AJAX de guardado y carga del modal global de ajustes, actualizando `bookState`. Serializa correctamente valores enteros y decimales.
*   **[editor-chapter-settings.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapter-settings.js)**: 
    Controla los ajustes específicos individuales del capítulo activo.
*   **[editor-markdown.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-markdown.js)**: 
    Parseador de markdown simple que traduce el texto a etiquetas HTML semánticas antes de enviarlo al motor PDF.

---

## 3. Motor de Renderizado PDF ([pdf/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/))

*   **[editor-pdf-compiler.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-compiler.js)**: 
    El orquestador de paginación. Gestiona el ciclo de renderizado secuencial.
*   **[editor-pdf-compiler-dimensions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-compiler-dimensions.js)**: 
    Cálculos de escala física de hojas en px/mm y layouts de página.
*   **[editor-pdf-compiler-parity.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-compiler-parity.js)**: 
    Reglas de asignación de paridad (páginas pares e impares, imágenes de paridad y márgenes dinámicos).
*   **[editor-pdf-dom.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-dom.js)**: 
    Generador del esqueleto virtual de las hojas (Headers, Footers, Page-numbering, y Footnote wrappers).
*   **[editor-pdf-pagination.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-pagination.js)**: 
    Algoritmos de detección de desborde y división de bloques de párrafos en múltiples páginas.
*   **[editor-pdf-html.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-html.js)**: 
    Pre-procesador del HTML base de capítulos, agregando índices, subtítulos y letras capitales.
*   **[editor-pdf-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/styles/editor-pdf-styles.js)** / **[editor-pdf-styles-base.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/styles/editor-pdf-styles-base.js)** / **[editor-pdf-styles-typography.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/styles/editor-pdf-styles-typography.js)**: 
    Construcción inyectada de stylesheets CSS dinámicos según los ajustes del libro.
*   **[editor-pdf-export.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/export/editor-pdf-export.js)**: 
    Prepara la pantalla de impresión completa para llamar a `window.print()`.

---

## 4. Lector Público de Ebook ([reader/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/))

*   **[reader-app.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-app.js)**: Inicializador básico, render de shortcodes e índice flotante.
*   **[reader-prefs.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-prefs.js)**: Preferencias persistentes en LocalStorage (fuente, tamaño, tema).
*   **[reader-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-styles.js)**: Generador e inyector de CSS dinámico para la experiencia aislada de lectura.
*   **[reader-navigation.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-navigation.js)**: Navegación por páginas físicas en modo "Flip" doble página o scroll continuo.

---

## 5. Módulos del Quiz Builder ([quiz-builder/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/))

*   **[quiz-builder-app.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-app.js)**: Orquestador principal y núcleo del estado global (inicialización del payload, actualización de paneles de la barra lateral, guardado mediante AJAX, tabulador de UI y listeners de interacción global).
*   **[quiz-builder-editor.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-editor.js)**: Inicialización de preguntas por defecto, inserción y duplicación de slides, remoción de respuestas, binding de estado y método principal `renderPreview()`.
*   **[quiz-builder-parser.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-parser.js)**: Extracción de JSON desde texto plano, normalización de payloads y Question Recovery Parser inteligente si el JSON está incompleto.
*   **[quiz-builder-preview.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-preview.js)**: Motor interactivo offline para previsualizar el quiz simulando al estudiante (`startInteractiveQuizPreview()`).
