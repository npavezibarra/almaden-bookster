# Arquitectura CSS

Este documento resume la arquitectura visual detectada en `almaden-bookster`.
Describe el estado actual; no define una refactorizacion obligatoria.

## Carpetas con CSS y assets visuales

- `assets/css/`: CSS principal del plugin.
- `assets/css/quiz-builder/`: CSS modular del editor/player de quizzes.
- `assets/css/authors/`: directorio y perfil publico de autores.
- `assets/css/publishers/`: onboarding publico de editoriales.
- `assets/fonts/bundled/`: fuentes locales y `bundled-fonts.css`.
- `assets/vendor/fontawesome/`: Font Awesome local y webfonts.
- `modules/login-register/assets/css/`: login, registro y verificacion.
- `modules/book-product/assets/css/`: panel libro-producto.
- `modules/blog-post/assets/css/`: blog publico y editor de blog.
- `modules/content-protection/assets/css/`: proteccion de copia y print CSS.
- `modules/learni/assets/dashboard/`: dashboard Learni.

No se detectaron archivos SCSS/Sass en el inventario actual.

## CSS global y compartido

- `assets/css/editor-style.css`: base visual del shell, editor, booklist y cover.
  Define `--almaden-ui-font`, temas `theme-light`, `theme-sepia`,
  `theme-dark`, reglas de Font Awesome y estilos del editor/PDF preview.
- `assets/css/reader-app.css`: base del reader y ficha ebook. Tambien contiene
  estilos de highlights, acceso, quiz overlay y layouts moviles.
- `assets/fonts/bundled/bundled-fonts.css`: fuentes locales usadas por shell,
  editor, cover, reader, ebook, bookshelf y auth.
- `assets/vendor/fontawesome/css/all.min.css`: iconos locales para shell,
  editor, cover y booklist. Algunas pantallas aun cargan Font Awesome desde CDN.

## CSS especifico de apps y modulos

- `assets/css/quiz-builder/quiz-builder-app.css`
- `assets/css/quiz-builder/quiz-builder-workspace.css`
- `assets/css/quiz-builder/quiz-builder-components.css`
- `assets/css/quiz-builder/quiz-builder-modal.css`
- `assets/css/quiz-builder/quiz-builder-simulation.css`
- `assets/css/authors/authors-app.css`
- `assets/css/authors/author-page.css`
- `assets/css/authors/author-page-responsive.css`
- `assets/css/publishers/publisher-onboarding.css`
- `assets/css/admin-fonts-page.css`
- `assets/css/admin-filesize-page.css`
- `modules/login-register/assets/css/auth-modal.css`
- `modules/login-register/assets/css/login-notice.css`
- `modules/login-register/assets/css/unverified-popup.css`
- `modules/book-product/assets/css/book-product.css`
- `modules/blog-post/assets/css/blog-post.css`
- `modules/blog-post/assets/css/blog-archive.css`
- `modules/blog-post/assets/css/blog-post-editor.css`
- `modules/content-protection/assets/css/content-protection.css`
- `modules/content-protection/assets/css/content-protection-print.css`
- `modules/learni/assets/dashboard/course-dashboard.css`

## Mecanismos de carga

El plugin usa tres mecanismos en paralelo:

- `wp_enqueue_style`: usado en admin, modulos y algunos flujos frontend.
- `<link rel="stylesheet">` manual dentro de templates app completos.
- `<style>` inline dentro de templates para overrides o estilos criticos.

Entradas principales:

- `includes/frontend/app-shell.php`: carga fuentes bundled, Font Awesome,
  `editor-style.css` e inline `almaden-app-shell-overrides`.
- `templates/admin/booklist-app.php`: carga fuentes bundled, Font Awesome,
  `editor-style.css` e inline `almaden-booklist-overrides`.
- `templates/editor/editor-app-head-extras.php`: carga fuentes bundled,
  Font Awesome, `editor-style.css`, `#dynamic-pdf-settings` e inline de editor.
- `templates/cover/cover-app.php`: carga fuentes bundled, Font Awesome,
  `editor-style.css` e inline de cover.
- `templates/reader/reader-app.php`: carga Font Awesome desde CDN, fuentes
  bundled, `reader-app.css` y CSS de `quiz-builder`.
- `templates/ebook/ebook-single-app.php`: carga fuentes bundled y
  `reader-app.css`.
- `templates/bookshelf/bookshelf-app.php`: carga fuentes bundled, Font Awesome
  desde CDN y CSS inline de bookshelf.
- `templates/quiz-builder/quiz-builder-app.php`: carga los cinco CSS de
  `assets/css/quiz-builder/` con `<link>`.
- `templates/authors/authors-app.php`: carga Font Awesome CDN, fuentes bundled
  y `authors-app.css`.
- `templates/authors/author-app.php`: carga `author-page.css` y
  `author-page-responsive.css`.
- `templates/publishers/publisher-onboarding-app.php`: carga
  `publisher-onboarding.css` y Google Fonts externos.
- `admin/admin-fonts-page.php`: encola `admin-fonts-page.css`.
- `includes/admin/admin-filesize.php`: encola `admin-filesize-page.css`.
- `modules/login-register/includes/Auth/AuthOrchestrator.php`: encola fuentes,
  Poppins y CSS del modulo.
- `modules/book-product/includes/class-assets.php`: encola `book-product.css`.
- `modules/blog-post/includes/class-blog-post-template.php`: encola
  `blog-post.css` o `blog-archive.css`.
- `modules/blog-post/includes/class-blog-post-editor.php`: encola
  `blog-post-editor.css`.
- `modules/content-protection/includes/class-content-protection.php`: imprime
  `<link>` para `content-protection.css` y `content-protection-print.css`.
- `modules/learni/includes/Dashboard/class-creator-dashboard.php` y
  `includes/frontend.php`: encolan `course-dashboard.css`.

## Templates y paginas consumidoras

- Shell: `includes/frontend/app-shell.php`, `templates/shell/*.php`.
- Taller/booklist: `templates/admin/booklist-app.php` y partials de admin.
- Editor: `templates/editor/editor-app.php`,
  `templates/editor/editor-app-head-extras.php`, `assets/js/editor/`.
- Cover: `templates/cover/cover-app.php` y partials de cover.
- Reader: `templates/reader/reader-app.php`, `assets/js/reader/`.
- Ebook publico: `templates/ebook/ebook-single-app.php`.
- Bookshelf: `templates/bookshelf/bookshelf-app.php`.
- Quiz builder: `templates/quiz-builder/quiz-builder-app.php`,
  `assets/js/quiz-builder/`.
- Autores: `templates/authors/authors-app.php`,
  `templates/authors/author-app.php`, `assets/js/authors/`.
- Editoriales: `templates/publishers/*.php`.
- Admin WordPress: `admin/admin-fonts-page.php`,
  `templates/admin/pages-app.php`, `templates/admin/distribution-access-app.php`,
  `templates/admin/filesize-app.php`.
- Modulos: `modules/login-register/`, `modules/book-product/`,
  `modules/blog-post/`, `modules/content-protection/`, `modules/learni/`.

## Estilos inline relevantes

Los estilos inline son una parte importante de la arquitectura actual. Antes de
moverlos o tocarlos, revisar el template consumidor.

- `includes/frontend/app-shell.php`: overrides del shell y clases Tailwind.
- `templates/admin/booklist-app.php`: overrides de taller.
- `templates/admin/pages-app.php`: CSS inline extenso para cards, estados y
  formularios de configuracion.
- `templates/admin/distribution-access-app.php`: layout y alertas inline.
- `templates/bookshelf/bookshelf-app.php`: estilos base y overrides.
- `templates/ebook/ebook-single-app.php`: overrides de ficha ebook.
- `templates/reader/reader-app.php`: estilos de reader, navbar y overlays.
- `templates/quiz-builder/quiz-builder-app.php`: overrides y estados del
  builder.
- `templates/publishers/publisher-page.php` y
  `templates/publishers/publisher-settings-app.php`: estilos de editoriales.
- `modules/login-register/templates/auth-modal.php` y reset password: CSS
  critico del flujo auth.
- `includes/io/epub-export.php`, `includes/io/cover-pdf-export.php` y
  `includes/helpers/cover-thumbnail-generator.php`: CSS generado para salida.

## Fuentes e iconos

- Fuente UI dominante: Urbanist.
- Fuentes frecuentes por area: Poppins en auth/Learni, Georgia en reader,
  Newsreader en blog, Fraunces/Inter en onboarding editorial.
- Fuentes bundled disponibles en `assets/fonts/bundled/`: Inter, Urbanist,
  Poppins, Lora, Cormorant Garamond, Cinzel, Playfair Display, Outfit,
  Merriweather.
- Iconos: Font Awesome local y CDN, Dashicons en admin y Learni.

## Riesgos conocidos

- Hay mezcla de `wp_enqueue_style`, `<link>` manual y CSS inline.
- Algunas pantallas cargan Font Awesome local y otras desde CDN.
- `quiz-builder` CSS se reutiliza en el reader para el quiz player.
- Existen selectores globales o amplios como `body, html`, `*`, `button`,
  `input`, `select`, `textarea`, `h1`-`h6` y `body.theme-* *`.
- Los tokens visuales estan duplicados por dominio (`--qb-*`, `--abp-*`,
  `--almaden-learni-*`, `--almaden-blog-*`) y valores hardcodeados.
- Las paginas integradas con el tema pueden heredar estilos del tema activo.
- La cantidad de CSS inline dificulta detectar estilos muertos y conflictos de
  especificidad.

## Archivos sobre 400 o 500 lineas

Alerta mayor a 400 lineas:

- `assets/fonts/bundled/bundled-fonts.css`: 464 lineas.

Superan 500 lineas:

- `assets/css/authors/author-page.css`: 776 lineas.
- `assets/css/editor-style.css`: 906 lineas.
- `assets/css/reader-app.css`: 1352 lineas.

Estos archivos no deben seguir acumulando responsabilidades. Antes de agregarles
logica visual nueva, proponer una division o una estrategia de aislamiento.
