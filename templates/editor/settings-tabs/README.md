# Directorio settings-tabs

Contiene los paneles estáticos del modal **Ajustes del libro**. Los IDs de los controles son parte del contrato con `editor-settings-tabs.js`, `editor-settings-fields.js` y `editor-settings-api.js`; una reorganización visual debe conservarlos.

## Archivos

- `functions.php`: helpers PHP y opciones compartidas.
- `tab-page.php`: tres tarjetas abiertas —Formato de página, Márgenes y área de contenido, y Sangrado— sin subtabs adicionales.
- `tab-typography.php`: subtabs Cuerpo/Títulos. Cuerpo agrupa fuente y composición de párrafos; Títulos contiene tarjetas para H1, H2 y H3.
- `tab-chapters.php`: subtabs Estructura, Prefijo, Título y Subtítulo; la posición de apertura se muestra contextualmente dentro de Estructura.
- `tab-ebook-chapters.php`: configuración tipográfica de capítulos para eBook.
- `tab-footnotes.php`: configuración de notas al pie.
- `tab-commerce.php`: punto de montaje del módulo `book-product` dentro de General > Producto.

## Convenciones de interfaz

- Un subtab representa navegación de tercer nivel y usa `.settings-inner-tabs`; debe ir antes y fuera de la tarjeta.
- Una tarjeta principal usa `.settings-section-card`; una tarjeta asociada a un subtab complejo puede usar `.settings-inner-panel-card`.
- Los encabezados principales de tarjeta incluyen icono, texto en estilo oración y peso `700`.
- Las rejillas normales no deben superar dos columnas en Tipografía. En smartphone todas las rejillas pasan a una columna.
- No introducir tamaños funcionales menores de `14px` ni controles menores de `44px`.
