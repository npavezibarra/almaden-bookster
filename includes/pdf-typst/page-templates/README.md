# Typst Page Templates

This module owns reusable templates applied to physical PDF pages. It is kept
outside `typst-document.php` so page-template growth does not enlarge the main
book composer.

- `bootstrap.php` loads the page-template module.
- `page-template-registry.php` declares the presets supported by Typst.
- `page-template-normalizer.php` validates the persisted collection.
- `page-template-persistence.php` stores the normalized collection in book meta.
- `page-template-placeholder.php` owns the temporary image placeholder output.
- `page-template-slots.php` normalizes and renders slot metadata plus assigned
  images.
- `page-template-composer.php` is the only boundary allowed to transform the
  generated Typst source.
- `page-template-word-flow.php` probes Typst word positions to split a text
  flow at the physical bottom of a template page without estimating line wraps.

The collection is stored in `_almaden_page_templates` as normalized JSON and is
loaded into `settings.page_templates` for the Typst compiler. Each template can
declare one or more slots, and each slot can carry an image attachment
assignment. The Typst renderer emits unique metadata anchors for those slots so
the editor can manage them later from the image panel.
