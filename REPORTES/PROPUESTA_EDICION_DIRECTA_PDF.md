# INFORME TÉCNICO Y PROPUESTA ARQUITECTÓNICA: EDICIÓN DIRECTA DE TEXTO EN EL PDF VIEWER DE ALMADEN BOOKSTER

**Fecha:** 16 de Septiembre, 2026  
**Ubicación:** `REPORTES/PROPUESTA_EDICION_DIRECTA_PDF.md`  
**Estado:** Propuesta Técnica de Análisis y Diseño (Sin cambios de código en producción)  
**Cumplimiento de Estándares:** `AGENTS.md` (Modularización, Límite de 500 líneas por archivo, Frontend App Shell)

---

## 1. RESUMEN EJECUTIVO

El presente informe detalla el análisis técnico y el plan de arquitectura para dotar al **PDF Viewer de Almaden Bookster** de capacidad de **edición directa visual de texto (WYSIWYG Inline Editing)**. 

Actualmente, el visor muestra un renderizado vectorial-a-rasterizado mediante Canvas HTML5 impulsado por **PDF.js**, compondido por el motor **Typst** a partir de archivos Markdown/BBCode. La edición requiere modificar un área de texto (`textarea`) lateral y esperar la recomposición del libro.

La propuesta arquitectónica contempla una **capa interactiva (TextLayer Overlay)** sobre el canvas de PDF.js, combinada con un **Mapeador Bidireccional de Fuentes (Source Mapper)** y una **Barra de Herramientas Flotante (Formatting Toolbar)**. Esto permitirá al usuario hacer clic directamente sobre cualquier palabra en el PDF, posicionar un cursor nativo, agregar/eliminar texto en tiempo real con latencia cero (0ms) y aplicar estilos avanzados (**Bold**, **Italic**, **Justificar**, **H1**, **H2**, **Line Height**, **Letter Spacing**), sincronizándose suavemente con la fuente en Typst y el estado de la aplicación.

---

## 2. DIAGNÓSTICO DEL PDF VIEWER ACTUAL

### 2.1 Flujo de Datos Actual
```
[Estado del Capítulo: Markdown/BBCode]
         │
         ▼
[Backend PHP: typst-document.php / typst-markup.php]
         │
         ▼
[Binario Typst: Generación de PDF vectorial]
         │
         ▼
[Frontend JS: editor-typst-pdf-view.js]
         │
         ▼
[PDF.js: Renderizado sobre <canvas> HTML5]
```

### 2.2 Limitaciones para la Edición Directa
1. **El `<canvas>` es una matriz de píxeles**: No contiene nodos HTML del DOM ni admite cursores nativos (`contenteditable` o selección directa por palabra).
2. **Asincronía en la compilación**: Typst compila el documento completo o por fragmento de capítulo en el servidor. Recomponer el PDF en cada pulsación de tecla genera latencia.
3. **Desconexión visual-fuente**: Las coordenadas en el PDF renderizado no están vinculadas directamente con el offset de caracteres en el código fuente Markdown/BBCode del capítulo.

---

## 3. ARQUITECTURA TÉCNICA PROPUESTA

Para resolver este desafío sin romper el contrato del motor Typst ni la fidelidad tipográfica, se propone una **Arquitectura de 5 Capas**:

```
┌─────────────────────────────────────────────────────────────────┐
│ Capa 5: Floating Formatting Toolbar (Bold, H1, Line Height, etc)│
├─────────────────────────────────────────────────────────────────┤
│ Capa 4: Interactive Text Layer (contenteditable / Caret Overlay)│
├─────────────────────────────────────────────────────────────────┤
│ Capa 3: Source Mapper Engine (Mapeo PDF <-> Markdown / AST)    │
├─────────────────────────────────────────────────────────────────┤
│ Capa 2: Canvas Visual Render (PDF.js Canvas Original)           │
├─────────────────────────────────────────────────────────────────┤
│ Capa 1: Backend Typst Compiler & Debounced Re-sync Engine      │
└─────────────────────────────────────────────────────────────────┘
```

### 3.1 Descripción de las Capas

#### 1. Capa de Capas de Texto (PDF.js TextLayer API)
- Aprovecha la función `page.getTextContent()` de PDF.js para extraer las posiciones exactas `(x, y, width, height, transform, string)` de cada glifo y palabra en la página.
- Genera un contenedor transparente de texto superpuesto al `<canvas>` con resolución y escalado dinámico (`transform: scale(...)`).

#### 2. Mapeador Bidireccional de Fuentes (Source Mapper Engine)
- Vincula cada elemento de texto del `TextLayer` con su posición exacta en el contenido Markdown/BBCode del capítulo activo (`bookState.chapters`).
- Utiliza una estrategia de tokenización y alineación tipo *Needleman-Wunsch* o *Levenshtein Diff* para correlacionar palabras en pantalla con nodos del AST.

#### 3. Editor HTML Flotante e Interactivo (Caret & Inline Editing)
- Al hacer clic en una palabra, activa un nodo HTML `contenteditable` transparente e invisible colocado exactamente sobre el área bounding-box de la palabra o párrafo.
- La edición local (agregar o eliminar caracteres) actualiza inmediatamente el DOM local (0ms lag) y refleja provisionalmente el cambio mediante la tipografía del sistema.
- Envía el cambio al Markdown original y dispara la recomposición asíncrona de Typst.

#### 4. Barra de Formato Flotante (Formatting Toolbar & Bridge)
- Toolbar contextual que aparece al seleccionar texto en el PDF.
- Convierte acciones de UI en etiquetas Markdown/BBCode en la fuente:
  - **Bold**: Encierra el texto seleccionado entre `**` o `[b]`.
  - **Italic**: Encierra entre `*` o `[i]`.
  - **H1 / H2**: Modifica el inicio de la línea a `# ` o `## `.
  - **Justificar**: Aplica envoltorio `[align=justify]...[/align]` o atributo de alineación de párrafo.
  - **Line Height**: Inserción de parámetro `[leading=1.5em]...[/leading]` o propiedad de estilo de capítulo.
  - **Letter Spacing**: Inserción de parámetro `[tracking=0.05em]...[/tracking]`.

#### 5. Sincronización Asíncrona y Recompilación Suave
- Mantiene la sesión de compilación existente (`window.compilePDFPreview`).
- Mientras Typst compila en segundo plano, la capa provisional mantiene el cursor en su coordenada actual para evitar saltos de pantalla o pérdida de foco.

---

## 4. CUMPLIMIENTO CON `AGENTS.MD` Y NORMAS DE CÓDIGO

En cumplimiento estricto de las directrices operativas definidas en `AGENTS.md`, la solución debe respetar la arquitectura modular del plugin y el límite de líneas por archivo.

### 4.1 Regla Verificable de 500 Líneas
Ningún archivo nuevo o modificado superará las 500 líneas de código (alerta preventiva a las 400 líneas). La lógica se dividirá en módulos especializados dentro de `assets/js/pdf/typst/`:

| Archivo Proyectado | Responsabilidad Principal | Estimación Líneas |
|---|---|---|
| `assets/js/pdf/typst/editor-typst-pdf-text-layer.js` | Extracción de `getTextContent()` con PDF.js y renderizado de overlays transparentes por página. | ~320 líneas |
| `assets/js/pdf/typst/editor-typst-pdf-interactive-editor.js` | Captura de eventos `click`, posicionamiento del cursor, gestión de `contenteditable` e inserción/borrado de texto. | ~380 líneas |
| `assets/js/pdf/typst/editor-typst-pdf-source-mapper.js` | Algoritmo de mapeo bidireccional entre coordenadas PDF y offsets en Markdown/BBCode. | ~390 líneas |
| `assets/js/pdf/typst/editor-typst-pdf-toolbar.js` | Interfaz de usuario flotante para herramientas de estilo (Bold, Italic, Justify, H1, H2, Line-height, Letter-spacing). | ~290 líneas |
| `assets/js/pdf/typst/editor-typst-pdf-formatting-bridge.js` | Conversión de comandos de estilo visual a marcado Typst/BBCode/Markdown. | ~310 líneas |
| `includes/pdf-typst/typst-text-mapper.php` | Helper PHP para adjuntar metadatos de marcado y mapeo de etiquetas en la compilación de Typst. | ~260 líneas |

### 4.2 App Pages Frontend & Visual Consistency
- Se integrará con el contenedor existente en `templates/editor/` y `includes/frontend/app-shell.php`.
- La barra de formato flotante reutilizará componentes de UI del Design System (`docs/design-system.md`) y clases Tailwind estandarizadas.
- La parte visual y de responsive design se coordinará usando el subagente `css-designer`.

---

## 5. DETALLE TÉCNICO DE LAS FUNCIONALIDADES

### 5.1 Click, Cursor y Edición de Texto
1. **Detección de Clic**: Evento `pointerdown` en el contenedor `[data-page-number]`.
2. **Cálculo de Coordenadas**: Traducción de las coordenadas de la pantalla `(e.clientX, e.clientY)` a las coordenadas relativas del canvas teniendo en cuenta `zoomFactor` y `devicePixelRatio`.
3. **Colocación del Cursor**: Activación del elemento overlay editable e inserción de un elemento caret parpadeante (`.pdf-interactive-caret`).
4. **Mutación de Texto**: Escucha de eventos `beforeinput` e `input`. La eliminación (Backspace/Delete) o adición de texto actualiza tanto el overlay como la cadena fuente del capítulo.

### 5.2 Barra de Herramientas y Formatos Solicitados

| Formato | Acción de Usuario | Marcado Generado en Capítulo | Traducción en Typst Backend |
|---|---|---|---|
| **Bold** | Clic en botón **B** | `**texto**` | `*texto*` |
| **Italic** | Clic en botón *I* | `*texto*` | `_texto_` |
| **H1** | Selección en dropdown "Título 1" | `# Título` | `= Título` |
| **H2** | Selección en dropdown "Título 2" | `## Subtítulo` | `== Subtítulo` |
| **Justificar** | Clic en icono Justificar | `[align=justify]párrafo[/align]` | `#align(justify)[párrafo]` / `#set par(justify: true)` |
| **Line Height** | Selector numérico (ej. 1.4, 1.6) | `[leading=1.5em]párrafo[/leading]` | `#set par(leading: 1.5em)` |
| **Letter Spacing** | Selector numérico (ej. 0.02em) | `[tracking=0.05em]párrafo[/tracking]` | `#set text(tracking: 0.05em)` |

---

## 6. FASES DE IMPLEMENTACIÓN (ROADMAP)

Para garantizar un desarrollo ordenado, libre de regresiones y conforme a `AGENTS.md`, la implementación se estructurará en **6 Fases**:

```
┌────────────────────────────────────────────────────────────────────────┐
│ FASE 1: Capa de Texto y Detección de Clics (TextLayer PDF.js)          │
├────────────────────────────────────────────────────────────────────────┤
│ FASE 2: Motor de Mapeo de Posiciones PDF <-> Markdown Fuente           │
├────────────────────────────────────────────────────────────────────────┤
│ FASE 3: Cursor Interactivo Inline y Edición DOM sin Latencia (0ms)     │
├────────────────────────────────────────────────────────────────────────┤
│ FASE 4: Barra de Herramientas Flotante y Puente de Formatos            │
├────────────────────────────────────────────────────────────────────────┤
│ FASE 5: Integración de Sincronización Asíncrona con Typst Compiler      │
├────────────────────────────────────────────────────────────────────────┤
│ FASE 6: Pruebas, Accesibilidad, Auditoría wc -l y Documentación        │
└────────────────────────────────────────────────────────────────────────┘
```

### Fase 1: Capa de Texto y Detección de Clics
- **Objetivo**: Habilitar el overlay de texto transparente en cada página del PDF Renderizado.
- **Entregables**: `editor-typst-pdf-text-layer.js`.
- **Tareas**:
  - Implementar llamada a `pdfPage.getTextContent()` tras renderizar la página en el canvas.
  - Generar div transparente `.pdf-text-layer` superpuesto en `makePageShell`.
  - Probar precisión de colisión de clics en desktop y dispositivos móviles/pantallas táctiles.

### Fase 2: Motor de Mapeo de Posiciones (Source Mapper)
- **Objetivo**: Relacionar palabras del PDF con posiciones exactas en el texto del capítulo.
- **Entregables**: `editor-typst-pdf-source-mapper.js` y `typst-text-mapper.php`.
- **Tareas**:
  - Crear algoritmo de indexación de tokens en frontend.
  - Inyectar marcadores de bloque/párrafo opcionales desde PHP si la precisión simple por texto requiere desambiguación.

### Fase 3: Cursor Interactivo Inline (WYSIWYG Caret & Input)
- **Objetivo**: Permitir escribir, agregar y borrar caracteres con respuesta instantánea.
- **Entregables**: `editor-typst-pdf-interactive-editor.js`.
- **Tareas**:
  - Habilitar campo editable invisible/transparente sincronizado con la tipografía de la página.
  - Manejo de teclas `Backspace`, `Delete`, `Enter` y caracteres Unicode.
  - Actualización reactiva del objeto `bookState.chapters` en memoria.

### Fase 4: Barra de Herramientas Flotante y Formatos
- **Objetivo**: Permitir aplicar Bold, Italic, Justificar, H1, H2, Line Height y Letter Spacing directamente sobre la selección.
- **Entregables**: `editor-typst-pdf-toolbar.js` y `editor-typst-pdf-formatting-bridge.js`.
- **Tareas**:
  - Diseñar la barra contextual flotante con el subagente `css-designer`.
  - Implementar lógica de modificación de marcado Markdown/BBCode para los 7 elementos requeridos.

### Fase 5: Integración y Recompilación Asíncrona
- **Objetivo**: Conectar la edición directa con la compilación continua de Typst en segundo plano.
- **Entregables**: Integración con `editor-typst-pdf.js` y `editor-typst-provisional-text.js`.
- **Tareas**:
  - Configurar debounce dinámico para recomponer Typst tras pausas en la escritura.
  - Reemplazar el canvas al terminar la compilación sin parpadeos ni pérdida de posición del cursor.

### Fase 6: Pruebas, Auditoría `wc -l` y Cierre
- **Objetivo**: Verificación integral del sistema y cumplimiento normativo de `AGENTS.md`.
- **Tareas**:
  - Ejecutar verificación del tamaño de líneas (`wc -l`) en todos los archivos modificados.
  - Pruebas sintácticas de PHP (`php -l`).
  - Validación visual y responsive design en el navegador.

---

## 7. MATRIZ DE RIESGOS Y MITIGACIÓN

| Riesgo Técnico | Impacto | Estrategia de Mitigación |
|---|---|---|
| **Desalineación por Reflow** (un cambio de texto altera saltos de página). | Medio | Mostrar overlay provisional y actualizar el PDF completo vía Typst tras debounce. |
| **Latencia de servidor en libros largos**. | Alto | Utilizar la compilación por fragmentos de capítulo (`preview_scope: chapter-fragment`) ya disponible en el plugin. |
| **Complejidad de Mapeo con fuentes personalizadas**. | Medio | Usar las métricas reales de los glifos de PDF.js para posicionar la capa editable. |
| **Superación del límite de 500 líneas en JS**. | Alto | Estricta separación de funciones en los 5 archivos JS desacoplados descritos en la Fase 4.1. |

---

## 8. PLAN DE VERIFICACIÓN TÉCNICA

Antes del paso a producción de cualquier implementación futura basada en esta propuesta, se deberán ejecutar los siguientes comandos y pruebas de validación:

1. **Auditoría de Líneas (Regla 500 líneas)**:
   ```bash
   wc -l assets/js/pdf/typst/editor-typst-pdf-*.js includes/pdf-typst/typst-text-mapper.php
   ```
2. **Sintaxis PHP**:
   ```bash
   php -l includes/pdf-typst/typst-text-mapper.php
   ```
3. **Verificación en Navegador**:
   - Probar clic en primera, intermedia y última palabra de una página.
   - Probar aplicación de **Bold**, **Italic**, **Justificar**, **H1**, **H2**, **Line Height** y **Letter Spacing**.
   - Confirmar preservación del cursor durante la recomposición debounced de Typst.

---

*Fin del Informe Técnico. Guardado exitosamente en `REPORTES/PROPUESTA_EDICION_DIRECTA_PDF.md`.*
