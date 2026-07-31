# Typst PDF engine

This module composes editor RAW content directly with Typst.

- `typst-markup.php` parses the supported RAW nomenclature without using the paginated DOM.
- `typst-document.php` maps book settings and chapters to one Typst document.
- `typst-compiler.php` runs the local Typst binary and validates extracted PDF text.
- `../ajax/ajax-typst-pdf.php` exposes the authenticated binary endpoint.

The runtime binary is resolved from `ALMADEN_BOOKSTER_TYPST_BINARY`, the plugin-local
`runtime/typst/typst`, or `PATH`. The local binary is intentionally ignored by Git.
