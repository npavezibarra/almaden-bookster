// ============================================================
// MÓDULO: editor-pdf-compiler-parity.js
// Responsabilidad: Control de paridad para asegurar páginas
// de inicio correctas (izq/der) de capítulos mediante páginas en blanco.
// ============================================================

window.handleChapterParity = function(chapter, index, settings, currentPageNumber, scroller, virtualizePage) {
    const chapterStartParity = window.getChapterStartParity ? window.getChapterStartParity(chapter, settings) : (
        chapter && chapter.is_toc === '1'
            ? 'even'
            : ((chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : settings.chapter_start_parity)
    );
    
    if (index > 0 && chapterStartParity && chapterStartParity !== 'any') {
        const isOdd = (currentPageNumber % 2 === 1);
        if (chapterStartParity === 'odd') {
            if (!isOdd) {
                const blankPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
                blankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(blankPage);
                virtualizePage(blankPage, currentPageNumber);
                currentPageNumber++;
            } else {
                const pureBlankPage = window.createNewPageElement(currentPageNumber, { ...chapter, parity_image: null, opening_page_mode: 'blank' }, false, true);
                pureBlankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(pureBlankPage);
                virtualizePage(pureBlankPage, currentPageNumber);
                currentPageNumber++;

                const parityChapter = (window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none') === 'none'
                    ? { ...chapter, opening_page_mode: 'blank' }
                    : chapter;
                const parityPage = window.createNewPageElement(currentPageNumber, parityChapter, false, true);
                parityPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(parityPage);
                virtualizePage(parityPage, currentPageNumber);
                currentPageNumber++;
            }
        } else if (chapterStartParity === 'even' && isOdd) {
            const blankPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
            blankPage.setAttribute('data-chapter-id', chapter.id);
            scroller.appendChild(blankPage);
            virtualizePage(blankPage, currentPageNumber);
            currentPageNumber++;
        }
    }
    return currentPageNumber;
};

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
