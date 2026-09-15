# Almaden Bookster

Plugin principal de Almaden para crear libros, editar contenido, diseñar portadas, publicar una estanteria publica, renderizar lectores y conectar quizzes por capitulo con Learni.

## Leer primero

Si eres un agente o vas a modificar codigo, usa este orden:

1. [AGENTS.md](AGENTS.md)
2. Este [README.md](README.md)
3. El README local de la carpeta que corresponda al area a tocar.
4. README de carpetas vecinas solo si el cambio cruza fronteras entre areas.
5. Documentacion adicional en [docs/](docs/README.md) solo cuando sea relevante.

Cada subcarpeta con codigo debe tener su propio `README.md`. Si vas a tocar una carpeta sin README, primero crea uno breve con responsabilidad, archivos principales, flujo de entrada, reglas locales, validacion y archivos relacionados.

## Estado actual del plugin

- CPTs de libros y capitulos.
- Editor de contenido de libros.
- Editor de portadas.
- Configuracion interna de rutas y paginas del creador.
- Libreria publica.
- Reader publico.
- Protección de copia convencional del contenido del Reader.
- Integracion nativa con Learni para quizzes de libro y quizzes por capitulo.
- Nuevo quiz builder por capitulo con panel de prompts, preview y contenido raw.

## Mapa rapido por funcionalidad

### Backend PHP

- [includes/README.md](includes/README.md)
- [includes/frontend/README.md](includes/frontend/README.md)
- [includes/authors/README.md](includes/authors/README.md)
- [includes/books/README.md](includes/books/README.md)
- [includes/ajax/README.md](includes/ajax/README.md)
- [includes/integrations/learni-integration.php](includes/integrations/learni-integration.php)

### Templates

- Taller / admin: [templates/admin/README.md](templates/admin/README.md)
- Book editor: [templates/editor/README.md](templates/editor/README.md)
- Cover editor: [templates/cover/README.md](templates/cover/README.md)
- Ebook public page: [templates/ebook/README.md](templates/ebook/README.md)
- Catalogo publico: [templates/bookshelf/README.md](templates/bookshelf/README.md)
- Shell: [templates/shell/README.md](templates/shell/README.md)
- Reader: [templates/reader/README.md](templates/reader/README.md)
- Quiz builder: [templates/quiz-builder/README.md](templates/quiz-builder/README.md)
- Autores: [templates/authors/README.md](templates/authors/README.md)

### Frontend JS

- [assets/js/README.md](assets/js/README.md)
- [assets/js/editor/README.md](assets/js/editor/README.md)
- [assets/js/cover/README.md](assets/js/cover/README.md)
- [assets/js/reader/README.md](assets/js/reader/README.md)
- [assets/js/pdf/README.md](assets/js/pdf/README.md)
- [assets/js/quiz-builder/README.md](assets/js/quiz-builder/README.md)

## Learni y quizzes

Bookster no usa Learni como un plugin externo para ebooks. Lo consume como un
modulo nativo en `modules/learni`.

### Que resuelve Bookster

- Quizzes para ebooks.
- Quizzes por capitulo.
- Asociacion libro-capitulo-quiz.
- Builder editorial en `/almaden-book-quiz/`.

### Que no resuelve Bookster

- El LMS de cursos.
- El dashboard de creadores de cursos.
- El editor clasico de cursos y lecciones.

### Lectura recomendada para este contexto

1. [modules/learni/README.md](modules/learni/README.md)
2. [includes/integrations/learni-integration.php](includes/integrations/learni-integration.php)
3. [templates/quiz-builder/quiz-builder-app.php](templates/quiz-builder/quiz-builder-app.php)

### Distincion operativa

Los quizzes de ebooks y los quizzes de cursos comparten la idea de pregunta,
respuesta y evaluacion, pero no comparten el mismo contrato de ejecucion.

- Ebooks: contexto editorial, capitulos, orden de lectura y flujo de libro.
- Cursos: contexto LMS, lecciones, progreso academico y dashboard de creador.

### Optimizaciones recomendadas

1. Mantener `modules/learni` como fuente de verdad de Bookster.
2. Evitar depender de `learni-standalone` para abrir o guardar quizzes de ebooks.
3. Centralizar la normalizacion de payloads en helpers comunes, no en la UI.
4. Si hace falta crecer, extraer una capa compartida de "quiz core" y dejar dos
   adaptadores:
   - uno para `bookster`
   - uno para `learni-standalone`

### Cuando debas tocar codigo de quizzes

Si el cambio afecta persistencia o CRUD del quiz de ebooks, entra a:

- [modules/learni/includes/QuizEditor/QuizEditor.php](modules/learni/includes/QuizEditor/QuizEditor.php)
- [modules/learni/includes/QuizEditor/QuizRepository.php](modules/learni/includes/QuizEditor/QuizRepository.php)
- [includes/integrations/learni-integration-helpers.php](includes/integrations/learni-integration-helpers.php)

## Regla importante para paginas publicas

Para paginas publicas integradas con el tema, no se debe reemplazar toda la pagina con `template_redirect` si el objetivo es mantener el layout del tema activo. En esos casos, el contenido debe entrar por `the_content`.

Las apps tipo dashboard o editor interno si pueden usar `template_redirect` para renderizar una superficie limpia desde cero.

## Modelo de acceso del plugin

Este plugin separa sus superficies en dos grupos:

### Publicas

- `Autores`
- `Editoriales`
- `Ebook Store`

`Dashboard` mantiene su comportamiento actual y no forma parte del catalogo publico principal.

### Navegacion de usuario

- **Main navbar**: `Autores`, `Editoriales`, `Ebook Store`
- **Profile menu**: `Dashboard`, `Taller`, `Sala de clases` y `Cerrar sesión`
- El profile menu no debe duplicar enlaces de navegacion principal.

### Privadas o internas

- `Taller`
- `Sala de clases`
- Cualquier flujo de creacion, edicion o administracion editorial

### Regla de implementacion

- Las paginas publicas se renderizan sin exigir login.
- Las paginas privadas deben redirigir a login con `auth_redirect()` y luego validar permisos especificos.
- La navegacion compartida debe ocultar enlaces privados para usuarios anonimos.
- Si una vista cambia de publico a privado, la URL puede mantenerse, pero su acceso debe pasar por la misma regla en todos los puntos: `template_redirect`, menus y CTAs.

* almaden-bookster.php
* modules
* docs
