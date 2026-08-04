# Almaden Bookster: arquitectura de distribución comercial y acceso al ebook

## Objetivo

Definir la capa que conecta `almaden-bookster` con el sitio madre WordPress sin romper la estructura nativa del sitio, manteniendo estas reglas:

- Bookster sigue siendo el shell editorial interno.
- El sitio madre conserva sus menús, posts, páginas, productos y reglas de navegación.
- El acceso al ebook siempre depende de la compra de un producto comercial asociado.
- El sistema debe poder cambiar entre dos estrategias de distribución sin rehacer el contenido del libro.

## Problema que resuelve

Hoy existen dos necesidades que se cruzan:

1. Crear o enlazar un producto comercial para un ebook generado en Bookster.
2. Decidir cómo se expone ese ebook en el sitio:
   - como parte de una tienda existente, o
   - como una experiencia propia de Bookshelf dentro de WordPress.

La arquitectura debe permitir que un mismo libro pueda:

- vivir como contenido editorial en `almaden-books`,
- estar ligado a un producto WooCommerce simple o variable,
- mostrarse en una página de producto de WooCommerce con un CTA `LEER EBOOK`,
- o publicarse dentro de una página Bookshelf administrada por Bookster.

## Principios de diseño

- Separar publicación editorial de distribución comercial.
- Separar acceso autorizado de presentación pública.
- No asumir WooCommerce como única tienda, aunque sí como primer adapter.
- No eliminar estructuras creadas por el usuario; solo administrar las que Bookster conoce.
- Permitir cambio de estrategia sin perder vínculos, URLs o permisos.
- Tratar el `product_id` o `variation_id` como el ancla real de acceso, no la página de lectura.

## Estado actual relevante

El plugin ya tiene piezas que sirven como base:

- CPT `almaden-books` para el libro editorial.
- `includes/payments/woocommerce-integration.php` para link libro-producto.
- `includes/frontend/access-control.php` para validación de acceso.
- `includes/frontend/pages-settings.php`, `pages-sync.php` y `pages-menus.php` para páginas y menús.
- `templates/ebook/ebook-single-app.php` para la página del ebook individual.
- `templates/bookshelf/bookshelf-app.php` para el catálogo Bookshelf.
- `templates/admin/booklist-app.php` como superficie administrativa principal del taller.

La implementación futura debe extender esas piezas, no reemplazarlas.

## Propuesta de arquitectura

### 1. Capa de dominio

Crear un modelo explícito para la distribución del libro.

Conceptos mínimos:

- `distribution_mode`
  - `store_integrated`
  - `bookshelf_managed`
- `commerce_provider`
  - `woocommerce`
  - futuros proveedores
- `access_product_type`
  - `simple`
  - `variable_parent`
  - `variation`
- `reader_entry_mode`
  - `product_cta`
  - `bookshelf_page`

La decisión principal no debe vivir dispersa en varios metaboxes sueltos.

### 2. Capa de configuración global

Agregar una pantalla administrativa de configuración global del plugin para decidir:

- modo de distribución por defecto,
- proveedor comercial activo,
- política de retorno desde el lector,
- comportamiento del Bookshelf automático,
- reglas de creación de producto por defecto.

Esta configuración debe guardarse en una opción única, por ejemplo:

- `almaden_bookster_distribution_settings`

### 3. Capa de configuración por libro

Cada libro debe tener su propia configuración de distribución para sobreescribir el valor global.

Debe incluir:

- estrategia de publicación del libro,
- producto o variación comercial ligada,
- URL de retorno al volver desde el lector,
- estado del vínculo con la tienda madre,
- opción de crear producto nuevo,
- opción de enlazar producto existente,
- opción de convertir a variación de un producto padre.

### 4. Capa de integración comercial

WooCommerce se trata como primer proveedor, no como dependencia conceptual del dominio.

El adapter Woo debe resolver:

- crear producto simple de ebook,
- enlazar libro a producto existente,
- enlazar libro a variación existente,
- crear producto variable padre si el flujo lo pide,
- crear atributo de formato si no existe,
- mantener metadatos espejo entre libro y producto.

### 5. Capa de presentación

Existen dos entradas públicas válidas:

- página de producto madre con botón `LEER EBOOK`,
- página Bookshelf creada por Bookster.

La página de libro individual debe ser el mismo objeto funcional en ambos casos:

- portada,
- metadatos,
- lista de capítulos,
- validación de acceso,
- acceso al reader real.

### 6. Capa de autorización

La regla central es:

> un usuario solo accede al ebook si compró el producto asociado al libro o a su variación autorizada.

La autorización debe considerar:

- compra del producto ligado,
- compra de la variación ligada,
- estados válidos de pedido,
- usuarios con permisos administrativos,
- casos legacy donde el libro no tiene producto asociado aún.

## Modelo de datos propuesto

### Opción global

Usar una opción única de WordPress para la configuración de distribución:

```php
almaden_bookster_distribution_settings
```

Campos sugeridos:

- `default_distribution_mode`
- `default_commerce_provider`
- `default_reader_entry_mode`
- `bookshelf_page_policy`
- `auto_create_store_product`
- `auto_create_bookshelf_page`
- `menu_injection_enabled`
- `menu_location`
- `return_url_policy`

### Opción por libro

Guardar metadatos en `post_meta` del `almaden-books`.

Llaves sugeridas:

- `_almaden_distribution_mode`
- `_almaden_commerce_provider`
- `_almaden_reader_entry_mode`
- `_almaden_commerce_object_type`
- `_almaden_wc_product_id`
- `_almaden_wc_variation_id`
- `_almaden_wc_parent_product_id`
- `_almaden_reader_return_target`
- `_almaden_bookshelf_page_id`
- `_almaden_bookshelf_menu_item_id`
- `_almaden_distribution_locked`
- `_almaden_distribution_source`

Observación:

- `_almaden_wc_product_id` puede seguir siendo el ancla principal para compatibilidad.
- `_almaden_wc_variation_id` debe agregarse para el caso de variación.
- Si el vínculo apunta a una variación, el parent product sigue siendo útil para navegación y para comprar el formato físico o ambos formatos.

## Pantalla administrativa propuesta

### Nombre sugerido

`Distribución y acceso`

Alternativas válidas si se quiere algo más comercial:

- `Publicación comercial`
- `Entrega y acceso`
- `Canales de distribución`

### Ubicación

La propuesta más consistente es integrarla dentro de `Taller`, porque ahí vive el flujo editorial principal del libro.

### Secciones

#### 1. Resumen de distribución

Muestra estado actual del libro:

- modo activo,
- proveedor activo,
- producto ligado,
- tipo de vínculo,
- página de lectura activa,
- página de Bookshelf activa,
- estado de acceso.

#### 2. Canal comercial

Permite elegir:

- crear producto nuevo,
- enlazar producto existente,
- enlazar variación existente,
- convertir un producto simple en variable,
- mantener el vínculo actual.

#### 3. Entrada pública

Permite elegir:

- usar tienda madre con botón `LEER EBOOK`,
- usar Bookshelf administrado por Bookster.

#### 4. Navegación y retorno

Permite definir:

- URL de retorno al salir del ebook,
- CTA de regreso a tienda,
- comportamiento si el producto enlazado está ausente,
- fallback si el Bookshelf no fue creado.

#### 5. Sincronización

Acciones manuales:

- crear o reintentar producto,
- crear o reintentar Bookshelf,
- regenerar enlace producto-libro,
- regenerar menú,
- validar permisos del libro.

## Flujo funcional propuesto

### Caso A: tienda madre existente

1. El libro se crea en Bookster.
2. El usuario selecciona `store_integrated`.
3. Bookster crea o enlaza un producto Woo.
4. En la página del producto aparece el CTA `LEER EBOOK`.
5. El CTA abre la página individual del ebook.
6. El lector guarda un retorno seguro hacia el producto Woo o su fallback.
7. Al salir del ebook, el usuario vuelve al producto original o a la tienda madre.

### Caso B: Bookshelf administrado por Bookster

1. El libro se crea en Bookster.
2. El usuario selecciona `bookshelf_managed`.
3. Bookster crea una página WP para Bookshelf si no existe.
4. Bookster registra esa página en el menú configurado.
5. El libro aparece en el catálogo Bookshelf.
6. El acceso al ebook sigue exigiendo la compra del producto vinculado.
7. La navegación interna vuelve al Bookshelf o al destino configurado.

## Reglas para crear productos

### Producto simple

Crear un producto simple cuando:

- el libro solo requiere una versión ebook,
- no existe una estructura comercial previa,
- se quiere un flujo rápido y seguro.

Sugerencias:

- tipo `product`,
- `virtual = yes`,
- `downloadable = no` salvo que el negocio lo requiera,
- `catalog visibility` según la estrategia,
- estado `draft` o `publish` según el nivel de automatización.

### Producto variable

Usar variaciones cuando el sitio madre ya vende el mismo contenido en varios formatos.

Ejemplo:

- producto padre: `Red Book`
- variación 1: `Físico`
- variación 2: `Ebook`

Reglas:

- si el producto padre ya es variable, añadir o reutilizar la variación `Ebook`,
- si el producto padre es simple, no convertir automáticamente sin confirmación explícita,
- si se crea la variación, el acceso del ebook debe quedar anclado a esa variación.

### Reglas de seguridad operativa

- No sobrescribir descripciones, precios o imágenes del producto madre sin confirmación.
- No borrar productos manuales creados por el usuario.
- Guardar metadatos espejo para diagnóstico.
- Mostrar advertencia si el libro está ligado a un producto publicado pero no vendible.

## Reglas para Bookshelf

Si se usa Bookshelf administrado:

- crear una página WordPress propia o reutilizar una existente si está marcada como administrada por Bookster,
- asignar la plantilla de Bookshelf,
- registrar su `page_id` y su `menu_item_id`,
- insertar un ítem de menú en el menú nativo configurado,
- poder desactivar y reactivar el ítem sin romper el menú del usuario,
- no eliminar menús creados por el usuario fuera del alcance de Bookster.

La experiencia Bookshelf debe ser reversible.

## Reglas de acceso

La autorización debe resolver el siguiente orden:

1. Si el usuario es administrador, editor autorizado o rol equivalente de gestión, permitir acceso.
2. Si el libro está ligado a una variación, verificar compra de la variación.
3. Si el libro está ligado a un producto simple, verificar compra del producto.
4. Si el pedido no cumple estados permitidos, denegar acceso.
5. Si el libro no tiene vínculo comercial, mantenerlo en modo no público o legacy según configuración.

Estados Woo recomendados para compra válida:

- `processing`
- `completed`

Si el proyecto necesita otra política, debe quedar como setting, no como hardcode.

## Navegación de retorno

El lector no debe confiar en cualquier URL enviada desde el navegador.

Debe existir una política de retorno segura:

- retornar al producto ligado si existe,
- retornar al parent product si la compra fue por variación,
- retornar al Bookshelf si ese fue el punto de entrada,
- retornar a la tienda madre o home como último fallback.

La URL de retorno debe validarse contra una allowlist interna de destinos generados por Bookster.

## Compatibilidad y migración

La migración debe ser no destructiva.

Cuando un libro ya existe:

- conservar `_almaden_wc_product_id`,
- agregar soporte para `_almaden_wc_variation_id`,
- no invalidar el enlace actual si el modo nuevo aún no está completo,
- permitir activar Bookshelf sin romper el acceso desde producto,
- permitir desactivar Bookshelf sin perder el producto ya asociado.

La regla de oro es que el contenido no debe “quedar huérfano” por cambiar de estrategia.

## Riesgos técnicos que hay que controlar

- Open redirect al volver desde el reader.
- Doble fuente de verdad entre producto y libro.
- Menús huérfanos si se borra una página Bookster sin limpiar el ítem asociado.
- Productos duplicados si se reintenta la creación sin idempotencia.
- Conflictos entre producto simple y variable si se intenta convertir automáticamente sin revisión.
- Acceso libre accidental si el libro queda sin vínculo comercial.

## Fases de implementación

### Fase 0: naming, mapa de dominio y contrato de datos

Objetivo:

- cerrar el nombre funcional del módulo,
- fijar los conceptos oficiales,
- definir llaves de `post_meta` y de opciones,
- dejar el contrato del sistema antes de tocar UI o lógica.

Entregables:

- nombre del proyecto,
- lista final de modos,
- lista final de metas y opciones,
- diagrama de flujo de acceso,
- criterios de migración.

Si se me pide “dale un nombre a este proyecto”, esta es la fase desde la que conviene partir.

### Fase 1: infraestructura de configuración

Objetivo:

- crear la opción global,
- crear el metabox o panel por libro,
- mostrar el estado actual de la distribución,
- persistir el modo elegido sin ejecutar todavía automatizaciones complejas.

Entregables:

- pantalla `Distribución y acceso`,
- lectura/escritura del modo por libro,
- lectura/escritura de settings globales,
- validación básica de estado.

### Fase 2: integración comercial WooCommerce

Objetivo:

- crear o enlazar productos Woo,
- soportar producto simple,
- soportar product parent + variation ebook,
- sincronizar metadatos entre libro y tienda.

Entregables:

- enlace libro-producto robusto,
- enlace libro-variación robusto,
- acción de crear producto nuevo,
- acción de enlazar producto existente,
- acción de enlazar variación existente.

### Fase 3: acceso y CTA del ebook

Objetivo:

- validar acceso de manera consistente,
- exponer `LEER EBOOK` en el producto madre,
- regresar al destino seguro correcto al salir del reader.

Entregables:

- botón en producto Woo,
- lógica de retorno seguro,
- validación de compra para producto simple y variación,
- compatibilidad con usuarios administrativos.

### Fase 4: Bookshelf administrado por Bookster

Objetivo:

- crear página Bookshelf si no existe,
- asignar template,
- registrar y mantener el ítem de menú,
- permitir cambiar entre modo tienda y modo Bookshelf sin perder datos.

Entregables:

- page registry de Bookshelf,
- menú administrado,
- fallback reversible,
- sincronización de estado.

### Fase 5: provider abstraction

Objetivo:

- desacoplar la lógica de negocio de WooCommerce,
- introducir una interfaz de proveedor comercial,
- permitir que otros proveedores se conecten después sin reescribir la capa de dominio.

Entregables:

- contrato de provider,
- adapter WooCommerce,
- puntos de extensión para futuros providers.

### Fase 6: hardening, migración y QA

Objetivo:

- cerrar casos borde,
- limpiar compatibilidad legacy,
- evitar duplicados,
- validar permisos y retorno,
- documentar comportamiento final.

Entregables:

- migrador de metadatos,
- pruebas de acceso,
- pruebas de variación,
- pruebas de navegación,
- pruebas de reversibilidad de Bookshelf.

## Orden recomendado de ejecución

Si vamos a implementar esto en serio, el orden más seguro es:

1. Fase 0.
2. Fase 1.
3. Fase 2.
4. Fase 3.
5. Fase 4.
6. Fase 5.
7. Fase 6.

## Decisión práctica recomendada

La estrategia más estable es esta:

- `WooCommerce` sigue siendo el ancla de acceso y venta.
- `Bookshelf` es solo la capa de descubrimiento o catálogo.
- La página del libro individual es la experiencia central del ebook.
- El modo de distribución cambia la entrada pública, no la identidad del libro.

Con eso, el sistema puede evolucionar sin romper los libros ya publicados.
