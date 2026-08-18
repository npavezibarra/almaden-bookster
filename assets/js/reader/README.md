# Directorio Ebook Reader (`assets/js/reader/`)

Este directorio contiene los archivos JavaScript que controlan la interactividad de la lectura pública de eBooks en el frontend.

## Archivos y Funcionalidades

*   **[reader-app.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-app.js)**: Inicialización del visor y renderizado de shortcodes.
*   **[reader-navigation.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-navigation.js)**: Lógica de navegación del lector (Modo Scroll continuo vs Modo Flip de doble página). Utiliza el `bookData` global de forma segura.
*   **[reader-prefs.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-prefs.js)**: Gestión y almacenamiento persistente de las preferencias del lector (fuente, tema, tamaño de texto).
*   **[reader-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-styles.js)**: Construcción dinámica del CSS scoped aplicado al visor del libro.
*   **[reader-quizzes.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-quizzes.js)**: Control del flujo de las evaluaciones (quizzes) incrustadas en los capítulos. Reforzado para utilizar `window.bookData` como fallback seguro para evitar condiciones de carrera en la carga de variables.
*   **[reader-progress.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-progress.js)**: Panel flotante de resultados, intentos, avance del libro y reset condicionado a la finalización total de quizzes.
*   **Highlights Modulares**: La experiencia de highlights está dividida en 6 módulos para mantener responsabilidades acotadas:
    *   **[reader-highlights-state.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-state.js)**: Estado global y utilidades base.
    *   **[reader-highlights-dom.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-dom.js)**: Manipulación del DOM, selección de texto, posicionamiento y focos contextuales.
    *   **[reader-highlights-ui.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-ui.js)**: Interfaz de usuario (panel lateral, toolbar de selección y acciones sobre highlights existentes).
    *   **[reader-highlights-api.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-api.js)**: Comunicación asíncrona con el backend (guardar, borrar, listar).
    *   **[reader-highlights-page.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-page.js)**: Vista expandida con feed cronológico, filtros por capítulo y navegación de regreso al texto.
    *   **[reader-highlights-events.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-events.js)**: Registro de todos los eventos globales de usuario.

## Página expandida de highlights

La página expandida de highlights se construyó como una tercera vista del app shell del reader, al mismo nivel que el índice y la vista de capítulo. La idea fue extender el panel `reader-highlights-panel` sin reescribir la lógica existente de highlights.

### Piezas que la componen

*   **Template principal**: `templates/reader/reader-app.php`
    *   Agrega el bloque `#almaden-view-highlights` al app shell.
    *   Inserta una barra superior del reader con navegación al índice y de vuelta a la lectura.
    *   Monta el layout de dos columnas: sidebar de capítulos y área principal del feed.
    *   Añade el botón de expandir en el panel lateral de highlights.

*   **Vista expandida**: `assets/js/reader/reader-highlights-page.js`
    *   Toma `bookData.chapters` y `getSortedBookHighlights()` como fuente de verdad.
    *   Filtra por capítulo o muestra `ALL`.
    *   Ordena el feed por fecha descendente.
    *   Reutiliza `renderReaderHighlightCommentsSection()` para mostrar comentarios embebidos en cada card.
    *   Resuelve el botón `Leer capítulo` desde la barra fija superior, no desde cada item.
    *   Vuelve al capítulo correcto usando `showChapterView(index)` y conserva `pendingFocusHighlightId` cuando se entra desde un highlight concreto.

*   **Backend de datos**: `includes/reader/highlight-comments.php`
    *   Expone `wp_ajax_almaden_list_book_highlights_feed`.
    *   Devuelve highlights del usuario y sus comentarios asociados en una sola respuesta.
    *   Agrupa comentarios por highlight para evitar consultas extra en el frontend.

*   **Estilos**: `assets/css/reader-app.css`
    *   Define la barra sticky superior de la página expandida.
    *   Separa el ancho visual del toolbar interno del ancho total de la franja.
    *   Mantiene la paleta de la vista expandida en blanco, negro y grises neutros.
    *   Conserva el feed y los comentarios con una jerarquía visual más editorial.

*   **Navegación**: `assets/js/reader/reader-navigation.js`
    *   Oculta la vista expandida cuando el usuario vuelve al índice o a un capítulo.
    *   Evita que queden dos superficies activas al mismo tiempo.

### Flujo de uso

1. El usuario abre el panel `reader-highlights-panel`.
2. Desde ahí, usa el botón `Expandir` para entrar a `#almaden-view-highlights`.
3. La vista expandida carga el feed agregado desde el backend.
4. El sidebar izquierdo filtra por capítulo.
5. La barra fija superior muestra el capítulo activo y ofrece `Leer capítulo`.
6. Al pulsar `Leer capítulo`, el reader vuelve a la página del capítulo correspondiente.

### Nota para futuras iteraciones

Esta vista está pensada para crecer sin tocar el panel lateral original. Si hay que modificar la experiencia de lectura de highlights, la regla general es:

*   UI de la vista expandida en `reader-highlights-page.js` + `reader-app.php`
*   datos agregados en `includes/reader/highlight-comments.php`
*   estilos de layout en `assets/css/reader-app.css`
*   navegación entre vistas en `reader-navigation.js`

El toolbar del Reader se reutiliza para dos contextos: al seleccionar texto
permite guardar o comentar un highlight, y al hacer click sobre un highlight ya
existente se reabre con la acción de borrado como opción principal.
