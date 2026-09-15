# Book Templates

## Responsabilidad

Repositorio PHP para acceder a plantillas de libro administradas por Bookster.

## Archivos principales

- `repository.php`: lectura y persistencia de datos asociados a plantillas de
  libro.

## Flujo de entrada

Se carga desde el bootstrap del plugin o desde consumidores de plantillas que
necesitan consultar la configuracion disponible.

## Reglas locales

- Mantener esta carpeta enfocada en acceso a datos de plantillas.
- No mezclar renderizado de UI ni logica de templates visuales aqui.
- Si crece el area, dividir por responsabilidad antes de ampliar
  `repository.php`.

## Al modificar aqui

- Revisar consumidores en `includes/templates/` y flujos de editor.
- Ejecutar `php -l` en archivos PHP modificados.
- Verificar el tamano de archivos modificados con `wc -l`.

## Archivos relacionados

- [../templates/README.md](../templates/README.md)
- [../../templates/editor/README.md](../../templates/editor/README.md)
- [../../docs/book-settings-map.md](../../docs/book-settings-map.md)
