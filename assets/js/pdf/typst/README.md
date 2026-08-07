# Preview Typst de PDF

Este directorio contiene la superficie activa del preview tipográfico de
**Almaden Bookster**. La compilación real ocurre en PHP con Typst; este lado
del cliente solo serializa el estado del libro, solicita el PDF, lo renderiza
con PDF.js y expone herramientas de diagnóstico para editar plantillas y
medidas sin tocar el manuscrito RAW.

## Qué resuelve

- Previsualización en vivo del PDF que produce Typst.
- Selección de páginas y aplicación de plantillas físicas.
- Carga de imágenes para los slots de cada plantilla.
- Toggle visual para ver los límites de la caja editorial.
- Diagnóstico de apertura separada, geometría y estado de plantillas.

## Flujo de datos

```mermaid
graph TD
    State[bookState] --> Payload[payload()]
    Payload --> Request[compileTypstPreview()]
    Request --> Ajax[almaden_compile_typst_pdf]
    Ajax --> Typst[typst-document.php]
    Typst --> Binary[typst-compiler.php]
    Binary --> PDF[PDF response]
    PDF --> Viewer[showPdf() + renderPdfPreview()]
```

1. `payload()` sincroniza el editor visual o RAW con `bookState`.
2. `compileTypstPreview()` manda el payload por `FormData` al endpoint AJAX.
3. El servidor recompone el documento Typst y devuelve el PDF más headers de
   diagnóstico.
4. `showPdf()` y `renderPdfPreview()` usan PDF.js para pintar el resultado.
5. `updateTextBounds()` puede dibujar el contorno editorial real de cada hoja.

## Archivos activos

- [`editor-typst-pdf.js`](./editor-typst-pdf.js)
  - Orquestador del preview.
  - Mantiene el PDF actual, el blob, el documento PDF.js, el layout y la
    secuencia de compilación/render.
  - Funciones clave:
    - `payload()`: arma el JSON final desde `bookState`.
    - `compileTypstPreview()`: dispara la compilación y consume headers de
      diagnóstico.
    - `showPdf()`: prepara el visor, carga el blob y actualiza el estado de
      integridad.
    - `renderPdfPreview()`: crea las páginas y aplica los overlays.
    - `applyLayout()`: alterna vista simple o spread.
    - `bindTextBoundsToggle()` / `updateTextBounds()`: activan el contorno de
      la caja editorial.

- [`page-templates/editor-page-template-selector.js`](./page-templates/editor-page-template-selector.js)
  - Selección de página en el visor.
  - Asignación o eliminación de la plantilla física de esa página.
  - Reaplica el PDF después de guardar `bookState.settings.page_templates`.
  - Funciones clave:
    - `bind(root)`: enlaza clicks sobre páginas renderizadas.
    - `applyTemplate()`: crea o reemplaza la plantilla de la página activa.
    - `removeTemplate()`: elimina la plantilla asociada a la página activa.
    - `updateSelection(root)`: pinta el estado visual de la página elegida.

- [`page-templates/editor-page-template-images.js`](./page-templates/editor-page-template-images.js)
  - Panel modal para administrar los slots de imagen de cada plantilla.
  - Lanza la biblioteca multimedia de WordPress y persiste attachments por slot.
  - Funciones clave:
    - `renderRows()`: lista todos los rectángulos/slots encontrados.
    - `openMediaUploader(rowData)`: abre la media library y asigna una imagen.
    - `clearSlotImage(rowData)`: limpia un slot.
    - `saveAndRefresh(message)`: guarda settings y recompila el preview.

## Contrato de datos

La UI espera que `bookState.settings.page_templates` sea un arreglo normalizado
de plantillas físicas. Cada entrada tiene esta forma aproximada:

```json
{
  "id": "page-2-one-column-one-image",
  "page_number": 2,
  "template_id": "one-column-one-image",
  "placeholder": { "enabled": true },
  "slots": [
    {
      "id": "image-1",
      "label": "Imagen 1",
      "kind": "image",
      "attachment_id": 0,
      "url": "",
      "preview_url": "",
      "original_url": ""
    }
  ]
}
```

## Diagnóstico y logs

- `compileTypstPreview()` escribe en consola el payload enviado.
- Si el backend responde con `X-Almaden-Typst-Opening-Debug`, el preview lo
  guarda en `window.almadenTypstOpeningDebug`.
- Si el backend responde con `X-Almaden-Page-Template-Results`, el preview lo
  guarda en `window.almadenPageTemplateResults` y muestra toasts cuando una
  plantilla no pudo aplicarse.
- `window.almadenPageTemplateFlowMap` conserva el mapa físico devuelto por el
  compilador para depuración de reflujo.

## Cómo extenderlo

1. Si agregas una nueva pantalla visual, crea un archivo propio en este mismo
   submódulo y enlázalo desde el loader central.
2. Si agregas nuevas plantillas o slots, no modifiques la persistencia desde
   la UI directamente; primero normaliza en PHP y luego refleja la estructura en
   la interfaz.
3. Si necesitas más logs, agrega `console.info` o headers específicos, no
   mensajes sueltos mezclados con la lógica de render.
4. Si este archivo supera 500 líneas durante una expansión futura, divídelo en
   un core y módulos auxiliares. Esa regla viene del `AGENT_GUIDELINES.md`.

## Relación con el motor Typst

Este directorio no compone el PDF. Solo prepara el request, consume el
resultado y ayuda a inspeccionar la geometría visual de la maqueta.
La composición editorial real vive en:

- [`includes/pdf-typst/README.md`](../../../includes/pdf-typst/README.md)
- [`includes/pdf-typst/page-templates/README.md`](../../../includes/pdf-typst/page-templates/README.md)
