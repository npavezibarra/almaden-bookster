# Learni module for Bookster

Este modulo es la implementacion nativa de Learni dentro de `almaden-bookster`.
Su responsabilidad es cubrir el flujo de quizzes para ebooks y capitulos, sin
imponer el modelo LMS completo de cursos.

## Alcance actual

- Quizzes asociados a libros y capitulos.
- Dashboard interno y editor de quizzes para el flujo de Bookster.
- Persistencia en tablas `almaden_learni_*`.
- Integracion con `almaden-bookster` via `includes/integrations/learni-integration.php`.

## Diferencia clave con `learni-standalone`

Este modulo no es el mismo producto que el plugin standalone de Learni.

- `modules/learni` resuelve el caso ebook/capitulo.
- `learni-standalone` resuelve el caso curso/leccion.

Comparten ADN y varios conceptos, pero no el mismo contrato de negocio:

- Bookster trabaja con `book`, `book_chapter` y flujo editorial.
- Standalone trabaja con `course`, `lesson` y flujo LMS.

## Recomendaciones de arquitectura

1. Mantener este modulo como fuente de verdad para Bookster.
2. Extraer solo las piezas realmente compartibles:
   - normalizacion de quizzes
   - persistencia CRUD
   - reglas comunes de permisos
   - helpers de payload
3. No forzar el mismo frontend para ebooks y cursos.
4. Si se unifica mas adelante, hacerlo con adaptadores por contexto:
   - un adaptador para cursos
   - un adaptador para ebooks

## Nota para agentes

Antes de mover codigo, revisa:

1. `almaden-bookster/includes/integrations/learni-integration.php`
2. `almaden-bookster/templates/quiz-builder/quiz-builder-app.php`
3. `almaden-bookster/modules/learni/includes/QuizEditor/QuizEditor.php`
4. `almaden-bookster/modules/learni/includes/QuizEditor/QuizRepository.php`
