# Operación, métricas y rollback

## Controles

En WordPress: **Libros → Protección**.

- **Bandera global**: apagado de emergencia. Al desactivarla, ningún override
  por libro puede reactivar el módulo.
- **Rollout**: porcentaje estable de libros entre 0 y 100.
- **Override por libro**: en el metabox “Protección del Reader”, permite forzar
  activación o desactivación mientras la bandera global esté encendida.

También puede forzarse el apagado desde `wp-config.php`:

```php
define( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_ENABLED', false );
```

## Secuencia recomendada

1. Activar 5% y validar la matriz con libros sin quiz y con quiz.
2. Subir a 25% y revisar durante 24 horas.
3. Subir a 50% y revisar durante 24 horas.
4. Completar 100% si no hay regresiones críticas.

Los libros permanecen en una cohorte determinística al cambiar de sesión.

## Métricas

Consultar los agregados de `wp_almaden_content_protection_events`:

- `chapter_load_error`: error real de entrega/render inicial;
- `chapter_rate_limited`: extracción secuencial bloqueada;
- `copy`, `cut`, `drag`, `print`: acciones convencionales impedidas.
- `capture_shortcut`: atajo de captura observado por el navegador; no confirma
  que el sistema haya creado una imagen.

Nunca usar estos contadores como prueba concluyente de abuso. Son señales
operativas pseudónimas. La retención automática es de 30 días.

## Rollback

1. Desactivar la bandera global en **Libros → Protección**.
2. Confirmar que el Reader vuelve a contenido inline y deja de cargar los assets
   del módulo.
3. Conservar la telemetría agregada para diagnóstico; no es necesario borrar la
   tabla para hacer rollback.
4. Corregir y reactivar primero al 5%.

Si wp-admin no está disponible, usar la constante de `wp-config.php`. La
constante prevalece sobre opciones y overrides.

## Decisión sobre DRM fuerte

La Fase 4 no adopta Readium LCP en el navegador. Si el producto necesita EPUB o
PDF descargable con DRM, debe abrirse una iniciativa separada para una app
nativa/certificada. La protección web actual reduce copia casual y scraping,
pero no crea una frontera criptográfica frente al propietario del dispositivo.

## Capturas y grabación de pantalla

El Reader web ya no activa una cortina negra al perder foco o visibilidad, ni
ante atajos de captura observables. Esa estrategia se descartó porque en un
navegador no garantiza una captura negra del sistema y sí degrada la lectura.

Para una protección real a nivel de ventana:

- Android: aplicar `WindowManager.LayoutParams.FLAG_SECURE` a la Activity del
  Reader.
- Windows/Electron: llamar `win.setContentProtection(true)` y verificar la
  versión mínima de Windows.
- iOS/iPadOS: observar `sceneCaptureState` para grabación, mirroring o control
  remoto y señalar la cortina. Una captura estática solo puede notificarse
  después de realizada; no existe una API pública general equivalente a
  `FLAG_SECURE` para vistas arbitrarias.
- macOS/Electron: `setContentProtection` no bloquea todos los capturadores
  modernos basados en ScreenCaptureKit; en web no hay una solución universal
  para forzar una pantalla negra.
