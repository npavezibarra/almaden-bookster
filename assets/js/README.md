# Directorio JS (`assets/js/`) - Arquitectura Frontend

Este directorio contiene todo el código del lado del cliente que da vida a la aplicación de editor visual y renderizador de PDFs del plugin **AlmadenBookster**. Siguiendo el principio de **Modularidad Extrema** (con un límite estricto de <500 líneas por archivo), las responsabilidades se han dividido lógicamente en los siguientes archivos.

## Módulos Principales de la Aplicación

- **`editor-core.js`**:
  El cerebro de la aplicación. Maneja la inicialización, la gestión del estado global (`bookState`), el guardado automático (autosave), y la carga inicial de los datos desde la base de datos vía AJAX.

- **`editor-chapters.js`**:
  Controla el panel lateral izquierdo. Maneja la creación, eliminación, reordenamiento (drag and drop) de capítulos, y el cambio del capítulo "activo".

- **`editor-settings.js`**:
  Controla el Modal global de *Ajustes de Maquetación*. Lee, escribe y envía (AJAX) las configuraciones de formato del libro (márgenes, tipografías, encabezados, etc.).

- **`editor-chapter-settings.js`**:
  Controla el modal de ajustes individuales a nivel de capítulo (permitiendo sobreescribir configuraciones globales, forzar saltos de página a páginas pares o impares, asignar imágenes de paridad, etc.).

## Motor de Interpretación (Markdown -> HTML)

- **`editor-markdown.js`**:
  Se encarga de parsear el texto plano ingresado por el usuario y transformarlo en una estructura de elementos HTML limpios y semánticos (párrafos, listas, títulos, cajas). También intercepta y procesa todos los *shortcodes* personalizados del plugin (ej. `[align=center]`, `[box]`, `[page_break]`).

## Motor de Renderizado PDF (Virtual Pagination Engine)

El motor PDF es el componente más complejo de la aplicación, encargado de simular con exactitud cómo se verá el libro impreso físicamente, con sus saltos de página y partición silábica. Para mantenerlo modular, está dividido en 4 archivos:

- **`editor-pdf-compiler.js`**:
  El "Orquestador Principal". Es el encargado del bucle que itera sobre los capítulos y va encolando nodos para pintarlos en pantalla.
  
- **`editor-pdf-dom.js`**:
  Un módulo auxiliar dedicado puramente a generar el "esqueleto" HTML virtual de una hoja (encabezados, pies de página, notas al pie, layout flexbox).

- **`editor-pdf-pagination.js`**:
  Contiene los algoritmos matemáticos complejos encargados de la paginación fina. Es decir, calcular la altura en píxeles de los bloques y dividir los párrafos a la mitad cuando no caben en la página actual (haciendo tracking a nivel de línea y palabra).

- **`editor-pdf-styles.js`**:
  Se encarga de leer los ajustes (Settings) configurados por el usuario y construir dinámicamente un bloque `<style>` de CSS que se inyecta en el DOM para aplicar márgenes, fuentes, e interlineado exacto en la vista previa del libro.

## Exportación Física

- **`editor-pdf-export.js`**:
  Prepara el DOM para imprimir invocando el diálogo de sistema `window.print()`, limpiando la interfaz para que el navegador genere correctamente el PDF nativo final.

## Panel de Administración

- **`admin-fonts-page.js`**:
  A diferencia del resto, este archivo **no** se carga en el editor del frontend. Su única función es brindar interactividad a la pantalla de configuración en el WP Admin Dashboard, concretamente para subir, procesar y eliminar archivos tipográficos personalizados (TTF, WOFF).
