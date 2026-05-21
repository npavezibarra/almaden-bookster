# Guía para Agentes AI: AlmadenBookster Plugin

Esta guía establece las reglas estrictas de desarrollo y arquitectura para cualquier agente o desarrollador que trabaje en el plugin `AlmadenBookster`. Estas directrices deben seguirse sin excepción.

## REGLA PRINCIPAL: Límite de 500 Líneas de Código

**Ningún archivo dentro de este plugin debe superar NUNCA las 500 líneas de código.**

La modularidad extrema es la prioridad de este proyecto. Si la implementación de una nueva característica o idea va a provocar que un archivo supere este límite, tu responsabilidad inmediata es **refactorizar y dividir** el archivo antes de continuar.

## Principios de Modularidad a Seguir:

1. **Separación de Responsabilidades:** Nunca mezcles diferentes contextos en un solo archivo masivo. Si el archivo principal (`almaden-bookster.php`) comienza a crecer, divídelo en carpetas lógicas (ej. `includes/`, `admin/`, `public/`, `cpt/`) y requiere los archivos correspondientes.
2. **Funciones Pequeñas y Específicas:** Unifica y simplifica las funciones. Cada función debe tener un único propósito bien definido. Evita los bloques condicionales gigantescos y el código procedimental extenso.
3. **Refactorización Continua:** Si ves un archivo con 400 líneas, considéralo en estado crítico. Comienza a planificar su división en componentes más pequeños.
4. **Componentización:** Para interfaces de usuario o plantillas, utiliza archivos separados. No incluyas HTML extenso o scripts inline masivos dentro de los archivos PHP de lógica; cárgalos desde archivos parciales o encola los assets adecuadamente.

*Cualquier código que incumpla estas reglas será considerado como un fallo en la implementación de la arquitectura.*

## Conexión a Base de Datos (Local by Flywheel)

Para operaciones directas en la base de datos MySQL, utilizar el siguiente socket:

```
/Users/nicolasibarra/Library/Application Support/Local/run/J__JXc6LL/mysql/mysqld.sock
```

Ejemplo de uso con `mysql` CLI:

```bash
mysql --socket="/Users/nicolasibarra/Library/Application Support/Local/run/J__JXc6LL/mysql/mysqld.sock" -u root -e "USE local; SHOW TABLES;"
```

## Credenciales WordPress (Local)

- **URL de Admin:** `http://ada.local/wp-admin/`
- **Usuario:** `chatgpt`
- **Contraseña:** `chatgpt123`
