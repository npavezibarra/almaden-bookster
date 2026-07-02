# Almaden Bookster

Plugin principal de Almaden para crear libros, editar contenido, diseñar portadas, publicar una estanteria publica, renderizar lectores y conectar quizzes por capitulo con Learni.

## Leer primero

Si eres un agente o vas a modificar codigo, usa este orden:

1. [AGENT_GUIDELINES.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/AGENT_GUIDELINES.md)
2. Este [README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/README.md)
3. [includes/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/README.md)
4. La carpeta que corresponda al area a tocar:
- [templates/editor/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/README.md)
- [templates/cover/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/cover/README.md)
- [templates/bookshelf/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/bookshelf/README.md)
   - [templates/reader/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/reader/README.md)
   - [templates/quiz-builder/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/quiz-builder/README.md)
5. [assets/js/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/README.md)

## Estado actual del plugin

- CPTs de libros y capitulos.
- Editor de contenido de libros.
- Editor de portadas.
- Configuracion interna de rutas y paginas del creador.
- Libreria publica.
- Reader publico.
- Integracion con Learni para quizzes de libro y quizzes por capitulo.
- Nuevo quiz builder por capitulo con panel de prompts, preview y contenido raw.

## Mapa rapido por funcionalidad

### Backend PHP

- [includes/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/README.md)
- [includes/ajax/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/ajax/README.md)
- [includes/learni-integration.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/learni-integration.php)

### Templates

- Taller / admin: [templates/admin/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/admin/README.md)
- Book editor: [templates/editor/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/editor/README.md)
- Cover editor: [templates/cover/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/cover/README.md)
- Catálogo público: [templates/bookshelf/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/bookshelf/README.md)
- Reader: [templates/reader/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/reader/README.md)
- Quiz builder: [templates/quiz-builder/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/quiz-builder/README.md)

### Frontend JS

- [assets/js/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/README.md)
- [assets/js/editor/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/editor/README.md)
- [assets/js/cover/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/cover/README.md)
- [assets/js/reader/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/reader/README.md)
- [assets/js/pdf/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/pdf/README.md)
- [assets/js/quiz-builder/README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/assets/js/quiz-builder/README.md)

## Flujo de quiz con Learni

Cuando trabajes en quizzes:

1. Revisa primero [includes/learni-integration.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/includes/learni-integration.php).
2. Luego revisa [templates/quiz-builder/quiz-builder-app.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/templates/quiz-builder/quiz-builder-app.php).
3. Si el cambio afecta persistencia o CRUD del quiz, entra a:
   - [learni-standalone/includes/QuizEditor/QuizEditor.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/learni-standalone/includes/QuizEditor/QuizEditor.php)
   - [learni-standalone/includes/QuizEditor/QuizRepository.php](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/learni-standalone/includes/QuizEditor/QuizRepository.php)

## Regla importante para paginas publicas

Para paginas publicas integradas con el tema, no se debe reemplazar toda la pagina con `template_redirect` si el objetivo es mantener el layout del tema activo. En esos casos, el contenido debe entrar por `the_content`.

Las apps tipo dashboard o editor interno si pueden usar `template_redirect` para renderizar una superficie limpia desde cero.
