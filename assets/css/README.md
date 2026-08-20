# Directorio css

Este directorio forma parte del plugin Almaden Bookster.
Archivos y subdirectorios contenidos aquí:

* admin-fonts-page.css
* editor-style.css
* authors
* quiz-builder
* reader-app.css

## Nota reciente

`editor-style.css` incluye ahora los estilos de la superficie editable del PDF en la vista `Dividido`. Esa capa ya no se comporta como un overlay separado; la seleccion y el caret se dibujan sobre el propio contenido renderizado por Paged.js para mantener alineacion visual y edicion confiable.

### Sistema visual del modal Ajustes del libro

`editor-style.css` también define el sistema visual compartido bajo `#settings-modal`:

- `.settings-dialog`: modal de hasta `960px`, altura limitada por `100dvh` y layout flex con header/footer fijos.
- `.settings-scroll-region`: única región desplazable para PDF, eBook y General.
- `.settings-primary-tabs`: navegación de segundo nivel.
- `.settings-inner-tabs`: navegación segmentada de tercer nivel usada por Plantillas, Tipografía, Cabecera/Pie y Capítulos.
- `.settings-section-card` y `.settings-inner-panel-card`: superficies de contenido con borde, radio y padding comunes.
- `.settings-type-level-card`: tarjetas H1/H2/H3 dentro de Tipografía > Títulos.

La escala mínima es `14px` para labels y ayudas, `16px` para controles y `17px/700` para encabezados de tarjeta. Los controles interactivos tienen un alto mínimo de `44px`. Bajo `768px`, el modal ocupa toda la pantalla y las rejillas se reducen a una columna.

* admin-filesize-page.css
* publishers
