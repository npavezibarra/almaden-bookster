# Directorio publishers

Este directorio concentra la base de datos y utilidades iniciales para el modelo de editoriales del plugin.

Archivos y subdirectorios contenidos aquí:

* `publishers.php`
* `settings.php`

En esta primera fase se crean las tablas base para:

* editoriales como entidades independientes;
* membresías entre usuarios y editoriales.

En la segunda fase también se agrega la persistencia base para libros:

* cada `almaden-books` puede guardar `_almaden_publisher_id` como meta;
* la relación es liviana al inicio y puede evolucionar luego a una relación más formal;
* se incluyen helpers para crear, actualizar y consultar editoriales desde PHP.

En la fase de permisos también se agrega:

* helpers para consultar membresías de un usuario;
* helpers para validar si un usuario puede administrar una editorial o un libro;
* helpers para crear, actualizar y borrar membresías entre usuario y editorial.

En la fase de onboarding también se agrega:

* sincronización de la página pública `/crear-editorial`;
* wizard de alta con creación de usuario, editorial y membresía de propietario;
* redirección inmediata al taller tras completar el registro.

En la fase de taller editorial también se agrega:

* estado persistente del onboarding para mostrar la guía inicial solo una vez;
* checklist de configuración y CTA directo al primer libro;
* cierre automático del onboarding cuando el usuario crea o importa su primer libro.

En la fase de ajustes de editorial también se agrega:

* ruta pública `/editorial/{slug}/ajustes`;
* persistencia de configuración avanzada en `settings_json`;
* panel separado para datos legales, financieros, contacto, branding y preferencias.

* onboarding.php
* tour.php
* permissions.php
