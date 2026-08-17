# Validación E2E y matriz de release

Fixture local:

```text
/wp-content/plugins/almaden-bookster/modules/content-protection/tests/fixtures/phase-four.html
```

## Automatizable en navegador

- El árbol accesible conserva `main`, `article`, headings, formulario, radio y
  textarea.
- Copy/cut/drag sobre `#chapter-content` termina cancelado y presenta el estado
  `aria-live`.
- Copy/cut dentro de `#reader-note` permanece permitido.
- Los botones Scroll/Flip mantienen `aria-pressed` y no desmontan el watermark.
- La nota al pie protegida abre, conserva `aria-expanded` y no puede copiarse.
- El quiz sigue siendo interactivo.
- Crear highlight no elimina la selección ni el watermark.

## Matriz manual antes de ampliar el rollout

Ejecutar con una cuenta que tenga una licencia real. Registrar versión, sistema
operativo, resultado y evidencia en el ticket de release.

| Plataforma | Copy/menú | Drag | Highlight/nota | Print | Watermark |
| --- | --- | --- | --- | --- | --- |
| Chrome macOS/Windows | pendiente | pendiente | pendiente | pendiente | pendiente |
| Safari macOS + VoiceOver | pendiente | pendiente | pendiente | pendiente | pendiente |
| Firefox macOS/Windows | pendiente | pendiente | pendiente | pendiente | pendiente |
| Edge Windows + Narrator | pendiente | pendiente | pendiente | pendiente | pendiente |
| iOS Safari + VoiceOver | pendiente | n/a | pendiente | pendiente | pendiente |
| Android Chrome + TalkBack | pendiente | n/a | pendiente | pendiente | pendiente |

Una fila con regresión crítica impide aumentar el porcentaje de rollout. El
apagado global es el rollback inmediato; no requiere despliegue de código.
