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

async function triggerPrint() {
    // Si estaba en modo rápido, forzamos compilación completa para poder imprimir el libro entero
    const previousMode = window.currentPreviewMode;
    if (window.currentPreviewMode !== 'full') {
        const scroller = document.getElementById('pdf-scroller');
        if (scroller) {
            scroller.innerHTML = '<div class="flex items-center justify-center h-full w-full text-indigo-500 gap-2"><i class="fa-solid fa-spinner fa-spin"></i> Preparando libro completo para imprimir...</div>';
        }
        window.currentPreviewMode = 'full';
        
        // Esperar a que el navegador dibuje el loading
        await new Promise(resolve => setTimeout(resolve, 50));
        await compilePDFPreview();
        
        // Update select UI
        const select = document.getElementById('preview-mode-select');
        if (select) select.value = 'full';
    } else {
        await compilePDFPreview();
    }

    const { width, height, unit } = getPageDimensions();

    let styleEl = document.getElementById('print-export-style');
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'print-export-style';
        document.head.appendChild(styleEl);
    }

    styleEl.innerHTML = `
        @media print {
            header, aside, #editor-pane, .pdf-toolbar, #split-resizer {
                display: none !important;
            }
            html, body {
                height: auto !important;
                overflow: visible !important;
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body > div.flex.flex-1, main, #preview-pane, #pdf-container, #pdf-scroller {
                display: block !important;
                height: auto !important;
                overflow: visible !important;
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                width: 100% !important;
                max-width: 100% !important;
                position: static !important;
                transform: none !important;
            }
            @page {
                size: ${width}${unit} ${height}${unit};
                margin: 0mm;
            }
            .pdf-page {
                margin: 0 !important; /* Ensure it sticks to top-left of the @page */
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                break-after: page !important;
                width: ${width}${unit} !important;
                height: ${height}${unit} !important;
                min-height: ${height}${unit} !important;
                max-height: ${height}${unit} !important;
                transform: none !important;
                box-sizing: border-box !important;
                padding: 0 !important;
            }
            .pdf-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .print\\:hidden {
                display: none !important;
            }
        }
    `;

    // Pequeño delay para permitir que el navegador aplique los estilos antes de abrir el diálogo
    setTimeout(() => {
        window.print();
    }, 300);
}
