# Directorio Reader Templates (`templates/reader/`)

Este directorio contiene la plantilla del lector de eBooks (Web Reader).

## Archivos y Funcionalidades

*   **[reader-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/reader/reader-app.php)**: Renderiza el visor HTML interactivo para leer libros digitales. Permite navegar paginadamente, cambiar preferencias de lectura (colores, fuentes) y ver las notas al pie dinámicas. Importante: Exporta la variable `window.bookData` al entorno global, la cual es consumida como Single Source of Truth por scripts como `reader-quizzes.js` de forma robusta.
