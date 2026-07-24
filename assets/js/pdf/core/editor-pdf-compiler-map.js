// ============================================================
// MÓDULO: editor-pdf-compiler-map.js
// Responsabilidad: Mapeo y firma de paginación de capítulos y caché de páginas
// ============================================================

window.getBookPageMapSignature = function() {
    const chapters = (bookState.chapters || []).map(ch => ({
        id: ch.id,
        title: ch.title,
        content: ch.content,
        is_toc: ch.is_toc,
        is_credits: ch.is_credits,
        parity_image: ch.parity_image,
        opening_page_mode: ch.opening_page_mode,
        opening_blank_intentional: ch.opening_blank_intentional,
        opening_block_enabled: ch.opening_block_enabled,
        opening_block_horizontal_align: ch.opening_block_horizontal_align,
        opening_block_vertical_align: ch.opening_block_vertical_align,
        start_parity: ch.start_parity,
        hide_all_headers_footers: ch.hide_all_headers_footers,
        toc_hide_header: ch.toc_hide_header,
        toc_hide_page_numbers: ch.toc_hide_page_numbers,
        credits_hide_page_number: ch.credits_hide_page_number
    }));

    return JSON.stringify({
        settings: bookState.settings || {},
        chapters
    });
};

window.ensureBookPageMap = async function() {
    if (window._isEnsuringBookPageMap) {
        return window._bookPageMapPromise || Promise.resolve(0);
    }

    window._isEnsuringBookPageMap = true;
    const expectedSignature = typeof window.getBookPageMapSignature === 'function'
        ? window.getBookPageMapSignature()
        : '';

    window._bookPageMapPromise = (async () => {
        let dummyScroller = document.getElementById('dummy-pdf-scroller');
        if (!dummyScroller) {
            dummyScroller = document.createElement('div');
            dummyScroller.id = 'dummy-pdf-scroller';
            dummyScroller.style.position = 'absolute';
            dummyScroller.style.visibility = 'hidden';
            dummyScroller.style.pointerEvents = 'none';
            dummyScroller.style.zIndex = '-9999';
            dummyScroller.style.top = '0';
            dummyScroller.style.left = '0';

            const realScroller = document.getElementById('pdf-scroller');
            let w = realScroller ? realScroller.clientWidth : 0;
            if (w <= 0) w = 800;
            dummyScroller.style.width = w + 'px';
            document.body.appendChild(dummyScroller);
        }

        const totalPages = await _compilePDFPreviewInternal(false, 'dummy-pdf-scroller', true);
        dummyScroller.innerHTML = '';
        if (totalPages > 0) {
            window._bookPageMapSignature = expectedSignature;
        }
        return totalPages || 0;
    })().finally(() => {
        window._isEnsuringBookPageMap = false;
        window._bookPageMapPromise = null;
    });

    return window._bookPageMapPromise;
};
