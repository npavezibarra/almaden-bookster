// ============================================================
// MÓDULO: editor-pdf-compiler-builder.js
// Responsabilidad: Construcción del HTML continuo del libro
// ============================================================

window.buildContinuousBookHTML = function(isSingleChapterMode, bookState, settings, bookChapterPages, paginationOptions = {}) {
    const creditsBlankBefore = Math.max(0, parseInt(settings.credits_blank_before || 0, 10) || 0);
    const creditsBlankAfter = Math.max(0, parseInt(settings.credits_blank_after || 0, 10) || 0);

    const buildCreditsBlankPage = (chapterId = '') => `
        <section class="credits-blank-page"${chapterId ? ` data-chapter-id="${chapterId}"` : ''}>
            <div style="height: 1px;"></div>
        </section>
    `;

    const buildBookStartLeadingPage = (chapterId = '') => `
        <section class="book-start-leading-page"${chapterId ? ` data-chapter-id="${chapterId}"` : ''}>
            <div style="height: 1px;"></div>
        </section>
    `;

    const buildBookEndBlankPage = () => `
        <section class="book-end-blank-page" aria-hidden="true">
            <div style="height: 1px;"></div>
        </section>
    `;

    const buildChapterTransitionBlankPage = (chapterId = '') => `
        <section class="chapter-transition-blank-page"${chapterId ? ` data-chapter-id="${chapterId}"` : ''} aria-hidden="true">
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
    const shouldSeparateChapterOpening = window.shouldSeparateChapterOpening || function(chapter, settings) {
        return (settings && String(settings.book_separate_opening_content) !== '0') && chapterHasOpeningPage(chapter);
    };
    const chapterUsesSeparateOpeningPage = (chapter) => shouldSeparateChapterOpening(chapter, settings);

    const buildOpeningPageSection = (chapter, chapterIndex, isSingleChapterPreview = false) => {
        const openingMode = getEffectiveOpeningPageMode(chapter);
        const includeOpeningBlock = openingMode === 'blank';
        const openingContent = includeOpeningBlock && typeof window.buildChapterOpeningHtml === 'function'
            ? window.buildChapterOpeningHtml(chapter, chapterIndex, settings, bookState, { variant: 'blank-page', forceRenderTitle: true })
            : '<div class="chapter-parity-blank-page"></div>';

        const openingSectionStyle = openingMode === 'blank'
            ? 'display: flex; width: 100%; min-height: 100%; height: 100%; box-sizing: border-box;'
            : '';

        return `
        <section class="chapter-opening-page-section-${chapter.id} pdf-content${isSingleChapterPreview ? ' single-chapter-opening-preview' : ''}" data-opening-mode="${openingMode}" style="${openingSectionStyle}">
            ${openingContent}
        </section>
    `;
    };

    const buildMainChapterSection = (chapter, compiledHtml, isSingleChapterPreview = false) => `
        <section class="chapter-section-${chapter.id} pdf-content${isSingleChapterPreview ? ' single-chapter-main-preview' : ''}" id="chapter-section-${chapter.id}">
            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
            ${compiledHtml}
        </section>
    `;

    let needsDummyPage = false;
    let pageCounterReset = 0;
    let startPageNum = 1;
    let previewFirstPhysicalPageNumber = 1;
    let fullBookHTML = '';

    if (isSingleChapterMode) {
        const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
        if (activeChapter) {
            const activeIndex = bookState.chapters.indexOf(activeChapter);
            const firstChapter = bookState.chapters[0] || null;
            const isBookStartChapter = firstChapter && firstChapter.id === activeChapter.id;
            const firstChapterLength = firstChapter
                ? (window.bookChapterPhysicalLengths?.[firstChapter.id] || window.bookChapterLengths?.[firstChapter.id] || null)
                : null;
            const needsBookStartLeadingPage = isBookStartChapter && (
                window.shouldInsertBookStartLeadingPage
                    ? window.shouldInsertBookStartLeadingPage(activeChapter, settings, firstChapterLength)
                    : (firstChapter
                        ? (chapterHasOpeningPage(activeChapter) || ((firstChapter.start_parity && firstChapter.start_parity !== 'any') ? firstChapter.start_parity : settings.chapter_start_parity) === 'even')
                        : false)
            );
            let cachedPageNum = bookChapterPages ? bookChapterPages[activeChapter.id] : undefined;
            if (needsBookStartLeadingPage) {
                startPageNum = 1;
            } else if (cachedPageNum !== undefined && cachedPageNum !== null) {
                startPageNum = cachedPageNum;
            } else if (chapterUsesSeparateOpeningPage(activeChapter)) {
                const chapterFlowMode = window.getBookChapterFlowMode
                    ? window.getBookChapterFlowMode(settings)
                    : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
                startPageNum = chapterFlowMode === 'left' ? 3 : 1;
            } else if (activeIndex === 0) {
                startPageNum = 1;
            } else {
                const chapterStartParity = window.getChapterStartParity
                    ? window.getChapterStartParity(activeChapter, settings)
                    : ((activeChapter.start_parity && activeChapter.start_parity !== 'any') ? activeChapter.start_parity : settings.chapter_start_parity);
                startPageNum = (chapterStartParity === 'even') ? 2 : 3;
            }

            const chapterFlowMode = window.getBookChapterFlowMode
                ? window.getBookChapterFlowMode(settings)
                : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
            // The transition blank belongs to the previous chapter. The active
            // chapter preview must not render that same physical page again.
            const needsChapterStartTransitionPage = false;
            const knownActiveChapterLength = window.bookChapterLengths?.[activeChapter.id]
                || window.bookChapterPhysicalLengths?.[activeChapter.id]
                || null;
            const activeChapterContentStartPage = needsBookStartLeadingPage
                ? 2
                : startPageNum;
            const activeChapterEndsOnEvenPage = knownActiveChapterLength !== null
                && Number.isFinite(Number(knownActiveChapterLength))
                && (activeChapterContentStartPage + Number(knownActiveChapterLength) - 1) % 2 === 0;
            const needsChapterEndTransitionPage = activeIndex < bookState.chapters.length - 1
                && chapterFlowMode === 'left'
                && activeChapterEndsOnEvenPage;

            previewFirstPhysicalPageNumber = startPageNum;
            if (needsChapterStartTransitionPage) {
                previewFirstPhysicalPageNumber = Math.max(1, startPageNum - 1);
            }
            pageCounterReset = Math.max(0, previewFirstPhysicalPageNumber - 1);
            
            if (needsBookStartLeadingPage || chapterUsesSeparateOpeningPage(activeChapter) || needsChapterStartTransitionPage) {
                needsDummyPage = needsBookStartLeadingPage || needsChapterStartTransitionPage;
            }
            
            fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}" style="counter-reset: page ${pageCounterReset};">`;
            
            if (needsDummyPage) {
                const previousChapter = bookState.chapters[activeIndex - 1] || null;
                fullBookHTML += buildBookStartLeadingPage(
                    needsBookStartLeadingPage ? '' : (previousChapter ? previousChapter.id : '')
                );
            }
            
            if (chapterUsesSeparateOpeningPage(activeChapter)) {
                fullBookHTML += buildOpeningPageSection(activeChapter, activeIndex, true);
                fullBookHTML += buildMainChapterSection(
                    activeChapter,
                    window.buildChapterHTML(activeChapter, activeIndex, settings, bookState, { includeOpeningBlock: activeChapter.opening_page_mode !== 'blank' }),
                    true
                );
            } else if (activeChapter.is_credits === '1') {
                for (let i = 0; i < creditsBlankBefore; i++) {
                    fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                }

                const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                fullBookHTML += buildMainChapterSection(activeChapter, compiledHtml, true);

                for (let i = 0; i < creditsBlankAfter; i++) {
                    fullBookHTML += buildCreditsBlankPage(activeChapter.id);
                }
            } else {
                const compiledHtml = window.buildChapterHTML(activeChapter, activeIndex, settings, bookState);
                fullBookHTML += buildMainChapterSection(activeChapter, compiledHtml, true);
            }

            if (needsChapterEndTransitionPage) {
                fullBookHTML += buildBookStartLeadingPage(activeChapter.id);
            }
        } else {
            fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}">`;
        }
    } else {
        // Full Book Mode: check if the first chapter needs a real leading page.
        const firstCh = bookState.chapters[0];
        if (firstCh) {
            const firstChLength = window.bookChapterPhysicalLengths?.[firstCh.id] || window.bookChapterLengths?.[firstCh.id] || null;
            const firstChapterParity = ((firstCh.start_parity && firstCh.start_parity !== 'any') ? firstCh.start_parity : settings.chapter_start_parity);
            const needsBookStartLeadingPage = window.shouldInsertBookStartLeadingPage
                ? window.shouldInsertBookStartLeadingPage(firstCh, settings, firstChLength)
                : (chapterHasOpeningPage(firstCh) || firstChapterParity === 'even');
            if (needsBookStartLeadingPage) {
                needsDummyPage = true;
            }
        }
        
        fullBookHTML = `<div class="book-container" lang="${settings.content_language || 'es'}" style="counter-reset: page 0;">`;
        if (needsDummyPage) {
            fullBookHTML += buildBookStartLeadingPage();
        }
        for (let index = 0; index < bookState.chapters.length; index++) {
            const chapter = bookState.chapters[index];
            const compiledHtml = window.buildChapterHTML(chapter, index, settings, bookState, {
                includeOpeningBlock: !(chapterUsesSeparateOpeningPage(chapter) && getEffectiveOpeningPageMode(chapter) === 'blank')
            });
            
            if (chapterUsesSeparateOpeningPage(chapter)) {
                fullBookHTML += buildOpeningPageSection(chapter, index);
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

            // In left-flow books, a first-page TOC starts on page 2 after the
            // mandatory page 1. If its content has an odd physical length,
            // it ends on an even page and owns the following odd blank.
            if (index === 0 && chapter.is_toc === '1') {
                const flowMode = window.getBookChapterFlowMode
                    ? window.getBookChapterFlowMode(settings)
                    : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
                const tocLength = Number(
                    window.bookChapterPhysicalLengths?.[chapter.id]
                    || window.bookChapterLengths?.[chapter.id]
                    || 0
                );
                if (flowMode === 'left' && tocLength > 0 && tocLength % 2 === 1) {
                    fullBookHTML += buildChapterTransitionBlankPage(chapter.id);
                }
            }
        }

        if (paginationOptions.forceFinalBlankPage) {
            fullBookHTML += buildBookEndBlankPage();
        }
    }
    fullBookHTML += '</div>';

    return {
        fullBookHTML,
        previewFirstPhysicalPageNumber,
        startPageNum,
        needsDummyPage
    };
};
