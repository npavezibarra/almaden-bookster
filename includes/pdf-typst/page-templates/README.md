# Typst Page Templates

Este submódulo concentra toda la lógica de plantillas físicas aplicadas sobre
páginas reales del PDF Typst. Se mantiene fuera de `typst-document.php` para
que la composición principal no crezca con la lógica de plantillas.

## Qué modela

- Plantillas por página física, no por bloque HTML.
- Una plantilla puede tener uno o más rectángulos/slots.
- Cada slot puede almacenar una imagen asignada desde el panel del editor.
- El composer puede reinyectar el source Typst para que el texto restante
  siga fluyendo hacia páginas posteriores.

## Archivos y responsabilidades

- `bootstrap.php`
  - Carga el submódulo Typst de plantillas.

- `page-template-registry.php`
  - Declara los presets disponibles.
  - Hoy los presets activos incluyen `one-column-one-image` e `inner-full-page`.
  - Aquí se registran también el label visible y los slots esperados.

- `page-template-normalizer.php`
  - Normaliza el arreglo persistido.
  - Elimina instancias duplicadas, no páginas duplicadas.
  - Garantiza identidad, ancla, preset y estructura de slots válidos.

- `page-template-identity.php`
  - Normaliza `instance_id` y `anchor.flow_id`.
  - Migra registros antiguos que solo tenían `page_number`.
  - Resuelve la página física actual desde el mapa de flujo Typst.

- `page-template-persistence.php`
  - Lee y escribe `_almaden_page_templates`.
  - La persistencia siempre se guarda como JSON normalizado.

- `page-template-placeholder.php`
  - Renderiza el placeholder temporal de cada slot.
  - En ausencia de imagen, devuelve un rectángulo naranjo.
  - Si existen varios slots, arma una grilla vertical para todos.

- `page-template-slots.php`
  - Normaliza los slots de acuerdo al registry.
  - Resuelve IDs únicos y seguros.
  - Convierte attachments a rutas de asset aptas para Typst.
  - Si `attachment_id` no viene en el payload persistido, intenta recuperarlo
    desde `original_url`, `url` o `preview_url` para no perder la imagen al
    recompilar.
  - Si WordPress no puede convertir la URL en un `attachment_id` (por ejemplo,
    una URL redimensionada, `-scaled` o un registro antiguo), resuelve la URL
    directamente contra `wp_upload_dir()` antes de compilar con Typst.
  - Renderiza el metadata anchor por slot.

- `page-template-composer.php`
  - Es el único boundary autorizado para transformar el source Typst
    generado.
  - Busca los bloques físicos del flujo.
  - Reemplaza la región de la página objetivo por un wrapper de página
    completa.
  - Soporta layouts `split` y `full` para poder sumar más presets sin tocar el
    composer principal en cada nuevo caso.

- `page-template-word-flow.php`
  - Ejecuta el probe de Typst por palabra.
  - Detecta el último punto visible real de la caja de texto.
  - Usa ese corte para dividir el bloque entre lo visible en la página actual y
    lo que debe continuar en páginas posteriores.

## Contrato de datos

El arreglo persistido en `_almaden_page_templates` termina en algo así:

```json
{
  "id": "tpl-550e8400-e29b-41d4-a716-446655440000",
  "instance_id": "tpl-550e8400-e29b-41d4-a716-446655440000",
  "page_number": 2,
  "resolved_page": 4,
  "anchor": { "flow_id": "almaden-flow-17" },
  "template_id": "one-column-one-image",
  "placeholder": { "enabled": true },
  "slots": [
    {
      "id": "image-1",
      "label": "Imagen 1",
      "kind": "image",
      "attachment_id": 123,
      "url": "https://...",
      "preview_url": "https://...",
      "original_url": "https://..."
    }
  ]
}
```

## Flujo interno

1. El editor guarda `page_templates` en settings.
2. `ajax-typst-pdf.php` vuelve a inyectar la colección persistida antes de
   compilar.
3. `typst-document.php` llama al composer de plantillas.
4. El composer toma la página física objetivo, identifica los bloques del flujo
   y calcula el corte exacto.
5. Typst recompone el resto del libro a partir de la nueva página insertada.

## Identidad frente a paginación

- `instance_id` es la fuente de verdad para editar, reemplazar o eliminar.
- `anchor.flow_id` une la instancia al contenido del manuscrito.
- `resolved_page` es un resultado de compilación y puede cambiar libremente.
- `page_number` queda como compatibilidad y punto inicial para migrar registros
  antiguos sin ancla.
- El compilador procesa por orden de ancla y vuelve a consultar el flujo tras
  cada plantilla, recolocando las siguientes después de cada reflujo.
- Al eliminar una instancia desaparece el registro completo con sus slots. El
  panel de imágenes solo muestra resultados con `applied: true`.

## Funciones clave para mantenimiento

- `almaden_bookster_typst_page_template_context()`
- `almaden_bookster_typst_page_template_source_blocks()`
- `almaden_bookster_typst_page_template_apply_blocks()`
- `almaden_bookster_typst_page_template_take_slice()`
- `almaden_bookster_typst_apply_page_template_flow()`
- `almaden_bookster_typst_page_template_block_parts()`
- `almaden_bookster_typst_page_template_transform_words()`
- `almaden_bookster_typst_page_template_split_body_at_word()`
- `almaden_bookster_typst_page_template_probe_page()`
- `almaden_bookster_typst_page_template_prepare_word_probe()`
- `almaden_bookster_typst_page_template_probe_cut()`
- `almaden_bookster_typst_page_template_fragment_layout()`
- `almaden_bookster_typst_page_template_slot_anchor_id()`
- `almaden_bookster_typst_page_template_render_slot()`

## Si vas a agregar un preset nuevo

1. Regístralo en `page-template-registry.php`.
2. Define qué slots necesita y qué tipo de contenido tendrá cada uno.
3. Ajusta `page-template-placeholder.php` si el layout visual requiere una
   disposición distinta.
4. Ajusta `page-template-slots.php` si cambian la semántica o los IDs.
5. Si el reflujo del texto se comporta distinto, toca primero
   `page-template-word-flow.php` y luego el composer.
6. La UI del editor debe reflejar el nuevo preset en
   `assets/js/pdf/typst/page-templates/`.

## Reglas de optimización

- Mantén los IDs de slots estables.
- Nunca construyas identidad desde el número de página física.
- Guarda siempre `attachment_id` cuando exista; las URLs quedan como respaldo
  para reconstruirlo si hace falta, pero Typst compila mejor cuando el ID está
  presente.
- Evita estimar cortes de texto por cantidad fija de caracteres.
- Prefiere el probe por palabra cuando la página tenga imagen y el texto deba
  refluír.
- Si la plantilla necesita más de un rectángulo, modela cada rectángulo como un
  slot independiente para que el panel de imágenes siga siendo directo.
