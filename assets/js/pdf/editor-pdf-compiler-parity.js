// ============================================================
// MÓDULO: editor-pdf-compiler-parity.js
// Responsabilidad: Control de paridad para asegurar páginas
// de inicio correctas (izq/der) de capítulos mediante páginas en blanco.
// ============================================================

window.handleChapterParity = function(chapter, index, settings, currentPageNumber, scroller, virtualizePage) {
    const chapterStartParity = (chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : settings.chapter_start_parity;
    
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
                const pureBlankPage = window.createNewPageElement(currentPageNumber, { ...chapter, parity_image: null }, false, true);
                pureBlankPage.setAttribute('data-chapter-id', chapter.id);
                scroller.appendChild(pureBlankPage);
                virtualizePage(pureBlankPage, currentPageNumber);
                currentPageNumber++;

                const parityPage = window.createNewPageElement(currentPageNumber, chapter, false, true);
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
