# Almaden Bookster - Settings / Meta Map

This document maps the canonical source of truth for the current book layout system.

## Rules

- `almaden_book_settings` table stores global book settings.
- `post_meta` on the `almaden-books` post stores global book metadata that is not in the table.
- `post_meta` on each `book_chapter` stores chapter-specific overrides only.
- Legacy keys are kept only for compatibility with older exports/imports and should not be treated as active UI state.

## Canonical map

| Scope | Concept | Canonical storage | Key(s) |
|---|---|---|---|
| Global PDF | Page size, margins, padding, bleed, export mode | Table `almaden_book_settings` | `unit`, `page_size`, `page_width`, `page_height`, `margin_*`, `padding_*`, `bleeding`, `export_grayscale` |
| Global PDF | Header/footer layout | Table `almaden_book_settings` | `header_*`, `footer_*`, `first_page_header_*`, `first_page_footer_*`, `first_page_header_show`, `first_page_footer_show`, `header_hyphenate` |
| Global PDF | Chapter flow | Table `almaden_book_settings` + book `post_meta` | `chapter_start_parity`, `chapter_page_one_align`, `chapter_page_one_vertical` (legacy), `_almaden_book_separate_opening_content`, `_almaden_book_chapter_flow_mode` |
| Global PDF | Chapter title style | Table `almaden_book_settings` | `chapter_title_*` |
| Global PDF | Chapter prefix style | Table `almaden_book_settings` | `chapter_prefix_*` |
| Global PDF | Footnote separator | Table `almaden_book_settings` | `footnote_separator_*` |
| Global PDF | Footnote placement and typography | Table `almaden_book_settings` | `footnote_mode`, `footnote_*_title`, `footnote_font_*`, `footnote_align`, `footnote_line_height`, `footnote_letter_spacing`, `footnote_entry_spacing`, `footnote_hyphenate` |
| Global PDF | Subtitle defaults | `post_meta` on book | `_almaden_chapter_subtitle_*` |
| Global Ebook | Ebook typography / chapter opener | Table `almaden_book_settings` | `ebook_*`, `ebook_chapter_*` |
| Global Ebook | Ebook subtitle defaults | `post_meta` on book | `_almaden_ebook_subtitle_*` |
| Chapter override | Chapter structural flow | `post_meta` on chapter | `_start_parity`, `_opening_page_mode`, `_opening_blank_intentional`, `_opening_block_enabled`, `_opening_block_horizontal_align`, `_opening_block_vertical_align`, `_parity_image`, `_parity_image_*` |
| Chapter override | Visibility / numbering / header behavior | `post_meta` on chapter | `_hide_title`, `_exclude_from_numbering`, `_hide_all_headers_footers`, `_custom_running_header`, `_first_page_header_*`, `_first_page_footer_*` |
| Chapter override | Chapter body options | `post_meta` on chapter | `_drop_cap_enabled`, `_disable_hyphenation` |
| Chapter override | Chapter subtitle override | `post_meta` on chapter | `_subtitle_*` |
| Chapter TOC override | TOC-specific chapter layout | `post_meta` on chapter | `_is_toc`, `_toc_*` |
| Chapter credits override | Credits-specific chapter layout | `post_meta` on chapter | `_is_credits`, `_credits_*` |

## Legacy / compatibility keys

These keys may still exist in old books or exports, but they are no longer part of the active chapter editing flow:

- `book_start_page_footer_type` (schema-only / retired; page 1 is always a technical blank)
- `_page_one_vertical`
- `_toc_page_one_vertical`

They should be treated as legacy compatibility only.

## Practical precedence

- PDF chapter opener alignment: global only from `chapter_page_one_align` (combined horizontal/vertical), with `chapter_page_one_vertical` kept for legacy compatibility.
- Chapter start parity: chapter override wins, otherwise global.
- Book flow mode: if `_almaden_book_chapter_flow_mode` is `left`, the book-level legacy parity resolves to even/start-left.
- Chapter opening page mode: chapter override wins; if absent, compatibility falls back to current parity-image behavior.
- Chapter opening block: if absent, compatibility defaults to enabled.
- Subtitle alignment: chapter override wins, otherwise global.
- TOC title alignment: TOC override wins, otherwise global.
- Ebook settings: remain independent from PDF settings.
