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
    const chapterIsCredits = (chapter) => typeof window.isCreditsChapter === 'function'
        ? window.isCreditsChapter(chapter)
        : String(chapter && chapter.is_credits || '') === '1';

    const escapeAttr = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/'/g, '&#039;');

    const getOpeningCanvasHeight = () => {
        const geometry = typeof window.resolvePDFGeometry === 'function'
            ? window.resolvePDFGeometry(settings)
            : null;
        const unit = (geometry && geometry.unit) || settings.unit || 'cm';
        const pageHeight = (geometry && geometry.height) || parseFloat(settings.page_height) || 29.7;
        const ptToUnit = (points) => unit === 'cm' ? (points * 2.54 / 72) : (points / 72);
        const valueOr = (value, fallback) => {
            const parsed = parseFloat(value);
            return Number.isFinite(parsed) ? parsed : fallback;
        };
        const headerHeight =
            valueOr(settings.header_margin_top, 1.0) +
            ptToUnit(valueOr(settings.header_font_size, 8.5)) +
            valueOr(settings.header_margin_bottom, 0.5) +
            valueOr(settings.padding_top, 0);
        const footerHeight =
            valueOr(settings.footer_margin_top, 0.5) +
            ptToUnit(valueOr(settings.footer_font_size, 9.0)) +
            valueOr(settings.footer_margin_bottom, 1.0) +
            valueOr(settings.padding_bottom, 0);

        return {
            unit,
            height: Math.max(pageHeight - headerHeight - footerHeight, 1),
        };
    };

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
        if (!chapter || chapterIsCredits(chapter)) {
            return '<div class="chapter-parity-blank-page"></div>';
        }

        const fallbackHtml = typeof window.buildChapterOpeningHtml === 'function'
            ? window.buildChapterOpeningHtml(
                chapter,
                chapterIndex,
                settings,
                bookState,
                {
                    variant: 'blank-page',
                    forceRenderOpeningBlock: true,
                }
            )
            : '';

        if (fallbackHtml && String(fallbackHtml).trim()) {
            return fallbackHtml;
        }

        return '<div class="chapter-parity-blank-page"></div>';
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
                    forceRenderOpeningBlock: true,
                    separateOpeningCanvas: true
                }
            )
            : '';

        if (!openingContent || !String(openingContent).trim()) {
            openingContent = buildFallbackOpeningContent(chapter, chapterIndex);
        }

        const canvas = getOpeningCanvasHeight();
        const alignment = typeof window.getSeparateOpeningPageAlignment === 'function'
            ? window.getSeparateOpeningPageAlignment(settings)
            : { horizontal: 'center', vertical: 'top' };
        const horizontalAlign = ['left', 'center', 'right'].includes(alignment.horizontal)
            ? alignment.horizontal
            : 'center';
        const verticalAlign = alignment.vertical === 'bottom'
            ? 'bottom'
            : (alignment.vertical === 'center' ? 'middle' : 'top');

        const openingCanvasStyle = [
            'position: relative',
            'width: 100%',
            `height: ${canvas.height.toFixed(4)}${canvas.unit}`,
            'margin: 0',
            'padding: 0',
            'box-sizing: border-box',
        ].join('; ');
        const openingPositionerStyle = [
            'position: absolute',
            'display: block',
            'width: fit-content',
            'max-width: 100%',
            'margin: 0',
            'padding: 0',
            'box-sizing: border-box',
            `text-align: ${horizontalAlign}`,
            horizontalAlign === 'left' ? 'left: 0; right: auto' : '',
            horizontalAlign === 'right' ? 'right: 0; left: auto' : '',
            horizontalAlign === 'center' ? 'left: 50%; right: auto' : '',
            verticalAlign === 'top' ? 'top: 0; bottom: auto' : '',
            verticalAlign === 'bottom' ? 'bottom: 0; top: auto' : '',
            verticalAlign === 'middle' ? 'top: 50%; bottom: auto' : '',
            `transform: ${[
                horizontalAlign === 'center' ? 'translateX(-50%)' : '',
                verticalAlign === 'middle' ? 'translateY(-50%)' : '',
            ].filter(Boolean).join(' ') || 'none'}`,
        ].filter(Boolean).join('; ');
        const openingBreakClass = chapterImageLeadingPage
            ? 'chapter-opening-page-section--after-image'
            : 'chapter-opening-page-section--default';

        return `
        <section class="chapter-opening-page-section-${chapter.id} ${openingBreakClass} pdf-content${isSingleChapterPreview ? ' single-chapter-opening-preview' : ''}" data-chapter-editorial-role="opening" data-opening-mode="${openingMode}" data-opening-position="${alignment.combined || `${alignment.horizontal}-${alignment.vertical}`}" data-has-chapter-image-page="${chapterImageLeadingPage ? '1' : '0'}">
            <div class="chapter-opening-canvas" style="${openingCanvasStyle}">
                <div class="chapter-opening-positioner" style="${openingPositionerStyle}">
                    ${openingContent}
                </div>
            </div>
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
            } else if (chapterIsCredits(activeChapter)) {
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
            } else if (chapterIsCredits(chapter)) {
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
