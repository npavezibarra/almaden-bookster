# Modules (`modules/`)

## Responsabilidad

Esta carpeta contiene dominios encapsulados que extienden Almaden Bookster sin convertir el core del plugin en un archivo o namespace unico. Cada modulo debe tener bootstrap, assets, templates y logica propios cuando corresponda.

## Modulos principales

- `learni/`: quizzes nativos para ebooks y capitulos.
- `book-product/`: vinculacion libro-producto, compra y panel de producto.
- `login-register/`: experiencia de login y registro integrada al shell.
- `content-protection/`: proteccion convencional contra copia en reader y pantallas relacionadas.
- `blog-post/`: publicaciones tipo blog con editor, templates y assets propios.

## Flujo de entrada

El plugin principal carga cada modulo desde su `init.php` o bootstrap equivalente. Desde ahi se registran hooks, assets, templates, AJAX, rutas y servicios propios.

## Reglas locales

- Cada modulo debe tener `README.md` en su raiz.
- El modulo debe mantenerse encapsulado salvo helpers verdaderamente compartidos.
- No duplicar servicios del core si existe un contrato estable en `includes/`.
- No cruzar contratos entre modulos sin documentar el consumidor y el motivo.
- Respetar la regla global de 500 lineas por archivo.

## Al modificar aqui

Leer el README del modulo especifico y los README de sus subcarpetas cuando existan. Validar el flujo completo del modulo tocado, no solo el archivo puntual.

## Archivos relacionados

- `includes/`
- `templates/`
- `assets/js/`
- `assets/css/`
