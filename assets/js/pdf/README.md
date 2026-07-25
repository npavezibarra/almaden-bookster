# Motor de Renderizado PDF (`assets/js/pdf/`)

Este directorio alberga la arquitectura del motor de maquetación, paginación y exportación a PDF de **Almaden Bookster**. Diseñado sobre la API y el ciclo de vida de **Paged.js**, este motor transforma contenido HTML continuo en pliegos de páginas listos para impresión física, respetando estándares del W3C Paged Media.

En cumplimiento estricto de las directrices del proyecto (**límite de 500 líneas por archivo**), las hojas de estilo y el compilador están divididos en módulos independientes y altamente cohesivos.

---

## Módulos y Responsabilidades

```mermaid
graph TD
    Flow[editor-pdf-chapter-flow.js] --> Parity[editor-pdf-compiler-parity.js]
    Flow --> DOMFactory[editor-pdf-dom.js]
    Flow --> Compiler[editor-pdf-compiler.js]
    Compiler --> Dimensions[editor-pdf-compiler-dimensions.js]
    Compiler --> HTMLProc[editor-pdf-html.js]
    
    Styles[editor-pdf-styles.js] --> StylesBase[editor-pdf-styles-base.js]
    Styles --> StylesChapters[editor-pdf-styles-chapters.js]
    Styles --> StylesTypos[editor-pdf-styles-typography.js]
    
    Export[editor-pdf-export.js] --> Compiler
```

### 1. Compilación y Flujo de Paginación

*   **[editor-pdf-compiler.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler.js)**
    *   **Responsabilidad**: Orquestador central del flujo. Toma el estado global (`bookState`), concatena el contenido del libro o capítulo activo y lanza la instancia de `Paged.Previewer` para procesar el layout.
    *   **Funciones Clave**:
        *   [compilePDFPreview](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler.js#L312): Función encolada y libre de condiciones de carrera para programar renderizaciones secuenciales del visor.
        *   [_compilePDFPreviewInternal](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler.js#L8): Ejecuta la inicialización de buffers, generación de HTML continuo y mapeo de metadatos de inicio de capítulos.

*   **[editor-pdf-compiler-dimensions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler-dimensions.js)**
    *   **Responsabilidad**: Lógica de conversión de medidas del libro. Traduce tamaños estándar (A4, Letter, Custom) y márgenes desde la unidad de ajuste (`cm` o `in`) a dimensiones físicas de pantalla en píxeles.
    *   **Funciones Clave**:
        *   [calculatePageDimensions](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler-dimensions.js#L7): Retorna anchos, altos, factores de conversión y la altura máxima disponible de contenido por página.

*   **[editor-pdf-chapter-flow.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-chapter-flow.js)**
    *   **Responsabilidad**: Centraliza el modo efectivo de apertura de capítulos y la paridad base para evitar discrepancias entre compilador, DOM y estilos.
    *   **Funciones Clave**:
        *   [getEffectiveOpeningPageMode](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-chapter-flow.js#L7): Resuelve si un capítulo abre con imagen, blanco intencional o sin página previa.
        *   [chapterHasOpeningPage](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-chapter-flow.js#L21): Indica si un capítulo debe reservar una página de apertura separada.
        *   [getChapterStartParity](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-chapter-flow.js#L26): Resuelve la paridad inicial (odd/even) según el capítulo y la configuración global.

*   **[editor-pdf-compiler-parity.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-compiler-parity.js)**
    *   **Responsabilidad**: Inserta páginas en blanco lógicas cuando un capítulo necesita cumplir una paridad específica de arranque (ej.: comenzar a la derecha/odd).

---

### 2. Estructura de DOM y HTML

*   **[editor-pdf-dom.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-dom.js)**
    *   **Responsabilidad**: Helpers para crear páginas físicas virtuales en el DOM y encapsular la estructura visual de cada hoja.
    *   **Funciones Clave**:
        *   [createNewPageElement](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-dom.js#L7): Construye la envoltura HTML de cada página (cajas de cabecera, pie y clases de paridad).

*   **[editor-pdf-html.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-html.js)**
    *   **Responsabilidad**: Procesamiento del Markdown a nivel de estructura de página. Prepara bloques de capítulos, genera el listado dinámico del Índice (TOC), renderiza las secciones de Créditos y aplica letras capitales y prefijos de capítulo.
    *   **Funciones Clave**:
        *   [buildChapterHTML](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-html.js#L7): Construye y compila el contenido HTML enriquecido para el capítulo seleccionado.
        *   [updateTOCPagesInCache](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-html.js#L234): Mapea y reemplaza las páginas correspondientes en la tabla de contenidos interactiva.

---

### 3. Generación y Combinación de Estilos CSS

*   **[editor-pdf-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles.js)**
    *   **Responsabilidad**: Inyección reactiva del CSS dinámico en la cabecera de la aplicación.
    *   **Funciones Clave**:
        *   [applyDynamicPDFStyles](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles.js#L9): Concentrador que recopila los fragmentos base, tipográficos y de capítulos y los consolida en el elemento `#dynamic-pdf-settings`.

*   **[editor-pdf-styles-base.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-base.js)**
    *   **Responsabilidad**: Define las reglas `@page`, dimensiones de caja, márgenes simétricos/asimétricos (`:left`/`:right`), estilos globales de headers/footers y los hacks de ocultamiento de páginas vacías iniciales para vistas de spreads en pantalla.
    *   **Funciones Clave**:
        *   [getPDFStylesBase](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-base.js#L58): Retorna la estructura principal de maquetación física CSS.
        *   [getHeaderFooterCSS](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-base.js#L525): Genera las directivas W3C de asignación de contenido de cabecera y pie por página.

*   **[editor-pdf-styles-chapters.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-chapters.js)**
    *   **Responsabilidad**: *[NUEVO]* Genera de forma aislada las reglas de Named Pages y paridad exclusivas para cada capítulo (permitiendo saltos de página inteligentes e inyección de imágenes de fondo personalizadas de cortesía).

*   **[editor-pdf-styles-typography.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-typography.js)**
    *   **Responsabilidad**: Estilizado de texto, espaciados de párrafo, tipografías h1/h2/h3, reglas de guionado (`hyphens: auto`), diseño del Índice (incluyendo guías de puntos de lomo en CSS Grid) y estilos tipográficos especiales para Créditos.
    *   **Funciones Clave**:
        *   [getPDFStylesTypography](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-typography.js#L6): Retorna la colección de estilos tipográficos del cuerpo del texto.

---

### 4. Exportación e Impresión

*   **[editor-pdf-export.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-export.js)**
    *   **Responsabilidad**: Gestiona la llamada nativa a `window.print()`.
    *   **Funciones Clave**:
        *   [triggerPrint](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-export.js#L25): Detiene la virtualización de scroll, forzar pre-compilación del libro completo, inyecta hojas de estilo específicas de medios de impresión (`@media print` para ocultar paneles laterales y layouts del editor) y abre el panel de exportación PDF del navegador de forma segura.

---

## Reglas de paginación por flujo de capítulos

Estas reglas se aplican al PDF completo y usan la numeración física visible del
libro. La paridad describe la posición en el pliego: impar es la página derecha
y par es la página izquierda.

### Reglas globales

- El primer capítulo físico comienza en la página impar 1. Esto también aplica
  cuando el primer capítulo es el Índice.
- El último capítulo siempre termina en una página par. Si el renderizado real
  termina en impar, el compilador agrega una única página blanca final.
- Un blanco de transición necesario para encadenar capítulos pertenece
  físicamente al capítulo anterior, aunque el salto CSS se origine al comenzar
  el capítulo siguiente.
- Cuando el primer capítulo físico es el Índice y el flujo es `Iniciar izquierda`,
  el Índice comienza en la página 2 porque la página 1 es blanca. Si el Índice
  ocupa una cantidad impar de páginas, termina en par y recibe explícitamente
  la página blanca impar siguiente antes del capítulo posterior.
- La longitud de un capítulo se mide con las páginas físicas que realmente
  ocupa. No se redondea artificialmente a un número par.

### Flujo `Continuo / Cualquiera`

- Cada capítulo comienza en la página inmediatamente siguiente a la anterior,
  sin forzar izquierda o derecha.
- Si un capítulo termina en par, el libro puede continuar en la siguiente
  página impar; no se agrega una página blanca intermedia.
- Si termina en impar, el siguiente capítulo continúa en la página par
  siguiente.
- Solo se aplica la regla global del último capítulo: el libro se completa con
  un blanco final si ese último capítulo termina en impar.

### Flujo `Iniciar izquierda (par)`

- Cada capítulo posterior al primero debe comenzar en una página par, es decir,
  en la página izquierda. El primer capítulo conserva la excepción global: el
  libro nace en la página impar 1 y, si el diseño requiere contenido en la
  izquierda, la página impar 1 se reserva como blanco inicial.
- Si un capítulo no final termina en par, se agrega una página blanca impar a
  su derecha. El siguiente capítulo comienza entonces en la página par
  siguiente.
- Si un capítulo no final termina en impar, no se agrega blanco: la página
  siguiente ya es par y puede iniciar el capítulo.
- El último capítulo no recibe un blanco intermedio adicional. Si termina en
  impar, recibe únicamente el blanco final necesario para que el libro termine
  en par.

### Implementación técnica

Paged.js realiza el primer corte del contenido. Después, el compilador inspecciona
la última página visible con contenido de cada capítulo no final. En flujo
`Iniciar izquierda`, si esa página es par, reconstruye el libro con una
`.chapter-transition-blank-page` asociada al capítulo que acaba de terminar. Esta
detección se repite hasta que la cadena de capítulos queda estable, porque cada
blanco insertado puede cambiar la paridad de los capítulos posteriores.
Finalmente se evalúa el cierre global del libro sobre el primer y último
capítulo: `.book-end-blank-page` se usa solo cuando la maqueta completa queda
impar y necesita un folio final para cerrar en página par.

### 5. Archivos Inactivos o Deprecados

*   **`editor-pdf-pagination.js`**: *[DEPRECADO]* Antiguo algoritmo procedimental de medición de píxeles. Actualmente inactivo ya que Paged.js maneja nativamente la fragmentación del DOM físico en base al flujo del renderizador de Chrome.

---

## Cambios de Refactorización Recientes (500 Líneas)

Para cumplir con la directriz principal en `AGENT_GUIDELINES.md` que prohíbe archivos de más de 500 líneas de código:
1. **Deduplicación de Helpers**: Se extrajeron las funciones duplicadas (`getMarginBox`, `getFooterMarginBox`, `getHeaderContent`, `getFooterContent`) al ámbito superior de archivo en [editor-pdf-styles-base.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-base.js) para reducir duplicidad.
2. **Aislamiento en Módulo de Capítulos**: El generador de CSS para Named Pages de capítulos (que consumía ~155 líneas) fue trasladado a su propio módulo modular independiente: [editor-pdf-styles-chapters.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles-chapters.js).
3. **Integración en Orquestación**: Se encoló el nuevo archivo en [editor-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/editor-app.php) y se unificó la lógica en [editor-pdf-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/editor-pdf-styles.js).

* core
