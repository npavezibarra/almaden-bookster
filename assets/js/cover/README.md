# Directorio Cover (`assets/js/cover/`) - Editor de Portadas (Bookster Cover Editor)

Este directorio alberga la arquitectura modular del **Editor de Portadas**. Anteriormente un archivo monolítico gigante, el editor fue fragmentado en componentes independientes para acatar el principio estricto de <500 líneas por archivo y garantizar un mantenimiento ordenado.

## Arquitectura de Módulos

*   **[cover-state.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-state.js)**: 
    Gestiona la estructura de datos pura y el estado (`CoverEditor.state`) de la portada. Esto incluye las dimensiones, la definición del *background*, el arreglo principal de `layers` (capas) y el registro de la capa actualmente seleccionada.

*   **[cover-layers.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-layers.js)**: 
    Actúa como el **Orquestador Principal** de las capas. Inicializa los Listeners, gestiona eventos de ratón (seleccionar, arrastrar, interactuar), atajos de teclado y la propagación de actualizaciones hacia el panel o el canvas principal.

*   **[cover-layers-canvas.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-layers-canvas.js)**: 
    Responsable exclusivo de la representación visual de las capas (texto, imágenes, formas) dentro del lienzo interactivo (HTML DOM). Contiene todo el código de inyección en tiempo real, escalas y renderizado de texto/tipografía.

*   **[cover-layers-panel.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-layers-panel.js)**: 
    Maneja la UI del "Panel de Capas" lateral izquierdo. Renderiza la lista visual de elementos, permite su ocultamiento/visualización y gestiona íntegramente la re-ordenación Z-Index mediante la funcionalidad Drag & Drop interactiva (`Sortable` o lógica nativa).

*   **[cover-book-format.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-book-format.js)**: 
    Controla la sección plegable de `Formato del libro` en el panel izquierdo, donde viven papel interior, páginas y ancho del lomo.

*   **[cover-layers-interactions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-layers-interactions.js)**: 
    Centraliza las interacciones sobre capas ya seleccionadas, incluyendo arrastre con mouse, movimiento fino con teclado y cancelación segura del drag cuando el foco cambia.

*   **[cover-dimensions.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-dimensions.js)**: 
    Responsable del entorno espacial del lienzo. Contiene las matemáticas detrás de calcular los márgenes de sangría (bleed), espinas de libros (spine width), zoom interactivo, la grilla magnética (grid snapping) y re-ajuste (resize) de los contenedores.

*   **[cover-media.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-media.js)**: 
    Controla todo el flujo de trabajo con imágenes. Se encarga de la invocación de la API de medios de WordPress (`wp.media`), la subida de nuevos recursos gráficos, y la lógica para incrustar estos medios como "Background" (fondo principal) o como una "Capa" individual.

*   **[cover-image-diagnostics-format.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-image-diagnostics-format.js)**, **[cover-image-diagnostics-render.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-image-diagnostics-render.js)** y **[cover-image-diagnostics-bootstrap.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-image-diagnostics-bootstrap.js)**:
    Separan el preflight de imágenes, el render de paneles y la orquestación del análisis editorial para mantener cada archivo por debajo de 500 líneas.

*   **[cover-save.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-save.js)**: 
    Empaqueta y serializa todo el `CoverEditor.state` actual para enviarlo al servidor mediante peticiones AJAX, asegurando que la última iteración de la portada persista en la base de datos de WordPress de forma segura.

*   **[cover-export.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/cover-export.js)**: 
    Contiene los algoritmos necesarios para rasterizar o compilar el lienzo interactivo (DOM) hacia formatos finales exportables o imprimibles (por ejemplo la generación de un raster en baja o alta resolución de la imagen de portada).

* cover-image-diagnostics.js (compatibilidad histórica)
* cover-utils.js
