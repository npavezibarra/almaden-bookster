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
├── authors/        # Página de autores, perfiles y modal de edición
├── quiz-builder/   # Creador de evaluaciones interactivo (Quiz Builder)
└── almaden-shortcodes.js # Procesamiento de shortcodes comunes
```

---

## 🔗 Subcarpetas del Frontend
*   **Taller y Admin**: [assets/js/admin/](admin)
*   **Editor de Contenido**: [assets/js/editor/](editor)
*   **Motor PDF**: [assets/js/pdf/](pdf)
*   **Lector eBook**: [assets/js/reader/](reader)
*   **Diseñador de Portadas**: [assets/js/cover/](cover)
*   **Autores**: [assets/js/authors/](authors)
*   **Creador de Quizzes**: [assets/js/quiz-builder/](quiz-builder)
*   **Shortcodes Comunes**: [almaden-shortcodes.js](almaden-shortcodes.js)

---

## 1. Módulos de Administración ([admin/](admin))

*   **[admin-fonts-page.js](admin/admin-fonts-page.js)**:
    Interactividad para la pantalla de configuración en el WP Admin Dashboard, concretamente para buscar, instalar, probar Google Drive y desinstalar archivos tipográficos.
*   **[booklist-ui.js](admin/booklist-ui.js)**:
    Controla el dashboard o taller de listado de libros (crear nuevo libro, duplicar, eliminar, publicar en el Ebook Store).

---

## 2. Componentes del Editor ([editor/](editor))

*   **[editor-core.js](editor/editor-core.js)**:
    El cerebro de la aplicación. Maneja la inicialización principal (`window.onload`), la gestión del estado global (`bookState`), y la declaración del `initEventListeners` central.
*   **[editor-ui.js](editor/editor-ui.js)**:
    Controla de forma exclusiva la interfaz de usuario. Maneja cambios de tema visual (claro, sepia, oscuro), los modos de vista, la barra lateral de capítulos, y el sistema de notificaciones (Toasts).
*   **`editor-toolbar.js`**:
    Lógica de la barra de formato superior. Inserta textos envueltos (negrita, cursiva), procesa imágenes (Uploader Media), imágenes de paridad y altera tamaños/fuentes de texto en el editor.
*   **[editor-chapters.js](editor/editor-chapters.js)**:
    Controla el panel lateral izquierdo. Maneja la creación, eliminación, reordenamiento (drag and drop) de capítulos, y el cambio del capítulo "activo".
*   **[editor-virtualization.js](editor/editor-virtualization.js)**:
    Optimiza el rendimiento inicializando la Virtualización del PDF en el DOM vía IntersectionObserver, limitando los elementos inyectados a lo visible.
*   **[editor-settings-tabs.js](editor/editor-settings-tabs.js)**:
    Maneja los tres niveles de navegación de Ajustes del libro, incluidos los subtabs de Tipografía, Cabecera/Pie y Capítulos. Implementa coalescencia nula (`??`) para conservar configuraciones estables (e.g., permite márgenes exactamente en 0).
*   **[editor-settings-fields.js](editor/editor-settings-fields.js)**:
    Maneja la lógica condicional que muestra/oculta campos, además de la integración de color pickers y selección de imágenes del PDF.
*   **[editor-settings-credits.js](editor/editor-settings-credits.js)**:
    Controla dinámicamente la UI del editor para crear y modificar en tiempo real la página de créditos personalizada, prescindiendo del archivo estático PHP antiguo.
*   **[editor-settings-templates.js](editor/editor-settings-templates.js)**:
    Manejo y guardado de plantillas de ajustes y conexión UI/AJAX para cargarlas. Controla el subtab externo Estándar/Mis plantillas mediante `aria-selected` e `.is-active`.
*   **[editor-settings-api.js](editor/editor-settings-api.js)**:
    Peticiones AJAX de guardado y carga del modal global de ajustes, actualizando `bookState`. Serializa correctamente valores enteros y decimales.
*   **[editor-chapter-settings-guide.js](editor/editor-chapter-settings-guide.js)**,
    **[editor-chapter-settings-labels.js](editor/editor-chapter-settings-labels.js)**,
    **[editor-chapter-settings-controls.js](editor/editor-chapter-settings-controls.js)** y
    **[editor-chapter-settings-modal.js](editor/editor-chapter-settings-modal.js)**:
    Controlan los ajustes específicos individuales del capítulo activo y mantienen el modal dividido por responsabilidades.
*   **[editor-markdown.js](editor/editor-markdown.js)**:
    Parseador de markdown simple que traduce el texto a etiquetas HTML semánticas antes de enviarlo al motor PDF.

---

## 3. Motor de Renderizado PDF ([pdf/](pdf))

*   **`editor-pdf-compiler.js`**:
    El orquestador de paginación. Gestiona el ciclo de renderizado secuencial.
*   **`editor-pdf-compiler-dimensions.js`**:
    Cálculos de escala física de hojas en px/mm y layouts de página.
*   **`editor-pdf-compiler-parity.js`**:
    Regla de cierre: agrega la última página técnica cuando el libro debe terminar en una página par.
*   **`editor-pdf-pagination.js`**:
    Algoritmos de detección de desborde y división de bloques de párrafos en múltiples páginas.
*   **`editor-pdf-html.js`**:
    Pre-procesador del HTML base de capítulos, agregando índices, subtítulos y letras capitales.
*   **`editor-pdf-styles.js`** / **`editor-pdf-styles-base.js`** / **`editor-pdf-styles-typography.js`**:
    Construcción inyectada de stylesheets CSS dinámicos según los ajustes del libro.
*   **`editor-pdf-export.js`**:
    Prepara la pantalla de impresión completa para llamar a `window.print()`.

---

## 4. Lector Público de Ebook ([reader/](reader))

*   **[reader-app.js](reader/reader-app.js)**: Inicializador básico, render de shortcodes e índice flotante.
*   **[reader-prefs.js](reader/reader-prefs.js)**: Preferencias persistentes en LocalStorage (fuente, tamaño, tema).
*   **[reader-styles.js](reader/reader-styles.js)**: Generador e inyector de CSS dinámico para la experiencia aislada de lectura.
*   **[reader-navigation.js](reader/reader-navigation.js)**: Navegación por páginas físicas en modo "Flip" doble página o scroll continuo.
*   **[reader-quizzes.js](reader/reader-quizzes.js)**: Interceptor de navegación y reproductor interactivo de evaluaciones (quizzes) de Learni dentro del Ebook.

---

## 5. Módulos del Quiz Builder ([quiz-builder/](quiz-builder))

*   **[quiz-builder-app.js](quiz-builder/quiz-builder-app.js)**: Orquestador principal y núcleo del estado global (inicialización del payload, actualización de paneles de la barra lateral, guardado mediante AJAX, tabulador de UI y listeners de interacción global).
*   **[quiz-builder-editor.js](quiz-builder/quiz-builder-editor.js)**: Inicialización de preguntas por defecto, inserción y duplicación de slides, remoción de respuestas, binding de estado y método principal `renderPreview()`.
*   **[quiz-builder-parser.js](quiz-builder/quiz-builder-parser.js)**: Extracción de JSON desde texto plano, normalización de payloads y Question Recovery Parser inteligente si el JSON está incompleto.
*   **[quiz-builder-preview.js](quiz-builder/quiz-builder-preview.js)**: Motor interactivo offline para previsualizar el quiz simulando al estudiante (`startInteractiveQuizPreview()`).

* publishers
* vendor
