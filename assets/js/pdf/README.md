# Directorio PDF Engine (`assets/js/pdf/`)

Este directorio contiene todo el motor de renderizado y maquetación de PDF del plugin.

## Archivos y Funcionalidades

- **`editor-pdf-compiler.js`**: El orquestador del bucle de paginación que procesa los capítulos secuencialmente.
- **`editor-pdf-compiler-dimensions.js`**: Lógica de cálculo de dimensiones de página (márgenes, tamaños en mm/px, etc.).
- **`editor-pdf-compiler-parity.js`**: Reglas de asignación de paridad de hojas, imágenes de paridad y flujos de márgenes opuestos.
- **`editor-pdf-dom.js`**: Inicializa la estructura HTML virtual de cada hoja (cabeceras, números de página, pies y footnotes).
- **`editor-pdf-pagination.js`**: Algoritmos matemáticos de detección de desbordamiento de contenido y segmentación de párrafos.
- **`editor-pdf-html.js`**: Preprocesamiento del HTML base del capítulo (incluyendo índices automáticos, subtítulos, letras capitales y ahora el diseño dinámico a dos bloques de la página de Créditos).
- **`editor-pdf-styles.js`** / **`editor-pdf-styles-base.js`** / **`editor-pdf-styles-typography.js`**: Construyen inyecciones CSS dinámicas para aplicar las tipografías y formatos al visor. Implementan el sistema avanzado de retícula (`grid-template-columns` rígidas) para evitar colapsos visuales y asegurar que la alineación de la regla (ruler) encaje milimétricamente con el lomo virtual.
- **`editor-pdf-export.js`**: Manejo de la preparación de impresión y invocación de `window.print()`.
