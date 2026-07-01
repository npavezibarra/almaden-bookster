# Creador de Evaluaciones (`assets/js/quiz-builder/`)

Este directorio contiene los módulos JavaScript que manejan la interfaz del Creador de Quizzes (Evaluaciones) de **Almaden Bookster**. 

Siguiendo el principio de **Modularidad Extrema** con el límite estricto de menos de 500 líneas por archivo definido en [AGENT_GUIDELINES.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/AGENT_GUIDELINES.md), la lógica original del builder se fragmentó en cuatro submódulos desacoplados que interactúan a través de un espacio de nombres global compartido en `window.ALMADEN_QUIZ_BUILDER`.

---

## Estructura de Módulos y Responsabilidades

### 1. Orquestación Central
*   **[quiz-builder-app.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-app.js)**
    *   **Responsabilidad**: Punto de entrada de la aplicación y gestor del ciclo de vida del estado. Controla las variables globales compartidas (`loadedQuiz`, `activeChapterIndex`, `activePreviewQuestionIndex`), actualiza los paneles laterales y la cabecera del editor, realiza el guardado asíncrono (AJAX) y asocia los escuchadores de eventos globales (pestañas y diálogos).

### 2. Edición de Diapositivas
*   **[quiz-builder-editor.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-editor.js)**
    *   **Responsabilidad**: Maneja las interacciones del creador visual de preguntas (slides). Controla la creación de plantillas de diapositivas en blanco, la inserción, duplicación y remoción de preguntas/alternativas, y renderiza la vista previa del formulario editable en el editor (`renderPreview`).

### 3. Parseado e Inteligencia de Datos
*   **[quiz-builder-parser.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-parser.js)**
    *   **Responsabilidad**: Procesa y valida el texto crudo pegado desde el portapapeles (generado por modelos LLM).
    *   **Algoritmo Especial**:
        *   `extractJsonFromRawText`: Contiene un **Question Recovery Parser** inteligente que extrae objetos JSON válidos ignorando bloques markdown y recupera preguntas individuales de forma robusta a partir de fragmentos JSON incompletos o truncados.
        *   `normalizeQuizPayload`: Adapta arreglos crudos o estructuras anidadas al esquema estándar de preguntas y respuestas requeridas por el plugin.

### 4. Simulación Interactiva (Preview)
*   **[quiz-builder-preview.js](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/quiz-builder-preview.js)**
    *   **Responsabilidad**: Controla la visualización offline interactiva del quiz (`startInteractiveQuizPreview()`). Renderiza el modal simulando el entorno del alumno real (vista intro, paso a paso con alternativas ordenadas en bloques y cálculo de puntuación y porcentaje al enviar).

---

## Flujo de Integración en el Servidor

Estos archivos se encolan en la plantilla del Quiz Builder ([quiz-builder-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/quiz-builder/quiz-builder-app.php)) en el orden secuencial correcto de dependencias de scripts usando `filemtime` para evitar problemas con la caché del navegador:

1. `quiz-builder-parser.js`
2. `quiz-builder-editor.js`
3. `quiz-builder-preview.js`
4. `quiz-builder-app.js` (Orquestador cargado al final)
