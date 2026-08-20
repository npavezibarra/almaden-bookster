# Directorio Editor Templates (`templates/editor/`)

Este directorio contiene las plantillas PHP relacionadas con la interfaz principal del editor de libros (Content Editor) y sus modales de configuración.

## Archivos y Funcionalidades

*   **[editor-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/editor-app.php)**: Plantilla principal que renderiza el contenedor y las zonas de la aplicación de edición de libros.
*   **[editor-settings-modal.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/editor-settings-modal.php)**: Modal global de ajustes del libro para PDF, eBook y configuración General. Mantiene header y footer estables, un único cuerpo con scroll y navegación jerárquica por formato, sección y subtab.
*   **[chapter-settings-modal.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/chapter-settings-modal.php)**: El modal de configuración específico a nivel de capítulo.
*   **[chapter-settings-normal.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/chapter-settings-normal.php)**: El contenido y formulario para un capítulo de tipo normal.
*   **`image-viewport-modal.php`**: Modal único para elegir, subir, recortar y guardar imágenes desde el editor RAW sin apilar selectores secundarios.
*   **[chapter-settings-toc.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/chapter-settings-toc.php)**: El formulario de configuración para la Tabla de Contenidos.
*   **[settings-tabs/](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/settings-tabs/)**: Pestañas de configuración individuales estáticas (páginas, tipografías, encabezados/pies, capítulos, ebooks). *Nota: La pestaña de créditos (`tab-credits.php`) fue eliminada y trasladada al controlador dinámico `editor-settings-credits.js`*.

## Sistema visual de Ajustes del libro

El modal usa tres niveles de navegación claramente diferenciados:

1. **Formato**: `PDF`, `eBook` y `General`, mediante control segmentado.
2. **Sección**: pestañas principales como Página, Tipografía, Cabecera y pie, Notas al pie o Capítulos.
3. **Subtab**: control segmentado independiente situado siempre encima del panel activo. Se usa en Plantillas, Tipografía, Cabecera/Pie y Capítulos; nunca debe quedar incrustado en el encabezado de una tarjeta.

Los paneles de contenido usan `.settings-section-card` o `.settings-inner-panel-card`. Sus encabezados comparten tamaño, peso, divisor, espaciado e iconografía. En escritorio el modal alcanza hasta `960px`; en pantallas menores de `768px` ocupa `100dvh`, convierte las rejillas en una columna y mantiene acciones táctiles de al menos `44px`.

La tipografía funcional parte de `14px`, mientras que inputs y selects usan `16px`. El header y el footer del modal no participan del scroll del contenido.
