# Módulo Content Protection

Plan técnico para proteger el contenido textual del Web Reader de Almaden
Bookster contra la copia convencional y reducir la extracción automatizada.

> Estado: Fases 1, 2, 3 y 4 implementadas el 2026-08-17. La ampliación del
> rollout queda condicionada a completar la matriz manual de cada release.

## Objetivo

Impedir que el texto del ebook llegue al portapapeles mediante las acciones
convencionales del computador o dispositivo:

- atajos `Ctrl/Cmd + C` y `Ctrl/Cmd + X`;
- menú contextual y menú del navegador;
- acciones Copy/Cut del sistema operativo y de interfaces táctiles;
- arrastre de una selección hacia otra aplicación;
- impresión convencional del contenido protegido.

La selección nativa debe continuar disponible porque el Reader la usa para
crear highlights y comentarios. El módulo no debe bloquear que el usuario copie
texto escrito por él mismo en formularios, comentarios o campos editables.

## Garantía real y límites

La meta verificable es bloquear la copia convencional, no declarar que el texto
HTML es imposible de extraer.

Cuando un navegador muestra texto, un usuario con control de su equipo todavía
puede recuperarlo mediante DevTools, una extensión, una petición inspeccionada,
automatización, captura de pantalla, cámara u OCR. JavaScript, CSS, Shadow DOM,
WebAssembly y cifrado cuya clave llega al navegador no constituyen una frontera
de seguridad frente al propietario de ese navegador.

Por esa razón, la estrategia propuesta combina:

1. prevención efectiva de las acciones normales;
2. reducción de la exposición masiva del contenido;
3. disuasión y trazabilidad silenciosa, sin capa negra ni marca visible sobre el texto;
4. observabilidad, pruebas y accesibilidad.

Si en el futuro el producto exige DRM fuerte para archivos descargables, la
ruta correcta es una app lectora certificada con Readium LCP. LCP protege EPUB y
PDF con licencias y límites de copia/impresión, pero su propia documentación
indica que una aplicación web no puede manejar de forma segura sus secretos.

## Diagnóstico del Reader actual

Antes de la Fase 2, el Reader tenía dos características que condicionaban la
solución:

- `templates/reader/reader-app.php` serializa todos los capítulos completos en
  el HTML inicial y los expone en `window.bookData`.
- `assets/js/reader/reader-highlights-dom.js` usa la selección nativa y offsets
  de texto para guardar highlights.

La Fase 2 eliminó el primer riesgo mediante entrega bajo demanda. La segunda
característica se conserva y descarta una solución global basada en
`user-select: none` o en renderizar el texto como imagen/canvas.

## Modelo de amenazas

### Dentro del alcance

- lector legítimo que intenta copiar con teclado, menú o gesto táctil;
- arrastre de texto o imágenes del capítulo;
- impresión casual desde el navegador;
- descarga masiva sencilla desde el payload HTML inicial;
- capturas compartidas por un usuario identificado;
- previews del app switcher y capturas iniciadas después de perder foco;
- abuso repetitivo observable desde la aplicación.

### Fuera del alcance del Web Reader

- DevTools, extensiones privilegiadas o navegador modificado;
- intercepción de respuestas en el equipo del usuario;
- OCR, cámara externa o captura de pantalla del sistema operativo;
- capturas del sistema mientras el Reader web permanece visible y enfocado;
- malware con acceso al perfil del navegador;
- transcripción manual;
- garantías criptográficas después de que el texto fue descifrado y renderizado.

## Decisiones técnicas

| Técnica | Decisión | Motivo |
| --- | --- | --- |
| Evento `copy`/`cut` en fase capture + `preventDefault()` | Adoptar | Es el mecanismo estándar que cubre atajos y acciones iniciadas desde la UI del navegador. |
| Validar que la selección intersecte contenido protegido | Adoptar | Evita bloquear la copia de notas propias y controles del Reader. |
| `keydown` para `Ctrl/Cmd+C`, `Ctrl/Cmd+X` | Adoptar como capa UX | Permite mostrar aviso inmediato, pero no reemplaza el evento `copy`. |
| Cancelar `dragstart` de selección/contenido protegido | Adoptar | Cierra la salida por drag-and-drop. |
| Estilos `@media print` + evento `beforeprint` | Adoptar | Oculta el cuerpo y presenta una página informativa al imprimir. |
| Watermark visible y repetido por licencia/sesión | Adoptar | Disuade capturas y permite atribución sin degradar el texto. |
| Cortina negra por blur/visibility/señal nativa | Adoptar como mitigación | Reduce previews y algunos flujos de captura, sin prometer control del sistema operativo. |
| Carga de capítulos bajo demanda | Adoptar | Evita entregar el libro completo en un único payload y dificulta scraping masivo. |
| Telemetría agregada de intentos bloqueados | Adoptar | Permite detectar abuso sin registrar el texto seleccionado. |
| `user-select: none` global | Rechazar | Rompe highlights, selección por teclado y parte de la accesibilidad. |
| Bloquear solamente clic derecho | Rechazar | Es fácil de eludir y no cubre menú del navegador ni teclado. |
| Dividir letras en spans, ordenarlas con CSS o usar una fuente cifrada | Rechazar | Perjudica lectura asistida, búsqueda y highlights; el mapeo se puede reconstruir. |
| Renderizar todo en canvas/imágenes | Rechazar por defecto | Elimina semántica y accesibilidad, rompe highlights y sigue siendo vulnerable a OCR. |
| Cifrar capítulos con una clave entregada a JavaScript/WASM | Rechazar | La clave y el texto descifrado quedan disponibles en el cliente. |
| Shadow DOM cerrado u `iframe sandbox` como DRM | Rechazar | Sirven para encapsulación, no para proteger el contenido del usuario del navegador. |
| EME/Widevine | No aplicable | Encrypted Media Extensions protege flujos de `HTMLMediaElement`, no texto HTML. |
| Readium LCP | Ruta futura fuera de la web | Adecuado para EPUB/PDF en apps certificadas, no para el Reader web actual. |

## Arquitectura propuesta

El módulo será autónomo y se cargará desde `almaden-bookster.php` mediante su
propio `init.php`. Ningún archivo podrá superar 500 líneas.

### Implementación actual de las cuatro fases

```text
modules/content-protection/
├── README.md
├── ROLLOUT.md
├── init.php
├── includes/
│   ├── class-protection-policy.php
│   ├── class-protection-admin.php
│   ├── class-content-protection.php
│   ├── class-chapter-endpoint.php
│   ├── class-watermark-token.php
│   └── class-protection-telemetry.php
├── assets/
│   ├── js/
│   │   ├── clipboard-guard.js
│   │   ├── chapter-loader.js
│   │   ├── print-guard.js
│   │   ├── telemetry.js
│   │   └── watermark.js
│   └── css/
│       ├── content-protection.css
│       └── content-protection-print.css
└── tests/
    ├── fixtures/phase-one.html, phase-three.html, phase-four.html
    ├── e2e/README.md
    ├── js/*.test.js
    └── php/*.test.php
```

El test automatizado puede ejecutarse con:

```bash
node --test modules/content-protection/tests/js/*.test.js
for test in modules/content-protection/tests/php/*.test.php; do php "$test"; done
```

### Arquitectura objetivo al completar las cuatro fases

```text
modules/content-protection/
├── README.md
├── init.php
├── includes/
│   ├── class-content-protection.php       # Orquestador y registro de hooks
│   ├── class-protection-policy.php        # Política efectiva global/por libro
│   ├── class-chapter-endpoint.php         # Entrega autorizada de un capítulo
│   ├── class-watermark-token.php          # Token firmado/pseudónimo por licencia
│   └── class-protection-telemetry.php      # Eventos agregados y rate limiting
├── assets/
│   ├── js/
│   │   ├── clipboard-guard.js             # copy, cut y shortcuts
│   │   ├── drag-print-guard.js            # dragstart y print lifecycle
│   │   ├── chapter-loader.js              # carga efímera bajo demanda
│   │   └── protection-notice.js           # aviso accesible no intrusivo
│   └── css/
│       ├── content-protection.css          # zonas protegidas y estilos del Reader
│       └── content-protection-print.css    # salida de impresión segura
└── tests/
    ├── js/
    ├── php/
    └── e2e/
```

### Contrato DOM

El módulo no debe acoplarse a clases visuales de Tailwind. Usará atributos
estables:

```html
<div id="chapter-content" data-almaden-protected-content="ebook"></div>
<div data-almaden-protected-excerpt="highlight"></div>
<textarea data-almaden-copy-allowed="user-note"></textarea>
```

Al producirse un evento, el guard examinará la selección completa, no solo
`event.target`. Si cualquier `Range` intersecta una zona protegida, cancelará el
evento en fase capture. Los campos con `data-almaden-copy-allowed` conservarán
su comportamiento nativo.

### Comportamiento del portapapeles

El handler primario escuchará `copy` y `cut` sobre `document` con `{capture:
true}`. Para una selección protegida:

1. ejecutará `preventDefault()`;
2. limpiará los tipos transferibles disponibles o escribirá contenido vacío
   compatible con el navegador;
3. no leerá ni persistirá el contenido actual del portapapeles;
4. mostrará el mensaje accesible “La copia de texto está desactivada en este
   ebook. Puedes guardarlo como highlight”;
5. emitirá, como máximo, un evento de telemetría agregado y rate-limited.

`keydown` será una capa secundaria para feedback rápido. Nunca será la única
defensa porque no cubre menús del navegador, accesibilidad ni interfaces
táctiles.

### Política configurable

La política se resolverá en servidor con defaults seguros y filtros WordPress:

```php
array(
    'enabled'              => true,
    'block_clipboard'      => true,
    'block_drag'           => true,
    'block_print'          => true,
    'watermark'            => false,
    'telemetry'            => true,
    'capture_deterrence'   => false,
    'chapter_delivery'     => 'on_demand',
    'accessibility_bypass' => false,
)
```

El backend es la fuente de verdad. Un nonce protege contra CSRF, pero nunca
sustituye la comprobación de sesión, compra/licencia y acceso al libro en cada
petición de capítulo.

### Entrega de capítulos

El HTML inicial contendrá metadatos e índice, no el texto completo de todos los
capítulos. El Reader solicitará solamente el capítulo actual y, opcionalmente,
uno adyacente para navegación fluida.

Cada respuesta deberá:

- verificar usuario autenticado, licencia/compra y pertenencia del capítulo;
- ignorar un `book_id` o `chapter_id` no autorizado aunque el nonce sea válido;
- usar `Cache-Control: private, no-store` y evitar caches públicas;
- devolver únicamente los campos necesarios para renderizar;
- aplicar rate limiting a recorridos anormalmente rápidos desde la Fase 3;
- no guardar el cuerpo de capítulos en `localStorage`, IndexedDB o Service
  Worker cache;
- abortar y purgar referencias al cambiar de capítulo cuando sea razonable.

Esto limita exposición y automatización básica, pero no oculta al usuario el
capítulo que está leyendo.

### Disuasión sin marca visible

El Reader evita la copia convencional con bloqueo de clipboard, carga de
capítulos bajo demanda, control de impresión y telemetría agregada. No se
superpone una marca visible sobre el texto para no degradar la experiencia de
lectura.

### Telemetría y privacidad

La tabla `wp_almaden_content_protection_events` registra solamente agregados
diarios:

- hash HMAC pseudónimo del sujeto;
- libro y último capítulo asociado al agregado;
- tipo de acción bloqueada (`copy`, `cut`, `drag`, `print`);
- fecha, último timestamp y contador.

No se registran ID de usuario, email, dirección IP, user-agent, texto
seleccionado, contenido del portapapeles, teclas generales, comentarios ni
fingerprints. Los datos se eliminan automáticamente después de 30 días mediante
WP-Cron y también se depuran por fecha en la tabla agregada.

El cliente emite como máximo un evento del mismo tipo por capítulo cada 10
segundos y el servidor acepta como máximo 30 eventos por minuto por sujeto y
libro. La entrega de capítulos se considera anormal cuando supera 24 peticiones
y al menos 18 capítulos distintos en 60 segundos; se bloquea durante 120
segundos, se agrega `chapter_rate_limited` y se dispara el hook
`almaden_bookster_content_protection_alert`.

Antes de producción, la política de privacidad del sitio debe informar esta
telemetría de protección y su retención de 30 días.

## Plan de implementación en cuatro fases

### Fase 1 — Guard de portapapeles sin romper highlights

**Estado: implementada el 2026-08-17. La validación automatizada está completa;
la matriz manual multiplaforma se ejecutará durante el rollout de la Fase 4.**

Entregables:

- crear el bootstrap, política y assets del módulo;
- marcar `#chapter-content` y excerpts guardados como zonas protegidas;
- interceptar `copy`, `cut` y `dragstart` en fase capture;
- añadir shortcut guard solamente como feedback;
- mantener selección, highlights, comentarios, enlaces y notas editables;
- añadir aviso `aria-live` traducible;
- cubrir Chrome, Safari, Firefox, Edge, iOS Safari y Android Chrome.

Criterio de salida: ninguna acción convencional copia texto protegido; crear,
guardar, enfocar y borrar highlights sigue funcionando sin regresiones.

### Fase 2 — Entrega mínima y protección de impresión

**Estado: implementada el 2026-08-17. La validación automatizada está completa;
la prueba E2E con una licencia comprada se incluye en el rollout de la Fase 4.**

Entregables:

- sacar el cuerpo completo de capítulos de `window.bookData`;
- implementar endpoint autenticado para cargar el capítulo actual;
- mantener solo actual + adyacente en memoria;
- añadir headers privados/no-store y autorización por recurso;
- bloquear impresión con stylesheet y documento informativo;
- impedir drag de imágenes originales del capítulo.

Criterio de salida: el HTML inicial y `window.bookData` ya no contienen el libro
completo; un usuario sin acceso no obtiene capítulos variando IDs.

### Fase 3 — Watermark y señales de abuso

**Estado: implementada el 2026-08-17. La validación automatizada está completa;
la comprobación visual y multiplataforma final corresponde a la Fase 4.**

Entregables:

- token de licencia firmado y pseudónimo;
- overlay responsive que funcione en scroll y flip;
- rotación de posición por sesión/capítulo;
- endpoint de telemetría agregado, rate-limited y sin texto;
- umbrales de alerta para extracción secuencial anormal;
- documentación de privacidad y retención.

Criterio de salida: toda captura normal contiene una marca atribuible y los
intentos repetidos generan señales útiles sin almacenar contenido sensible.

### Fase 4 — Hardening, accesibilidad y rollout

**Estado: implementada el 2026-08-17. Suite automatizada y fixture E2E
completos. La matriz física de navegadores, VoiceOver, Narrator y TalkBack es
un gate operativo que debe repetirse antes de aumentar cada release.**

Entregables:

- suite PHP/JS/E2E y matriz de navegadores/dispositivos;
- pruebas con teclado, VoiceOver y lectores de pantalla;
- compatibilidad con quizzes, notas al pie, temas y ambos modos de lectura;
- feature flag global y override por libro;
- despliegue gradual, métricas de error y rollback documentado;
- decisión de producto separada sobre app nativa + Readium LCP si se requiere
  DRM fuerte o distribución de EPUB/PDF descargable.

Criterio de salida: cero regresiones críticas, política reversible por feature
flag y límites de protección comunicados al equipo editorial.

Controles entregados:

- panel **Libros → Protección** con apagado global y rollout porcentual estable;
- override heredar/activar/desactivar en cada libro;
- constante `ALMADEN_BOOKSTER_CONTENT_PROTECTION_ENABLED` para rollback cuando
  wp-admin no esté disponible;
- evento agregado `chapter_load_error` para observar fallos del endpoint;
- fixture E2E accesible para scroll, flip, highlight, nota al pie, quiz y nota
  editable;
- runbook de rollout, métricas, privacidad y rollback en `ROLLOUT.md`;
- matriz manual versionada en `tests/e2e/README.md`.

El apagado global prevalece sobre cualquier override. Con la bandera activa,
el override por libro prevalece sobre el porcentaje. La cohorte se calcula de
forma determinística por libro y no usa identidad, IP ni fingerprint.

## Matriz mínima de aceptación

| Caso | Resultado esperado |
| --- | --- |
| Seleccionar y `Cmd/Ctrl+C` en capítulo | No sale texto; aparece aviso. |
| Copiar desde menú contextual/sistema | No sale texto; aparece aviso. |
| Copiar desde menú Edit del navegador | No sale texto. |
| Arrastrar selección o imagen del capítulo | Operación cancelada. |
| Seleccionar y crear highlight | Funciona y conserva offsets. |
| Copiar texto escrito en comentario | Permitido. |
| Activar VoiceOver/lector de pantalla | El contenido mantiene semántica y orden. |
| Imprimir Reader | No imprime el capítulo; muestra aviso informativo. |
| Ver HTML inicial | No contiene cuerpos completos de capítulos. |
| Solicitar capítulo ajeno/sin compra | `403` sin filtrar metadatos sensibles. |
| Desactivar feature flag | Reader vuelve al comportamiento anterior. |

## Fuentes técnicas

- W3C, [Clipboard API and events](https://www.w3.org/TR/clipboard-apis/):
  especifica que `copy` y `cut` pueden sobrescribirse cancelando el evento.
- MDN, [Element: copy event](https://developer.mozilla.org/en-US/docs/Web/API/Element/copy_event):
  compatibilidad y uso de `clipboardData` + `preventDefault()`.
- MDN, [CSS user-select](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/user-select):
  comportamiento y limitaciones de compatibilidad de la selección CSS.
- OWASP, [AJAX Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/AJAX_Security_Cheat_Sheet.html):
  recuerda que el usuario controla la lógica cliente y que la autorización debe
  resolverse en servidor.
- OWASP, [HTML5 Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTML5_Security_Cheat_Sheet.html):
  el almacenamiento local no ofrece confidencialidad frente al usuario/equipo.
- W3C, [Encrypted Media Extensions](https://www.w3.org/TR/encrypted-media-2/):
  EME extiende `HTMLMediaElement` y no es DRM para texto HTML.
- Readium, [LCP Specifications](https://readium.org/lcp-specs/): cifrado,
  formatos soportados y limitación expresa de integración en browser apps.
- EDRLab, [Readium LCP Principles](https://www.edrlab.org/readium-lcp/principles/):
  límites de copia/impresión y modelo de licencia para publicaciones protegidas.
- Android Developers, [Secure sensitive activities](https://developer.android.com/security/fraud-prevention/activities):
  `FLAG_SECURE`, capturas en blanco y limitaciones documentadas.
- Electron, [BrowserWindow](https://www.electronjs.org/docs/latest/api/browser-window):
  `setContentProtection()` en Windows/macOS y la limitación de ScreenCaptureKit.
- Apple Developer, [Protecting sensitive content when screen sharing](https://developer.apple.com/documentation/swiftui/protecting-sensitive-content-when-screen-sharing):
  detección nativa de grabación/mirroring y redacción de contenido sensible.
