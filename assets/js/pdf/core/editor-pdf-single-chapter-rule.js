// ============================================================
// MÓDULO: editor-pdf-single-chapter-rule.js
// Responsabilidad: Regla especial para libros con un solo capítulo.
// Aísla el caso "capítulo inicial y final al mismo tiempo" para no
// interferir con el flujo normal de múltiples capítulos.
// ============================================================

window.isSingleChapterBook = function(bookState) {
    const chapters = Array.isArray(bookState && bookState.chapters) ? bookState.chapters : [];
    return chapters.length === 1;
};

window.getSingleChapterBookRule = function(bookState, settings) {
    const isSingleChapterBook = window.isSingleChapterBook
        ? window.isSingleChapterBook(bookState)
        : (Array.isArray(bookState && bookState.chapters) && bookState.chapters.length === 1);
    const flowMode = window.getBookChapterFlowMode
        ? window.getBookChapterFlowMode(settings)
        : ((settings && settings.chapter_start_parity === 'even') ? 'left' : 'continuous');
    const shouldUseBookStartAsPageOne = isSingleChapterBook && flowMode === 'left';

    return {
        isSingleChapterBook,
        flowMode,
        shouldUseBookStartAsPageOne,
        shouldOverrideBookStartLeadingPage: shouldUseBookStartAsPageOne
    };
};

window.getSingleChapterPreviewFirstPhysicalPageNumber = function(bookState, settings, startPageNum, mappedActiveChapterStart) {
    const rule = window.getSingleChapterBookRule
        ? window.getSingleChapterBookRule(bookState, settings)
        : {
            isSingleChapterBook: false,
            shouldUseBookStartAsPageOne: false
        };

    if (rule.shouldUseBookStartAsPageOne) {
        return Number.isFinite(startPageNum) && startPageNum > 0 ? startPageNum : 1;
    }

    if (Number.isFinite(mappedActiveChapterStart) && mappedActiveChapterStart > 0) {
        return mappedActiveChapterStart;
    }

    return Number.isFinite(startPageNum) && startPageNum > 0 ? startPageNum : 1;
};

window.getSingleChapterContentFirstPhysicalPageNumber = function(scroller, bookState, settings, fallbackPageNumber) {
    const rule = window.getSingleChapterBookRule
        ? window.getSingleChapterBookRule(bookState, settings)
        : null;
    if (!rule || !rule.isSingleChapterBook || !scroller) {
        return Number.isFinite(fallbackPageNumber) && fallbackPageNumber > 0 ? fallbackPageNumber : 1;
    }

    const activeChapter = (bookState && Array.isArray(bookState.chapters))
        ? bookState.chapters.find(ch => String(ch.id) === String(bookState.activeChapterId))
        : null;
    if (!activeChapter) {
        return Number.isFinite(fallbackPageNumber) && fallbackPageNumber > 0 ? fallbackPageNumber : 1;
    }

    const escapeSelectorValue = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(String(value));
        }
        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    };
    const chapterId = escapeSelectorValue(activeChapter.id);
    const selectors = [
        `.chapter-image-page-section-${chapterId}`,
        `.chapter-opening-page-section-${chapterId}`,
        `.chapter-section-${chapterId}`
    ].join(', ');

    const anchorElement = scroller.querySelector(selectors);
    if (!anchorElement) {
        return Number.isFinite(fallbackPageNumber) && fallbackPageNumber > 0 ? fallbackPageNumber : 1;
    }

    const anchorPage = anchorElement.closest('.pagedjs_page');
    if (!anchorPage) {
        return Number.isFinite(fallbackPageNumber) && fallbackPageNumber > 0 ? fallbackPageNumber : 1;
    }

    const pageNumber = parseInt(anchorPage.dataset.pageNumber || anchorPage.getAttribute('data-page-number') || '', 10);
    if (Number.isFinite(pageNumber) && pageNumber > 0) {
        return pageNumber;
    }

    const visiblePages = Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'))
        .filter(page => !page.querySelector('.book-start-dummy-page'));
    const index = visiblePages.indexOf(anchorPage);
    if (index >= 0) {
        return index + 1;
    }

    return Number.isFinite(fallbackPageNumber) && fallbackPageNumber > 0 ? fallbackPageNumber : 1;
};

window.getSingleChapterBookStyles = function(bookState, settings) {
    const rule = window.getSingleChapterBookRule
        ? window.getSingleChapterBookRule(bookState, settings)
        : null;

    if (!rule || !rule.shouldOverrideBookStartLeadingPage) {
        return '';
    }

    return `
        #pdf-scroller .book-start-leading-page {
            break-before: auto !important;
            page-break-before: auto !important;
        }
    `;
};
