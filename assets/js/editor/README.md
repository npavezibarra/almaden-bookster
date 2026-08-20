# Arquitectura del Content Editor (`assets/js/editor/`)

Este directorio contiene la arquitectura modular en JavaScript vanilla que impulsa el editor interactivo de libros de **Almaden Bookster**. Diseñado bajo un estricto principio de modularidad y bajo la regla de que ningún archivo individual debe exceder las **500 líneas de código**, este sistema orquesta la interfaz del usuario, la toolbar de formato, el motor de parsing de Markdown, la sincronización de ajustes y la compilación/paginación dinámica del PDF mediante Paged.js.

## Fuente canonica y preview PDF

El contenido RAW del capitulo es la unica fuente canonica. La vista generada por
Paged.js es un derivado de solo lectura y nunca se serializa de vuelta al estado
del libro. Esta separacion garantiza que los saltos de pagina, guiones visuales y
clones internos de Paged.js no puedan eliminar espacios, tags ni texto original.

- El usuario edita el textarea RAW.
- La toolbar aplica formatos sobre el RAW.
- Paged.js recibe un clon HTML continuo con hifenacion exclusiva de render.
- Guardar sincroniza el textarea, no los fragmentos `.pagedjs_page`.
- La integridad se comprueba por bloque antes de permitir la exportacion.

Los archivos que participan en este flujo son:

- [editor-visual-editor.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-visual-editor.js)
- [editor-visual-session.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-visual-session.js)
- [editor-chapters-utils.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-utils.js)
- [editor-chapters-save.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-save.js)
- [editor-chapters-actions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-actions.js)
- [editor-chapters-sidebar.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-sidebar.js)
- [editor-pdf-html.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/core/editor-pdf-html.js)
- [editor-style.css](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/css/editor-style.css)

---

## Flujo General y Arquitectura

```mermaid
graph TD
    %% Núcleo y UI
    Core[editor-core.js] --> UI[editor-ui.js]
    Core --> Chapters[editor-chapters-*]
    
    %% Gestión de Capítulos y Ajustes
    Chapters --> ChapterSettings[editor-chapter-settings-*]
    Chapters --> SettingsAPI[editor-settings-api.js]
    
    %% Barra de Herramientas e Inputs
    Toolbar[editor-toolbar.js] --> Markdown[editor-markdown.js]
    Core --> Toolbar
    
    %% Configuración General
    SettingsAPI --> SettingsTabs[editor-settings-tabs.js]
    SettingsTabs --> SettingsFields[editor-settings-fields.js]
    SettingsTabs --> SettingsCredits[editor-settings-credits.js]
    SettingsTabs --> SettingsTemplates[editor-settings-templates.js]
    
    %% Renderizado y Rendimiento
    PDFCompiler[pdf/editor-pdf-compiler.js] -.-> Core
    PDFVirtualizer[editor-virtualization.js] -.-> UI
```

---

## Módulos y Responsabilidades

### 1. Inicialización y Estado Central

*   **[editor-core.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-core.js)**
    *   **Responsabilidad**: Punto de entrada de la aplicación, configuración de observadores del editor, registro de cursor y bindings iniciales.
    *   **Variables Globales**:
        *   `window.editorLastSelection`: Almacena el inicio y fin de la selección activa en el textarea del editor.
        *   `window.currentPreviewMode`: `'active'` (previsualizar solo el capítulo actual) o `'full'` (previsualizar el libro entero).
    *   **Funciones Clave**:
        *   [trackEditorSelection](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-core.js#L5): Captura la posición exacta de la selección del cursor en el textarea del editor.
        *   [initEventListeners](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-core.js#L104): Bindea escuchas a entradas de texto, redimensionamiento de pantalla y clicks de autoguardado manual.

*   **[editor-ui.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js)**
    *   **Responsabilidad**: Controla el diseño visual del workspace (modos de pantalla dividida, solo editor o solo visor PDF), transiciones de tema y la calibración métrica en pantalla.
    *   **Funciones Clave**:
        *   [setViewMode](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js#L4): Conmuta clases CSS para cambiar la vista (`split`, `edit`, `preview`).
        *   [changeTheme](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js#L39): Cambia el esquema visual global (`light`, `sepia`, `dark`).
        *   [toggleSpreadView](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js#L78): Alterna la visualización de una sola página con la de doble página encarada (spread layout).
        *   [renderRuler](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js#L174): Dibuja y calibra la regla física milimétrica. En base al modo de vista (spread o single page), calcula el punto cero exactamente alineado con el lomo central divisor de Paged.js.
        *   [showToast](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-ui.js#L109): Dispara notificaciones flotantes temporales con animaciones.

---

### 2. Gestión de Capítulos y Persistencia

*   **Módulos de capítulos**
    *   **Responsabilidad**: Operaciones CRUD sobre los capítulos, ordenación Drag and Drop en el panel lateral, cálculo optimizado de palabras y el mecanismo asíncrono de paginación de fondo. La compatibilidad histórica se mantiene mediante `editor-chapters.js`, que hoy solo actúa como stub.
    *   **Funciones Clave**:
        *   [editor-chapters-utils.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-utils.js): Recuento de palabras, extractos breves y helpers de DOM.
        *   [editor-chapters-sidebar.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-sidebar.js): Render del sidebar de capítulos y reordenación visual con drag and drop.
        *   [editor-chapters-actions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-actions.js): Carga del capítulo activo, selección, creación, borrado y movimiento.
        *   [editor-chapters-save.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters-save.js): Autoguardado, serialización AJAX y `window.calculateAllPagesBackground`.

*   **[editor-chapter-settings-guide.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapter-settings-guide.js)**, **[editor-chapter-settings-labels.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapter-settings-labels.js)**, **[editor-chapter-settings-controls.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapter-settings-controls.js)** y **[editor-chapter-settings-modal.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapter-settings-modal.js)**:
    *   **Responsabilidad**: El subsistema de ajustes por capítulo quedó dividido para respetar el límite de 500 líneas y aislar responsabilidades.
    *   **Funciones Clave**:
        *   `editor-chapter-settings-guide.js`: guía de nomenclatura y modal de ayuda copiable.
        *   `editor-chapter-settings-labels.js`: texto dinámico del modal según capítulo normal, índice o créditos.
        *   `editor-chapter-settings-controls.js`: toggles de UI, tabs internas y uploader de imágenes del capítulo.
        *   `editor-chapter-settings-modal.js`: apertura, cierre y guardado del modal del capítulo activo.
        *   `editor-chapter-settings.js`: stub de compatibilidad histórica, ya sin lógica activa.

    *   **Nota de imagen de capítulo**: el modo `image_inner` usa como referencia el ancho total de la página, incluyendo bleed. Por eso el 100% del slider significa "de borde exterior a borde exterior", no el content box recortado por márgenes. El modo `image_full_page` conserva el ancho completo de la imagen y solo deja que la diferencia de proporción se resuelva en vertical.

---

### 3. Barra de Herramientas y Markdown

*   **[editor-toolbar.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js)**
    *   **`toolbar/toolbar-image-media-library.js`**: Biblioteca inline para listar y subir imágenes del libro sin abrir un segundo modal.
    *   **`toolbar/toolbar-image-viewport-ui.js`**, **`toolbar/toolbar-image-viewport-state.js`** y **`toolbar/toolbar-image-viewport-drag.js`**: Estado, interfaz y arrastre para recortar y encuadrar la imagen dentro del mismo flujo.
    *   **`toolbar/toolbar-image-layout.js`**: Edición contextual desde RAW, aplicación visual de altura/márgenes y apertura por `blockId` desde los overlays del PDF.
    *   **Responsabilidad**: Formateo de texto inline, inserción de marcadores y manejo de los diálogos de carga de la biblioteca multimedia (`wp.media`) de WordPress.
    *   **Funciones Clave**:
        *   [wrapText](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js#L4): Envuelve la selección o el cursor con prefijos y sufijos Markdown (ej. `**` para negritas), restaurando automáticamente el foco al editor.
        *   [addPrefix](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js#L35): Inserta prefijos a nivel de inicio de línea (listas, cabeceras, blockquotes).
        *   [openMediaUploader](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js#L57) / [openParityImageUploader](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-toolbar.js#L92): Instancia la interfaz de WordPress Media para insertar etiquetas de imagen personalizadas en el cursor o asociar imágenes de paridad al capítulo actual.

*   **[editor-markdown.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-markdown.js)**
    *   **Responsabilidad**: Compilador de alto rendimiento para convertir sintaxis Markdown a HTML estructurado y semántico compatible con el motor de Paged.js.
    *   **Mecanismo de Parseo**:
        1. **Preservación Multimedia**: Extrae etiquetas nativas `<img>`, bloques `[html]` y shortcodes estructurales (`[box]`, `[align]`, etc.) mediante expresiones regulares antes de cualquier escape, inyectando marcadores de posición temporales (ej. `%%IMG_PLACEHOLDER_N%%`).
        2. **Escape Seguro**: Escapa caracteres reservados HTML del cuerpo de texto.
        3. **Formateo Inline**: Compila negritas (`**`), cursivas (`*`), subrayados (`<u>`) y shortcodes de traducción inline.
        4. **Formateo de Bloque**: Analiza línea por línea para encapsular elementos en `<h1>`, `<h2>`, `<blockquote>`, `<ul>/<li>` o `<p>`.
        5. **Restauración de Bloques**: Restaura los placeholders a sus posiciones originales asegurándose de que el contenido HTML puro o los contenedores estructurales no queden rodeados erróneamente por etiquetas `<p>`.
        6. **Notas al Pie**: Resuelve definiciones inline del tipo `[^ref]` usando la etiqueta como referencia interna y genera la numeración visible de forma automática en el orden de aparición.

---

### 4. Configuración Global del Libro

*   **[editor-settings-api.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-api.js)**
    *   **Responsabilidad**: Serialización y validación profunda de la configuración del libro y sincronización con el servidor mediante peticiones AJAX.
    *   **Funciones Clave**:
        *   `window.savePDFSettings`: Recopila todos los campos del modal de ajustes, normaliza meticulosamente las comas decimales a puntos decimales para evitar fallas en el cálculo de dimensiones CSS del compilador PDF, y realiza una llamada al endpoint `almaden_save_book_settings`.

*   **[editor-settings-tabs.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-tabs.js)**
    *   **Responsabilidad**: Administra la navegación por formato (`PDF`, `eBook`, `General`), las secciones principales y los subtabs de Tipografía, Cabecera/Pie y Capítulos, además de cargar valores en los inputs.
    *   **Funciones Clave**:
        *   `switchTypographyTab`: alterna Cuerpo/Títulos y restablece Cuerpo al entrar en Tipografía.
        *   `switchHeaderFooterTab`: alterna Cabecera/Pie de página y restablece Cabecera al entrar en la sección.
        *   `switchChapterSettingsInnerTab`: controla Estructura, Prefijo, Título y Subtítulo.
        *   `window.populateSettingsForm`: Lee la configuración de `bookState.settings` e inyecta los valores actuales en el formulario del modal, inicializando fallbacks métricos seguros si no se encuentran valores definidos.

*   **[editor-settings-fields.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-fields.js)**
    *   **Responsabilidad**: Reglas y visibilidades condicionales en los campos de formulario del modal de configuración general.
    *   **Funciones Clave**:
        *   `toggleCustomPageFields`, `toggleCustomHeaderFields`, `updateUnitFields`: Modifican dinámicamente visibilidades de campos de tamaño de página, etiquetas de unidades métricas (`cm` o `in`) e inputs de texto personalizado para cabeceras y pies de página.

*   **[editor-settings-credits.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-credits.js)** (y sus submódulos)
    *   **Responsabilidad**: Lógica modularizada para la creación dinámica, persistencia de estado y serialización del modal de créditos (roles, colaboradores y logo). Dividido en submódulos para asegurar la mantenibilidad y respetar el límite de líneas:
        *   `-constants.js`: Opciones predefinidas, diccionarios y fallbacks tipográficos.
        *   `-utils.js`: Funciones de normalización de datos y parsing de estilos.
        *   `-config.js`: Lógica para rellenar vacíos con configuraciones por defecto y mapeos legacy.
        *   `-state.js`: Aislamiento del ciclo de persistencia AJAX (`creditsForceRemoteSave`, `creditsPersistRemoteConfig`).
        *   `-ui.js`: Únicamente constructores de strings HTML (Rows, Tabs, Modals).
        *   `-events.js`: Bindings puros de eventos del DOM en el namespace de créditos.
    *   **Funciones Clave**:
        *   `creditsPopulateForm` y `creditsBindRootEvents` (en el archivo principal): Punto de entrada y orquestación del subsistema de créditos.

*   **[editor-settings-templates.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-templates.js)**
    *   **Responsabilidad**: Permite guardar configuraciones globales como `Book Templates`, importarlas y alternar el subtab externo Estándar/Mis plantillas sin mezclar navegación y contenido dentro de una misma tarjeta.
    *   **Funciones Clave**:
        *   [loadBookTemplates](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-templates.js#L4): Solicita presets de formato persistidos en el repositorio de `Book Templates` y los dibuja en el modal.
        *   [applyBookTemplate](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-templates.js#L68): Carga los valores de un book template preestablecido y simula eventos de interacción del usuario en cada input para actualizar el estado global.

---

### 5. Rendimiento y Virtualización

*   **[editor-virtualization.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-virtualization.js)**
    *   **Responsabilidad**: Rendimiento del scroll interactivo en el modo de previsualización completa del libro (`'full'`) mediante Virtualización del DOM.
    *   **Mecanismo**:
        *   Usa `IntersectionObserver` con un margen de renderizado (`rootMargin: '1500px 0px'`).
        *   Cuando una página del PDF renderizada por Paged.js sale de los límites de intersección, su contenido interno es guardado en caché y reemplazado en el DOM por un nodo ligero (`.virtual-placeholder`), liberando memoria de renderizado y logrando un rendimiento fluido del scroll sobre libros de cientos de páginas. Al reingresar al área visible, su contenido original es restaurado de inmediato.

---

## Ciclos de Vida y Arquitecturas Críticas

### A. Ciclo de Autoguardado e Intercambio de IDs AJAX
Cuando el usuario escribe en el editor:
1. Se calcula el recuento de palabras local y se actualiza el estado en memoria `bookState`.
2. Se activa un timer de autosave debounced a 15 segundos.
3. Si el guardado es manual o inmediato:
   * Si está en previsualización de capítulo individual, se invoca a `window.calculateAllPagesBackground()` que calcula silenciosamente las páginas totales reales del libro entero usando un visor temporal invisible `#dummy-pdf-scroller`.
   * Se recopila el total de páginas y se envía vía AJAX con los capítulos serializados.
   * La respuesta de base de datos devuelve una lista de correspondencia de IDs temporales (`cap-xxxx`) a los IDs definitivos autogenerados por la base de datos.
   * El estado del editor reemplaza los IDs viejos por los nuevos en tiempo real para evitar que se pierdan las imágenes de paridad o metadatos tras agregar capítulos nuevos.

### B. Flujo de Paridad y Página Izquierda en Blanco
El control de la alineación de páginas pares e impares al iniciar un capítulo utiliza un mecanismo de **Named Pages** de Paged.js sin reestructurar el árbol DOM de forma artificial:
1. El compilador en [editor-pdf-compiler.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler.js) inyecta una sección vacía para la imagen de paridad `.chapter-parity-section-${id}` antes de la sección de contenido principal `.chapter-section-${id}` si el capítulo está configurado para iniciar en página impar (`odd`).
2. Se inyecta un spacer invisible en la primera página (`.book-start-dummy-page`) para evitar que Paged.js ignore la directiva de salto de página `break-before: left` sobre el primer elemento del flujo de render.
3. El spacer se oculta usando posicionamiento absoluto offscreen (`position: absolute; left: -9999px; visibility: hidden; width: 0; height: 0;`) para que no altere la visualización interactiva ni cause errores de cálculo en el método `.getBoundingClientRect()` de Paged.js.
4. En [editor-pdf-styles-base.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-base.js), la sección de paridad se enlaza a un Named Page específico configurado con `break-before: left; break-after: page;` y la sección del capítulo a `break-before: right;`, garantizando que el inicio físico del capítulo comience de forma natural a la derecha (impar) y la imagen a la izquierda (par).

---

## Directrices para Desarrolladores

> [!IMPORTANT]
> **Límite de Código**: Cualquier refactorización o incorporación de características debe respetar la modularidad de archivos de este directorio. Bajo ninguna circunstancia se debe permitir que un archivo supere las **500 líneas**. Si es necesario, utiliza la estructura de subarchivos o crea utilidades compartidas.

> [!TIP]
> **Layout Thrashing**: Para evitar cuellos de botella y parpadeos en el editor durante los eventos de teclado (`input`), nunca midas dimensiones del DOM (`offsetHeight`, `offsetWidth`, etc.) de forma directa en los loops de recuento de palabras o procesamiento. Utiliza lecturas y cachés de tamaño almacenados en memoria como hace [editor-chapters.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-chapters.js).

> [!WARNING]
> **Localización Decimal**: Al recuperar números desde el DOM en configuraciones físicas (márgenes, paddings, bleeding), siempre ejecuta `.replace(',', '.')` antes de realizar cálculos o de enviar la petición AJAX. Varias instalaciones locales o de producción configuradas en español ingresan comas para los decimales, lo cual provoca cálculos fallidos en CSS (ej. `2,5cm` es ignorado por Paged.js, mientras que `2.5cm` funciona correctamente).

* editor-visual-selection.js
