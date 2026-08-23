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

## Lógica obligatoria para crear nuevas páginas frontend

Las nuevas páginas frontend del plugin no deben construirse como páginas genéricas del tema de WordPress. Deben construirse como **app pages** con su propio wrapper, navegación y layout consistente.

### Flujo correcto

1. **Definir la ruta o endpoint propio** dentro del plugin, usando `rewrite rules`, `query vars` o un loader por `template_redirect` cuando corresponda.
2. **Crear un archivo wrapper específico** para esa página, por ejemplo `*-app.php`, que sea el punto de entrada visual de la pantalla.
3. **Reutilizar el shell compartido** para mantener consistencia de logo, navbar, encabezado, fuentes y estructura general.
4. **Dejar el contenido real en partials o templates internos** separados del wrapper.
5. **Usar IDs únicos en el contenedor principal** de cada página para facilitar CSS, JS y testing.
6. **No depender del template del tema** para estas pantallas. Si una página debe verse como producto interno del plugin, debe renderizarse de forma autónoma.

### Regla de implementación

- Si la página es pública o de producto, crea un wrapper con `template_redirect` y `exit` después de renderizar.
- Si la página necesita variaciones por rol o contexto, separa cada variante en su propio wrapper, pero comparte el mismo shell base.
- Si la página necesita navegación propia, define los links explícitamente en el wrapper o en el shell compartido, no en el tema.
- Si aparece HTML repetido entre varias pantallas, extrae primero un helper o shell común antes de duplicar.

### Instalación obligatoria de páginas Almaden Shell

Cada vez que una instrucción solicite crear una nueva página de **Almaden Shell**, esa página debe incorporarse también al proceso de instalación y activación del plugin. No basta con crear el wrapper, la ruta o el template: hay que registrarla como página canónica del sistema para que se cree automáticamente al activar AlmadenBookster en una instalación nueva.

La implementación debe cumplir lo siguiente:

1. Añadir la configuración predeterminada de la página, incluyendo su título, slug y referencia de `page_id`.
2. Crear o actualizar su función de sincronización para generar la página de WordPress con el contenido dinámico del plugin.
3. Incluirla en la rutina de instalación de páginas principales ejecutada por `register_activation_hook`.
4. Marcarla como página perteneciente al Almaden Shell para que las reglas de navegación, visibilidad y menús la reconozcan.
5. Mantener separadas las páginas personalizadas creadas desde el administrador: estas no deben convertirse en páginas canónicas ni instalarse automáticamente en otros sitios.

Si el título o el slug de una página Shell son editables, esos valores deben conservarse como configuración del sitio, pero la página y su funcionalidad base deben seguir siendo instaladas por el plugin.

### Ejemplo de arquitectura esperada

- `includes/frontend/app-shell.php`: estructura común de navegación y documento.
- `templates/<dominio>/<pantalla>-app.php`: wrapper de la pantalla.
- `templates/<dominio>/<pantalla>.php`: contenido parcial o vista interna.
- `includes/<dominio>/*.php`: reglas de ruta, permisos, query vars y loaders.

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
- **`editor-settings-tabs.js`**, **`editor-settings-fields.js`**, **`editor-settings-credits.js`**, **`editor-settings-templates.js`** & **`editor-settings-api.js`**: Controller logic, UI conditionals, and AJAX communication for the layout Settings Modal and dynamic credits form.
- **`editor-chapter-settings.js`**: Specific chapter overrides and target page parity properties.
- **`editor-markdown.js`**: Conversion of raw markdown into HTML.

### 2. PDF Rendering (`assets/js/pdf/`)
- **`typst/editor-typst-pdf.js`**: Typst-based PDF preview engine and export bridge.
- **`typst/page-templates/editor-page-template-selector.js`**: Page template picker and application flow.
- **`typst/page-templates/editor-page-template-images.js`**: Image binding for template placeholders.
- **`typst/README.md`**: Entry documentation for the Typst preview pipeline.

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
  - **`tab-page.php`**, `tab-typography.php`, `tab-header-footer.php`, `tab-chapters.php`, `tab-ebook-chapters.php`: Custom pages settings tabs.

### 2. Admin & Lists (`templates/admin/`)
- **`booklist-app.php`**: Taller / workshop list of books.
- **`booklist-create-modal.php`**: New book creation dialog.

### 3. Other Apps (`templates/ebook/`, `templates/reader/`, `templates/bookshelf/`, `templates/cover/`)
- **`ebook/ebook-single-app.php`**: Public ebook detail page with preview, purchase CTA, and handoff to the reader when access is granted.
- **`reader/reader-app.php`**: Ebook Reader page shell.
- **`bookshelf/bookshelf-app.php`**: Public Ebook store / bookshelf template.
- **`cover/cover-app.php`**: Page shell layout for the Book Cover editor.
