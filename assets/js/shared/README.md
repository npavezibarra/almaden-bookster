# Shared JavaScript (`assets/js/shared/`)

## Responsabilidad

Contiene utilidades JavaScript reutilizables por mas de una pantalla o modulo frontend.

## Archivos principales

- `book-media-picker.js`: selector compartido de medios/libros para flujos que necesitan elegir o adjuntar assets sin duplicar UI.

## Flujo de entrada

Los scripts compartidos deben encolarse explicitamente desde PHP solo en las pantallas que los requieren.

## Reglas locales

- No depender de una pantalla concreta salvo que el nombre y README lo indiquen.
- Exponer APIs globales solo cuando sea necesario para integracion con scripts legacy.
- Mantener inicializadores idempotentes para evitar doble binding.

## Al modificar aqui

Buscar todos los consumidores antes de cambiar nombres, eventos, selectores o shape de datos. Validar cada pantalla que use el helper compartido.

## Archivos relacionados

- `assets/js/editor/`
- `assets/js/cover/`
- `includes/frontend/`
