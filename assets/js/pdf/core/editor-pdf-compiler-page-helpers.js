// ============================================================
// MÓDULO: editor-pdf-compiler-page-helpers.js
// Responsabilidad: Utilidades para inspeccionar páginas visibles,
// detectar blancos técnicos y calcular transiciones entre capítulos.
// ============================================================

window.getPdfVisibleRenderedPages = function(scroller) {
    if (!scroller) {
        return [];
    }

    return Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'))
        .filter(page => !page.querySelector('.book-start-dummy-page'));
};

window.getPdfEscapeSelectorValue = function(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(String(value));
    }
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
};

window.getPdfEscapeAttributeValue = function(value) {
    return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
};

window.getPdfChapterContentPages = function(chapter, visiblePages) {
    const selectorId = window.getPdfEscapeSelectorValue
        ? window.getPdfEscapeSelectorValue(chapter.id)
        : String(chapter.id);
    const attrId = window.getPdfEscapeAttributeValue
        ? window.getPdfEscapeAttributeValue(chapter.id)
        : String(chapter.id).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    const selectors = [
        `.chapter-image-page-section-${selectorId}`,
        `.chapter-opening-page-section-${selectorId}`,
        `.chapter-section-${selectorId}`,
        `.credits-blank-page[data-chapter-id="${attrId}"]`
    ].join(', ');

    return visiblePages.filter(page => page.querySelector(selectors));
};

window.removePdfTrailingAccidentalBlankPages = function(scroller, isSingleChapterMode) {
    if (!isSingleChapterMode || !scroller) {
        return;
    }

    let pages = window.getPdfVisibleRenderedPages ? window.getPdfVisibleRenderedPages(scroller) : [];
    while (pages.length && !((pages[pages.length - 1].querySelector('.pagedjs_area') || pages[pages.length - 1]).textContent || '').trim() && !pages[pages.length - 1].querySelector('.chapter-transition-blank-page, .book-end-blank-page, .credits-blank-page')) {
        pages.pop().remove();
    }
};

window.getPdfNeededTransitionBlankChapterIds = function(scroller, bookState, settings, firstPhysicalPageNumber, onlyChapterId = null) {
    if (!scroller) {
        return [];
    }

    const flowMode = window.getBookChapterFlowMode
        ? window.getBookChapterFlowMode(settings)
        : (settings && settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
    if (flowMode !== 'left') {
        return [];
    }

    const chapters = Array.isArray(bookState && bookState.chapters) ? bookState.chapters : [];
    const visiblePages = window.getPdfVisibleRenderedPages ? window.getPdfVisibleRenderedPages(scroller) : [];
    const physicalPageModel = window.buildPdfPhysicalPageModel
        ? window.buildPdfPhysicalPageModel(scroller, { firstPhysicalPageNumber })
        : null;
    const physicalPageByElement = physicalPageModel
        ? new Map(physicalPageModel.entries.map(entry => [entry.page, entry]))
        : null;
    const neededIds = [];
    const onlyId = onlyChapterId === null ? null : String(onlyChapterId);

    chapters.forEach((chapter, index) => {
        if (index >= chapters.length - 1) {
            return;
        }
        if (onlyId !== null && String(chapter.id) !== onlyId) {
            return;
        }

        const existingBlank = scroller.querySelector(
            `.chapter-transition-blank-page[data-chapter-id="${window.getPdfEscapeAttributeValue ? window.getPdfEscapeAttributeValue(chapter.id) : String(chapter.id).replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"]`
        );
        if (existingBlank) {
            return;
        }

        const chapterPages = window.getPdfChapterContentPages ? window.getPdfChapterContentPages(chapter, visiblePages) : [];
        if (chapterPages.length === 0) {
            return;
        }

        const lastContentPage = chapterPages[chapterPages.length - 1];
        const lastVisibleIndex = visiblePages.indexOf(lastContentPage);
        const lastPhysicalPage = physicalPageByElement?.get(lastContentPage)?.physicalPageNumber
            || (firstPhysicalPageNumber + lastVisibleIndex);
        if (lastPhysicalPage % 2 === 0) {
            const nextPage = visiblePages[lastVisibleIndex + 1] || null;
            const nextPhysicalPage = nextPage
                ? (physicalPageByElement?.get(nextPage)?.physicalPageNumber || (firstPhysicalPageNumber + lastVisibleIndex + 1))
                : null;
            const nextPageHasChapterContent = nextPage && chapters.some(otherChapter => {
                return (window.getPdfChapterContentPages ? window.getPdfChapterContentPages(otherChapter, [nextPage]) : []).length > 0;
            });
            const nextPageIsBlankTransition = nextPage
                && nextPhysicalPage % 2 === 1
                && !nextPageHasChapterContent;
            if (nextPageIsBlankTransition) {
                return;
            }
            neededIds.push(chapter.id);
        }
    });

    return neededIds;
};
