# Typst PDF Page Template UI

Este submódulo contiene la UI que administra las plantillas físicas desde el
preview PDF.js. No compone el PDF: solo edita `bookState.settings.page_templates`
y dispara recompilaciones para que el backend Typst vuelva a renderizar.

## Archivos y responsabilidades

- `editor-page-template-selector.js`
  - Detecta clics sobre páginas renderizadas.
  - Resalta la página elegida.
  - Abre el modal de plantillas.
  - Crea, reemplaza o elimina la plantilla asignada a una página.
  - Funciones clave:
    - `bind(root)`
    - `applyTemplate()`
    - `removeTemplate()`
    - `updateSelection(root)`
    - `openModal()` / `closeModal()`

- `editor-page-template-images.js`
  - Administra los rectángulos/slots que se muestran en el panel de imágenes.
  - Abre la media library de WordPress.
  - Guarda el attachment asignado a cada slot.
  - Limpia imágenes y recompila el PDF para reflejar el cambio.
  - Funciones clave:
    - `bind()`
    - `renderRows()`
    - `openMediaUploader(rowData)`
    - `clearSlotImage(rowData)`
    - `saveAndRefresh(message)`

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

Cada slot puede tener:

- `id`
- `label`
- `kind`
- `attachment_id`
- `url`
- `preview_url`
- `original_url`

## Flujo de usuario

1. El usuario hace clic en una página del preview.
2. `editor-page-template-selector.js` abre el modal de plantillas.
3. Si aplica una plantilla, el selector escribe la entrada normalizada en
   `bookState.settings.page_templates`.
4. Si abre el panel de imágenes, `editor-page-template-images.js` muestra todos
   los slots detectados.
5. Al subir o quitar una imagen, la UI guarda settings, recompila Typst y
   redibuja el preview.

## Regla de identidad

`page_number` nunca debe usarse para buscar, reemplazar o eliminar una
plantilla. La página cambia cuando el texto refluye. Esas operaciones usan
`instance_id`; `resolved_page` sirve exclusivamente para presentar y
seleccionar la página actual. Los slots pertenecen a la instancia, por lo que
eliminarla elimina también sus imágenes y evita registros fantasma.

## Extensiones futuras

- Si aparece un preset nuevo, este submódulo debe mostrarlo sin hardcodear IDs
  en la UI.
- Si una plantilla trae más de un rectángulo, cada rectángulo debe ser un slot
  independiente y seguir teniendo un `id` estable.
- Si el backend cambia la forma del JSON, actualiza primero el normalizador en
  `includes/pdf-typst/page-templates/` y luego esta UI.
