# TODO: Modulo de seguimiento de lectura

## Objetivo

Crear un modulo ligero para registrar el tiempo efectivo que un usuario dedica a
leer cada capitulo de un ebook dentro del Reader.

El contador debe ejecutarse en el navegador, distinguir una lectura activa de
una pagina simplemente abierta, detectar cuando se alcanza el final del
capitulo y persistir sesiones consolidadas en la base de datos.

## Decisiones iniciales

- El modulo vivira en `modules/reading-tracker/`.
- Solo se registraran usuarios autenticados con acceso valido al ebook.
- No se guardara un registro por cada evento de scroll.
- La sesion se preparara al abrir el capitulo.
- El tiempo activo comenzara con la primera interaccion real de lectura.
- El contador se pausara cuando la pestana no este visible.
- El contador se pausara despues de 45 segundos sin actividad.
- Se enviara un checkpoint cada 30 segundos.
- El final se considerara alcanzado al 98% del recorrido.
- En modo flip, el final sera la ultima pagina fisica.
- Alcanzar el final no cambiara automaticamente de capitulo.
- El cambio ocurrira cuando el lector use la navegacion existente.
- Cada sesion tendra un UUID para que los envios sean idempotentes.
- Las fechas se almacenaran en UTC.

Los umbrales de inactividad, checkpoint y final deben quedar definidos como
constantes configurables, no como valores dispersos en el codigo.

## Arquitectura propuesta

```text
modules/reading-tracker/
|-- README.md
|-- init.php
|-- includes/
|   |-- class-reading-schema.php
|   |-- class-reading-endpoint.php
|   |-- class-reading-repository.php
|   `-- class-reading-assets.php
|-- assets/js/
|   |-- reading-tracker.js
|   |-- reading-tracker-activity.js
|   `-- reading-tracker-transport.js
`-- tests/
    |-- js/
    `-- php/
```

Todos los archivos nuevos y modificados deben respetar el limite de 500 lineas
establecido en `AGENT_GUIDELINES.md`.

## Fase 0: modularizacion previa

- [ ] Extraer responsabilidades de `almaden-bookster.php`, actualmente sobre
      el limite de 500 lineas.
- [ ] Extraer la lista de scripts o bloques reutilizables de
      `templates/reader/reader-app.php`, actualmente sobre el limite.
- [ ] Confirmar que la extraccion no cambia el comportamiento del Reader.
- [ ] Mantener todos los archivos afectados bajo 500 lineas.

## Fase 1: esquema de base de datos

### Tabla `{$wpdb->prefix}almaden_reading_sessions`

Almacena una fila por sesion de lectura de capitulo.

| Campo | Tipo | Descripcion |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Clave primaria autoincremental. |
| `session_uuid` | `CHAR(36)` | Identificador idempotente del navegador. |
| `user_id` | `BIGINT UNSIGNED` | Usuario lector. |
| `book_id` | `BIGINT UNSIGNED` | Ebook publico. |
| `chapter_id` | `BIGINT UNSIGNED` | Capitulo leido. |
| `opened_at` | `DATETIME` | Momento de entrada al capitulo. |
| `engaged_at` | `DATETIME NULL` | Primera actividad real de lectura. |
| `ended_at` | `DATETIME NULL` | Cierre definitivo de la sesion. |
| `last_activity_at` | `DATETIME NULL` | Ultima actividad valida. |
| `active_seconds` | `INT UNSIGNED` | Tiempo efectivo de lectura. |
| `elapsed_seconds` | `INT UNSIGNED` | Tiempo total desde la apertura. |
| `max_progress_bp` | `SMALLINT UNSIGNED` | Progreso maximo entre 0 y 10000. |
| `reached_end` | `TINYINT(1)` | Indica si se alcanzo el final. |
| `status` | `VARCHAR(20)` | Estado `active` o `closed`. |
| `end_reason` | `VARCHAR(30)` | Motivo de cierre. |
| `created_at` | `DATETIME` | Fecha de creacion. |
| `updated_at` | `DATETIME` | Ultimo checkpoint procesado. |

Indices requeridos:

- [ ] Indice unico por `user_id, session_uuid`.
- [ ] Indice por `user_id, opened_at`.
- [ ] Indice por `user_id, book_id, chapter_id`.
- [ ] Indice por `book_id, chapter_id, reached_end`.

### Tabla `{$wpdb->prefix}almaden_reading_chapter_progress`

Mantiene el resumen acumulado por usuario, libro y capitulo para evitar agregar
todas las sesiones en cada consulta.

| Campo | Tipo | Descripcion |
| --- | --- | --- |
| `id` | `BIGINT UNSIGNED` | Clave primaria autoincremental. |
| `user_id` | `BIGINT UNSIGNED` | Usuario lector. |
| `book_id` | `BIGINT UNSIGNED` | Ebook. |
| `chapter_id` | `BIGINT UNSIGNED` | Capitulo. |
| `total_active_seconds` | `BIGINT UNSIGNED` | Tiempo acumulado de lectura. |
| `session_count` | `INT UNSIGNED` | Cantidad de sesiones efectivas. |
| `max_progress_bp` | `SMALLINT UNSIGNED` | Mayor progreso alcanzado. |
| `last_position_bp` | `SMALLINT UNSIGNED` | Ultima posicion para reanudacion futura. |
| `completed_at` | `DATETIME NULL` | Primera llegada al final. |
| `first_read_at` | `DATETIME NULL` | Primera lectura efectiva. |
| `last_read_at` | `DATETIME NULL` | Ultima lectura efectiva. |
| `last_session_uuid` | `CHAR(36)` | Ultima sesion procesada. |
| `updated_at` | `DATETIME` | Fecha de actualizacion. |

- [ ] Crear una restriccion unica por `user_id, book_id, chapter_id`.
- [ ] Implementar el esquema con `dbDelta()` y una version propia.
- [ ] Integrar el instalador con `includes/database/schema.php`.
- [ ] Crear helpers centralizados para obtener los nombres de las tablas.

## Fase 2: contador en el navegador

- [ ] Crear una sesion local al entrar correctamente en un capitulo.
- [ ] Registrar por separado el momento de apertura y el primer engagement.
- [ ] Escuchar el scroll del contenedor real del capitulo.
- [ ] Reconocer rueda, gesto tactil y navegacion flip como actividad.
- [ ] Ignorar movimientos menores o ruido del layout.
- [ ] Aplicar throttle a los eventos frecuentes.
- [ ] Contar tiempo solo cuando `document.visibilityState` sea `visible`.
- [ ] Pausar el contador tras el umbral de inactividad.
- [ ] Reanudar la misma sesion cuando vuelva la actividad.
- [ ] Calcular el progreso maximo sin permitir retrocesos.
- [ ] Detectar el final en modo scroll y modo flip.
- [ ] Descartar aperturas sin actividad significativa.
- [ ] Cerrar la sesion al cambiar de capitulo o volver al indice.

## Fase 3: transporte confiable

- [ ] Enviar checkpoints periodicos mediante `fetch`.
- [ ] Usar `keepalive: true` cuando corresponda.
- [ ] Usar `navigator.sendBeacon()` en `pagehide` o cierre de pestana.
- [ ] Mantener una cola pequena y acotada en `localStorage` para envios fallidos.
- [ ] Reintentar la cola al volver a abrir el Reader.
- [ ] Evitar duplicados mediante el UUID de sesion.
- [ ] Limitar la cantidad y antiguedad de elementos de la cola.

## Fase 4: endpoint y persistencia

- [ ] Crear un endpoint AJAX exclusivo para usuarios autenticados.
- [ ] Validar un nonce asociado al libro.
- [ ] Verificar que el usuario tiene acceso al ebook.
- [ ] Verificar que el capitulo pertenece al libro correspondiente.
- [ ] Sanitizar y limitar todos los valores recibidos.
- [ ] Impedir que `active_seconds` supere `elapsed_seconds`.
- [ ] Rechazar progreso fuera del rango 0 a 10000.
- [ ] Procesar tiempo y progreso de forma monotona.
- [ ] Insertar o actualizar la sesion por UUID.
- [ ] Actualizar el resumen del capitulo de manera idempotente.
- [ ] No confiar en identificadores de usuario enviados por JavaScript.
- [ ] Responder con un payload pequeno y consistente.

Motivos de cierre iniciales:

- `chapter_change`
- `index_view`
- `pagehide`
- `reader_exit`
- `completed`

La inactividad pausa la sesion, pero no debe crear una nueva fila ni cerrarla de
forma inmediata.

## Fase 5: integracion con el Reader

- [ ] Cargar el modulo solo cuando existe acceso efectivo al Reader.
- [ ] Exportar al navegador solo endpoint, nonce, libro y configuracion.
- [ ] Integrarse despues de `reader-navigation.js` y `reader-quizzes.js`.
- [ ] Respetar los bloqueos de quizzes obligatorios.
- [ ] Iniciar una sesion solo despues de que el capitulo se renderice.
- [ ] Finalizar la sesion antes de navegar a otro capitulo.
- [ ] Evitar mezclar la logica del tracker con la navegacion visual.
- [ ] Dejar helpers de consulta disponibles para `My Reading Stats`.
- [ ] No modificar la interfaz de estadisticas en esta primera entrega.

## Fase 6: pruebas

### JavaScript

- [ ] No contar tiempo antes de la primera actividad.
- [ ] Pausar por inactividad.
- [ ] Pausar al ocultar la pestana.
- [ ] Reanudar sin crear una sesion duplicada.
- [ ] Detectar el final en modo scroll.
- [ ] Detectar el final en modo flip.
- [ ] Cerrar al cambiar de capitulo.
- [ ] Crear un beacon valido al cerrar la pestana.
- [ ] Reintentar un checkpoint fallido.
- [ ] Mantener monotono el progreso maximo.

### PHP y base de datos

- [ ] Instalar y actualizar ambas tablas.
- [ ] Procesar dos veces el mismo UUID sin duplicarlo.
- [ ] Rechazar usuarios anonimos.
- [ ] Rechazar nonces invalidos.
- [ ] Rechazar libros sin acceso.
- [ ] Rechazar capitulos ajenos al libro.
- [ ] Limitar tiempos y progreso manipulados.
- [ ] Actualizar correctamente el resumen acumulado.

### Flujo completo

- [ ] Probar un capitulo corto y uno largo.
- [ ] Probar scroll continuo y flip.
- [ ] Probar cambio rapido entre capitulos.
- [ ] Probar pestana oculta durante varios minutos.
- [ ] Probar cierre y reapertura del navegador.
- [ ] Probar convivencia con highlights y quizzes obligatorios.
- [ ] Confirmar que no se generan solicitudes por cada evento de scroll.
- [ ] Ejecutar validacion de sintaxis PHP y pruebas JavaScript.
- [ ] Confirmar que ningun archivo del plugin supera 500 lineas.

## Criterios de aceptacion

- [ ] Una pagina abierta sin interaccion no suma tiempo de lectura.
- [ ] El tiempo inactivo y el tiempo con la pestana oculta no se contabilizan.
- [ ] Una sesion conserva inicio, termino, libro, capitulo y tiempo activo.
- [ ] Llegar al final queda registrado para el progreso del capitulo.
- [ ] Cerrar la pestana no duplica ni pierde una sesion ya sincronizada.
- [ ] El Reader mantiene su navegacion y quizzes actuales.
- [ ] El modulo no degrada perceptiblemente el scroll.
- [ ] Las tablas permiten consultar tiempo por usuario, libro y capitulo.
- [ ] La implementacion cumple `AGENT_GUIDELINES.md`.
