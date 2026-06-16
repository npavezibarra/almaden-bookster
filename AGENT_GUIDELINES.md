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

The frontend editor logic is broken down into specific modular files to avoid massive files and maintain clean architecture.

- **`editor-core.js`**: 
  The brain of the application. Handles initialization, global state management (`bookState`), fetching book data on load, autosaving, and managing global event listeners.
- **`editor-chapters.js`**: 
  Handles everything related to chapter management in the left sidebar. This includes creating new chapters, deleting chapters, reordering chapters, and selecting an active chapter.
- **`editor-markdown.js`**: 
  Responsible for parsing the raw text input and converting it into structured HTML elements (paragraphs, headers, lists) before they are sent to the compiler.
- **`editor-pdf-compiler.js`**: 
  The most complex file. Takes the HTML elements and handles the **pagination logic**. It measures heights, applies page breaks, manages "flow-root" margins, and splits paragraphs across pages when they overflow the physical boundaries of a page.
- **`editor-pdf-styles.js`**: 
  Takes the user's saved settings (margins, typography, line height, etc.) and dynamically constructs a `<style>` block. This CSS is injected into the DOM to style the compiled PDF preview pages accurately.
- **`editor-pdf-export.js`**: 
  Contains the logic to trigger the actual browser print dialog (`window.print()`) for exporting the compiled pages to a physical PDF file.
- **`editor-settings.js`**: 
  Manages the behavior of the Settings Modal. It handles opening/closing the modal, reading current values into the UI fields, updating UI logic (like toggling custom unit fields), and sending the `FormData` via AJAX to save the settings in the database.
- **`admin-fonts-page.js`**: 
  *Note:* This does not run in the editor. It runs in the WordPress wp-admin dashboard to handle the custom font upload and management interface.

---

## 📂 Templates (`templates/`)

These files define the HTML structure and PHP rendering for the BookCraft application shell.

- **`editor-app.php`**: 
  The main entry point for the editor application. It renders the entire application shell: the left sidebar (chapters), the top toolbar, the main content area (text input), and the right preview area (PDF visualizer). It also enqueues the necessary scripts and styles.
- **`editor-settings-modal.php`**: 
  The shell container for the PDF Layout Settings modal. To adhere to file length limits (< 500 lines), this file is strictly a wrapper that includes the individual tabs from the `settings-tabs` directory.

### `templates/settings-tabs/`
Contains the modularized components of the Settings Modal:

- **`functions.php`**: Contains PHP arrays defining the default available font families and utility functions for rendering `<select>` options.
- **`tab-page.php`**: The HTML UI for configuring physical page dimensions, margins, bleed, and units.
- **`tab-typography.php`**: The HTML UI for configuring global content typography, headers (H1/H2/H3), paragraph spacing, and hyphenation.
- **`tab-header-footer.php`**: The HTML UI for configuring the content, typography, and margins of the running headers and footers across pages.
- **`tab-chapters.php`**: The HTML UI for configuring how chapters behave (e.g., forcing them to start on odd pages) and the styling/spacing of the main Chapter Titles.
