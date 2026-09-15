# Directorio Reader Templates (`templates/reader/`)

Este directorio contiene la plantilla del lector de eBooks (Web Reader) para usuarios que ya tienen acceso.
La ficha pública y la vista previa bloqueada ahora viven en `templates/ebook/`, y el panel de resultados/progreso se monta desde `assets/js/reader/reader-progress.js`.

## Archivos y Funcionalidades

*   **[reader-app.php](reader-app.php)**: Renderiza el visor HTML interactivo del ebook completo. Exporta `window.bookData` al entorno global para scripts como `reader-quizzes.js` y `reader-progress.js`.
