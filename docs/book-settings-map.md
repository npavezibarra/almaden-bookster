# Almaden Bookster: mapa mental de los ajustes del libro

Este documento resume dónde se guardan los ajustes del libro en la base de datos, qué archivos los crean, cómo se guardan y cómo se recuperan para el editor, el frontend y las exportaciones.

La idea principal es esta:

- Los ajustes globales del libro viven en una tabla propia: `{$wpdb->prefix}almaden_book_settings`.
- Los ajustes que dependen de un libro o de un capítulo viven en `post_meta`.
- Los capítulos son posts del CPT `book_chapter`.
- El libro principal es un post del CPT `almaden-books`.
- PDF impreso y ebook comparten la misma tabla global, pero usan campos distintos.

## Mapa rápido

| Área | Dónde se guarda | Cómo se lee |
|---|---|---|
| Ajustes globales del libro | Tabla `almaden_book_settings` | `almaden_get_book_pdf_settings()` |
| Ajustes de flujo global del libro | `post_meta` del libro | `get_post_meta()` en `includes/frontend.php` y `editor-data-loader.php` |
| Ajustes de subtítulo global PDF | `post_meta` del libro con prefijo `_almaden_chapter_subtitle_*` | `almaden_get_book_pdf_settings()` |
| Ajustes de subtítulo global ebook | `post_meta` del libro con prefijo `_almaden_ebook_subtitle_*` | `almaden_get_book_pdf_settings()` |
| Créditos globales del libro | `post_meta` del libro con `_almaden_credits_config` y llaves legacy | `almaden_get_book_pdf_settings()`, `editor-data-loader.php` |
| Ajustes por capítulo | `post_meta` del `book_chapter` | `includes/frontend.php` y `includes/io/book-export.php` |
| Índice / TOC | `post_meta` del capítulo marcado como TOC | `includes/frontend.php` y exportadores |
| Página de créditos | `post_meta` del capítulo marcado como créditos | `includes/frontend.php` y exportadores |

## 1. Dónde vive cada tipo de ajuste

### 1.1 Ajustes globales del libro

La tabla personalizada se crea en [`almaden-bookster.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/almaden-bookster.php) con `almaden_bookster_create_settings_table()`.

Archivo clave:

- [`almaden-bookster.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/almaden-bookster.php#L73)

La fila está indexada por `book_id` y contiene casi toda la configuración de maquetación:

- Página e impresión: tamaño, unidades, márgenes, padding, bleeding, escala de grises.
- Tipografía base.
- Cabecera y pie.
- Footnotes.
- Inicio de capítulo.
- Ajustes de ebook.

Importante: PDF impreso y ebook no van a tablas separadas. Van en la misma fila, pero con columnas distintas:

- PDF impreso: `page_*`, `margin_*`, `padding_*`, `header_*`, `footer_*`, `footnote_*`, `chapter_*`, etc.
- Ebook: `ebook_*`.

### 1.2 Ajustes globales que no están en la tabla

Hay una segunda capa de ajustes globales guardada en `post_meta` del libro:

- `_almaden_book_separate_opening_content`
- `_almaden_book_chapter_flow_mode`
- `_almaden_book_flow_legacy_parity`
- `_almaden_chapter_subtitle_*`
- `_almaden_ebook_subtitle_*`
- `_almaden_credits_config`
- `_almaden_credits_edition`
- `_almaden_credits_date`
- `_almaden_credits_isbn`
- `_almaden_credits_copyright`
- `_almaden_credits_printer`
- `_almaden_credits_blank_before`
- `_almaden_credits_blank_after`
- `_almaden_credits_license`
- `_almaden_credits_custom`

### 1.3 Ajustes por capítulo

Cada capítulo es un post del CPT `book_chapter`.

Los ajustes se guardan en `post_meta` del capítulo, por ejemplo:

- `_start_parity`
- `_opening_page_mode`
- `_opening_blank_intentional`
- `_opening_block_enabled`
- `_opening_block_horizontal_align`
- `_opening_block_vertical_align`
- `_parity_image`
- `_parity_image_mode`
- `_parity_image_width`
- `_parity_image_height`
- `_hide_title`
- `_hide_all_headers_footers`
- `_exclude_from_numbering`
- `_custom_running_header`
- `_subtitle_*`
- `_drop_cap_enabled`
- `_disable_hyphenation`
- `_first_page_header_*`
- `_first_page_footer_*`
- `_is_toc`
- `_is_credits`
- `_credits_*`
- `_toc_*`

## 2. Archivo que crea la tabla

### [`almaden-bookster.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/almaden-bookster.php#L73)

`almaden_bookster_create_settings_table()` crea `almaden_book_settings` al activar el plugin o al iniciar si detecta que falta o que la versión de esquema cambió.

Ahí se define el schema completo:

- `book_id` como clave única.
- Columnas para settings de PDF.
- Columnas para settings de ebook.
- Columnas para flow de capítulos.
- Columnas para títulos de capítulos.

## 3. Cómo se crean los ajustes al crear un libro nuevo

### [`includes/frontend.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php#L316)

Cuando se crea un libro nuevo desde el formulario del creador, el plugin hace esto:

- Inserta el post `almaden-books`.
- Si el libro se creó con formato impreso y hay tamaño definido, inserta una fila mínima en `almaden_book_settings` con:
  - `book_id`
  - `page_size`
  - `page_width`
  - `page_height`

Eso significa que el libro puede nacer con una configuración básica de impresión, aunque el resto de ajustes todavía no se hayan tocado.

## 4. Cómo se guardan los ajustes globales

### 4.1 Guardado principal de configuración global

Archivo:

- [`includes/ajax/ajax-settings.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings.php#L6)

Función:

- `almaden_bookster_save_settings_ajax()`

Qué hace:

1. Verifica `book_id` y nonce.
2. Construye un array `$data` con casi todos los campos globales del formulario.
3. Busca si ya existe una fila en `almaden_book_settings` para ese `book_id`.
4. Si existe, hace `UPDATE`; si no, hace `INSERT`.
5. Guarda además algunos valores complementarios en `post_meta` del libro.

### 4.2 Qué se guarda en la tabla

La tabla recibe, entre otros:

- Página:
  - `unit`
  - `page_size`
  - `page_width`
  - `page_height`
  - `margin_top`, `margin_bottom`, `margin_left`, `margin_right`
  - `margin_left_odd`, `margin_right_odd`
  - `margin_left_even`, `margin_right_even`
  - `padding_*`
  - `bleeding`
  - `export_grayscale`
- Ebook:
  - `ebook_bg_*`
  - `ebook_cover_panel_bg_*`
  - `ebook_font_*`
  - `ebook_line_height_*`
  - `ebook_text_align_justify`
  - `ebook_hyphenation`
  - `ebook_chapter_title_*`
  - `ebook_chapter_prefix_*`
- Tipografía y layout global PDF:
  - `font_*`
  - `content_*`
  - `header_*`
  - `footer_*`
  - `first_page_*`
  - `book_start_page_footer_type`
  - `footnote_*`
  - `chapter_*`

### 4.3 Qué se guarda en `post_meta` del libro

En el mismo guardado global, el plugin escribe:

- `_almaden_book_separate_opening_content`
- `_almaden_book_chapter_flow_mode`
- `_almaden_book_flow_legacy_parity`

Y también los subtítulos globales:

- `_almaden_chapter_subtitle_show`
- `_almaden_chapter_subtitle_font_family`
- `_almaden_chapter_subtitle_font_size`
- `_almaden_chapter_subtitle_align`
- `_almaden_chapter_subtitle_font_style`
- `_almaden_chapter_subtitle_text_transform`
- `_almaden_chapter_subtitle_font_weight`
- `_almaden_chapter_subtitle_margin_top`
- `_almaden_chapter_subtitle_margin_bottom`
- `_almaden_chapter_subtitle_letter_spacing`
- `_almaden_ebook_subtitle_show`
- `_almaden_ebook_subtitle_font_family`
- `_almaden_ebook_subtitle_font_size`
- `_almaden_ebook_subtitle_align`
- `_almaden_ebook_subtitle_font_style`
- `_almaden_ebook_subtitle_text_transform`
- `_almaden_ebook_subtitle_font_weight`
- `_almaden_ebook_subtitle_padding_top`
- `_almaden_ebook_subtitle_padding_bottom`
- `_almaden_ebook_subtitle_letter_spacing`

### 4.4 Guardado de créditos desde el guardado global

Si el request trae `credits_config`, el guardado global llama a:

- `almaden_bookster_save_credits_from_request()`

Eso vive en [`includes/ajax/ajax-credits-persistence.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-credits-persistence.php#L93).

## 5. Cómo se guardan los créditos

### Archivo principal

- [`includes/ajax/ajax-credits-persistence.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-credits-persistence.php#L58)

### Idea central

Los créditos tienen dos representaciones al mismo tiempo:

1. Una versión estructurada moderna en `_almaden_credits_config`.
2. Una versión legacy plana en varias llaves sueltas para compatibilidad con pantallas viejas, exportaciones viejas o integraciones antiguas.

### Flujo de guardado

1. El frontend recopila la estructura completa de créditos.
2. Se normaliza con `almaden_bookster_normalize_credits_config()`.
3. Se guarda el JSON completo en `_almaden_credits_config`.
4. Se regeneran y guardan también las llaves legacy:
   - `_almaden_credits_edition`
   - `_almaden_credits_date`
   - `_almaden_credits_isbn`
   - `_almaden_credits_copyright`
   - `_almaden_credits_printer`
   - `_almaden_credits_blank_before`
   - `_almaden_credits_blank_after`
   - `_almaden_credits_license`
   - `_almaden_credits_custom`

### Endpoints que lo usan

- `almaden_bookster_save_credits_config_ajax()`
- `almaden_bookster_save_credits_from_request()`
- el guardado global de settings en `ajax-settings.php`
- el guardado completo del libro en `ajax-save-book.php`

### Normalización y compatibilidad

La normalización vive en [`includes/ajax/ajax-settings-credits.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings-credits.php#L262).

Eso permite aceptar:

- la estructura nueva `credits_config`
- datos legacy como `credits_custom`
- valores antiguos como `credits_edition`, `credits_date`, etc.

En resumen: el plugin no depende solo de un formato; puede leer ambos y producir uno canónico.

## 6. Cómo se guardan los capítulos

### Archivo principal

- [`includes/ajax/ajax-save-book.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-save-book.php#L8)

### Función

- `almaden_bookster_save_book_ajax()`

### Qué hace

1. Recibe el array `chapters` en JSON.
2. Para cada capítulo:
   - Crea un `book_chapter` nuevo si el ID no es numérico.
   - Actualiza el post existente si el ID ya existe.
3. Guarda todos los metadatos del capítulo con `update_post_meta()`.
4. Borra los capítulos que ya no están en el payload.
5. Devuelve los capítulos actualizados para refrescar el editor.

### Dónde se guardan los ajustes del capítulo

Cada uno de estos campos se escribe en `post_meta` del `book_chapter`:

- Flujo y apertura:
  - `_start_parity`
  - `_opening_page_mode`
  - `_opening_blank_intentional`
  - `_opening_block_enabled`
  - `_opening_block_horizontal_align`
  - `_opening_block_vertical_align`
  - `_parity_image`
  - `_parity_image_mode`
  - `_parity_image_width`
  - `_parity_image_height`
- Visibilidad y numeración:
  - `_hide_title`
  - `_hide_all_headers_footers`
  - `_exclude_from_numbering`
  - `_custom_running_header`
- Subtítulo:
  - `_subtitle_text`
  - `_subtitle_font_family`
  - `_subtitle_align`
  - `_subtitle_font_size`
  - `_subtitle_letter_spacing`
  - `_subtitle_font_style`
  - `_subtitle_text_transform`
  - `_subtitle_font_weight`
  - `_subtitle_margin_top`
  - `_subtitle_margin_bottom`
- Comportamiento tipográfico:
  - `_drop_cap_enabled`
  - `_disable_hyphenation`
- Cabecera / pie de primera página:
  - `_first_page_header_type`
  - `_first_page_header_custom`
  - `_first_page_footer_type`
  - `_first_page_footer_custom`
- Índice / TOC:
  - `_is_toc`
  - `_toc_font_family`
  - `_toc_font_size`
  - `_toc_enumerate`
  - `_toc_font_style`
  - `_toc_font_weight`
  - `_toc_text_transform`
  - `_toc_letter_spacing`
  - `_toc_line_height`
  - `_toc_item_spacing`
  - `_toc_hide_header`
  - `_toc_hide_page_numbers`
  - `_toc_separate_opening_content`
  - `_toc_item_align`
  - `_toc_leader_style`
  - `_toc_leader_position`
  - `_toc_title_align`
  - `_toc_title_font_family`
  - `_toc_title_font_size`
  - `_toc_title_font_style`
  - `_toc_title_text_transform`
  - `_toc_title_font_weight`
  - `_toc_title_padding_top`
  - `_toc_title_padding_bottom`
  - `_toc_title_line_height`
- Página de créditos:
  - `_is_credits`
  - `_credits_font_family`
  - `_credits_align`
  - `_credits_font_size`
  - `_credits_letter_spacing`
  - `_credits_font_weight`
  - `_credits_hide_header`
  - `_credits_hide_page_number`
  - `_credits_margin_top`
  - `_credits_margin_bottom`

## 7. Cómo se leen los datos en el editor

### 7.1 Carga inicial de capítulos y settings

Archivo:

- [`includes/helpers/editor-data-loader.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/helpers/editor-data-loader.php#L1)

Ahí se hace una carga completa para montar el editor:

- Obtiene el libro.
- Obtiene los capítulos `book_chapter`.
- Lee todos los `post_meta` de capítulo.
- Llama a `almaden_get_book_pdf_settings()` para traer la configuración global.

### 7.2 Carga de ajustes globales

Archivo:

- [`includes/ajax/ajax-settings.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings.php#L233)

`almaden_bookster_get_settings_ajax()` devuelve:

- `settings => almaden_get_book_pdf_settings( $book_id )`

### 7.3 Qué hace `almaden_get_book_pdf_settings()`

Archivo:

- [`includes/ajax/ajax-settings.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings.php#L238)

Y más arriba en el mismo archivo:

- [`includes/ajax/ajax-settings.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/ajax-settings.php#L14)

Esta función:

1. Lee la fila de `almaden_book_settings`.
2. Si no hay fila, parte de defaults en PHP.
3. Sobrescribe defaults con los valores guardados.
4. Lee créditos y subtítulos desde `post_meta`.
5. Lee `book_separate_opening_content` y `book_chapter_flow_mode`.
6. Devuelve un array ya listo para el editor y para exportación.

### 7.4 Cómo se rellenan los campos del modal

Archivo:

- [`assets/js/editor/editor-settings-tabs.js`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-tabs.js#L96)

`window.populateSettingsForm()` toma `bookState.settings` y lo vuelca en los inputs del modal:

- Pestaña Página
- Pestaña ebook
- Pestaña Tipografía
- Pestaña Cabecera y Pie
- Pestaña Footnotes
- Pestaña Capítulos / Libro
- Pestaña Créditos

### 7.5 Cómo se rellena el editor de créditos

Archivo:

- [`assets/js/editor/editor-settings-credits.js`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-credits.js#L66)

`creditsSyncStateFromForm()` y `initCreditsForm()` hacen dos cosas:

- convierten la UI a un `credits_config` estructurado
- sincronizan `bookState.settings.credits_config` con la versión que vino del servidor

La fuente autoritativa es el servidor, no el `localStorage`.

## 8. Cómo se guardan los cambios desde el editor

### Archivo principal

- [`assets/js/editor/editor-settings-api.js`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/editor-settings-api.js#L2)

### Qué envía

`savePDFSettings()` manda por `fetch()` un `FormData` enorme con:

- todos los settings globales
- todos los settings ebook
- todos los settings de subtítulos
- todos los settings de capítulo global
- `credits_config`
- los campos legacy de créditos

### Qué ocurre después del guardado

Si el servidor responde bien:

- el botón vuelve a su estado normal
- `bookState.settings` se refresca con los valores actuales del formulario
- el editor sigue trabajando con esa copia local, pero la fuente real sigue siendo la BD

### Nota importante

Hay una sincronización intencional entre:

- el objeto estructurado `credits_config`
- las llaves legacy `credits_*`

Eso evita romper exportaciones antiguas y permite que el editor viejo y el nuevo convivan.

## 9. Cómo se usa en exportación y frontend

### Exportación

Archivo:

- [`includes/io/book-export.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/io/book-export.php#L74)

Ahí se arma un paquete con:

- metadatos del libro
- configuración global desde la tabla
- créditos desde `credits_config`
- capítulos con todos sus `post_meta`

### Frontend

Archivo:

- [`includes/frontend.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php#L1)

Este archivo carga:

- la lista de capítulos
- la configuración global del libro
- las plantillas de la app del editor y del reader

Es una buena referencia para entender qué se considera “estado del libro” en tiempo de ejecución.

## 10. Precedencia y reglas que conviene recordar

- PDF y ebook comparten tabla, pero no comparten semántica completa.
- Los subtítulos globales viven en `post_meta`, no en la tabla.
- Los créditos tienen formato moderno estructurado y formato legacy en paralelo.
- Los capítulos siempre mandan sobre el libro cuando tienen override explícito.
- El capítulo TOC y el capítulo créditos son capítulos normales con flags especiales:
  - `_is_toc`
  - `_is_credits`
- Si un capítulo es TOC, el editor fuerza por defecto:
  - `_toc_hide_header = 1`
  - `_toc_hide_page_numbers = 1`

## 11. Cosas adicionales que vale la pena saber

### 11.1 El plugin mantiene compatibilidad hacia atrás

Hay varias llaves legacy que siguen existiendo aunque ya no sean el camino principal:

- `_page_one_vertical`
- `_toc_page_one_vertical`
- `credits_custom`
- `credits_edition`, `credits_date`, etc.

Esto significa que una lectura o exportación puede encontrar más de una representación del mismo concepto.

### 11.2 Duplicar un libro copia la fila de settings

En [`includes/frontend.php`](/Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/frontend.php#L434), al duplicar un libro:

- se copia la fila completa de `almaden_book_settings`
- se copian varios `post_meta` del libro
- los capítulos se gestionan aparte

### 11.3 El borrado del libro elimina la fila de settings

Cuando se elimina un libro, el plugin borra también la fila correspondiente de `almaden_book_settings`.

### 11.4 El editor siempre parte de la BD

En `initCreditsForm()` el editor de créditos toma como fuente primaria lo que llegó del servidor, no lo que haya quedado en el navegador.

Eso ayuda a evitar que la UI se desincronice con la base real.

## 12. Resumen mental corto

Piensa el sistema así:

1. El libro principal es un post `almaden-books`.
2. Sus ajustes globales viven en una fila de `almaden_book_settings`.
3. Algunos ajustes globales complementarios viven en `post_meta` del libro.
4. Cada capítulo es un post `book_chapter` con su propio `post_meta`.
5. Un capítulo puede ser “normal”, “índice” o “créditos” según sus flags.
6. El editor carga todo desde BD, lo muestra en formularios, y al guardar vuelve a escribir tanto la tabla como los `post_meta`.

Si quieres, el siguiente paso natural sería convertir este mapa en una tabla más compacta, tipo “campo -> tabla/meta -> archivo que lo lee/escribe”, para usarlo como referencia rápida de desarrollo.
