# Directorio Admin Templates (`templates/admin/`)

Este directorio contiene las plantillas PHP del panel de administración del lado del taller.

## Archivos y Funcionalidades

*   **[pages-app.php](pages-app.php)**: Pantalla de configuracion de rutas internas, incluyendo el slug del creador de libros y la URL resultante.
*   **[distribution-access-app.php](distribution-access-app.php)**: Pantalla técnica de contrato global de distribución y acceso, con el modo base, el proveedor comercial, la política de retorno y los flags de automatización.
*   **[booklist-app.php](booklist-app.php)**: Renderiza el panel principal o "Taller" donde se listan todos los proyectos de libros creados, permitiendo duplicarlos, exportar ePubs, subirlos a Google Drive o eliminarlos.
*   **[booklist-onboarding.php](booklist-onboarding.php)**: Panel de activación inicial con tutorial, checklist y acceso directo al primer libro.
*   **[booklist-create-modal.php](booklist-create-modal.php)**: El modal de formulario flotante para la creación de nuevos libros. La miniatura de plantilla se mantiene oculta temporalmente; en el futuro se podrá rehacer con la familia tipográfica exacta y una proyección real de la plantilla guardada.
*   **[booklist-share-modal.php](booklist-share-modal.php)**: Modal flotante para compartir libros con otros usuarios de WordPress mediante autocompletado en tiempo real y gestión de accesos.

* filesize-app.php
