# Debugging y sincronizacion

## Contexto local

Las credenciales, sockets y datos de una instalacion local no son reglas de
arquitectura. Usarlos solo cuando la tarea requiera debugging del entorno Local,
WordPress, base de datos o flujos autenticados.

No mostrar secretos en respuestas, logs ni commits. Si se necesita consultar un
archivo local con credenciales, limitar la lectura al dato necesario y evitar
copiarlo a otros archivos.

## Metodo de sincronizacion diferencial asistida por LLM

Este metodo estandariza la correccion y sincronizacion de contenidos complejos
de WordPress cuando existen shortcodes, tablas HTML u otras etiquetas de
formato, usando una fuente de verdad externa como un `.docx`.

Flujo de trabajo:

1. Extraer el texto de la fuente de verdad, por ejemplo convirtiendo `.docx` a
   `.txt` plano o extrayendo capitulos especificos, y guardarlo en un entorno
   temporal.
2. Descargar el contenido actual en crudo con WP-CLI, por ejemplo:
   `wp post get <ID> --field=post_content > ch_actual.txt`.
3. Generar un diff de palabras con un script, ignorando tags HTML y shortcodes,
   para identificar palabras faltantes, sobrantes o discrepantes.
4. Aplicar correcciones quirurgicas sobre el archivo actual con reemplazos
   controlados, cuidando no alterar tablas HTML, atributos `style` ni
   shortcodes como `[box]` o `[html]`.
5. Subir el contenido actualizado con WP-CLI, por ejemplo:
   `wp post update <ID> /ruta/al/archivo/actualizado.txt`.
6. Validar visualmente o mediante logs que la actualizacion fue exitosa y que
   la maquetacion se conservo.
