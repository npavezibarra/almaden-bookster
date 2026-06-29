// ============================================================
// MÓDULO: editor-pdf-compiler-builder.js
// Responsabilidad: Construcción del HTML continuo del libro
// ============================================================

window.buildContinuousBookHTML = function(isSingleChapterMode, bookState, settings, bookChapterPages) {
    const creditsBlankBefore = Math.max(0, parseInt(settings.credits_blank_before || 0, 10) || 0);
    const creditsBlankAfter = Math.max(0, parseInt(settings.credits_blank_after || 0, 10) || 0);

    const buildCreditsBlankPage = (chapterId = '') => `
        <section class="credits-blank-page"${chapterId ? ` data-chapter-id="${chapterId}"` : ''}>
            <div style="height: 1px;"></div>
        </section>
    `;

    const getEffectiveOpeningPageMode = window.getEffectiveOpeningPageMode || function(chapter) {
        const configuredMode = chapter && chapter.opening_page_mode ? chapter.opening_page_mode : 'auto';
        if (configuredMode === 'auto') {
            return chapter && chapter.parity_image ? 'image' : 'none';
        }
        if (configuredMode === 'image' && !(chapter && chapter.parity_image)) {
            return 'blank';
        }
        return configuredMode;
    };

    const chapterHasOpeningPage = window.chapterHasOpeningPage || function(chapter) {
        const mode = getEffectiveOpeningPageMode(chapter);
        return mode === 'blank' || mode === 'image';
    };

    const buildOpeningPageSection = (chapter) => `
        <section class="chapter-opening-page-section-${chapter.id} pdf-content" data-opening-mode="${getEffectiveOpeningPageMode(chapter)}">
            <div class="chapter-parity-blank-page"></div>
        </section>
    `;

    const buildMainChapterSection = (chapter, compiledHtml) => `
        <section class="chapter-section-${chapter.id} pdf-content" id="chapter-section-${chapter.id}">
            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
            ${compiledHtml}
        </section>
    `;

    let needsDummyPage = false;
    let pageCounterReset = 0;
    let prependBlankPage = false;
    let startPageNum = 1;
    let previewFirstPhysicalPageNumber = 1;
    let fullBookHTML = '';

    if (isSingleChapterMode) {
        const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
        if (activeChapter) {
            const activeIndex = bookState.chapters.indexOf(activeChapter);
            let cachedPageNum = bookChapterPages ? bookChapterPages[activeChapter.id] : undefined;
            if (cachedPageNum !== undefined && cachedPageNum !== null) {
                startPageNum = cachedPageNum;
            } else {
                if (chapterHasOpeningPage(activeChapter)) {
                    startPageNum = 3;
                } else if (activeIndex === 0) {
                    startPageNum = 1;
                } else {
                    const chapterStartParity = (activeChapter.start_parity && activeChapter.start_parity !== 'any') ? activeChapter.start_parity : settings.chapter_start_parity;
                    startPageNum = (chapterStartParity === 'even') ? 2 : 3;
                }
            }

            previewFirstPhysicalPageNumber = startPageNum;
            
            // Si la página de inicio es impar y mayor a 1, comenzamos con una página par (en blanco) para ver la paridad en doble página
            if (startPageNum > 1 && startPageNum % 2 !== 0) {
                prependBlankPage = true;
            }

            if (chapterHasOpeningPage(activeChapter) || prependBlankPage) {
                previewFirstPhysicalPageNumber = Math.max(1, startPageNum - 1);
            }
            
            pageCounterReset = Math.max(0, previewFirstPhysicalPageNumber - 1);
            
            if (startPageNum > 1) {
                needsDummyPage = true;
            }
            
            fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}" style="counter-reset: page ${pageCounterReset};">`;
            
            if (needsDummyPage) {
                fullBookHTML += `
                    <div class="book-start-dummy-page">
                        <div style="height: 1px;"></div>
                    </div>
                `;
            }
            
            if (chapterHasOpeningPage(activeChapter)) {
                fullBookHTML += buildOpeningPageSection(activeChapter);
                fullBookHTML += buildMainChapterSection(
                    activeChapter,
                    window.buildChapterHTML(activeChapter, activeIndex, settings, bookState)
                );
            } else if (activeChapter.is_credits === '1') {
                for (let i = 0; i < creditsBlankBefore; i++) {
                    fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                }

                const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                fullBookHTML += buildMainChapterSection(activeChapter, compiledHtml);

                for (let i = 0; i < creditsBlankAfter; i++) {
                    fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                }
            } else {
                if (prependBlankPage) {
                    // Insertar página en blanco a la izquierda del pliego (global)
                    fullBookHTML += `
                        <section class="chapter-preview-blank-page">
                            <div style="height: 1px;"></div>
                        </section>
                    `;
                }
                
                const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                fullBookHTML += buildMainChapterSection(activeChapter, compiledHtml);
            }
        } else {
            fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}">`;
        }
    } else {
        // Full Book Mode: check if first chapter starts on left page
        const firstCh = bookState.chapters[0];
        if (firstCh) {
            const firstChStartParity = (firstCh.start_parity && firstCh.start_parity !== 'any') ? firstCh.start_parity : settings.chapter_start_parity;
            if (chapterHasOpeningPage(firstCh) || firstChStartParity === 'even') {
                needsDummyPage = true;
            }
        }
        
        fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}">`;
        if (needsDummyPage) {
            fullBookHTML += `
                <div class="book-start-dummy-page">
                    <div style="height: 1px;"></div>
                </div>
            `;
        }
        for (let index = 0; index < bookState.chapters.length; index++) {
            const chapter = bookState.chapters[index];
            const compiledHtml = window.buildChapterHTML(chapter, index, settings, bookState);
            
            if (chapterHasOpeningPage(chapter)) {
                fullBookHTML += buildOpeningPageSection(chapter);
                fullBookHTML += buildMainChapterSection(chapter, compiledHtml);
            } else if (chapter.is_credits === '1') {
                for (let i = 0; i < creditsBlankBefore; i++) {
                    fullBookHTML += buildCreditsBlankPage(chapter.id);
                }

                fullBookHTML += buildMainChapterSection(chapter, compiledHtml);

                for (let i = 0; i < creditsBlankAfter; i++) {
                    fullBookHTML += buildCreditsBlankPage(chapter.id);
                }
            } else {
                fullBookHTML += buildMainChapterSection(chapter, compiledHtml);
            }
        }
    }
    fullBookHTML += '</div>';

    return {
        fullBookHTML,
        previewFirstPhysicalPageNumber,
        startPageNum,
        needsDummyPage,
        prependBlankPage
    };
};
