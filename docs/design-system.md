# Sistema visual detectado

Este documento registra solo patrones respaldados por el codigo actual. No es
todavia un design system formal ni una especificacion cerrada.

## Tokens confirmados

### Tipografias

- `Urbanist`: fuente UI principal en shell, editor, cover, reader, autores y
  varias app pages.
- `Poppins`: usada en login/register y Learni.
- `Georgia`: usada para cuerpo de lectura en reader.
- `Newsreader`: usada en blog post.
- `Fraunces` e `Inter`: usadas en onboarding editorial.

### Colores frecuentes

- Texto principal: `#111827`, `#0f172a`, `#000000`.
- Texto secundario: `#64748b`, `#6b7280`, `#475569`, `#666666`.
- Bordes: `#e5e7eb`, `#e2e8f0`, `#dbe3ee`, `#d1d5db`.
- Fondos claros: `#ffffff`, `#f8fafc`, `#f5f5f5`, `#f6f6f6`, `#fafafa`.
- Fondo editorial/calido: `#f5f1ea`, `#f6f4ef`, `#fbfaf7`.
- Accento oscuro: `#111827`, `#000000`.
- Accento cobre/marron: `#a45f2a`, `#783F27`, `#B87333`, `#E5AA70`.
- Accento autor: `#ebff43`.
- Exito: `#16a34a`, `#166534`, `#dcfce7`, `#047857`.
- Error/peligro: `#dc2626`, `#be123c`, `#b91c1c`, `#991b1b`, `#fee2e2`.
- Informativo: `#1d4ed8`, `#2563eb`, `#eff6ff`.

### Variables CSS existentes

- `--almaden-ui-font`
- `--bg-app`, `--bg-sidebar`, `--text-main`, `--text-muted`,
  `--border-color`, `--bg-editor`, `--bg-pdf-container`
- `--qb-bg`, `--qb-panel`, `--qb-text`, `--qb-muted`, `--qb-line`,
  `--qb-soft`, `--qb-soft-2`, `--qb-dark`, `--qb-success`, `--qb-danger`
- `--abp-ink`, `--abp-muted`, `--abp-line`, `--abp-soft`
- `--almaden-learni-bg`, `--almaden-learni-panel`,
  `--almaden-learni-panel-border`, `--almaden-learni-text`,
  `--almaden-learni-muted`, `--almaden-learni-accent`,
  `--almaden-learni-accent-soft`
- `--almaden-blog-bg`, `--almaden-blog-surface`, `--almaden-blog-border`,
  `--almaden-blog-text`, `--almaden-blog-muted`, `--almaden-blog-accent`,
  `--almaden-blog-soft`

## Patrones frecuentes

### Tamaños tipograficos

- UI compacta: 10px, 11px, 12px, 13px, 14px.
- UI normal: 15px, 16px, 18px, 20px, 22px.
- Titulos fluidos: `clamp(...)` en reader, autores y blog.
- Letter spacing para etiquetas: `0.05em`, `0.08em`, `0.12em`,
  `0.15em`, `0.18em`, `0.22em`, `0.24em`.

### Radios

- Pequeños: 5px, 6px, 8px.
- Medios: 10px, 12px, 14px, 16px.
- Grandes: 1rem, 1.25rem, 1.5rem, 2rem, 24px.
- Pills/circulares: `999px`, `9999px`, `50%`.

### Espaciados

- Gaps pequeños: 4px, 6px, 8px, 10px, 12px.
- Espaciados medios: 14px, 16px, 18px, 20px, 24px.
- Espaciados grandes: 32px, 40px.
- Muchas app pages usan utilidades Tailwind para `px`, `py`, `gap`, `max-w` y
  layout flex/grid.

### Breakpoints

- 640px: auth y algunos layouts compactos.
- 720px, 760px, 767px, 768px: mobile/tablet.
- 860px: perfil de autor.
- 980px, 1024px: onboarding/editoriales/Learni/blog.
- 1120px: perfil de autor.
- 1200px: quiz builder workspace.

### Botones

- Primarios oscuros: fondo `#111827` o `#000000`, texto blanco, peso alto.
- Botones ghost/secundarios: fondo blanco o gris claro, borde slate/gris.
- Botones pill: usados en auth, book-product, reader y shell.
- Botones de icono: Font Awesome o Dashicons, especialmente cover/editor/admin.
- Estados hover frecuentes: levantar con `translateY(-1px/-3px)`, oscurecer,
  cambiar borde o sombra.

### Cards

- Card comun: fondo blanco, borde `#e5e7eb`/slate claro, radio 12-24px.
- Sombra suave: `0 8px 24px`, `0 16px 32px`, `0 20px 50px`.
- Algunas superficies modernas usan `backdrop-filter: blur(...)`.

### Formularios

- Inputs con borde gris claro, radio 8-12px, fuente heredada.
- Foco frecuente: borde oscuro o azul y `box-shadow` suave.
- Labels compactas: uppercase, peso 700/800, letter spacing.
- Admin WordPress conserva clases `button`, `button-primary`, `form-table`,
  `description`, `spinner`.

### Navegacion

- Shell: navbar blanca, borde inferior gris, enlaces con estados activos y
  profile menu.
- Cover/editor: barras superiores compactas con iconos y acciones.
- Reader: navbar sticky, modos moviles y controles de lectura.
- Learni: sidebar sticky con tabs y Dashicons.
- Quiz builder: header propio y sidebar de capitulos.

### Modales

- Auth modal: overlay oscuro con blur, card blanca, transiciones cortas.
- Reader/access/quiz overlays: fondo translúcido, cards blancas, radius grande.
- Autor: modales para foto y hero con panels amplios.
- Editor: modales controlados por template y JS, con estilos del editor.

### Estados

- Exito: verde suave y texto verde oscuro.
- Error/peligro: rojos suaves, textos `#be123c`, `#b91c1c`, `#991b1b`.
- Carga: spinners, overlays `is-loading`, opacidad reducida y `pointer-events:
  none`.
- Vacio: textos muted, cards/containers con borde gris y mensajes compactos.
- Activo/seleccionado: fondos soft, borde mas oscuro, chip o pill activo.

## Diferencias visuales por superficie

- Shell: utilitario, blanco, Tailwind-heavy, Urbanist, navbar compartida,
  `editor-style.css` como base.
- Editor: interfaz densa de herramienta, temas light/sepia/dark, preview PDF,
  muchos IDs y dependencias JS.
- Cover: herramienta full-screen, paneles laterales, Font Awesome, utilidades
  Tailwind y overrides especificos.
- Reader: experiencia aislada, `height: 100vh`, tipografia serif para contenido,
  controles moviles y CSS propio extenso.
- Admin WordPress: mezcla de estilos WP admin, Dashicons, CSS propio e inline.
- Modules: cada modulo define prefijos propios (`pl-auth`, `abp`,
  `almaden-learni`, `almaden-blog`, `almaden-content-protection`) y tokens
  locales.

## Decisiones todavia no formalizadas

- No existe una unica escala oficial de colores, radios, sombras o espaciados.
- No hay una capa compartida de tokens para todos los modulos.
- El orden de carga combina API WordPress, links manuales e inline CSS.
- Font Awesome no tiene una estrategia unica local/CDN.
- Los estilos de quiz builder se comparten con reader por necesidad practica,
  pero no existe una capa separada de "quiz player".
- Los archivos grandes de CSS aun no estan divididos por responsabilidad.
