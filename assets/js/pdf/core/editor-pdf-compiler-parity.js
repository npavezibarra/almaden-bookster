// ============================================================
// MÓDULO: editor-pdf-compiler-parity.js
// Responsabilidad: Cierre físico del libro en página par.
// ============================================================

window.getBookBoundaryChapters = function(bookState) {
    const chapters = Array.isArray(bookState && bookState.chapters) ? bookState.chapters : [];
    if (chapters.length === 0) {
        return { firstChapter: null, lastChapter: null };
    }

    return {
        firstChapter: chapters[0] || null,
        lastChapter: chapters[chapters.length - 1] || null
    };
};

window.shouldAppendBookEndBlankPage = function(scroller, bookState) {
    const boundaries = window.getBookBoundaryChapters ? window.getBookBoundaryChapters(bookState) : {
        firstChapter: null,
        lastChapter: null
    };

    if (!boundaries.firstChapter || !boundaries.lastChapter) {
        return false;
    }

    if (!scroller) {
        return false;
    }

    const renderedPages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'))
        .filter(page => !page.querySelector('.book-start-dummy-page'));
    if (renderedPages.length === 0) {
        return false;
    }

    const lastPage = renderedPages[renderedPages.length - 1];
    const hasExistingBookEndBlank = !!lastPage.querySelector('.book-end-blank-page');
    if (hasExistingBookEndBlank) {
        return false;
    }

    // Regla de borde del libro: el primer capítulo fija el arranque y el
    // último capítulo debe cerrar en página par. Si la maqueta termina en
    // impar, añadimos un blanco final para equilibrar el libro completo.
    return renderedPages.length % 2 === 1;
};

window.shouldAppendActiveBookEndBlankPage = function(scroller, bookState, activeChapterId, previewFirstPhysicalPageNumber) {
    const chapters = Array.isArray(bookState && bookState.chapters) ? bookState.chapters : [];
    if (!activeChapterId || chapters.length === 0) {
        return false;
    }

    const lastChapter = chapters[chapters.length - 1] || null;
    if (!lastChapter || String(lastChapter.id) !== String(activeChapterId)) {
        return false;
    }

    if (!scroller || !Number.isFinite(previewFirstPhysicalPageNumber)) {
        return false;
    }

    const renderedPages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'))
        .filter(page => !page.querySelector('.book-start-dummy-page'));
    if (renderedPages.length === 0) {
        return false;
    }

    const lastPage = renderedPages[renderedPages.length - 1];
    if (lastPage.querySelector('.book-end-blank-page')) {
        return false;
    }

    const lastPhysicalPageNumber = previewFirstPhysicalPageNumber + renderedPages.length - 1;
    return lastPhysicalPageNumber % 2 === 1;
};
