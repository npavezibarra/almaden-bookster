# Typst Page Styles

Este submódulo concentra la lógica de estilos por página que complementa a las
plantillas físicas del motor Typst.

## Qué modela

- Un estilo pertenece a una página concreta del libro.
- Un estilo no cambia la estructura del contenido ni los slots.
- Un estilo sí cambia la apariencia visual de esa página.
- Una misma página puede tener plantilla y estilo al mismo tiempo.

## Qué controla

Cada estilo normalizado guarda estos bloques:

- `background`
  - `type`: `color`, `gradient` o `image`.
  - `color`: color de respaldo o base.
  - `gradient`: dirección y stops del degradado.
  - `image`: attachment y URLs usadas por el compilador.
  - `overlay`: color y opacidad para capas sobre imágenes.
- `text_colors`
  - `content`
  - `header`
  - `footer`
  - `opening`
  - `opening_prefix`
  - `opening_title`
  - `opening_subtitle`

## Archivos y responsabilidades

- `bootstrap.php`
  - Carga el normalizador y la persistencia.

- `page-style-normalizer.php`
  - Limpia la colección persistida.
  - Valida colores, degradados, overlay e imágenes.
  - Garantiza `instance_id`, `page_number`, `resolved_page` y `anchor`.
  - Migra registros antiguos sin identidad estable.

- `page-style-persistence.php`
  - Lee y escribe `_almaden_page_styles`.
  - Convierte la colección a JSON normalizado antes de guardarla.

## Contrato de datos

La colección persistida termina con una forma parecida a esta:

```json
{
  "id": "sty-550e8400-e29b-41d4-a716-446655440000",
  "instance_id": "sty-550e8400-e29b-41d4-a716-446655440000",
  "page_number": 4,
  "resolved_page": 4,
  "anchor": { "flow_id": "almaden-flow-17" },
  "style": {
    "background": {
      "type": "image",
      "color": "#ffffff",
      "gradient": {
        "kind": "linear",
        "angle": 135,
        "stops": [
          { "color": "#ffffff", "position": 0 },
          { "color": "#f3f4f6", "position": 100 }
        ]
      },
      "image": {
        "attachment_id": 123,
        "url": "https://...",
        "preview_url": "https://...",
        "original_url": "https://...",
        "fit": "cover",
        "position": "center"
      },
      "overlay": {
        "color": "#000000",
        "opacity": 0.35
      }
    },
    "text_colors": {
      "content": "#111111",
      "header": "#111111",
      "footer": "#111111",
      "opening": "#111111",
      "opening_prefix": "#111111",
      "opening_title": "#111111",
      "opening_subtitle": "#111111"
    }
  }
}
```

`opening` se conserva como alias de compatibilidad para estilos antiguos y se
usa como valor de respaldo cuando todavía no existen las tres variantes
específicas de la apertura.

## Flujo interno

1. El editor escribe el estilo en `bookState.settings.page_styles`.
2. `ajax-settings.php` y `ajax-settings-pdf.php` lo persisten en
   `_almaden_page_styles` y lo vuelven a inyectar antes de compilar.
3. `typst-document-context.php` lo mezcla con el resto de settings del libro.
4. `typst-document-prefix.php` lo convierte en funciones Typst para color de
   fondo y color de texto por zona.
5. Typst renderiza el documento usando esos colores ya resueltos.

## Regla de identidad

La identidad real del estilo no depende del número de página visible en el
preview. Usa `instance_id` y `anchor.flow_id`. `resolved_page` se recalcula
cuando el libro refluye y solo sirve para ubicar el estilo en la página actual.

## Relación con plantillas

- La plantilla decide la estructura.
- El estilo decide la apariencia.
- La plantilla puede reservar slots de imagen o usar toda el área de contenido.
- El estilo puede pintar el fondo o cambiar el color del texto, incluso cuando
  no hay plantilla asignada.

## Dónde mirar si algo falla

- Si el estilo no persiste, revisar `_almaden_page_styles`.
- Si el color no cambia en Typst, revisar `typst-document-prefix.php`.
- Si la página correcta no recibe el estilo, revisar el mapeo de
  `resolved_page` y `anchor.flow_id`.
