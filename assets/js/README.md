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
└── almaden-shortcodes.js # Procesamiento de shortcodes comunes
```

---

## 1. Módulos de Administración (`assets/js/admin/`)

- **`admin-fonts-page.js`**: 
  Interactividad para la pantalla de configuración en el WP Admin Dashboard, concretamente para buscar, instalar, probar Google Drive y desinstalar archivos tipográficos.
- **`booklist-ui.js`**: 
  Controla el dashboard o taller de listado de libros (crear nuevo libro, duplicar, eliminar, publicar en el Ebook Store).

---

## 2. Componentes del Editor (`assets/js/editor/`)

- **`editor-core.js`**: 
  El cerebro de la aplicación. Maneja la inicialización principal (`window.onload`), la gestión del estado global (`bookState`), y la declaración del `initEventListeners` central.
- **`editor-ui.js`**: 
  Controla de forma exclusiva la interfaz de usuario. Maneja cambios de tema visual (claro, sepia, oscuro), los modos de vista, la barra lateral de capítulos, y el sistema de notificaciones (Toasts).
- **`editor-toolbar.js`**: 
  Lógica de la barra de formato superior. Inserta textos envueltos (negrita, cursiva), procesa imágenes (Uploader Media), imágenes de paridad y altera tamaños/fuentes de texto en el editor.
- **`editor-chapters.js`**: 
  Controla el panel lateral izquierdo. Maneja la creación, eliminación, reordenamiento (drag and drop) de capítulos, y el cambio del capítulo "activo".
- **`editor-virtualization.js`**: 
  Optimiza el rendimiento inicializando la Virtualización del PDF en el DOM vía IntersectionObserver, limitando los elementos inyectados a lo visible.
- **`editor-settings-ui.js`**: 
  Mapea y controla la visualización de los controles interactivos del modal global de ajustes de maquetación.
- **`editor-settings-api.js`**: 
  Peticiones AJAX de guardado y carga del modal global de ajustes, actualizando `bookState`.
- **`editor-chapter-settings.js`**: 
  Controla los ajustes específicos individuales del capítulo activo.
- **`editor-markdown.js`**: 
  Parseador de markdown simple que traduce el texto a etiquetas HTML semánticas antes de enviarlo al motor PDF.

---

## 3. Motor de Renderizado PDF (`assets/js/pdf/`)

- **`editor-pdf-compiler.js`**: 
  El orquestador de paginación. Gestiona el ciclo de renderizado secuencial.
- **`editor-pdf-compiler-dimensions.js`**: 
  Cálculos de escala física de hojas en px/mm y layouts de página.
- **`editor-pdf-compiler-parity.js`**: 
  Reglas de asignación de paridad (páginas pares e impares, imágenes de paridad y márgenes dinámicos).
- **`editor-pdf-dom.js`**: 
  Generador del esqueleto virtual de las hojas (Headers, Footers, Page-numbering, y Footnote wrappers).
- **`editor-pdf-pagination.js`**: 
  Algoritmos de detección de desborde y división de bloques de párrafos en múltiples páginas.
- **`editor-pdf-html.js`**: 
  Pre-procesador del HTML base de capítulos, agregando índices, subtítulos y letras capitales.
- **`editor-pdf-styles.js`** / **`editor-pdf-styles-base.js`** / **`editor-pdf-styles-typography.js`**: 
  Construcción inyectada de stylesheets CSS dinámicos según los ajustes del libro.
- **`editor-pdf-export.js`**: 
  Prepara la pantalla de impresión completa para llamar a `window.print()`.

---

## 4. Lector Público de Ebook (`assets/js/reader/`)

- **`reader-app.js`**: Inicializador básico, render de shortcodes e índice flotante.
- **`reader-prefs.js`**: Preferencias persistentes en LocalStorage (fuente, tamaño, tema).
- **`reader-styles.js`**: Generador e inyector de CSS dinámico para la experiencia aislada de lectura.
- **`reader-navigation.js`**: Navegación por páginas físicas en modo "Flip" doble página o scroll continuo.
