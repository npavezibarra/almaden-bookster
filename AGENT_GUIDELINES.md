# Guía para Agentes AI: AlmadenBookster Plugin

Esta guía establece las reglas estrictas de desarrollo y arquitectura para cualquier agente o desarrollador que trabaje en el plugin `AlmadenBookster`. Estas directrices deben seguirse sin excepción.

## REGLA PRINCIPAL: Límite de 500 Líneas de Código

**Ningún archivo dentro de este plugin debe superar NUNCA las 500 líneas de código.**

La modularidad extrema es la prioridad de este proyecto. Si la implementación de una nueva característica o idea va a provocar que un archivo supere este límite, tu responsabilidad inmediata es **refactorizar y dividir** el archivo antes de continuar.

## Principios de Modularidad a Seguir:

1. **Separación de Responsabilidades:** Nunca mezcles diferentes contextos en un solo archivo masivo. Si el archivo principal (`almaden-bookster.php`) comienza a crecer, divídelo en carpetas lógicas (ej. `includes/`, `admin/`, `public/`, `cpt/`) y requiere los archivos correspondientes.
2. **Funciones Pequeñas y Específicas:** Unifica y simplifica las funciones. Cada función debe tener un único propósito bien definido. Evita los bloques condicionales gigantescos y el código procedimental extenso.
3. **Refactorización Continua:** Si ves un archivo con 400 líneas, considéralo en estado crítico. Comienza a planificar su división en componentes más pequeños.
4. **Componentización:** Para interfaces de usuario o plantillas, utiliza archivos separados. No incluyas HTML extenso o scripts inline masivos dentro de los archivos PHP de lógica; cárgalos desde archivos parciales o encola los assets adecuadamente.
5. **IDs en Elementos Principales:** Siempre que se cree un nuevo template o página, los elementos HTML principales deben tener un ID especial o identificador único para poder referirnos correctamente a ellos con CSS, JavaScript u otros scripts.

*Cualquier código que incumpla estas reglas será considerado como un fallo en la implementación de la arquitectura.*

## Contexto Obligatorio (READMEs)

**Antes de crear o modificar cualquier código, el Agente AI DEBE buscar y leer los archivos `README.md` empezando desde el root folder (directorio raíz) hacia las subcarpetas.**
Esto permite hacer más eficiente la modificación o creación de archivos, ya que cada `README` indicará qué hace cada archivo dentro de la carpeta sin tener que escanear todo el código. Modificar código sin entender la arquitectura descrita en los `README.md` correspondientes está estrictamente prohibido.

## Conexión a Base de Datos (Local by Flywheel)

Para operaciones directas en la base de datos MySQL, utilizar el siguiente socket:

```
/Users/nicolasibarra/Library/Application Support/Local/run/J__JXc6LL/mysql/mysqld.sock
```

Ejemplo de uso con `mysql` CLI:

```bash
mysql --socket="/Users/nicolasibarra/Library/Application Support/Local/run/J__JXc6LL/mysql/mysqld.sock" -u root -e "USE local; SHOW TABLES;"
```

## Credenciales WordPress (Local)

- **URL de Admin:** `http://ada.local/wp-admin/`
- **Usuario:** `chatgpt`
- **Contraseña:** `chatgpt123`
# BookCraft Editor Architecture Guide

This file serves as a guide for AI agents (and developers) working on the Almaden Bookster plugin. It explains the responsibilities and structure of the JavaScript assets and PHP templates that power the main book editor interface.

## 📂 JavaScript (`assets/js/`)

The frontend JavaScript logic is organized into subfolders by domain to maintain clean modularity:

### 1. Editor Components (`assets/js/editor/`)
- **`editor-core.js`**: Application brain, global state (`bookState`), autosaving, and core initialization.
- **`editor-ui.js`**: UI theme switching, layout configurations, and sidebar chapters controls.
- **`editor-toolbar.js`**: Markdown format injection, media library attachment, and parity-image toggling.
- **`editor-chapters.js`**: Chapter CRUD (creation, sorting, selecting active chapters).
- **`editor-virtualization.js`**: Performance optimization for massive documents using IntersectionObserver.
- **`editor-settings-ui.js`** & **`editor-settings-api.js`**: Controller logic and AJAX communication for the layout Settings Modal.
- **`editor-chapter-settings.js`**: Specific chapter overrides and target page parity properties.
- **`editor-markdown.js`**: Conversion of raw markdown into HTML.

### 2. PDF Rendering (`assets/js/pdf/`)
- **`editor-pdf-compiler.js`**: Core pagination orchestration loop.
- **`editor-pdf-compiler-dimensions.js`**: Document physical dimensions calculations.
- **`editor-pdf-compiler-parity.js`**: Layout rules and parity assignment.
- **`editor-pdf-dom.js`**: HTML elements factory (headers, footers, footnote containers).
- **`editor-pdf-pagination.js`**: Pixel measuring and block-level paragraph split routines.
- **`editor-pdf-styles.js`** / **`editor-pdf-styles-base.js`** / **`editor-pdf-styles-typography.js`**: Dynamic CSS stylesheet builders.
- **`editor-pdf-export.js`**: Prep for browser print layouts and execution.

### 3. Reader & Admin (`assets/js/reader/` & `assets/js/admin/`)
- **`reader/reader-app.js`**, **`reader-navigation.js`**, **`reader-prefs.js`**, **`reader-styles.js`**: Visor engine for reading web-based EPUB/Ebooks.
- **`admin/admin-fonts-page.js`**: Google Font downloads and setup console in wp-admin.
- **`admin/booklist-ui.js`**: Control logic for the WordPress templates dashboard workshop list.

---

## 📂 Templates (`templates/`)

These files define the HTML structure and PHP rendering for the BookCraft application shells. They are organized into functional subfolders matching their respective contexts:

### 1. Editor Components (`templates/editor/`)
- **`editor-app.php`**: The main entry point for the editor application (left sidebar, top toolbar, text input, and right preview virtualizer).
- **`editor-settings-modal.php`**: Wrapper layout Settings Modal including tabs from `settings-tabs/` subdirectory.
- **`chapter-settings-modal.php`**, `chapter-settings-normal.php`, `chapter-settings-toc.php`: Options and layouts at the individual chapter level.
- **`settings-tabs/`**:
  - **`functions.php`**: Font arrays helper.
  - **`tab-page.php`**, `tab-typography.php`, `tab-header-footer.php`, `tab-chapters.php`, `tab-credits.php`, `tab-ebook-chapters.php`: Custom pages settings tabs.

### 2. Admin & Lists (`templates/admin/`)
- **`booklist-app.php`**: Taller / workshop list of books.
- **`booklist-create-modal.php`**: New book creation dialog.

### 3. Other Apps (`templates/reader/`, `templates/bookshelf/`, `templates/cover/`)
- **`reader/reader-app.php`**: Ebook Reader page shell.
- **`bookshelf/bookshelf-app.php`**: Public Ebook store / bookshelf template.
- **`cover/cover-app.php`**: Page shell layout for the Book Cover editor.

