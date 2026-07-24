# Importación de Documentos (IO Import)

Este directorio contiene la lógica de refactorización del módulo de importación de documentos, que fue separada del archivo monolítico `document-import.php` original.

Cada archivo está diseñado de manera modular para cumplir un propósito específico en el ciclo de vida del análisis, conversión e importación de archivos de texto:

### Archivos Principales
- **`import-mapping.php`**: Contiene la lógica para generar, validar y procesar las asignaciones de estilos (jerarquías, títulos, subtítulos y separadores) enviados por el usuario desde la interfaz.
- **`import-builder.php`**: Es el núcleo (core) que toma los bloques de texto plano y jerarquías, construyendo la estructura final en capítulos y convirtiéndolos en entradas/posts (CPT) de WordPress para el libro.
- **`import-parsers.php`**: Proporciona las utilidades generales de formateo y enrutamiento (routing) para delegar el análisis al parser correcto dependiendo del tipo de archivo subido.
- **`import-ajax.php`**: Expone y envuelve los "hooks" de AJAX para permitir la comunicación asíncrona con el cliente frontend (como el análisis en vivo y el commit/guardado del documento).

### Parsers Específicos (Analizadores)
- **`parser-docx.php`**: Lógica de extracción y análisis semántico para archivos de Microsoft Word (`.docx`) mediante la lectura de sus estructuras XML internas.
- **`parser-rtf.php`**: Funciones dedicadas a la extracción y decodificación de texto rico (`.rtf`).
- **`parser-txt.php`**: Extracción y análisis heurístico diseñado para archivos de texto plano (`.txt`).
- **`parser-pdf.php`**: Responsable de extraer el texto de documentos binarios PDF (`.pdf`), apoyándose en herramientas del sistema como `pdftotext`.

> **Nota:** Todos estos archivos son encolados de manera automática en cascada mediante el archivo punto de entrada `/includes/io/document-import.php`, por lo que **ninguna de las dependencias externas a este directorio necesita ser modificada.**
