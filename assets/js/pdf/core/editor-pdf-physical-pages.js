// ============================================================
// MÓDULO: editor-pdf-physical-pages.js
// Responsabilidad: Modelo canónico de páginas físicas para
// numeración, paridad y metadatos compartidos por el motor PDF.
// ============================================================

window.getPdfRenderedPages = function(scroller) {
    if (!scroller) {
        return [];
    }

    return Array.from(scroller.querySelectorAll('.pagedjs_pages > .pagedjs_page'));
};

window.getPdfPhysicalPageSide = function(physicalPageNumber) {
    return physicalPageNumber % 2 === 0 ? 'left' : 'right';
};

window.getPdfPhysicalPageRole = function(page) {
    if (!page) {
        return 'unknown';
    }

    if (page.querySelector('.book-start-dummy-page')) {
        return 'dummy';
    }
    if (page.querySelector('.book-start-leading-page')) {
        return 'book-start-leading-page';
    }
    if (page.querySelector('.chapter-transition-blank-page')) {
        return 'chapter-transition-blank-page';
    }
    if (page.querySelector('.book-end-blank-page')) {
        return 'book-end-blank-page';
    }
    if (page.querySelector('.credits-blank-page')) {
        return 'credits-blank-page';
    }
    if (page.querySelector('[class*="chapter-image-page-section-"]')) {
        return 'chapter-image-page';
    }
    if (page.querySelector('[class*="chapter-opening-page-section-"]')) {
        return 'chapter-opening-page';
    }
    if (page.querySelector('[class*="chapter-section-"]')) {
        return 'chapter-content-page';
    }
    if (page.querySelector('.pagedjs_blank_page')) {
        return 'blank';
    }

    return 'content';
};

window.getPdfPhysicalPageNumberForPage = function(page) {
    if (!page) {
        return NaN;
    }

    const directNumber = parseInt(
        page.dataset.booksterPhysicalPageNumber
        || page.getAttribute('data-bookster-physical-page-number')
        || page.dataset.pageNumber
        || page.getAttribute('data-page-number')
        || '',
        10
    );

    if (Number.isFinite(directNumber) && directNumber > 0) {
        return directNumber;
    }

    const visibleIndex = parseInt(
        page.dataset.booksterVisiblePageIndex
        || page.getAttribute('data-bookster-visible-page-index')
        || '',
        10
    );

    return Number.isFinite(visibleIndex) && visibleIndex > 0 ? visibleIndex : NaN;
};

window.buildPdfPhysicalPageModel = function(scroller, options = {}) {
    const pages = window.getPdfRenderedPages ? window.getPdfRenderedPages(scroller) : [];
    const firstPhysicalPageNumber = Number.isFinite(options.firstPhysicalPageNumber) && options.firstPhysicalPageNumber > 0
        ? Math.floor(options.firstPhysicalPageNumber)
        : 1;

    const entries = [];
    let visiblePageIndex = 0;

    pages.forEach((page, domIndex) => {
        const isDummyPage = !!page.querySelector('.book-start-dummy-page');
        const role = window.getPdfPhysicalPageRole ? window.getPdfPhysicalPageRole(page) : 'content';
        const entry = {
            page,
            domIndex,
            isDummyPage,
            role,
            physicalPageNumber: NaN,
            physicalPageParity: 'unknown',
            physicalPageSide: 'unknown',
            visiblePageIndex: NaN
        };

        if (!isDummyPage) {
            visiblePageIndex += 1;
            entry.visiblePageIndex = visiblePageIndex;
            entry.physicalPageNumber = firstPhysicalPageNumber + visiblePageIndex - 1;
            entry.physicalPageParity = entry.physicalPageNumber % 2 === 0 ? 'even' : 'odd';
            entry.physicalPageSide = window.getPdfPhysicalPageSide
                ? window.getPdfPhysicalPageSide(entry.physicalPageNumber)
                : (entry.physicalPageParity === 'even' ? 'left' : 'right');
        }

        entries.push(entry);
    });

    const visibleEntries = entries.filter(entry => !entry.isDummyPage);

    return {
        pages,
        entries,
        visibleEntries,
        firstPhysicalPageNumber,
        firstVisiblePageNumber: visibleEntries.length > 0 ? visibleEntries[0].physicalPageNumber : firstPhysicalPageNumber,
        lastPhysicalPageNumber: visibleEntries.length > 0
            ? visibleEntries[visibleEntries.length - 1].physicalPageNumber
            : firstPhysicalPageNumber - 1,
        totalPages: visibleEntries.length
    };
};

window.applyPdfPhysicalPageModel = function(scroller, options = {}) {
    const model = window.buildPdfPhysicalPageModel
        ? window.buildPdfPhysicalPageModel(scroller, options)
        : { entries: [], visibleEntries: [], firstPhysicalPageNumber: 1, totalPages: 0 };

    model.entries.forEach((entry) => {
        const { page } = entry;
        if (!page) {
            return;
        }

        if (entry.isDummyPage) {
            page.dataset.booksterPhysicalPageRole = 'dummy';
            page.dataset.booksterVisiblePageIndex = '';
            page.dataset.booksterPhysicalPageParity = '';
            page.dataset.booksterPhysicalPageSide = '';
            page.classList.remove(
                'pagedjs_left_page',
                'pagedjs_right_page',
                'bookster-left-page',
                'bookster-right-page'
            );
            return;
        }

        page.dataset.booksterVisiblePageIndex = String(entry.visiblePageIndex);
        page.dataset.booksterPhysicalPageNumber = String(entry.physicalPageNumber);
        page.dataset.booksterPhysicalPageParity = entry.physicalPageParity;
        page.dataset.booksterPhysicalPageSide = entry.physicalPageSide;
        page.dataset.booksterPhysicalPageRole = entry.role;
        page.dataset.pageNumber = String(entry.physicalPageNumber);
        page.setAttribute('data-bookster-visible-page-index', String(entry.visiblePageIndex));
        page.setAttribute('data-bookster-physical-page-number', String(entry.physicalPageNumber));
        page.setAttribute('data-bookster-physical-page-parity', entry.physicalPageParity);
        page.setAttribute('data-bookster-physical-page-side', entry.physicalPageSide);
        page.setAttribute('data-bookster-physical-page-role', entry.role);
        page.setAttribute('data-page-number', String(entry.physicalPageNumber));
        page.classList.remove(
            'pagedjs_left_page',
            'pagedjs_right_page',
            'bookster-left-page',
            'bookster-right-page'
        );
        page.classList.add(
            entry.physicalPageSide === 'left' ? 'pagedjs_left_page' : 'pagedjs_right_page'
        );
        page.classList.add(
            entry.physicalPageSide === 'left' ? 'bookster-left-page' : 'bookster-right-page'
        );
    });

    return model;
};
