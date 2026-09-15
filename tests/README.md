# Tests

## Responsabilidad

Pruebas de regresion y scripts de verificacion del plugin, especialmente para
importacion, plantillas de pagina, reader, shell y exportacion Typst/PDF.

## Archivos principales

- `*.php`: pruebas ejecutables con PHP para regresiones de importacion,
  renderizado, shell y pipeline Typst.
- `*.test.js`: pruebas JavaScript para coordinadores, estado de pagina y
  experiencia de preview.

## Flujo de entrada

Las pruebas se ejecutan manualmente segun el area modificada. No asumir que
forman parte de un runner unico sin revisar el archivo concreto.

## Reglas locales

- Mantener cada prueba enfocada en una regresion o contrato observable.
- No depender de credenciales locales salvo que la prueba lo documente
  explicitamente.
- Evitar fixtures generados o pesados dentro del repo; usar `tmp/` cuando sea
  necesario y no versionar salidas temporales.

## Al modificar aqui

- Leer el test vecino mas parecido antes de agregar uno nuevo.
- Ejecutar la prueba afectada cuando el entorno lo permita.
- Documentar cualquier dependencia externa necesaria para correrla.

## Archivos relacionados

- [../AGENTS.md](../AGENTS.md)
- [../docs/debugging.md](../docs/debugging.md)
