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
    const forcedTransitionBlankIds = new Set(
        Array.isArray(paginationOptions.forceTransitionBlankChapterIds)
            ? paginationOptions.forceTransitionBlankChapterIds.map(String)
            : []
    );

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
    const getEffectiveChapterImageMode = window.getEffectiveChapterImageMode || function(chapter, settings) {
        const configuredMode = chapter && chapter.chapter_image_mode
            ? String(chapter.chapter_image_mode)
            : (settings && settings.chapter_image_mode ? String(settings.chapter_image_mode) : 'page_blank');
        return ['page_blank', 'image_full_page', 'image_inner'].includes(configuredMode) ? configuredMode : 'page_blank';
    };
    const getEffectiveChapterImageEnabled = window.getEffectiveChapterImageEnabled || function(chapter, settings) {
        if (chapter && chapter.chapter_image_enabled !== undefined && chapter.chapter_image_enabled !== '') {
            return String(chapter.chapter_image_enabled) === '1';
        }
        const configuredMode = getEffectiveChapterImageMode(chapter, settings);
        const chapterImageUrl = chapter && chapter.chapter_image_url ? String(chapter.chapter_image_url).trim() : '';
        const legacySettingsMode = settings && settings.chapter_image_mode ? String(settings.chapter_image_mode) : 'page_blank';
        const legacySettingsUrl = settings && settings.chapter_image_url ? String(settings.chapter_image_url).trim() : '';
        return configuredMode !== 'page_blank' || chapterImageUrl !== '' || legacySettingsMode !== 'page_blank' || legacySettingsUrl !== '';
    };

    const chapterHasOpeningPage = window.chapterHasOpeningPage || function(chapter) {
        const mode = getEffectiveOpeningPageMode(chapter);
        return mode === 'blank' || mode === 'image';
    };
    const shouldSeparateChapterOpening = window.shouldSeparateChapterOpening || function(chapter, settings) {
        const getEffectiveOpeningSeparation = window.getEffectiveOpeningSeparation || function(chapter, settings) {
            const globalSeparate = settings && String(settings.book_separate_opening_content) !== '0';
            if (chapter && chapter.is_toc === '1' && chapter.toc_separate_opening_content !== undefined && chapter.toc_separate_opening_content !== '') {
                return String(chapter.toc_separate_opening_content) !== '0';
            }
            if (chapter && chapter.opening_separate_content !== undefined && chapter.opening_separate_content !== '') {
                return String(chapter.opening_separate_content) !== '0';
            }
            return globalSeparate;
        };

        if (!getEffectiveOpeningSeparation(chapter, settings)) {
            return false;
        }

        const hasVisibleOpeningBlock = !!(chapter
            && chapter.title
            && String(chapter.title).trim() !== ''
            && chapter.hide_title !== '1'
            && chapter.is_credits !== '1');
        const openingMode = getEffectiveOpeningPageMode(chapter);

        return hasVisibleOpeningBlock || openingMode === 'blank' || openingMode === 'image';
    };
    const chapterUsesSeparateOpeningPage = (chapter) => shouldSeparateChapterOpening(chapter, settings);
    const chapterHasLeadingImagePage = window.chapterHasLeadingImagePage
        ? window.chapterHasLeadingImagePage
        : function(chapter, settings) {
            if (chapter && chapter.is_credits === '1') {
                return false;
            }
            if (chapter && chapter.chapter_image_enabled !== undefined && chapter.chapter_image_enabled !== '') {
                if (String(chapter.chapter_image_enabled) !== '1') {
                    return false;
                }
            }
            const startParity = chapter && chapter.start_parity ? chapter.start_parity : 'any';
            if (startParity !== 'even') {
                return false;
            }
            return ['page_blank', 'image_full_page', 'image_inner'].includes(getEffectiveChapterImageMode(chapter, settings));
        };

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
                    <img src="${escapeAttr(imageUrl)}" alt="${escapeAttr(chapter.title || 'Chapter image')}" style="width: ${imageWidth}%; height: auto; display: block;" />
                </div>
            `;
        } else {
            innerHtml = '<div style="height: 1px;"></div>';
        }

        return `
            <section class="${sectionClasses}" data-image-mode="${escapeAttr(imageMode)}" data-image-url="${escapeAttr(imageUrl)}" data-image-inner-header="${imageInnerHeader ? '1' : '0'}" data-image-inner-footer="${imageInnerFooter ? '1' : '0'}">
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
        <section class="chapter-opening-page-section-${chapter.id} ${openingBreakClass} pdf-content${isSingleChapterPreview ? ' single-chapter-opening-preview' : ''}" data-opening-mode="${openingMode}" data-has-chapter-image-page="${chapterImageLeadingPage ? '1' : '0'}" style="${openingSectionStyle}">
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
                        ? (chapterUsesSeparateOpeningPage(activeChapter) || ((firstChapter.start_parity && firstChapter.start_parity !== 'any') ? firstChapter.start_parity : settings.chapter_start_parity) === 'even')
                        : false)
            );
            let cachedPageNum = bookChapterPages ? bookChapterPages[activeChapter.id] : undefined;
            if (needsBookStartLeadingPage) {
                startPageNum = 1;
            } else if (cachedPageNum !== undefined && cachedPageNum !== null) {
                startPageNum = cachedPageNum;
            } else if (chapterHasLeadingImagePage(activeChapter, settings)) {
                startPageNum = 1;
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
            const needsChapterEndTransitionPage = activeIndex < bookState.chapters.length - 1
                && chapterFlowMode === 'left'
                && forcedTransitionBlankIds.has(String(activeChapter.id));

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

            if (needsChapterEndTransitionPage) {
                fullBookHTML += buildChapterTransitionBlankPage(activeChapter.id);
            }

            if (paginationOptions.forceFinalBlankPage) {
                fullBookHTML += buildBookEndBlankPage();
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
                : (chapterUsesSeparateOpeningPage(firstCh) || firstChapterParity === 'even');
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
                const flowMode = window.getBookChapterFlowMode
                    ? window.getBookChapterFlowMode(settings)
                    : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
                if (flowMode === 'left' && forcedTransitionBlankIds.has(String(chapter.id))) {
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
