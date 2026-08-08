# Directorio payments

Este directorio contiene los proveedores de comercio y la integración modular con WooCommerce.

## Archivos y funcionalidades

* `commerce-providers.php`: Registro y despacho de proveedores de comercio.
* `commerce-hardening.php`: Reglas de seguridad y consistencia compartidas por los flujos comerciales.
* `woocommerce-integration.php`: Bootstrap compatible que carga los módulos WooCommerce.
* `woocommerce-relation.php`: Persistencia y normalización de la relación libro-producto.
* `woocommerce-products.php`: Creación de productos, variaciones y enlaces de compra.
* `woocommerce-access.php`: URLs del lector, retornos seguros y comprobación de acceso.
* `woocommerce-hooks.php`: Hooks del producto, carrito, checkout y confirmación de compra.
* `woocommerce-provider.php`: Adaptador que registra WooCommerce como proveedor de comercio.

La presentación de los hooks de tienda está en `templates/payments/` para mantener separadas la lógica y la salida HTML.
