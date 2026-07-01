# Quiz Builder

Esta carpeta contiene la vista del editor de quizzes por capitulo usada por Almaden Bookster con Learni.

## Leer primero

Si vas a modificar este flujo, revisa en este orden:

1. [includes/learni-integration.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/learni-integration.php)
2. [templates/quiz-builder/quiz-builder-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/quiz-builder/quiz-builder-app.php)
3. [learni-standalone/includes/QuizEditor/QuizEditor.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/learni-standalone/includes/QuizEditor/QuizEditor.php)
4. [learni-standalone/includes/QuizEditor/QuizRepository.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/learni-standalone/includes/QuizEditor/QuizRepository.php)

## Que hace esta pantalla

- Muestra la lista de capitulos a la izquierda.
- Para cada capitulo permite configurar prompt settings.
- Genera un prompt copiables para pegar en un LLM.
- Acepta JSON de vuelta y lo carga en preview.
- Permite editar preguntas y respuestas antes de guardar.
- Muestra el contenido raw del capitulo como referencia.
- Guarda el quiz asociado al capitulo correcto.

## Flujo de datos

1. El capitulo activo define `chapter_id`, `chapter_key` y el `quiz_id` actual.
2. El payload se normaliza en PHP antes de enviarse a Learni.
3. Si existe un quiz asociado a ese mismo capitulo, se actualiza.
4. Si no existe, se crea uno nuevo.
5. El resultado se persiste en el metadato del capitulo para futuras cargas.

## Puntos sensibles

- No reusar el quiz de otro capitulo.
- Validar JSON aunque venga con texto adicional o fences markdown.
- Mantener la vista sin herramientas de edicion en `Chapter Content`.
- Mantener `Quiz Preview` editable antes del guardado.
