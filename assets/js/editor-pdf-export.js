// ============================================================
// MÓDULO: editor-pdf-export.js
// Responsabilidad: Funciones de exportación e impresión del PDF.
// Depende de: editor-pdf-compiler.js (compilePDFPreview)
// ============================================================

// Resuelve el tamaño de página según la configuración actual del libro
function getPageDimensions() {
    const settings = bookState.settings || {};
    const unit = settings.unit || 'cm';
    let width  = parseFloat(settings.page_width)  || 21;
    let height = parseFloat(settings.page_height) || 29.7;

    if (settings.page_size === 'A4') {
        width  = (unit === 'cm') ? 21.0  : (21.0  / 2.54);
        height = (unit === 'cm') ? 29.7  : (29.7  / 2.54);
    } else if (settings.page_size === 'Letter') {
        width  = (unit === 'cm') ? (8.5  * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    return { width, height, unit };
}

// Abre una ventana de impresión limpia con solo las páginas del libro
function triggerPrint() {
    compilePDFPreview();

    const { width, height, unit } = getPageDimensions();

    const scroller = document.getElementById('pdf-scroller');
    const pagesHtml = scroller ? scroller.innerHTML : '<p>Sin contenido</p>';

    // Recoger todos los estilos activos (Google Fonts + estilos dinámicos)
    let stylesHtml = '';
    document.querySelectorAll('link[rel="stylesheet"], style').forEach(el => {
        stylesHtml += el.outerHTML;
    });

    const printContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>${bookState.title || 'Libro'}</title>
    ${stylesHtml}
    <style>
        @page {
            size: ${width}${unit} ${height}${unit};
            margin: 0mm;
        }
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }
        .pdf-page {
            margin: 0 !important;
            box-shadow: none !important;
            border: none !important;
            page-break-after: always;
            page-break-inside: avoid;
            break-after: page;
        }
        .pdf-page:last-child {
            page-break-after: auto;
        }
    </style>
</head>
<body>
    ${pagesHtml}
</body>
</html>`;

    const printWin = window.open('', '_blank', `width=${Math.round(width * 37.8)},height=${Math.round(height * 37.8)}`);
    if (!printWin) {
        showToast('Activa los popups para este sitio e intenta de nuevo.', 'fa-solid fa-triangle-exclamation');
        return;
    }
    printWin.document.open();
    printWin.document.write(printContent);
    printWin.document.close();

    // Esperar a que carguen las fuentes antes de lanzar el diálogo
    printWin.onload = function () {
        setTimeout(() => {
            printWin.focus();
            printWin.print();
        }, 1000);
    };
}
