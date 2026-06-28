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
