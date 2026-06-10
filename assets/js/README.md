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

El motor PDF es el componente más complejo de la aplicación, encargado de simular con exactitud cómo se verá el libro impreso físicamente, con sus saltos de página y partición silábica. Para mantener el rendimiento, **el visor interactivo del editor siempre compila únicamente el Capítulo Actual (`active` mode)**. La compilación del libro completo (`full` mode) está reservada estrictamente para el momento de imprimir o exportar, realizándose de forma sincrónica para evitar sobrecargar el navegador.

- **`editor-pdf-compiler.js`**:
  El "Orquestador Principal". Es el encargado del bucle que itera sobre los capítulos y va encolando nodos para pintarlos en pantalla.
  
- **`editor-pdf-html.js`**:
  El "Generador Estructural". Se encarga de procesar el objeto del capítulo (y sus ajustes) y convertir el contenido a un HTML preliminar. Inyecta los títulos, subtítulos, letra capitular, sufijos/prefijos de capítulo y la estructura base de la Tabla de Contenidos.

- **`editor-pdf-dom.js`**:
  Un módulo auxiliar dedicado puramente a generar el "esqueleto" HTML virtual de una hoja (encabezados, pies de página, notas al pie, layout flexbox).

- **`editor-pdf-pagination.js`**:
  Contiene los algoritmos matemáticos complejos encargados de la paginación fina. Es decir, calcular la altura en píxeles de los bloques y dividir los párrafos a la mitad cuando no caben en la página actual (haciendo tracking a nivel de línea y palabra).

- **`editor-pdf-styles.js`**:
  Se encarga de leer los ajustes configurados por el usuario y construir dinámicamente un bloque `<style>` de CSS que se inyecta en el DOM para aplicar márgenes, fuentes, e interlineado exacto en la vista previa del libro.

### Características del Renderizador y Problemas de Imprimir (`window.print()`)

- **Virtualización Completa:** El editor previsualiza un solo capítulo a la vez para mantener la interfaz ligera. Al compilar para la impresión final, se forzan todas las páginas del libro.
- **Bug de Flexbox en Chrome Print:** Cuando Chrome imprime (`window.print()`), su motor de cálculo de `@media print` suele fallar al evaluar contenedores que usan `flex: 1` para llenar espacio. Esto causa que el texto se "desborde" y se imprima sobre el pie de página. 
  - *Solución implementada:* Una vez que una página se llena de contenido y se completan las notas al pie, el script "congela" su altura (`pdfContent.style.flex = 'none'; pdfContent.style.height = clientHeight + 'px'`). Al usar un height fijo, Chrome Print renderiza los bloques de forma predecible sin depender del flex recalculado.

## Exportación Física

- **`editor-pdf-export.js`**:
  Controla el flujo de exportación. Al hacer clic en "Imprimir PDF", se bloquea el botón y se fuerza la compilación del libro completo (`compilePDFPreview(..., true)`). Prepara el DOM desactivando temporalmente la virtualización (para que existan en el DOM real todas las páginas) e invoca el diálogo de sistema `window.print()`.

## Panel de Administración

- **`admin-fonts-page.js`**:
  A diferencia del resto, este archivo **no** se carga en el editor del frontend. Su única función es brindar interactividad a la pantalla de configuración en el WP Admin Dashboard, concretamente para subir, procesar y eliminar archivos tipográficos personalizados (TTF, WOFF).

## Problemas Conocidos y Correcciones Críticas

Para futuros mantenedores, documentamos las soluciones a problemas complejos de renderizado que surgieron durante el desarrollo del motor PDF:

### 1. Letras "decapitadas" (Clipping Horizontal)
- **El Problema:** La primera o última línea de la página se veía cortada horizontalmente (especialmente los trazos altos y bajos como la tilde o la cola de la "p").
- **La Causa:** El contenedor `.pdf-content` tenía la propiedad `overflow: hidden;`. Al intentar meter "con calzador" el texto basándose en la altura de línea matemática, la tinta que sobresale de la caja de la línea (típico en fuentes serif altas como Merriweather) era rebanada por el contenedor como si fuera una guillotina.
- **La Solución:** Siempre asegurar que `.pdf-content` mantenga `overflow: visible !important`. La paginación evita que se rompa el layout, así que no necesitamos guillotinas CSS.

### 2. Espacios en Blanco o Borde Irregular al Final de la Página (Ragged Right Edge)
- **El Problema:** La última línea de texto de una página, antes de continuar en la siguiente, no se justificaba hacia la derecha (quedaba alineada a la izquierda o irregular).
- **La Causa:** Había un conflicto de especificidad CSS en `editor-pdf-styles.js`. La regla `.pdf-content p:last-child { text-align-last: auto !important; }` sobrescribía a `.pdf-content p.split-paragraph-start { text-align-last: justify !important; }`. 
- **La Solución:** Se protegió la especificidad agregando una pseudo-clase de negación: `.pdf-content p:last-child:not(.split-paragraph-start)`. Esto asegura que los párrafos partidos siempre se justifiquen perfectamente antes del salto de página.

### 3. Congelamiento al Compilar e Imprimir ("Stuck there forever")
- **El Problema:** Al compilar el libro completo, el proceso se quedaba colgado infinitamente mostrando el spinner de carga y resultaba en un PDF incompleto de un solo capítulo.
- **La Causa:** Al medir dinámicamente las imágenes en el contenedor oculto (`tempContainer`), WordPress por defecto incluye el atributo `loading="lazy"`. Al estar fuera de pantalla y oculto, Chrome nunca disparaba los eventos `onload` ni `onerror`, por lo que la promesa (`Promise.all(imagePromises)`) jamás se resolvía.
- **La Solución:** En `editor-pdf-compiler.js` se forzó la eliminación del atributo `loading` (`img.removeAttribute('loading')`) antes de crear las promesas, obligando al navegador a descargar las imágenes sin importar su visibilidad.
