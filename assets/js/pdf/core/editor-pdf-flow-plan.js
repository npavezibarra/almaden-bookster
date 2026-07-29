// ============================================================
// MÓDULO: editor-pdf-flow-plan.js
// Responsabilidad: Resolver el plan editorial del flujo de páginas
// antes de construir HTML o post-procesar la maqueta.
// ============================================================

window.buildBookFlowPlan = function(isSingleChapterMode, bookState, settings, bookChapterPages, paginationOptions = {}) {
    const chapters = Array.isArray(bookState && bookState.chapters) ? bookState.chapters : [];
    const activeChapterId = bookState && bookState.activeChapterId ? String(bookState.activeChapterId) : '';
    const activeChapter = chapters.find(ch => String(ch.id) === activeChapterId) || null;
    const activeIndex = activeChapter
        ? chapters.findIndex(ch => String(ch.id) === activeChapterId)
        : -1;
    const firstChapter = chapters[0] || null;
    const firstChapterLength = firstChapter
        ? (window.bookChapterPhysicalLengths?.[firstChapter.id] || window.bookChapterLengths?.[firstChapter.id] || null)
        : null;
    const flowMode = window.getBookChapterFlowMode
        ? window.getBookChapterFlowMode(settings)
        : ((settings && settings.chapter_start_parity === 'even') ? 'left' : 'continuous');
    const singleChapterRule = window.getSingleChapterBookRule
        ? window.getSingleChapterBookRule(bookState, settings)
        : {
            isSingleChapterBook: false,
            flowMode,
            shouldUseBookStartAsPageOne: false,
            shouldOverrideBookStartLeadingPage: false
        };
    const forcedTransitionBlankIds = new Set(
        Array.isArray(paginationOptions.forceTransitionBlankChapterIds)
            ? paginationOptions.forceTransitionBlankChapterIds.map(String)
            : []
    );
    const bookLanguage = typeof window.almadenGetBookLanguage === 'function'
        ? window.almadenGetBookLanguage(settings)
        : String(settings && (settings.book_language || settings.content_language) || 'es').trim().toLowerCase();

    let startPageNum = 1;
    let previewFirstPhysicalPageNumber = 1;
    let pageCounterReset = 0;
    let needsDummyPage = false;
    let needsChapterStartTransitionPage = false;
    let needsChapterEndTransitionPage = false;
    let needsBookStartLeadingPage = false;
    let bookStartLeadingPageChapterId = '';

    if (isSingleChapterMode && activeChapter) {
        const isBookStartChapter = firstChapter && firstChapter.id === activeChapter.id;
        const editorialStructure = window.getChapterEditorialStructure
            ? window.getChapterEditorialStructure(activeChapter, settings)
            : null;
        // The map stores the TOC anchor (opening/content). The active preview
        // must begin at the physical chapter start, which is one page earlier
        // when a chapter image precedes that anchor.
        const leadingImagePageCount = editorialStructure && editorialStructure.hasLeadingImage ? 1 : 0;
        needsBookStartLeadingPage = isBookStartChapter && flowMode === 'left';

        const cachedPageNum = bookChapterPages ? bookChapterPages[activeChapter.id] : undefined;
        if (needsBookStartLeadingPage) {
            startPageNum = 1;
        } else if (cachedPageNum !== undefined && cachedPageNum !== null) {
            startPageNum = Math.max(1, cachedPageNum - leadingImagePageCount);
        } else if (window.chapterHasLeadingImagePage && window.chapterHasLeadingImagePage(activeChapter, settings)) {
            startPageNum = 1;
        } else if (window.shouldSeparateChapterOpening && window.shouldSeparateChapterOpening(activeChapter, settings)) {
            startPageNum = flowMode === 'left' ? 3 : 1;
        } else if (activeIndex === 0) {
            startPageNum = 1;
        } else {
            const chapterStartParity = window.getChapterStartParity
                ? window.getChapterStartParity(activeChapter, settings)
                : ((activeChapter.start_parity && activeChapter.start_parity !== 'any') ? activeChapter.start_parity : settings.chapter_start_parity);
            startPageNum = (chapterStartParity === 'even') ? 2 : 3;
        }

        if (singleChapterRule && singleChapterRule.shouldUseBookStartAsPageOne) {
            startPageNum = 1;
        }

        needsChapterEndTransitionPage = activeIndex >= 0
            && activeIndex < chapters.length - 1
            && flowMode === 'left'
            && forcedTransitionBlankIds.has(String(activeChapter.id));

        previewFirstPhysicalPageNumber = startPageNum;
        if (needsChapterStartTransitionPage) {
            previewFirstPhysicalPageNumber = Math.max(1, startPageNum - 1);
        }

        pageCounterReset = Math.max(0, previewFirstPhysicalPageNumber - 1);
        needsDummyPage = needsBookStartLeadingPage || needsChapterStartTransitionPage;
        bookStartLeadingPageChapterId = needsBookStartLeadingPage
            ? ''
            : (activeIndex > 0 && chapters[activeIndex - 1] ? String(chapters[activeIndex - 1].id) : '');
    } else if (firstChapter) {
        needsBookStartLeadingPage = flowMode === 'left';
        needsDummyPage = needsBookStartLeadingPage;
        bookStartLeadingPageChapterId = '';
    }

    return {
        chapters,
        activeChapter,
        activeIndex,
        firstChapter,
        firstChapterLength,
        flowMode,
        singleChapterRule,
        bookLanguage,
        startPageNum,
        previewFirstPhysicalPageNumber,
        pageCounterReset,
        needsDummyPage,
        needsChapterStartTransitionPage,
        needsChapterEndTransitionPage,
        needsBookStartLeadingPage,
        bookStartLeadingPageChapterId
    };
};
