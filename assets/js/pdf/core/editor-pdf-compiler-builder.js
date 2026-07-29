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

    const getTransitionBlankMode = window.getBookTransitionBlankMode || function(settings) {
        const mode = settings && settings.chapter_transition_blank_mode ? String(settings.chapter_transition_blank_mode) : 'full_blank';
        return ['full_blank', 'blank_with_header_footer', 'intentional_text'].includes(mode) ? mode : 'full_blank';
    };
    const getTransitionBlankText = window.getBookTransitionBlankText || function(settings) {
        const text = settings && settings.chapter_transition_blank_text !== undefined ? String(settings.chapter_transition_blank_text) : '...';
        return text.trim() === '' ? '...' : text;
    };
    const buildBookEndBlankPage = () => {
        const transitionBlankMode = getTransitionBlankMode(settings);
        const transitionBlankText = getTransitionBlankText(settings);
        const modeClass = transitionBlankMode === 'blank_with_header_footer'
            ? 'book-end-blank-page--with-header-footer'
            : (transitionBlankMode === 'intentional_text'
                ? 'book-end-blank-page--intentional-text'
                : 'book-end-blank-page--full');
        const innerHtml = transitionBlankMode === 'intentional_text'
            ? `<div class="book-end-blank-page__message">${escapeAttr(transitionBlankText).replace(/\n/g, '<br>')}</div>`
            : '<div style="height: 1px;"></div>';

        return `
            <section class="book-end-blank-page ${modeClass}" aria-hidden="${transitionBlankMode === 'intentional_text' ? 'false' : 'true'}" data-transition-blank-mode="${escapeAttr(transitionBlankMode)}" data-transition-blank-text="${escapeAttr(transitionBlankText)}">
                ${innerHtml}
            </section>
        `;
    };

    const buildChapterTransitionBlankPage = (chapterId = '') => {
        const transitionBlankMode = getTransitionBlankMode(settings);
        const transitionBlankText = getTransitionBlankText(settings);
        const modeClass = transitionBlankMode === 'blank_with_header_footer'
            ? 'chapter-transition-blank-page--with-header-footer'
            : (transitionBlankMode === 'intentional_text'
                ? 'chapter-transition-blank-page--intentional-text'
                : 'chapter-transition-blank-page--full');
        const pageName = transitionBlankMode === 'blank_with_header_footer'
            ? 'chapter-transition-blank-page'
            : 'chapter-blank-page';
        const innerHtml = transitionBlankMode === 'intentional_text'
            ? `<div class="chapter-transition-blank-page__message">${escapeAttr(transitionBlankText).replace(/\n/g, '<br>')}</div>`
            : '<div style="height: 1px;"></div>';

        return `
            <section class="chapter-transition-blank-page ${modeClass}"${chapterId ? ` data-chapter-id="${chapterId}"` : ''} data-transition-blank-mode="${escapeAttr(transitionBlankMode)}" data-transition-blank-text="${escapeAttr(transitionBlankText)}" aria-hidden="${transitionBlankMode === 'intentional_text' ? 'false' : 'true'}" style="page: ${pageName} !important;">
                ${innerHtml}
            </section>
        `;
    };
    const forcedTransitionBlankIds = new Set(
        Array.isArray(paginationOptions.forceTransitionBlankChapterIds)
            ? paginationOptions.forceTransitionBlankChapterIds.map(String)
            : []
    );

    const getEffectiveOpeningPageMode = window.getEffectiveOpeningPageMode;
    const getEffectiveChapterImageMode = window.getEffectiveChapterImageMode;
    const getEffectiveChapterImageEnabled = window.getEffectiveChapterImageEnabled;
    const shouldSeparateChapterOpening = window.shouldSeparateChapterOpening;
    const chapterUsesSeparateOpeningPage = (chapter) => shouldSeparateChapterOpening(chapter, settings);
    const chapterHasLeadingImagePage = window.chapterHasLeadingImagePage;

    const escapeAttr = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#039;');

    const buildChapterImagePageSection = (chapter, chapterIndex, isSingleChapterPreview = false) => {
        if (!chapter || !chapterHasLeadingImagePage(chapter, settings)) {
            return '';
        }

        const imageMode = getEffectiveChapterImageMode(chapter, settings);
        const imageEnabled = getEffectiveChapterImageEnabled(chapter, settings);
        const imageUrl = String(chapter.chapter_image_url || settings.chapter_image_url || '').trim();
        const imageWidth = Math.min(Math.max(parseFloat(chapter.chapter_image_inner_width || settings.chapter_image_inner_width || 100) || 100, 10), 100);
        const imageInnerHeader = chapter.chapter_image_inner_header !== undefined && chapter.chapter_image_inner_header !== ''
            ? String(chapter.chapter_image_inner_header) === '1'
            : settings.chapter_image_inner_header == 1;
        const imageInnerFooter = chapter.chapter_image_inner_footer !== undefined && chapter.chapter_image_inner_footer !== ''
            ? String(chapter.chapter_image_inner_footer) === '1'
            : settings.chapter_image_inner_footer == 1;
        const hasImage = imageUrl !== '';
        const sectionInlineStyle = imageMode === 'image_inner'
            ? `--chapter-image-inner-width: ${imageWidth}%;`
            : '';
        const sectionClasses = [
            `chapter-image-page-section-${chapter.id}`,
            'pdf-content',
            imageMode === 'image_full_page' ? 'chapter-image-page-section--full' : '',
            imageMode === 'image_inner' ? 'chapter-image-page-section--inner' : '',
            isSingleChapterPreview ? 'single-chapter-image-preview' : ''
        ].filter(Boolean).join(' ');
        let innerHtml = '';
        if (imageEnabled && imageMode === 'image_full_page' && hasImage) {
            innerHtml = `
                <div class="chapter-image-page-full-bleed-layer">
                    <img src="${escapeAttr(imageUrl)}" alt="${escapeAttr(chapter.title || 'Chapter image')}" />
                </div>
            `;
        } else if (imageEnabled && imageMode === 'image_inner' && hasImage) {
            innerHtml = `
                <div class="chapter-image-page-inner">
                    <img src="${escapeAttr(imageUrl)}" alt="${escapeAttr(chapter.title || 'Chapter image')}" />
                </div>
            `;
        } else {
            innerHtml = '<div style="height: 1px;"></div>';
        }

        return `
            <section class="${sectionClasses}" data-chapter-editorial-role="image" data-image-mode="${escapeAttr(imageMode)}" data-image-url="${escapeAttr(imageUrl)}" data-image-inner-header="${imageInnerHeader ? '1' : '0'}" data-image-inner-footer="${imageInnerFooter ? '1' : '0'}" style="${sectionInlineStyle}">
                ${innerHtml}
            </section>
        `;
    };

    const buildFallbackOpeningContent = (chapter, chapterIndex) => {
        if (!chapter || !chapter.title || String(chapter.title).trim() === '' || chapter.is_credits === '1') {
            return '<div class="chapter-parity-blank-page"></div>';
        }

        const safeTitle = String(chapter.title).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        const subtitleText = chapter.subtitle_text && String(chapter.subtitle_text).trim() !== ''
            ? String(chapter.subtitle_text).trim().replace(/\n/g, '<br>')
            : '';
        const subtitleHtml = subtitleText
            ? `<div class="chapter-subtitle">${subtitleText}</div>`
            : '';

        return `
            <div class="chapter-opening-page-block chapter-opening-page-block--blank" style="display: flex !important; flex-direction: column !important; width: 100% !important; min-height: 100% !important; height: 100% !important; flex: 1 1 auto !important; box-sizing: border-box !important;">
                <div class="chapter-opening-content" data-align="${getOpeningPageHorizontalAlign(chapter, settings)}">
                    <h1 class="chapter-main-title" style="margin: 0 !important;">${safeTitle}</h1>
                    ${subtitleHtml}
                </div>
            </div>
        `;
    };

    const buildOpeningPageSection = (chapter, chapterIndex, isSingleChapterPreview = false) => {
        const openingMode = getEffectiveOpeningPageMode(chapter);
        const chapterImageLeadingPage = chapterHasLeadingImagePage(chapter, settings);
        let openingContent = typeof window.buildChapterOpeningHtml === 'function'
            ? window.buildChapterOpeningHtml(
                chapter,
                chapterIndex,
                settings,
                bookState,
                {
                    variant: openingMode === 'blank' ? 'blank-page' : 'standard',
                    forceRenderTitle: true,
                    forceRenderOpeningBlock: true
                }
            )
            : '';

        if (!openingContent || !String(openingContent).trim()) {
            openingContent = buildFallbackOpeningContent(chapter, chapterIndex);
        }

        const openingSectionStyle = openingMode === 'blank'
            ? 'display: flex; width: 100%; min-height: 100%; height: 100%; box-sizing: border-box;'
            : '';
        const openingBreakClass = chapterImageLeadingPage
            ? 'chapter-opening-page-section--after-image'
            : 'chapter-opening-page-section--default';

        return `
        <section class="chapter-opening-page-section-${chapter.id} ${openingBreakClass} pdf-content${isSingleChapterPreview ? ' single-chapter-opening-preview' : ''}" data-chapter-editorial-role="opening" data-opening-mode="${openingMode}" data-has-chapter-image-page="${chapterImageLeadingPage ? '1' : '0'}" style="${openingSectionStyle}">
            ${openingContent}
        </section>
    `;
    };

    const buildMainChapterSection = (chapter, compiledHtml, isSingleChapterPreview = false) => `
        <section class="chapter-section-${chapter.id} pdf-content${isSingleChapterPreview ? ' single-chapter-main-preview' : ''}" data-chapter-editorial-role="content" id="chapter-section-${chapter.id}">
            <div class="chapter-metadata-title" style="visibility: hidden; height: 0; line-height: 0; margin: 0; padding: 0; overflow: hidden; position: absolute;">${chapter.title || 'Sin título'}</div>
            ${compiledHtml}
        </section>
    `;

    const flowPlan = window.buildBookFlowPlan
        ? window.buildBookFlowPlan(isSingleChapterMode, bookState, settings, bookChapterPages, paginationOptions)
        : {
            activeChapter: bookState.chapters.find(c => c.id === bookState.activeChapterId) || null,
            activeIndex: bookState.chapters.findIndex(c => c.id === bookState.activeChapterId),
            firstChapter: bookState.chapters[0] || null,
            firstChapterLength: null,
            flowMode: window.getBookChapterFlowMode
                ? window.getBookChapterFlowMode(settings)
                : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous'),
            singleChapterRule: window.getSingleChapterBookRule
                ? window.getSingleChapterBookRule(bookState, settings)
                : null,
            bookLanguage: typeof window.almadenGetBookLanguage === 'function'
                ? window.almadenGetBookLanguage(settings)
                : String(settings.book_language || settings.content_language || 'es').trim().toLowerCase(),
            startPageNum: 1,
            previewFirstPhysicalPageNumber: 1,
            pageCounterReset: 0,
            needsDummyPage: false,
            needsChapterEndTransitionPage: false,
            bookStartLeadingPageChapterId: ''
        };
    const activeChapter = flowPlan.activeChapter;
    const activeIndex = flowPlan.activeIndex;
    const pageCounterReset = flowPlan.pageCounterReset;
    const needsDummyPage = flowPlan.needsDummyPage;
    const bookStartLeadingPageChapterId = flowPlan.bookStartLeadingPageChapterId;
    let fullBookHTML = '';

    if (isSingleChapterMode) {
        if (activeChapter) {
            if (flowPlan.needsBookStartLeadingPage) {
                fullBookHTML += buildBookStartLeadingPage();
            }

            const chapterImageSection = buildChapterImagePageSection(activeChapter, activeIndex, true);
            if (chapterImageSection) {
                fullBookHTML += chapterImageSection;
            }

            if (chapterUsesSeparateOpeningPage(activeChapter)) {
                fullBookHTML += buildOpeningPageSection(activeChapter, activeIndex, true);
                fullBookHTML += buildMainChapterSection(
                    activeChapter,
                    window.buildChapterHTML(activeChapter, activeIndex, settings, bookState, { includeOpeningBlock: false }),
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

            if (flowPlan.needsChapterEndTransitionPage) {
                fullBookHTML += buildChapterTransitionBlankPage(activeChapter.id);
            }

            if (paginationOptions.forceFinalBlankPage) {
                fullBookHTML += buildBookEndBlankPage();
            }
        } else {
            fullBookHTML = `<div class="book-container" lang="${flowPlan.bookLanguage}">`;
        }
    } else {
        // Full Book Mode: check if the first chapter needs a real leading page.
        fullBookHTML = `<div class="book-container" lang="${flowPlan.bookLanguage}" style="counter-reset: page 0;">`;
        if (needsDummyPage) {
            fullBookHTML += buildBookStartLeadingPage(bookStartLeadingPageChapterId);
        }
        for (let index = 0; index < bookState.chapters.length; index++) {
            const chapter = bookState.chapters[index];
            const chapterImageSection = buildChapterImagePageSection(chapter, index);
            if (chapterImageSection) {
                fullBookHTML += chapterImageSection;
            }
            const compiledHtml = window.buildChapterHTML(chapter, index, settings, bookState, {
                includeOpeningBlock: !chapterUsesSeparateOpeningPage(chapter)
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

            if (index < bookState.chapters.length - 1) {
                if (forcedTransitionBlankIds.has(String(chapter.id))) {
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
        previewFirstPhysicalPageNumber: flowPlan.previewFirstPhysicalPageNumber,
        startPageNum: flowPlan.startPageNum,
        needsDummyPage: flowPlan.needsDummyPage
    };
};
