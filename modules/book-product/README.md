# Modulo Book Product

Este modulo administra la relacion comercial entre un libro de Bookster y un
producto de WooCommerce. Es autonomo y no forma parte de las plantillas de
diseno del libro.

## Responsabilidades

- Buscar productos WooCommerce mediante autocomplete AJAX.
- Vincular un producto existente y convertirlo a variable con confirmacion.
- Crear productos variables con formato Fisico, Ebook o Ambos.
- Mantener slots independientes para las variaciones Fisico, Ebook y Ambos.
- Editar el producto vinculado desde el propio panel sin salir del modulo.
- Desvincular formatos o el producto completo sin borrar productos de WooCommerce.
- Sincronizar los metadatos legacy usados por compra, retorno y acceso al Reader.

## Estructura

```text
book-product/
|-- init.php
|-- includes/
|   |-- class-assets.php
|   |-- class-relation-repository.php
|   |-- class-product-catalog.php
|   |-- class-product-factory.php
|   |-- class-product-service.php
|   |-- class-access.php
|   |-- class-ajax-controller.php
|   `-- class-renderer.php
|-- templates/
|   `-- product-panel.php
|-- assets/
|   |-- css/book-product.css
|   `-- js/
|       |-- book-product-api.js
|       `-- book-product-app.js
`-- tests/
    `-- relation-state.test.php
```

## Persistencia

La fuente de verdad es `_almaden_book_product_relation`, version 3. Los campos
principales son `parent_product_id`, `physical_product_id`, `ebook_product_id`
y `both_product_id`. El repositorio mantiene sincronizados los metadatos
`_almaden_wc_*` para compatibilidad, y el acceso digital se concede por los
slots Ebook y Ambos.

## Seguridad

Todos los endpoints requieren sesion, nonce ligado al libro, permiso para
editar el libro y capacidad `edit_products`. Desvincular nunca elimina el
producto ni sus variaciones.
