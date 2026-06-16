# Directorio JS (`assets/js/`) - Arquitectura Frontend

Este directorio contiene todo el código del lado del cliente que da vida a la aplicación de editor visual, renderizador de PDFs y visor (lector) del plugin **AlmadenBookster**. Siguiendo el principio de **Modularidad Extrema** (con un límite estricto de <500 líneas por archivo), las responsabilidades se han dividido lógicamente en los siguientes archivos.

## Módulos del Editor de Libro (Content Editor)

- **`editor-core.js`**:
  El cerebro de la aplicación. Maneja la inicialización principal (`window.onload`), la gestión del estado global (`bookState`), y la declaración del `initEventListeners` central.

- **`editor-ui.js`**:
  Controla de forma exclusiva la interfaz de usuario. Maneja cambios de tema visual (claro, sepia, oscuro), los modos de vista (dividido, solo editor, solo PDF), la barra lateral de capítulos, y el sistema de notificaciones (Toasts).

- **`editor-toolbar.js`**:
  Aisla toda la lógica de la barra de formato Markdown superior. Contiene funciones para insertar textos envueltos (negrita, cursiva), procesar imágenes (Uploader Media), imágenes de paridad y alterar tamaños/fuentes de texto en el editor.

- **`editor-virtualization.js`**:
  Contiene un componente muy especializado para optimizar el rendimiento: La inicialización de la Virtualización del PDF en el DOM vía IntersectionObserver, limitando los elementos inyectados a lo visible.

- **`editor-chapters.js`**:
  Controla el panel lateral izquierdo. Maneja la creación, eliminación, reordenamiento (drag and drop) de capítulos, y el cambio del capítulo "activo".

- **`editor-settings.js`**:
  Controla el Modal global de *Ajustes de Maquetación*. Lee, escribe y envía (AJAX) las configuraciones de formato del libro (márgenes, tipografías, encabezados, etc.).

- **`editor-chapter-settings.js`**:
  Controla el modal de ajustes individuales a nivel de capítulo (permitiendo sobreescribir configuraciones globales, forzar saltos de página a páginas pares o impares, asignar imágenes de paridad, etc.).

## Motor de Interpretación (Markdown -> HTML)

- **`editor-markdown.js`**:
  Se encarga de parsear el texto plano ingresado por el usuario y transformarlo en una estructura de elementos HTML limpios y semánticos (párrafos, listas, títulos, cajas). También intercepta y procesa todos los *shortcodes* personalizados del plugin.

## Motor de Renderizado PDF (Virtual Pagination Engine)

El motor PDF es el componente encargado de simular con exactitud cómo se verá el libro impreso físicamente, con sus saltos de página y partición silábica. Para mantener el rendimiento, **el visor interactivo del editor siempre compila únicamente el Capítulo Actual (`active` mode)**.

- **`editor-pdf-compiler.js`**:
  El "Orquestador Principal". Es el encargado del bucle que itera sobre los capítulos y va encolando nodos para pintarlos en pantalla.
  
- **`editor-pdf-html.js`**:
  El "Generador Estructural". Se encarga de procesar el objeto del capítulo (y sus ajustes) y convertir el contenido a un HTML preliminar. Inyecta los títulos, subtítulos, letra capitular, sufijos/prefijos de capítulo y la estructura base de la Tabla de Contenidos.

- **`editor-pdf-dom.js`**:
  Un módulo auxiliar dedicado puramente a generar el "esqueleto" HTML virtual de una hoja (encabezados, pies de página, notas al pie, layout flexbox).

- **`editor-pdf-pagination.js`**:
  Contiene los algoritmos matemáticos complejos encargados de la paginación fina. Es decir, calcular la altura en píxeles de los bloques y dividir los párrafos a la mitad cuando no caben en la página actual.

- **`editor-pdf-styles.js`**:
  Se encarga de leer los ajustes configurados por el usuario y construir dinámicamente un bloque `<style>` de CSS que se inyecta en el DOM para aplicar márgenes, fuentes, e interlineado exacto en la vista previa del libro.

## Exportación Física

- **`editor-pdf-export.js`**:
  Controla el flujo de exportación. Al hacer clic en "Imprimir PDF", se bloquea el botón y se fuerza la compilación del libro completo. Prepara el DOM desactivando temporalmente la virtualización e invoca el diálogo de sistema `window.print()`.

## Lector Público de Ebook (`reader-*`)

El conjunto de scripts destinados exclusivamente a la previsualización final que los usuarios experimentan al leer un Ebook:

- **`reader-app.js`**: 
  El motor de inicialización básico del lector. Configura `markdown-it`, interactividad genérica (notas flotantes) y pinta la tabla de contenidos (Índice).
- **`reader-prefs.js`**: 
  Manejo del panel de Ajustes de Lectura. Contiene la sincronización vía LocalStorage/AJAX de preferencias como fuente, interlineado y tamaño del usuario.
- **`reader-styles.js`**: 
  Orquesta la generación e inyección del CSS al vuelo para aplicar variables base (Themes) a la experiencia de lectura de una manera no destructiva (aislada al root/DOM).
- **`reader-navigation.js`**: 
  Toda la lógica de flujo de lectura: Modo Scroll vs Modo Flip (2 páginas al mismo tiempo), inyección paginada y botones Pág Anterior/Siguiente.

## Panel de Administración

- **`admin-fonts-page.js`**:
  No se carga en el editor del frontend. Su única función es brindar interactividad a la pantalla de configuración en el WP Admin Dashboard, concretamente para subir, procesar y eliminar archivos tipográficos personalizados.

## Componentes Especializados
* La subcarpeta **`/cover`** alberga la lógica del Editor de Portadas. Consulte el archivo `README.md` localizado dentro de dicho directorio para mayores referencias.

## Problemas Conocidos y Correcciones Críticas
(El texto previo con detalles de bugs conocidos se ha preservado aquí para conocimiento de los nuevos mantenedores. Refiérase al historial de Git para contexto histórico).
