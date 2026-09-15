# AGENTS.md: Almaden Bookster

Guia operativa para agentes que trabajen en `almaden-bookster`. El root del
proyecto es este directorio del plugin; el directorio padre `app` pertenece al
sitio WordPress administrado por Local y no debe tratarse como root principal.

## Protocolo de contexto

Antes de crear o modificar archivos:

1. Leer este archivo completo.
2. Leer el [README.md](README.md) raiz.
3. Leer el `README.md` de la carpeta que se modificara.
4. Leer README de carpetas vecinas solo cuando la tarea cruce sus fronteras.
5. Consultar documentacion adicional solo cuando sea relevante para la tarea.
6. Revisar archivos vecinos del area modificada para entender patrones, hooks,
   dependencias y responsabilidades.

Durante el cambio:

- Mantener el alcance lo mas pequeno posible.
- Respetar la arquitectura descrita por el README local.
- Actualizar el README local si se agrega, elimina, mueve o cambia la
  responsabilidad de archivos.
- Evitar duplicar HTML, consultas, helpers, estilos o controladores cuando
  exista una pieza compartida clara.
- No cambiar contratos de rutas, slugs, metadatos, tablas, AJAX, permisos o
  payloads sin revisar sus consumidores.

Antes de cerrar:

- Ejecutar la verificacion minima que corresponda.
- Verificar con `wc -l` el tamano de los archivos modificados.
- Reportar archivos modificados, verificaciones realizadas y pruebas no
  ejecutadas.

## Alcance del workspace

- Priorizar siempre archivos dentro de `almaden-bookster`.
- Inspeccionar `app`, WordPress, logs o archivos externos solo cuando el
  debugging lo requiera.
- No modificar archivos fuera del plugin sin autorizacion explicita.
- Cuando se use una dependencia externa al plugin, explicar cual fue necesaria
  y por que.

## Secretos y contexto local

- No leer archivos de credenciales, login, tokens o secretos salvo que la tarea
  lo requiera.
- No mostrar secretos en respuestas, logs ni commits.
- Tratar la configuracion local como contexto de debugging, no como regla
  arquitectonica.
- `README.local-login.md` existe solo para acceso local y puede estar excluido
  de Git.

## Regla verificable de 500 lineas

- Archivos de mas de 400 lineas quedan en alerta.
- Ningun archivo debe superar 500 lineas.
- Antes de finalizar, verificar los archivos modificados con `wc -l`.
- Si un archivo supera el limite, dividirlo antes de seguir agregandole logica.
- Los archivos principales deben orquestar; la logica debe vivir en modulos,
  templates, partials, helpers y assets encolados.

## Contrato de README local

Cada carpeta funcional debe tener un `README.md` cuando contenga codigo propio
o defina una frontera de arquitectura.

Cada README local debe responder brevemente:

- `Responsabilidad`
- `Archivos principales`
- `Flujo de entrada`
- `Reglas locales`
- `Al modificar aqui`
- `Archivos relacionados`

No crear README para carpetas generadas, temporales o puramente auxiliares.

## App pages frontend

Las nuevas paginas frontend del plugin no deben construirse como paginas
genericas del tema cuando pertenecen a una experiencia propia del producto.
Deben usar wrapper, navegacion y layout consistentes.

Arquitectura esperada:

- `includes/frontend/app-shell.php`: documento, navegacion y estructura comun.
- `templates/<dominio>/<pantalla>-app.php`: wrapper de pantalla.
- `templates/<dominio>/<pantalla>.php`: contenido parcial o vista interna.
- `includes/<dominio>/*.php`: rutas, permisos, query vars, loaders y
  persistencia.

Reglas:

- Definir ruta o endpoint propio con rewrite rules, query vars o loader por
  `template_redirect` cuando corresponda.
- Reutilizar el shell compartido y usar IDs unicos en el contenedor principal.
- Para paginas publicas que deban mantener el layout del tema, insertar el
  contenido por `the_content` en vez de reemplazar toda la respuesta.
- Extraer un helper o shell comun antes de repetir HTML entre pantallas.

## Paginas Almaden Shell

Cada nueva pagina de Almaden Shell debe incorporarse al proceso de instalacion
y activacion del plugin.

La implementacion debe:

1. Agregar configuracion predeterminada con titulo, slug y referencia de
   `page_id`.
2. Crear o actualizar la sincronizacion que genera la pagina de WordPress con
   contenido dinamico del plugin.
3. Incluirla en la instalacion de paginas principales ejecutada por
   `register_activation_hook`.
4. Marcarla como pagina de Almaden Shell para navegacion, visibilidad y menus.
5. Mantener separadas las paginas personalizadas creadas desde el administrador.

## Acceso y navegacion

Superficies publicas:

- `Autores`
- `Editoriales`
- `Ebook Store`

Navegacion de usuario:

- Main navbar: `Autores`, `Editoriales`, `Ebook Store`.
- Profile menu: `Dashboard`, `Taller`, `Sala de clases` y `Cerrar sesion`.
- El profile menu no debe duplicar enlaces de navegacion principal.

Privadas o internas:

- `Taller`
- `Sala de clases`
- Flujos de creacion, edicion o administracion editorial.

Las paginas publicas no deben exigir login. Las privadas deben redirigir a login
con `auth_redirect()` o la regla equivalente del shell, y luego validar permisos
especificos. La navegacion compartida debe ocultar enlaces privados a usuarios
anonimos.

## Modulos y fronteras

`modules/learni` es la fuente de verdad para quizzes de ebooks y capitulos
dentro de Bookster. No depender de `learni-standalone` para abrir o guardar
quizzes de ebooks.

Los quizzes de ebooks y cursos comparten conceptos, pero no contrato de
ejecucion:

- Ebooks: contexto editorial, capitulos, orden de lectura y flujo de libro.
- Cursos: contexto LMS, lecciones, progreso academico y dashboard de creador.

Si se unifica logica, hacerlo con una capa compartida pequena y adaptadores por
contexto.

## Verificacion minima

- PHP: `php -l <archivo.php>` en archivos tocados.
- JS: revisar sintaxis y dependencias globales; probar en navegador cuando
  aplique.
- CSS/templates: revisar la pantalla afectada en desktop y movil si cambia
  layout.
- Rutas/app pages: visitar URL canonica y revisar anonimo/logueado cuando haya
  permisos.
- DB/schema: verificar activacion, migracion o consulta involucrada.
- README/docs: confirmar links, nombres de archivos y ejecutar
  `git diff --check`.

## Subagente CSS

Usar el subagente `css-designer` cuando la tarea sea principalmente visual:
CSS, layout, responsive design, consistencia de UI, fuentes, iconos o assets
graficos.

El subagente debe limitarse al alcance CSS/frontend visual. Si la tarea tambien
requiere rutas, permisos, AJAX, persistencia, pagos, quizzes o logica de
negocio, coordinar con otro agente o con el agente principal antes de cambiar
ese comportamiento.

Antes de trabajos visuales, consultar:

- [docs/css-architecture.md](docs/css-architecture.md)
- [docs/design-system.md](docs/design-system.md)
- [.codex/agents/css-designer.md](.codex/agents/css-designer.md)

## Documentacion relacionada

- [docs/README.md](docs/README.md): indice de documentacion.
- [docs/css-architecture.md](docs/css-architecture.md): mapa de CSS, cargas,
  consumidores y riesgos visuales.
- [docs/design-system.md](docs/design-system.md): tokens y patrones visuales
  detectados.
- [docs/debugging.md](docs/debugging.md): debugging local y sincronizacion
  diferencial asistida por LLM.
- [docs/distribution-commerce-architecture.md](docs/distribution-commerce-architecture.md):
  arquitectura de distribucion comercial y acceso al ebook.
