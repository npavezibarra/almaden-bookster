# Directorio styles

Este directorio forma parte del plugin Almaden Bookster.
Archivos y subdirectorios contenidos aquí:

* `editor-pdf-styles.js`: Punto de entrada principal. Orquesta e inyecta dinámicamente todas las reglas CSS de maquetación basándose en la configuración del libro (`bookState.settings`).
* `editor-pdf-styles-base.js`: Genera el CSS base para Paged.js, definiendo reglas `@page`, cajas de márgenes, cabeceras y pies de página globales.
* `editor-pdf-styles-chapters.js`: Configura las reglas de "Named Pages" y saltos de página específicos por capítulo (ej: manejo de paridad de páginas e imágenes de fondo).
* `editor-pdf-styles-flow.js`: Controla el flujo de contenido, reglas de fragmentación (evitar viudas/huérfanas) y el renderizado y disposición de las notas al pie.
* `editor-pdf-styles-semantic.js`: Define estilos semánticos y reglas específicas como los saltos de página manuales generados en el editor.
* `editor-pdf-styles-typography.js`: Maneja todo el CSS tipográfico, estilos de párrafos, alineación de texto y los estilos para la Tabla de Contenidos (TOC).
