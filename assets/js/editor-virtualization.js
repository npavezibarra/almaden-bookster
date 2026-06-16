// editor-virtualization.js

// ==========================================
// VIRTUALIZACIÓN DEL PDF (Intersection Observer)
// ==========================================
window.initPDFVirtualization = function(scroller) {
    if (window.isPrintingPDF) return;
    
    if (window.pdfVirtualObserver) {
        window.pdfVirtualObserver.disconnect();
    }
    
    // Configurar IntersectionObserver con márgenes amplios (aprox 2 páginas arriba/abajo)
    const options = {
        root: scroller,
        rootMargin: '1500px 0px', 
        threshold: 0
    };
    
    window.pdfVirtualObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const pageEl = entry.target;
            const pageNum = pageEl.getAttribute('data-virtual-page');
            if (!pageNum || !window.pdfPagesCache || !window.pdfPagesCache[pageNum]) return;
            
            if (entry.isIntersecting) {
                // Entrando a la vista: inyectar el HTML real
                if (pageEl.classList.contains('is-virtualized')) {
                    pageEl.innerHTML = window.pdfPagesCache[pageNum];
                    pageEl.classList.remove('is-virtualized');
                }
            } else {
                // Saliendo de la vista: volver al placeholder ligero (Virtualizar)
                if (!pageEl.classList.contains('is-virtualized')) {
                    // Solo virtualizamos de nuevo si seguimos en modo vista
                    if (window.currentPreviewMode === 'full') {
                        pageEl.innerHTML = '<div class="virtual-placeholder" style="display:flex; height:100%; align-items:center; justify-content:center; color:#e2e8f0; font-size:14px;"><i class="fa-solid fa-file-lines fa-3x mb-2"></i></div>';
                        pageEl.classList.add('is-virtualized');
                    }
                }
            }
        });
    }, options);
    
    const pages = scroller.querySelectorAll('.pdf-page[data-virtual-page]');
    pages.forEach(page => {
        window.pdfVirtualObserver.observe(page);
    });
};
