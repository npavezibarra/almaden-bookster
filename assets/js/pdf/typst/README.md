# Preview Typst de PDF

Este directorio contiene la superficie activa del preview tipográfico de
**Almaden Bookster**. La compilación real ocurre en PHP con Typst; este lado
del cliente solo serializa el estado del libro, solicita el PDF, lo renderiza
con PDF.js y expone herramientas de diagnóstico para editar plantillas y
medidas sin tocar el manuscrito RAW.

## Qué resuelve

- Previsualización en vivo del PDF que produce Typst.
- Render por capítulo o PDF completo según `bookState.pdfPreview.mode`.
- Toggle explícito en la barra del visor para alternar entre `chapter` y
  `full` sin tocar el manuscrito ni la estructura de plantillas.
- Selección de páginas y aplicación de plantillas físicas.
- Carga de imágenes para los slots de cada plantilla.
- Toggle visual para ver los límites de la caja editorial.
- Diagnóstico de apertura separada, geometría y estado de plantillas.

## Flujo de datos

```mermaid
graph TD
    State[bookState] --> Payload[payload()]
    Payload --> Request[compileTypstPreview()]
    Request --> BrowserCache[IndexedDB]
    BrowserCache -->|miss| Ajax[almaden_compile_typst_pdf]
    Ajax --> ServerCache[Cache privado por contenido]
    ServerCache -->|miss| Typst[typst-document.php]
    Typst --> Binary[typst-compiler.php]
    Binary --> PDF[PDF response]
    PDF --> Viewer[showPdf() + renderPdfPreview()]
```

1. `payload()` sincroniza el editor visual o RAW con `bookState`.
2. `compileTypstPreview()` reutiliza primero el blob en memoria o la entrada
   exacta almacenada en IndexedDB.
3. En un miss local, el servidor consulta su caché privado por contenido antes
   de ejecutar Typst y devuelve el PDF más headers de
   diagnóstico.
4. `showPdf()` y `renderPdfPreview()` usan PDF.js para pintar el resultado.
5. `updateTextBounds()` puede dibujar el contorno editorial real de cada hoja.
6. Si el modo está en `chapter`, el visor solo renderiza las páginas del
   capítulo activo usando el contador universal y `bookState.activeChapterId`.
7. Si el usuario cambia a `full`, el visor vuelve a pintar todo el PDF con la
   misma numeración global y conserva el layout de lectura para revisión
   completa.

## Guías de sangría (bleed)

El visor representa la sangría como una ayuda visual alrededor del canvas de
PDF.js. No cambia el tamaño del PDF, no modifica su contenido y no participa en
la exportación. La implementación vive en `updateBleedGuides()` dentro de
`editor-typst-pdf-view.js`.

### Geometría y guías

- `bookState.settings.bleeding` llega al visor mediante
  `shared.currentGeometry.bleed`, en la misma unidad que `width` y `height`.
- `getBleedGuideOffsetPx()` calcula los píxeles visibles a partir de la escala
  real de cada página. Para ello usa la menor relación entre ancho/alto del
  shell y las dimensiones editoriales, evitando diferencias por zoom.
- La línea continua marca el límite final de corte y coincide exactamente con
  el borde original de la página renderizada.
- La línea punteada marca el límite exterior de la sangría. La distancia entre
  ambas líneas corresponde al valor configurado; por ejemplo, `0.5 cm`.
- El fondo entre ambos límites es blanco en páginas sin arte full bleed.

La sangría solo existe arriba, abajo y en el lado exterior de la hoja. Nunca se
agrega en el lado interior o lomo:

| Página | Lado exterior con bleed | Lado interior sin bleed |
| --- | --- | --- |
| Par, página izquierda | Izquierdo | Derecho |
| Impar, página derecha | Derecho | Izquierdo |

Por esta razón, el fondo y la línea punteada usan offsets distintos según
`data-page-number`. En el lado interior solo aparece la línea continua del
límite de página; no debe existir gap ni una segunda guía.

### Imágenes full bleed

Las imágenes solo rellenan la sangría cuando el capítulo cumple las tres
condiciones siguientes:

1. `chapter_image_enabled` está activo.
2. `chapter_image_mode` es `image_full_page`.
3. `chapter_image_url` contiene una imagen.

El visor relaciona la página física con el capítulo usando
`bookState.pdfPreview.universalCounter.chapters[].startPage`. Después coloca la
imagen original como una sola capa que cubre la página y su bleed. Esa capa
comienza invisible; únicamente después de cargar correctamente oculta el canvas
de PDF.js y pasa a ser visible. De este modo nunca aparecen dos versiones de la
misma imagen. Si la carga falla, la capa se elimina y el canvas permanece como
respaldo para evitar una página vacía.

La capa de imagen usa `object-fit: cover` y `object-position: center center`:

- conserva siempre la relación de aspecto original;
- cubre el ancho de la página más la sangría exterior;
- centra la imagen respecto de la página;
- recorta el excedente superior e inferior cuando es necesario;
- reemplaza visualmente la imagen del canvas, no se superpone como una segunda
  copia;
- nunca estira franjas, filas de píxeles ni bordes del canvas.

No se debe reconstruir el bleed copiando o escalando píxeles del borde del
canvas. Ese enfoque deforma imágenes y también puede interpretar texto cercano
al borde como si fuera arte full bleed. Al volver a renderizar o cambiar zoom,
`updateBleedGuides()` elimina todas las capas anteriores y las reconstruye con
la geometría actual.

### Continuidad durante la edición

`editor-typst-preview-experience.js` desacopla la escritura de la composición
definitiva. Las ediciones RAW se agrupan durante una pausa breve, mientras que
acciones explícitas como cambiar de capítulo se procesan con una espera menor.
Cuando comienza Typst, el último PDF confirmado permanece visible en una capa
de continuidad hasta que PDF.js termina de pintar la nueva revisión.

- Una edición de texto espera 700 ms de inactividad y nunca más de 1800 ms.
- Una acción explícita del toolbar arranca Typst sin debounce adicional.
- El toolbar solicita la vista provisional antes de iniciar la composición,
  por lo que negrita, cursiva, tamaño y otros formatos responden en el siguiente
  frame del navegador.
- El scheduler original se invoca sin su segundo debounce para evitar esperas
  acumuladas.
- Si la compilación falla, se restaura el DOM/canvas confirmado; el RAW nunca
  se reemplaza con contenido del visor.
- El log `[Typst preview responsiveness]` separa tiempo en cola y tiempo real
  de compilación/render.
- `editor-typst-provisional-text.js` proyecta inmediatamente el párrafo donde
  está el cursor sobre una página estimada del capítulo. El recuadro se marca
  explícitamente como provisional porque no reproduce cortes, columnas ni
  separación silábica definitiva.

## Caché e invalidación

- La firma del cliente incluye manuscrito, configuración, plantillas, modo de
  assets y portada. El contador universal se excluye porque es resultado de la
  compilación y no una entrada editorial.
- IndexedDB conserva hasta seis composiciones por libro durante siete días.
- El servidor conserva hasta doce composiciones privadas por libro durante
  siete días. Su clave incluye el source Typst y la fecha/tamaño de imágenes y
  fuentes locales.
- Cambiar vista, zoom, spread o capítulo reutiliza el blob actual. Una edición
  que altere el payload genera una firma distinta y compila normalmente.
- La exportación usa `assetMode: original`, por lo que nunca puede coincidir
  con una entrada de preview optimizado.
- El header `X-Almaden-Typst-Cache` indica `HIT`, `MISS-STORED` o
  `MISS-NOSTORE`; el log
  `[Typst preview performance]` informa `memory`, `browser-cache`,
  `server-cache` o `typst` junto al tiempo total percibido.

## Archivos activos

- [`editor-typst-pdf.js`](./editor-typst-pdf.js)
  - Orquestador del preview y punto de entrada público.
  - Expone `compilePDFPreview`, `triggerPrint` y `window.almadenTypstPdf`.
  - Coordina el estado compartido con los módulos auxiliares.

- [`editor-typst-pdf-state.js`](./editor-typst-pdf-state.js)
  - Normalización de payload, firma de caché y contador universal.
  - Manejo de IndexedDB para el cache persistente del preview.
  - Construcción del contrato público `bookState.pdfPreview`.

- [`editor-typst-pdf-view.js`](./editor-typst-pdf-view.js)
  - Render PDF.js, layout single/spread y overlays de diagnóstico.
  - Gestión del blob actual, geometría, zoom y límites de texto.
  - Actualización visual del visor sin tocar el manuscrito.

- [`editor-typst-preview-experience.js`](./editor-typst-preview-experience.js)
  - Agrupa cambios rápidos antes de invocar Typst.
  - Conserva visible la última composición confirmada durante el procesamiento.
  - Reemplaza la capa anterior únicamente cuando el nuevo PDF terminó de
    compilarse y renderizarse.
  - Restaura la composición anterior cuando una actualización falla.

- [`editor-typst-provisional-text.js`](./editor-typst-provisional-text.js)
  - Compara el RAW actual con la última revisión confirmada.
  - Localiza el párrafo del cursor y estima su página por avance dentro del
    capítulo.
  - Muestra negrita, cursiva, subrayado y tamaño como respuesta optimista.
  - Desaparece cuando llega la composición real o cuando esta falla.

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

Además, la fase 1 deja preparado este contrato de preview:

```json
{
  "pdfPreview": {
    "mode": "chapter",
    "assetMode": "optimized",
    "counterMode": "global",
    "universalCounter": {
      "version": 1,
      "ready": false,
      "source": "full-book",
      "totals": {
        "pages": null,
        "blankPages": null,
        "chapters": null
      },
      "chapters": []
    }
  }
}
```

Ese bloque controla el modo visible, la selección de assets y la paginación.
Cuando el backend devuelve el índice de capítulos, el viewer lo combina con
`pdfDocument.numPages` para derivar rangos globales reales por capítulo.

## Diagnóstico y logs

- `compileTypstPreview()` escribe en consola el payload enviado.
- Si el backend responde con `X-Almaden-Typst-Opening-Debug`, el preview lo
  guarda en `window.almadenTypstOpeningDebug`.
- Si el backend responde con `X-Almaden-Page-Template-Results`, el preview lo
  guarda en `window.almadenPageTemplateResults` y muestra toasts cuando una
  plantilla no pudo aplicarse.
- Si el backend responde con `X-Almaden-Universal-Counter`, el preview lo
  normaliza en `bookState.pdfPreview.universalCounter`.
- El modo de preview del visor se puede persistir localmente en
  `almaden_pdf_preview_mode`; por defecto arranca en `chapter`.
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
