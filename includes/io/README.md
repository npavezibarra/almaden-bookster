# Directorio io

Este directorio forma parte del plugin Almaden Bookster.
Archivos y subdirectorios contenidos aquí:

* `book-import.php`: Maneja la lógica de carga (upload/import) de libros en formato ZIP (paquete de datos y recursos) hacia el sistema.
* `book-export.php`: Maneja la lógica de descarga (exportación) de libros en formato ZIP, incluyendo el escaneo de imágenes de portadas y contenido.
* `cover-pdf-export.php`: Maneja la lógica para la generación de portadas en formato PDF (CMYK) listo para impresión.
  * **Exportación PDF**: Utiliza Chrome Headless (`--headless=new`, `--disable-crash-reporter`, `--disable-background-networking`) para renderizar el HTML. Se manejan tiempos de espera (timeout a los 30s) aceptando PDFs parcialmente finalizados para prevenir cuelgues del SO (macOS).
  * **Fuentes y Escala**: Inserta dinámicamente hojas de estilo de Google Fonts extrayendo las tipografías usadas en el editor. Las dimensiones del canvas (`page_width`, `page_height`) están en CM y se traducen a MM para la escala de Chrome, de este modo las fuentes y dimensiones son idénticas al frontend de React/JS.
  * Se apoya en Ghostscript (`gs`) para la conversión a CMYK con el perfil de prepress.
* `process-utils.php`: Contiene utilidades genéricas para manejar y ejecutar procesos del sistema, específicamente para la búsqueda y ejecución de binarios externos (`Chrome`, `Ghostscript`).
* `epub-export.php`: Lógica para exportar libros en formato ePub.
* `gdrive-client.php`: Integración y cliente API para Google Drive.
