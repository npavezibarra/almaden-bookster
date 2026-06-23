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
    // Bloquear botón
    const btnPrint = document.getElementById('btn-export-pdf');
    if (btnPrint) {
        btnPrint.disabled = true;
        btnPrint.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparando...';
    }

    // Asegurar que el contenedor del PDF sea visible durante la preparación/compilación
    const previewPane = document.getElementById('pdf-preview-pane');
    if (previewPane && previewPane.classList.contains('hidden')) {
        previewPane.classList.remove('hidden');
    }

    // 1. Desactivar virtualización para asegurar que todas las páginas existan en el DOM real
    window.isPrintingPDF = true;
    if (window.pdfVirtualObserver) {
        window.pdfVirtualObserver.disconnect();
    }

    // Forzamos compilación completa para poder imprimir el libro entero
    const scroller = document.getElementById('pdf-scroller');
    if (scroller) {
        scroller.innerHTML = '<div class="flex flex-col items-center justify-center h-full w-full text-black dark:text-white gap-4"><i class="fa-solid fa-spinner fa-spin text-4xl"></i><span class="text-lg">Compilando libro completo para impresión...</span></div>';
    }
    
    // Esperar a que el navegador dibuje el loading
    await new Promise(resolve => setTimeout(resolve, 50));
    
    // Llamar al compilador forzando el modo full
    await compilePDFPreview(false, 'pdf-scroller', true);

    const { width, height, unit } = getPageDimensions();

    const conversionFactor = (unit === 'cm') ? 37.7952755906 : (37.7952755906 * 2.54);
    const toPx = (val) => val * conversionFactor;
    const widthPx = toPx(width);
    const heightPx = toPx(height);
    const globalBleedPx = toPx(unit === 'cm' ? 0.5 : (0.5 / 2.54));

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
            body > div.flex.flex-1, main, #pdf-preview-pane, #pdf-container, #pdf-scroller {
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
            .pagedjs_page {
                margin: 0 !important; /* Ensure it sticks to top-left of the @page */
                box-sizing: border-box !important;
                overflow: hidden !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: always !important;
                page-break-inside: avoid !important;
                break-after: page !important;
                break-inside: avoid !important;
                transform: none !important;
            }
            .pagedjs_page:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .print\\:hidden {
                display: none !important;
            }
        }
    `;

    // Forzar actualización del Índice en el DOM físico justo antes de imprimir
    const scrollerForPrint = document.getElementById('pdf-scroller');
    if (scrollerForPrint && window.bookChapterPages) {
        const tocItems = scrollerForPrint.querySelectorAll('.toc-item');
        tocItems.forEach(item => {
            const targetId = item.getAttribute('data-target-id');
            const pageSpan = item.querySelector('.toc-page');
            if (targetId && pageSpan && window.bookChapterPages[targetId]) {
                pageSpan.textContent = window.bookChapterPages[targetId];
            }
        });
    }

    // Pequeño delay para permitir que el navegador aplique los estilos antes de abrir el diálogo
    setTimeout(() => {
        const onAfterPrint = () => {
            window.removeEventListener('afterprint', onAfterPrint);
            
            // Terminado el print, volver al modo 'active' para no saturar memoria
            window.isPrintingPDF = false;
            
            // Restaurar el modo de vista original (ocultar panel si estaba en modo 'edit')
            if (typeof setViewMode === 'function' && bookState.viewMode) {
                setViewMode(bookState.viewMode);
            }
            
            // Renderizar solo el capítulo actual de nuevo
            if (scroller) {
                scroller.innerHTML = '<div class="flex flex-col items-center justify-center h-full w-full text-black dark:text-white gap-4"><i class="fa-solid fa-spinner fa-spin text-4xl"></i><span class="text-lg">Restaurando vista...</span></div>';
            }
            setTimeout(async () => {
                await compilePDFPreview(); // Automáticamente usará 'active'
                
                if (btnPrint) {
                    btnPrint.disabled = false;
                    btnPrint.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Imprimir PDF';
                }
            }, 50);
        };

        window.addEventListener('afterprint', onAfterPrint);
        window.print();
    }, 300);
}
