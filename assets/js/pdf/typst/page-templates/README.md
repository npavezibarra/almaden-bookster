# Typst PDF Page Template UI

Este submódulo contiene la UI que administra las plantillas físicas desde el
preview PDF.js. No compone el PDF: solo edita `bookState.settings.page_templates`
y dispara recompilaciones para que el backend Typst vuelva a renderizar.

## Archivos y responsabilidades

- `editor-page-template-selector.js`
  - Detecta clics sobre páginas renderizadas.
  - Resalta la página elegida.
  - Deselecciona al repetir el clic o pulsar el fondo libre del visor.
  - Abre el modal de plantillas y pinta las opciones desde
    `window.almadenPageTemplateRegistry`.
  - Crea, reemplaza o elimina la plantilla asignada a una página.
  - Funciones clave:
    - `bind(root)`
    - `applyTemplate()`
    - `removeTemplate()`
    - `updateSelection(root)`
    - `openModal()` / `closeModal()`

- `editor-page-template-options.js`
  - Renderiza las tarjetas y miniaturas del selector de plantillas.
  - Mantiene el selector principal por debajo del límite de 500 líneas.

- `editor-page-template-images.js`
  - Renderiza el panel global `SET IMAGES` desde los controles del visor.
  - Agrupa los slots por capítulo y ofrece filtros de pendientes/asignadas.
  - Abre el selector de media del libro y guarda cada attachment de inmediato.
  - Agrupa las modificaciones y recompila Typst una sola vez al actualizar o
    cerrar el panel.
  - Funciones clave:
    - `bind()`
    - `openModal()` / `closeModal()`
    - `applyPendingChanges()`

- `editor-image-setter-data.js`
  - Construye un índice puro de capítulos, páginas, plantillas y slots.
  - Usa `resolved_page` y el contador universal para asignar cada fila a su
    capítulo actual después del reflujo.
  - Lee `aspect_ratio` desde el registro y solo expone `preview_url` para las
    miniaturas ligeras.

- `editor-page-template-state.js`
  - Centraliza la identidad estable de cada instancia.
  - Relaciona `instance_id`, `anchor.flow_id` y `resolved_page`.
  - Expone solo plantillas confirmadas como aplicadas por Typst.
  - Migra en memoria los resultados antes de redibujar la UI.

## Contrato de datos

Ambos archivos esperan la misma estructura normalizada de
`bookState.settings.page_templates` que produce el backend Typst.

Cada entrada representa una instancia editorial estable con:

- `id`
- `instance_id`
- `page_number`
- `resolved_page`
- `anchor.flow_id`
- `template_id`
- `placeholder.enabled`
- `slots[]`

Las páginas blancas que `Iniciar izquierda` agrega entre capítulos usan un
ancla `almaden-transition-N`. Los rellenos configurados antes/después de un
capítulo y los placeholders de imagen usan `almaden-blank-*`. Son páginas
físicas seleccionables y admiten los mismos presets, pero su plantilla no toma
texto de las páginas vecinas.

Cada slot puede tener:

- `id`
- `label`
- `kind`
- `attachment_id`
- `url`
- `preview_url`
- `original_url`

La definición del slot en `page-template-registry.php` agrega
`aspect_ratio.width` y `aspect_ratio.height`. Es la proporción editorial de la
caja, no la proporción del archivo que el usuario seleccione.

El modal de plantillas muestra cada preset como una miniatura con su nombre
debajo, para que el listado siga siendo legible cuando haya muchas plantillas.

## Flujo de usuario

1. El usuario hace clic en una página del preview.
2. `editor-page-template-selector.js` abre el modal de plantillas.
3. Si aplica una plantilla, el selector escribe la entrada normalizada en
   `bookState.settings.page_templates`.
4. `SET IMAGES` muestra todos los capítulos y slots detectados, incluidos los
   vacíos.
5. Al subir o quitar una imagen, la UI guarda settings sin recompilar.
6. `Actualizar PDF` o cerrar el panel ejecuta una sola composición para todos
   los cambios pendientes.

## Regla de identidad

`page_number` nunca debe usarse para buscar, reemplazar o eliminar una
plantilla. La página cambia cuando el texto refluye. Esas operaciones usan
`instance_id`; `resolved_page` sirve para presentar la página actual. La UI
puede descubrir una instancia tanto por `resolved_page` como por `page_number`
para que una plantilla siga pudiendo quitarse desde la página donde fue creada,
aunque el reflujo la haya empujado a otra hoja. Los slots pertenecen a la
instancia, por lo que eliminarla elimina también sus imágenes y evita registros
fantasma.

## Extensiones futuras

- Si aparece un preset nuevo, este submódulo debe mostrarlo sin hardcodear IDs
  en la UI. Las miniaturas pueden variar por `definition.preview.type` cuando
  la estructura visual necesita una disposición distinta.
- Si una plantilla trae más de un rectángulo, cada rectángulo debe ser un slot
  independiente y seguir teniendo un `id` estable.
- Si el backend cambia la forma del JSON, actualiza primero el normalizador en
  `includes/pdf-typst/page-templates/` y luego esta UI.
