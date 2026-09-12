# Agent Guidelines: Almaden Bookster

Esta guia define el protocolo obligatorio para agentes AI y desarrolladores que trabajen en el plugin `Almaden Bookster`. Su objetivo es que cada cambio se haga con contexto local, modularidad estricta y verificacion suficiente.

## Protocolo Operativo

Antes de crear o modificar codigo:

1. Leer este archivo completo.
2. Leer el [README.md](file:///Users/nicolaspavez/Local%20Sites/almaden/app/public/wp-content/plugins/almaden-bookster/README.md) del plugin.
3. Leer todos los `README.md` en la ruta desde la raiz del plugin hasta la carpeta que se va a tocar.
4. Revisar los archivos vecinos del area modificada para entender patrones, nombres, hooks, dependencias y responsabilidades.
5. Confirmar si el cambio toca frontend publico, app page interna, WordPress admin, datos, pagos, quizzes, reader, editor o modulos.

Durante el cambio:

1. Mantener el alcance lo mas pequeno posible.
2. Respetar la arquitectura descrita por el README local.
3. Actualizar el README local si se agrega, elimina, mueve o cambia la responsabilidad de archivos.
4. Evitar duplicar HTML, consultas, helpers, estilos o controladores cuando exista una pieza compartida clara.
5. No cambiar contratos de rutas, slugs, metadatos, tablas, AJAX, permisos o payloads sin revisar sus consumidores.

Antes de cerrar:

1. Ejecutar la verificacion minima que corresponda.
2. Reportar los archivos modificados y la verificacion realizada.
3. Indicar explicitamente cualquier prueba que no se pudo ejecutar.

Modificar codigo sin leer los README correspondientes se considera una falla de proceso.

## Regla Principal: 500 Lineas

Ningun archivo dentro de este plugin debe superar las 500 lineas de codigo.

La modularidad extrema es una regla de arquitectura, no una recomendacion. Si una implementacion va a llevar un archivo por encima del limite, primero se debe dividir o refactorizar.

Reglas practicas:

- Si un archivo supera las 400 lineas, queda en estado de alerta y cualquier cambio grande debe planificar su division.
- Cada archivo debe tener una responsabilidad principal clara.
- Los archivos principales deben orquestar, no acumular toda la logica.
- Las funciones deben ser pequenas, especificas y nombradas segun su accion.
- No mezclar HTML extenso, scripts inline masivos y logica PHP en un mismo archivo.
- Las interfaces deben componerse con templates, partials, helpers y assets encolados.

## Contrato de README Local

Cada carpeta funcional debe tener un `README.md` cuando contenga codigo propio o defina una frontera de arquitectura.

Cada README local debe responder, en forma breve:

- `Responsabilidad`: que resuelve esa carpeta.
- `Archivos principales`: que hace cada archivo relevante.
- `Flujo de entrada`: desde donde se carga, llama o renderiza.
- `Reglas locales`: convenciones y limites propios del area.
- `Al modificar aqui`: que revisar y que validar.
- `Archivos relacionados`: enlaces a carpetas vecinas o consumidores.

Si una carpeta nueva contiene codigo, debe nacer con su README. Si una carpeta existente no tiene README y se va a tocar, crear o completar el README forma parte del cambio.

## App Pages Frontend

Las nuevas paginas frontend del plugin no deben construirse como paginas genericas del tema de WordPress cuando pertenecen a una experiencia propia del producto. Deben construirse como app pages con wrapper, navegacion y layout consistente.

Flujo correcto:

1. Definir la ruta o endpoint propio dentro del plugin, usando `rewrite rules`, `query vars` o un loader por `template_redirect` cuando corresponda.
2. Crear un wrapper especifico para esa pagina, por ejemplo `*-app.php`.
3. Reutilizar el shell compartido para mantener logo, navbar, encabezado, fuentes y estructura general.
4. Dejar el contenido real en partials o templates internos separados del wrapper.
5. Usar IDs unicos en el contenedor principal de cada pagina para facilitar CSS, JS y testing.
6. No depender del template del tema si la pantalla debe verse como producto interno del plugin.

Reglas de implementacion:

- Si la pagina es app publica o producto autonomo, puede usar `template_redirect` y `exit` despues de renderizar.
- Si la pagina publica debe mantener el layout del tema activo, el contenido debe entrar por `the_content` y no reemplazar toda la respuesta con `template_redirect`.
- Si la pagina necesita variaciones por rol o contexto, separar cada variante en su propio wrapper y compartir el shell base.
- Si la pagina necesita navegacion propia, definir los links en el wrapper o en el shell compartido.
- Si aparece HTML repetido entre pantallas, extraer primero un helper o shell comun.

Arquitectura esperada:

- `includes/frontend/app-shell.php`: estructura comun de navegacion y documento.
- `templates/<dominio>/<pantalla>-app.php`: wrapper de pantalla.
- `templates/<dominio>/<pantalla>.php`: contenido parcial o vista interna.
- `includes/<dominio>/*.php`: rutas, permisos, query vars, loaders y persistencia.

## Paginas Almaden Shell

Cada nueva pagina de Almaden Shell debe incorporarse al proceso de instalacion y activacion del plugin. No basta con crear wrapper, ruta o template.

La implementacion debe:

1. Agregar la configuracion predeterminada de la pagina, incluyendo titulo, slug y referencia de `page_id`.
2. Crear o actualizar la funcion de sincronizacion que genera la pagina de WordPress con el contenido dinamico del plugin.
3. Incluirla en la rutina de instalacion de paginas principales ejecutada por `register_activation_hook`.
4. Marcarla como pagina perteneciente a Almaden Shell para que navegacion, visibilidad y menus la reconozcan.
5. Mantener separadas las paginas personalizadas creadas desde el administrador.

Si el titulo o slug son editables, esos valores deben conservarse como configuracion del sitio, pero la pagina y su funcionalidad base deben seguir instaladas por el plugin.

## Acceso y Navegacion

El plugin separa sus superficies en publicas, navegacion de usuario e internas.

Publicas:

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

Reglas:

- Las paginas publicas se renderizan sin exigir login.
- Las paginas privadas deben redirigir a login con `auth_redirect()` o con la regla equivalente del shell, y luego validar permisos especificos.
- La navegacion compartida debe ocultar enlaces privados para usuarios anonimos.
- Si una vista cambia de publica a privada, la URL puede mantenerse, pero el acceso debe validarse igual en `template_redirect`, menus y CTAs.

## Modulos y Fronteras

`modules/learni` es la fuente de verdad para quizzes de ebooks y capitulos dentro de Bookster. No debe dependerse de `learni-standalone` para abrir o guardar quizzes de ebooks.

Los quizzes de ebooks y cursos comparten conceptos, pero no contrato de ejecucion:

- Ebooks: contexto editorial, capitulos, orden de lectura y flujo de libro.
- Cursos: contexto LMS, lecciones, progreso academico y dashboard de creador.

Si se unifica logica, hacerlo con una capa compartida pequena y adaptadores por contexto.

## Verificacion Minima

Elegir la verificacion segun el cambio:

- PHP: `php -l <archivo.php>` en archivos tocados.
- JS: revisar errores de sintaxis y dependencias globales; cuando aplique, probar en navegador.
- CSS/templates: revisar la pantalla afectada en desktop y movil si cambia layout.
- Rutas/app pages: visitar URL canonica y revisar usuario anonimo y logueado cuando haya permisos.
- DB/schema: verificar activacion, migracion o consulta involucrada.
- README/docs: confirmar que los links y nombres de archivos sigan vigentes.

## Contexto Local

Las credenciales, sockets y datos de una instalacion local no son reglas de arquitectura. Deben documentarse fuera de este guideline, por ejemplo en `README.local-login.md` o documentacion local equivalente.

## Método de Sincronización Diferencial Asistida por LLM

Este método estandariza el proceso para corregir y sincronizar contenidos complejos de WordPress (donde existen shortcodes, tablas HTML u otras etiquetas de formato) con una fuente de verdad externa (como un archivo `.docx`), asegurando que la maquetación no se pierda en el proceso.

**Flujo de trabajo:**

1. **Obtención de la fuente de verdad**: Extraer el texto de la fuente original (ej. convertir `.docx` a `.txt` plano o extraer capítulos específicos) y guardarlo en un entorno temporal (scratch).
2. **Obtención del contenido actual**: Utilizar comandos como `wp post get <ID> --field=post_content > ch_actual.txt` para descargar la versión en crudo de la base de datos de WordPress.
3. **Limpieza y Diffing**: Utilizar un script (`Python` con la librería `difflib`) para extraer solo las palabras (ignorando tags HTML y shortcodes) y generar un reporte diferencial preciso de las palabras faltantes, sobrantes o discrepancias.
4. **Actualización quirúrgica (LLM / Scripting)**: Con el reporte de diferencias, usar expresiones regulares o funciones de reemplazo (`replace()` o `re.sub()`) de manera programática sobre el archivo `ch_actual.txt` para insertar las correcciones, cuidando escrupulosamente de NO alterar las tablas HTML, atributos `style` ni estructuras de shortcode (`[box]`, `[html]`, etc.).
5. **Subida de los cambios**: Cargar el contenido actualizado de regreso a la base de datos usando WP-CLI (`wp post update <ID> /ruta/al/archivo/actualizado.txt`).
6. **Validación**: Verificar visualmente o mediante logs que la actualización fue exitosa y que la maquetación se conservó intacta.
