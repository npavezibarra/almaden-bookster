# Plan de implementación: preview por capítulo con paginación global y assets duales

Fecha: 2026-08-07

## Objetivo

Reducir drásticamente el tiempo percibido de carga del preview PDF en el editor sin perder fidelidad editorial.

La dirección acordada es:

- El preview de edición debe poder trabajar por capítulo, con ese modo como comportamiento por defecto.
- Debe existir una opción explícita para volver a ver el PDF completo.
- La paginación del capítulo aislado no debe mentir respecto del libro real.
- Debe existir un contador universal liviano, derivado del libro completo, que guíe la paginación real aun cuando visualicemos un solo capítulo.
- Las imágenes usadas en la vista previa deben ser optimizadas para pantalla, mientras que la exportación final debe seguir usando la versión original o de alta resolución.

## Contexto y problema

Hoy el preview:

- serializa todo `bookState`
- recompila el libro completo con Typst
- recibe un PDF completo
- renderiza todas las páginas del PDF con PDF.js

Eso hace que el editor se sienta lento, especialmente cuando:

- el libro ya tiene muchas páginas
- hay imágenes pesadas
- se está ajustando solo un capítulo
- se recompila por cambios pequeños

Además, si se muestra solo un capítulo sin contexto global, el número de páginas puede diferir del libro final, lo que introduce un riesgo editorial real.

## Decisión de diseño

### 1. Modo de preview

Agregar dos modos de visualización:

- `chapter` = mostrar solo el capítulo activo
- `full` = mostrar el PDF completo

Recomendación:

- `chapter` debe ser el default del editor
- `full` debe ser una opción visible para verificar el libro entero

### 2. Contador universal

No basta con contar páginas del capítulo aislado.

Se necesita un “universal counter” o índice global de paginación derivado del libro completo para:

- mantener números de página correctos
- conservar paridad real
- respetar páginas en blanco
- reflejar TOC, créditos y secciones iniciales
- evitar que un capítulo aislado parezca tener una paginación distinta a la final

### 3. Imágenes duales

Separar claramente:

- imagen de preview, liviana y optimizada para pantalla
- imagen de exportación, original o alta resolución

Esto ya existe conceptualmente en portada y conviene llevarlo al editor PDF.

## Principio rector

El preview por capítulo debe ser una mejora de experiencia, no una versión “inventada” del libro.

La fuente de verdad editorial sigue siendo el libro completo.

El capítulo aislado debe apoyarse en:

- el estado global del libro
- el mapa de paginación completo
- los assets optimizados para pantalla

## Alcance funcional

### Preview

- Ver solo el capítulo activo por defecto.
- Alternar a “PDF completo” cuando se necesite revisar flujo global.
- Mantener el render por PDF.js.
- Mantener overlays de diagnóstico si ayudan a depurar.

### Paginación

- Calcular y conservar numeración global real.
- Permitir que el capítulo visible se pinte con sus páginas correctas en contexto.
- Evitar que el capítulo aislado genere una numeración local engañosa.

### Imágenes

- Crear una ruta de asset liviana para preview.
- Mantener la ruta de asset original para exportación final.
- No degradar la calidad del PDF exportado.

## Arquitectura propuesta

### A. Compilación global como base de verdad

No reemplazar el compilador completo.

La compilación global sigue siendo necesaria para:

- exportación final
- cálculo de paginación real
- mapeo de páginas por capítulo
- TOC y referencias

La diferencia es que el preview no necesariamente tiene que renderizar todo el PDF.

### B. Índice global de paginación

Agregar una capa liviana que se obtenga a partir de la compilación global y que contenga al menos:

- `chapter_id`
- `chapter_title`
- `start_page`
- `end_page`
- `page_count`
- `has_toc`
- `has_credits`
- `has_intentional_blanks`
- `page_parity`
- `opening_mode`

Ese índice debe permitir:

- mostrar solo el capítulo activo
- saber qué páginas reales le corresponden
- conservar la relación con el libro completo

### C. Asset dual

Cada imagen seleccionada para plantilla o capítulo debería poder resolver dos URLs o dos representaciones:

- `preview_url` o equivalente: optimizada para el viewer
- `original_url` o attachment original: para exportación

Si no existe una versión optimizada, se debe hacer fallback ordenado:

- preview optimizado
- preview generado por WordPress
- original solo si es seguro y no compromete la experiencia

## Cambios que probablemente habrá que hacer

### Frontend

- `assets/js/pdf/typst/editor-typst-pdf.js`
  - agregar selector de modo `chapter` / `full`
  - cambiar la estrategia de render
  - soportar un índice global de paginación
  - renderizar solo el capítulo activo cuando corresponda
  - mantener posibilidad de descargar/exportar PDF completo

- `assets/js/pdf/typst/page-templates/*`
  - verificar que las plantillas sigan funcionando con el nuevo modo de preview
  - asegurar que los slots de imagen puedan consumir preview liviano

### Backend

- `includes/ajax/ajax-typst-pdf.php`
  - aceptar un parámetro o flag de preview
  - devolver metadata adicional útil para el índice global
  - diferenciar claramente preview de exportación

- `includes/pdf-typst/typst-document.php`
  - permitir construir un documento orientado a preview capítulo vs libro completo
  - mantener la lógica de exportación intacta
  - exponer el contexto necesario para el índice global

- `includes/pdf-typst/typst-document-context.php`
  - incorporar datos derivados para paginación global
  - preparar metadatos por capítulo

- `includes/pdf-typst/typst-document-render-helpers.php`
  - agregar helpers si hace falta para el mapa de páginas
  - mantener la generación de TOC y créditos sin degradar fidelidad

- `includes/helpers/cover-upload.php`
  - usar como referencia el patrón de preview liviano vs asset original
  - si conviene, abstraer una utilidad similar para imágenes de capítulos o plantillas

## Estrategia de implementación

### Fase 1: diseño de datos

- Definir el formato del índice global.
- Definir cómo se guarda en memoria y cómo se entrega al frontend.
- Definir qué parte del índice debe persistir y cuál puede recalcularse.

### Fase 2: preview por capítulo

- Agregar el modo `chapter`.
- Hacer que el viewer solo pinte el capítulo activo.
- Conservar el contexto global de numeración.
- Asegurar un fallback visible a `full`.

### Fase 3: universal counter

- Calcular el mapa global de páginas a partir de la compilación completa.
- Asociar cada capítulo a su rango real de páginas.
- Exponer esos datos al frontend.
- Usarlos para que el preview por capítulo no “mienta”.

### Fase 4: imágenes optimizadas

- Introducir resolución optimizada para preview.
- Mantener alta resolución para exportación.
- Verificar que el PDF exportado no use los assets livianos.

### Fase 5: UX y controles

- Mostrar un toggle claro:
  - `Capítulo actual`
  - `PDF completo`
- Mantener visible qué modo está activo.
- Indicar cuándo el preview es una vista rápida y cuándo es la referencia completa.

## Reglas importantes

- El export final siempre debe ser completo y fiel.
- El preview por capítulo no debe convertirse en una versión separada con numeración local inventada.
- El contador universal debe salir del libro completo, no de una aproximación local.
- Los assets livianos son solo para pantalla.
- No romper el comportamiento existente de TOC, créditos, apertura separada ni plantillas físicas.

## Riesgos

- Si se intenta simular la paginación sin compilar el libro completo, la numeración puede divergir.
- Si el índice global no se recalcula cuando cambian capítulos anteriores, el capítulo actual puede quedar desfasado.
- Si los assets optimizados se usan por error en exportación, la calidad final del PDF se verá afectada.
- Si el cambio de modo no está bien señalado en UI, el usuario puede asumir que está viendo el libro completo cuando no lo está.

## Criterios de aceptación

- El editor abre más rápido en modo capítulo.
- Al cambiar contenido dentro de un capítulo, el preview responde con menos costo que hoy.
- El número de páginas mostrado en capítulo aislado coincide con el contexto global.
- Existe un modo visible para ver el PDF completo.
- La exportación final sigue usando el libro completo y los assets de máxima calidad.
- Las imágenes de preview se ven más livianas sin perder legibilidad.
- No se rompen TOC, créditos, plantillas físicas ni apertura separada.

## Checklist para próxima sesión

- [ ] Definir el formato exacto del `universal counter`.
- [ ] Decidir dónde vive el flag de modo `chapter` / `full`.
- [ ] Diseñar el flujo de datos entre backend y frontend.
- [ ] Identificar cómo derivar el rango de páginas por capítulo desde la compilación global.
- [ ] Definir la estrategia de assets preview vs export.
- [ ] Revisar si el patrón de portada puede reutilizarse para capítulos/plantillas.
- [ ] Implementar el toggle de modo en la UI.
- [ ] Implementar el render parcial en el viewer.
- [ ] Validar que exportación completa siga intacta.
- [ ] Probar libros con TOC, créditos, páginas en blanco e imágenes pesadas.

## Nota final

La prioridad no es “quitar páginas” al preview, sino quitar trabajo innecesario sin perder verdad editorial.

La mejor combinación parece ser:

- preview por capítulo
- contador global liviano
- imágenes optimizadas para pantalla
- exportación final completa y fiel

