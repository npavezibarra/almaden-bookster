# Directorio Ebook Reader (`assets/js/reader/`)

Este directorio contiene los archivos JavaScript que controlan la interactividad de la lectura pública de eBooks en el frontend.

## Archivos y Funcionalidades

*   **[reader-app.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-app.js)**: Inicialización del visor y renderizado de shortcodes.
*   **[reader-navigation.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-navigation.js)**: Lógica de navegación del lector (Modo Scroll continuo vs Modo Flip de doble página). Utiliza el `bookData` global de forma segura.
*   **[reader-prefs.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-prefs.js)**: Gestión y almacenamiento persistente de las preferencias del lector (fuente, tema, tamaño de texto).
*   **[reader-styles.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-styles.js)**: Construcción dinámica del CSS scoped aplicado al visor del libro.
*   **[reader-quizzes.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-quizzes.js)**: Control del flujo de las evaluaciones (quizzes) incrustadas en los capítulos. Reforzado para utilizar `window.bookData` como fallback seguro para evitar condiciones de carrera en la carga de variables.
*   **[reader-progress.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-progress.js)**: Panel flotante de resultados, intentos, avance del libro y reset condicionado a la finalización total de quizzes.
*   **Highlights Modulares**: El antiguo archivo masivo de highlights fue dividido en 5 módulos para cumplir la regla de 500 líneas:
    *   **[reader-highlights-state.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-state.js)**: Estado global y utilidades base.
    *   **[reader-highlights-dom.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-dom.js)**: Manipulación del DOM, selección de texto y posicionamiento.
    *   **[reader-highlights-ui.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-ui.js)**: Interfaz de usuario (panel lateral y compositores de comentarios).
    *   **[reader-highlights-api.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-api.js)**: Comunicación asíncrona con el backend (guardar, borrar, listar).
    *   **[reader-highlights-events.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/reader-highlights-events.js)**: Registro de todos los eventos globales de usuario.
