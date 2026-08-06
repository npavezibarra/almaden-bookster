// ============================================================
// MÓDULO: editor-pdf-styles-chapters.js
// Responsabilidad: Generar las reglas de Named Pages y saltos de página
// específicas para cada capítulo (paridad, imágenes de fondo, etc.)
// ============================================================

function getPDFStylesChapters(settings, toPx) {
    const bookTitle = bookState.title || 'Libro';
    let chapterCSSRules = '';
    const firstPageHeaderShow = settings.first_page_header_show === undefined ? true : String(settings.first_page_header_show) !== '0';
    const firstPageFooterShow = settings.first_page_footer_show === undefined ? true : String(settings.first_page_footer_show) !== '0';
    const unit = settings.unit || 'cm';
    const resolveMargin = (value, fallback) => {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    };
    const getFooterMarginBox = window.getFooterMarginBox || function(align, isEven) {
        if (align === 'center') return 'bottom-center';
        if (align === 'left') return 'bottom-left';
        if (align === 'right') return 'bottom-right';
        if (align === 'outer') return isEven ? 'bottom-left' : 'bottom-right';
        if (align === 'inner') return isEven ? 'bottom-right' : 'bottom-left';
        return 'bottom-center';
    };
    const getFooterContent = window.getFooterContent || function(type, isEven, bookTitle, settings) {
        if (type === 'blank') return '""';
        if (type === 'page_number') return 'counter(page)';
        if (type === 'book_title') return `"${bookTitle.replace(/"/g, '\\"')}"`;
        if (type === 'chapter_title') return 'string(chapter-title)';
        if (type === 'author') return '"Autor"';
        if (type === 'custom') {
            const customText = isEven ? (settings.footer_even_custom || '') : (settings.footer_odd_custom || '');
            return `"${customText.replace(/"/g, '\\"')}"`;
        }
        return '""';
    };
    const getHeaderMarginBox = window.getMarginBox || function(align, isEven) {
        if (align === 'center') return 'top-center';
        if (align === 'left') return 'top-left';
        if (align === 'right') return 'top-right';
        if (align === 'outer') return isEven ? 'top-left' : 'top-right';
        if (align === 'inner') return isEven ? 'top-right' : 'top-left';
        return 'top-center';
    };
    const getFirstPageHeaderContent = window.getFirstPageHeaderContent || function(type, customText, shouldHide, bookTitle) {
        if (shouldHide) return '""';
        if (type === 'blank') return '""';
        if (type === 'book_title') return `"${bookTitle.replace(/"/g, '\\"')}"`;
        if (type === 'chapter_title') return 'string(chapter-title)';
        if (type === 'page_number') return 'counter(page)';
        if (type === 'author') return '"Autor"';
        if (type === 'custom') return `"${String(customText || '').replace(/"/g, '\\"')}"`;
        return '""';
    };
    const getFirstPageFooterContent = window.getFirstPageFooterContent || function(type, customText, shouldHide, bookTitle) {
        if (shouldHide) return '""';
        if (type === 'blank') return '""';
        if (type === 'page_number') return 'counter(page)';
        if (type === 'book_title') return `"${bookTitle.replace(/"/g, '\\"')}"`;
        if (type === 'chapter_title') return 'string(chapter-title)';
        if (type === 'author') return '"Autor"';
        if (type === 'custom') return `"${String(customText || '').replace(/"/g, '\\"')}"`;
        return '""';
    };
    const getEffectiveOpeningPageMode = window.getEffectiveOpeningPageMode;
    const shouldSeparateChapterOpening = window.shouldSeparateChapterOpening;

    if (bookState.chapters) {
        bookState.chapters.forEach((ch, idx) => {
            const parityImageUrl = ch.parity_image;
            const openingPageMode = getEffectiveOpeningPageMode(ch);
            const separateOpening = shouldSeparateChapterOpening(ch, settings);
            const firstHeaderType = (ch.first_page_header_type && ch.first_page_header_type !== 'global')
                ? ch.first_page_header_type
                : (settings.first_page_header_type || 'blank');
            const firstHeaderCustom = ch.first_page_header_custom || settings.first_page_header_custom || '';
            const firstPageFooterType = (ch.first_page_footer_type && ch.first_page_footer_type !== 'global')
                ? ch.first_page_footer_type
                : (settings.first_page_footer_type || 'page_number');
            const firstPageFooterCustom = ch.first_page_footer_custom || settings.first_page_footer_custom || '';
            const headerAlign = settings.header_align || 'center';
            const footerAlign = settings.footer_align || 'center';
            const footerEvenType = settings.footer_even_type || 'page_number';
            const footerOddType = settings.footer_odd_type || 'page_number';
            const effectiveStartParity = window.getChapterStartParity
                ? window.getChapterStartParity(ch, settings)
                : (ch.start_parity || 'any');
            const firstPageFooterBox = getFooterMarginBox(footerAlign, effectiveStartParity === 'even');
            const hideTocHeader = ch.is_toc === '1' && ch.toc_hide_header !== '0';
            const hideCreditsHeader = ch.is_credits === '1' && ch.credits_hide_header === '1';
            const hideCreditsPageNumber = ch.is_credits === '1' && ch.credits_hide_page_number === '1';
            const hideTocPageNumber = ch.is_toc === '1' && ch.toc_hide_page_numbers !== '0';
            const hideChapterPageNumber = hideCreditsPageNumber || hideTocPageNumber;
            const hideHeader = ch.hide_header === '1' || ch.hide_all_headers_footers === '1';
            const hideFooter = ch.hide_footer === '1' || ch.hide_all_headers_footers === '1';
            const isEditorialChapter = ch.is_toc !== '1' && ch.is_credits !== '1';
            // The opening is the page registered in the TOC, so normal
            // chapters always show its folio even when generic first-page
            // footer settings request a blank treatment.
            const editorialFirstPageFooterType = isEditorialChapter
                ? 'page_number'
                : firstPageFooterType;
            const chapterImageMode = window.getEffectiveChapterImageMode
                ? window.getEffectiveChapterImageMode(ch, settings)
                : ((ch.chapter_image_mode || settings.chapter_image_mode || 'page_blank'));
            const hasChapterImagePage = window.chapterHasLeadingImagePage
                ? window.chapterHasLeadingImagePage(ch, settings)
                : false;
            const chapterFlowMode = window.getBookChapterFlowMode
                ? window.getBookChapterFlowMode(settings)
                : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');

            if (hasChapterImagePage) {
                const chapterImageUrl = String(ch.chapter_image_url || settings.chapter_image_url || '').trim();
                const imageInnerWidth = Math.min(Math.max(parseFloat(ch.chapter_image_inner_width || settings.chapter_image_inner_width || 100) || 100, 10), 100);
                const imageInnerHeader = ch.chapter_image_inner_header !== undefined && ch.chapter_image_inner_header !== ''
                    ? String(ch.chapter_image_inner_header) === '1'
                    : settings.chapter_image_inner_header == 1;
                const imageInnerFooter = ch.chapter_image_inner_footer !== undefined && ch.chapter_image_inner_footer !== ''
                    ? String(ch.chapter_image_inner_footer) === '1'
                    : settings.chapter_image_inner_footer == 1;
                const safeChapterImageUrl = chapterImageUrl.replace(/"/g, '\\"');
                const firstHeaderType = (ch.first_page_header_type && ch.first_page_header_type !== 'global')
                    ? ch.first_page_header_type
                    : (settings.first_page_header_type || 'blank');
                const firstHeaderCustom = ch.first_page_header_custom || settings.first_page_header_custom || '';
                const firstPageFooterType = (ch.first_page_footer_type && ch.first_page_footer_type !== 'global')
                    ? ch.first_page_footer_type
                    : (settings.first_page_footer_type || 'page_number');
                const firstPageFooterCustom = ch.first_page_footer_custom || settings.first_page_footer_custom || '';
                const headerAlign = settings.header_align || 'center';
                const footerAlign = settings.footer_align || 'center';
                const footerEvenType = settings.footer_even_type || 'page_number';
                const footerOddType = settings.footer_odd_type || 'page_number';
                const hideTocHeader = ch.is_toc === '1' && ch.toc_hide_header !== '0';
                const hideCreditsHeader = ch.is_credits === '1' && ch.credits_hide_header === '1';
                const hideCreditsPageNumber = ch.is_credits === '1' && ch.credits_hide_page_number === '1';
                const hideTocPageNumber = ch.is_toc === '1' && ch.toc_hide_page_numbers !== '0';
                const hideChapterPageNumber = hideCreditsPageNumber || hideTocPageNumber;
                const effectiveImageInnerHeader = imageInnerHeader && !hideHeader;
                const effectiveImageInnerFooter = imageInnerFooter && !hideFooter;
                const openingPageHeaderHidden = hideHeader || hideTocHeader || hideCreditsHeader || !firstPageHeaderShow;
                const openingPageFooterHidden = hideFooter || hideChapterPageNumber || !firstPageFooterShow;
                const openingPageHeaderContent = getFirstPageHeaderContent(firstHeaderType, firstHeaderCustom, openingPageHeaderHidden, bookTitle);
                const openingPageFooterContent = getFirstPageFooterContent(firstPageFooterType, firstPageFooterCustom, openingPageFooterHidden, bookTitle);
                const headerContent = (chapterImageMode === 'image_inner' && effectiveImageInnerHeader)
                    ? `
                        @top-left { content: ""; }
                        @top-center { content: ""; }
                        @top-right { content: ""; }
                        @${getHeaderMarginBox(headerAlign, true)} { content: ${openingPageHeaderContent}; }
                    `
                    : `
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                    `;
                const footerContent = (chapterImageMode === 'image_inner' && effectiveImageInnerFooter)
                    ? `
                        @bottom-left { content: ""; }
                        @bottom-center { content: ""; }
                        @bottom-right { content: ""; }
                        @bottom-${getFooterMarginBox(footerAlign, true).replace('bottom-', '')} { content: ${openingPageFooterContent}; }
                        @bottom-${getFooterMarginBox(footerAlign, false).replace('bottom-', '')} { content: ${openingPageFooterContent}; }
                    `
                    : `
                        @bottom-left { content: "" !important; }
                        @bottom-center { content: "" !important; }
                        @bottom-right { content: "" !important; }
                    `;
                const imageBackgroundRules = chapterImageMode === 'image_full_page' && chapterImageUrl
                    ? `
                        .pagedjs_chapter-${ch.id}-image_page {
                            overflow: visible !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_pagebox,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_area {
                            overflow: visible !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page .chapter-image-page-full-bleed-layer {
                            position: absolute !important;
                            top: calc(-1 * var(--pagedjs-margin-top) - var(--bookster-image-bleed-top, var(--pagedjs-bleed-top))) !important;
                            bottom: calc(-1 * var(--pagedjs-margin-bottom) - var(--bookster-image-bleed-bottom, var(--pagedjs-bleed-bottom))) !important;
                            z-index: 0 !important;
                            overflow: hidden !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page.pagedjs_left_page .chapter-image-page-full-bleed-layer,
                        .pagedjs_chapter-${ch.id}-image_page.bookster-left-page .chapter-image-page-full-bleed-layer {
                            left: calc(-1 * var(--pagedjs-margin-left) - var(--bookster-image-bleed-left, var(--pagedjs-bleed-left-left))) !important;
                            right: calc(-1 * var(--pagedjs-margin-right) - var(--bookster-image-bleed-right, 0px)) !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page.pagedjs_right_page .chapter-image-page-full-bleed-layer,
                        .pagedjs_chapter-${ch.id}-image_page.bookster-right-page .chapter-image-page-full-bleed-layer {
                            left: calc(-1 * var(--pagedjs-margin-left) - var(--bookster-image-bleed-left, 0px)) !important;
                            right: calc(-1 * var(--pagedjs-margin-right) - var(--bookster-image-bleed-right, var(--pagedjs-bleed-right-right))) !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page .chapter-image-page-full-bleed-layer img {
                            width: 100% !important;
                            height: 100% !important;
                            object-fit: cover !important;
                            object-position: center center !important;
                            display: block !important;
                        }
                    `
                    : '';
                const innerPageRules = chapterImageMode === 'image_inner'
                    ? `
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        width: 100% !important;
                        min-height: 100% !important;
                        height: 100% !important;
                        box-sizing: border-box !important;
                    `
                    : '';
                chapterCSSRules += `
                    .chapter-image-page-section-${ch.id} {
                        page: chapter-${ch.id}-image;
                        /* Transition blanks are emitted explicitly by the
                         * compiler. A forced left break makes Paged.js add an
                         * invisible implicit page and skips a physical folio. */
                        break-before: page;
                        page-break-before: always;
                        break-after: page;
                        display: block !important;
                        position: relative !important;
                        width: 100% !important;
                        min-height: calc(
                            var(--pagedjs-pagebox-height)
                            - var(--pagedjs-margin-top)
                            - var(--pagedjs-margin-bottom)
                            - 1px
                        ) !important;
                        height: calc(
                            var(--pagedjs-pagebox-height)
                            - var(--pagedjs-margin-top)
                            - var(--pagedjs-margin-bottom)
                            - 1px
                        ) !important;
                        box-sizing: border-box !important;
                        ${innerPageRules}
                    }
                    .chapter-image-page-section-${ch.id}.chapter-image-page-section--inner {
                        position: relative !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        overflow: visible !important;
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-inner {
                        ${chapterImageMode === 'image_inner' ? `
                            width: var(--chapter-image-inner-width, 100%) !important;
                            max-width: 100% !important;
                            display: block !important;
                            height: auto !important;
                            margin: 0 auto !important;
                        ` : ''}
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-inner img {
                        ${chapterImageMode === 'image_inner' ? `
                            width: 100% !important;
                            height: auto !important;
                            object-fit: contain !important;
                            display: block !important;
                        ` : ''}
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-full-bleed-layer {
                        ${chapterImageMode === 'image_full_page' ? 'display: block !important;' : ''}
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-full-bleed-layer img {
                        ${chapterImageMode === 'image_full_page' ? 'display: block !important;' : ''}
                    }
                    @page chapter-${ch.id}-image {
                        ${headerContent}
                        ${footerContent}
                    }
                    ${imageBackgroundRules}
                    ${chapterImageMode === 'image_inner' ? `
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin {
                            visibility: ${effectiveImageInnerHeader || effectiveImageInnerFooter ? 'visible' : 'hidden'} !important;
                        }
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top-left-corner-holder,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top-right-corner-holder,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom-left-corner-holder,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom-right-corner-holder {
                            ${effectiveImageInnerHeader || effectiveImageInnerFooter ? '' : 'display: none !important;'}
                        }
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top-left,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top-center,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-top-right {
                            ${effectiveImageInnerHeader ? '' : 'display: none !important;'}
                        }
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom-left,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom-center,
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-bottom-right {
                            ${effectiveImageInnerFooter ? '' : 'display: none !important;'}
                        }
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin-content {
                            ${!effectiveImageInnerHeader && !effectiveImageInnerFooter ? 'display: none !important;' : ''}
                        }
                    ` : `
                        .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin {
                            visibility: hidden !important;
                        }
                    `}
                `;
            }
            
            if (shouldSeparateChapterOpening(ch, settings)) {
                const mode = ch.parity_image_mode || 'content';
                let parityPageStyle = '';
                const openingHeaderBoxEven = getHeaderMarginBox(settings.header_align || 'center', true);
                const openingHeaderBoxOdd = getHeaderMarginBox(settings.header_align || 'center', false);
                const openingFooterBoxEven = getFooterMarginBox(settings.footer_align || 'center', true);
                const openingFooterBoxOdd = getFooterMarginBox(settings.footer_align || 'center', false);
                const openingHeaderContent = getFirstPageHeaderContent(
                    ch.first_page_header_type && ch.first_page_header_type !== 'global'
                        ? ch.first_page_header_type
                        : (settings.first_page_header_type || 'blank'),
                    ch.first_page_header_custom || settings.first_page_header_custom || '',
                    hideTocHeader || hideCreditsHeader || !firstPageHeaderShow,
                    bookTitle
                );
                const openingFooterContent = getFirstPageFooterContent(
                    isEditorialChapter
                        ? 'page_number'
                        : ((ch.first_page_footer_type && ch.first_page_footer_type !== 'global')
                            ? ch.first_page_footer_type
                            : (settings.first_page_footer_type || 'page_number')),
                    ch.first_page_footer_custom || settings.first_page_footer_custom || '',
                    hideChapterPageNumber || (!isEditorialChapter && !firstPageFooterShow),
                    bookTitle
                );
                if (openingPageMode === 'image' && mode === 'bleed') {
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: cover !important;
                        background-position: center !important;
                        background-repeat: no-repeat !important;
                    `;
                } else if (openingPageMode === 'image' && mode === 'custom') {
                    const w = ch.parity_image_width ? `${ch.parity_image_width}%` : '100%';
                    const h = ch.parity_image_height ? `${ch.parity_image_height}%` : 'auto';
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: ${w} ${h} !important;
                        background-repeat: no-repeat !important;
                        background-position: center !important;
                    `;
                } else if (openingPageMode === 'image') { // content
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: contain !important;
                        background-repeat: no-repeat !important;
                        background-position: center !important;
                    `;
                }
                
                const openingBreakBefore = hasChapterImagePage
                    ? 'page'
                    : (chapterFlowMode === 'left' && idx === 0 ? 'left' : 'page');
                const openingPageBreakBefore = openingBreakBefore === 'left' ? 'left' : 'always';
                chapterCSSRules += `
                    .chapter-opening-page-section-${ch.id} {
                        page: chapter-${ch.id}-opening;
                        break-before: ${openingBreakBefore};
                        page-break-before: ${openingPageBreakBefore};
                        break-after: page;
                        display: block !important;
                        width: 100% !important;
                        box-sizing: border-box !important;
                        columns: initial !important;
                        column-count: initial !important;
                        column-width: auto !important;
                    }
                    .chapter-opening-page-section-${ch.id} .chapter-opening-canvas {
                        position: relative !important;
                        width: 100% !important;
                        box-sizing: border-box !important;
                    }
                    .chapter-opening-page-section-${ch.id} .chapter-opening-positioner {
                        position: absolute !important;
                        display: block !important;
                        margin: 0 !important;
                        padding: 0 !important;
                        box-sizing: border-box !important;
                    }
                    .chapter-opening-page-section-${ch.id} .chapter-opening-positioner > .chapter-opening-block,
                    .chapter-opening-page-section-${ch.id} .chapter-opening-positioner > .chapter-opening-page-block {
                        display: inline-block !important;
                        width: fit-content !important;
                        max-width: 100% !important;
                        margin: 0 !important;
                    }
                    .chapter-section-${ch.id} {
                        page: chapter-${ch.id};
                        break-before: page;
                    }
                    
                    @page chapter-${ch.id}-opening {
                        ${parityPageStyle}
                    }
                    @page chapter-${ch.id}-opening:left {
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                        @bottom-left { content: "" !important; }
                        @bottom-center { content: "" !important; }
                        @bottom-right { content: "" !important; }
                        @${openingHeaderBoxEven} { content: ${openingHeaderContent}; }
                        @${openingFooterBoxEven} { content: ${openingFooterContent}; }
                        ${parityPageStyle}
                    }
                    @page chapter-${ch.id}-opening:right {
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                        @bottom-left { content: "" !important; }
                        @bottom-center { content: "" !important; }
                        @bottom-right { content: "" !important; }
                        @${openingHeaderBoxOdd} { content: ${openingHeaderContent}; }
                        @${openingFooterBoxOdd} { content: ${openingFooterContent}; }
                        ${parityPageStyle}
                    }
                    
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin {
                        visibility: ${firstPageHeaderShow || firstPageFooterShow || isEditorialChapter ? 'visible' : 'hidden'} !important;
                    }
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-top,
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-top-left-corner-holder,
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-top-right-corner-holder {
                        ${firstPageHeaderShow ? '' : 'display: none !important;'}
                    }
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-bottom,
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-bottom-left-corner-holder,
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin-bottom-right-corner-holder {
                        ${firstPageFooterShow || isEditorialChapter ? '' : 'display: none !important;'}
                    }
                `;
                
                if (openingPageMode === 'image' && mode === 'bleed') {
                    chapterCSSRules += `
                        .pagedjs_chapter-${ch.id}-opening_page {
                            ${parityPageStyle}
                        }
                    `;
                } else if (openingPageMode === 'image') {
                    chapterCSSRules += `
                        .pagedjs_chapter-${ch.id}-opening_page .pagedjs_area {
                            ${parityPageStyle}
                        }
                    `;
                }
            } else {
                const bookFlowMode = settings.book_chapter_flow_mode === 'left'
                    ? 'left'
                    : (settings.chapter_start_parity === 'even' ? 'left' : 'continuous');
                let chapterStartParity = window.getChapterStartParity
                    ? window.getChapterStartParity(ch, settings)
                    : (ch.is_toc === '1'
                        ? 'even'
                        : ((ch.start_parity && ch.start_parity !== 'any') ? ch.start_parity : (bookFlowMode === 'left' ? 'even' : settings.chapter_start_parity)));
                let breakBefore = 'page';

                if (idx === 0 && ch.is_toc === '1') {
                    breakBefore = 'page';
                } else if (hasChapterImagePage) {
                    // The image owns the first page and already ends it.
                    // The opening/content must continue on the next page.
                    breakBefore = 'page';
                } else if (idx > 0) {
                    if (bookFlowMode === 'left') {
                        // The compiler adds an explicit transition blank only
                        // when the preceding chapter ends on an even page.
                        // A forced left break here would create an implicit
                        // Paged.js blank and bypass that editorial decision.
                        breakBefore = 'page';
                    } else if (chapterStartParity === 'odd') {
                        breakBefore = 'right';
                    } else if (chapterStartParity === 'even') {
                        breakBefore = 'left';
                    }
                } else {
                    if (chapterStartParity === 'even') {
                        breakBefore = 'left';
                    } else {
                        breakBefore = 'none';
                    }
                }
                
                chapterCSSRules += `
                    .chapter-section-${ch.id} {
                        page: chapter-${ch.id};
                        ${breakBefore !== 'none' ? `break-before: ${breakBefore}; page-break-before: ${breakBefore === 'left' ? 'left' : 'always'};` : ''}
                    }
                `;
            }
            
            // Reglas de cabecera específicas de la primera página del capítulo
            const firstPageHeaderHidden = hideTocHeader || hideCreditsHeader || (!separateOpening && !firstPageHeaderShow);
            const firstPageFooterHidden = hideChapterPageNumber || (!isEditorialChapter && !separateOpening && !firstPageFooterShow);
            const firstPageHeaderContent = getFirstPageHeaderContent(firstHeaderType, firstHeaderCustom, firstPageHeaderHidden, bookTitle);
            const firstPageFooterContent = getFirstPageFooterContent(editorialFirstPageFooterType, firstPageFooterCustom, firstPageFooterHidden, bookTitle);
            const globalMarginTop = resolveMargin(settings.margin_top, 2.5);
            const globalMarginBottom = resolveMargin(settings.margin_bottom, 2.5);
            const creditsMarginTop = ch.is_credits === '1'
                ? resolveMargin(ch.credits_margin_top, globalMarginTop)
                : globalMarginTop;
            const creditsMarginBottom = ch.is_credits === '1'
                ? resolveMargin(ch.credits_margin_bottom, globalMarginBottom)
                : globalMarginBottom;

            if (ch.is_credits === '1') {
                chapterCSSRules += `
                    @page chapter-${ch.id} {
                        margin-top: ${creditsMarginTop}${unit};
                        margin-bottom: ${creditsMarginBottom}${unit};
                    }
                `;
            }
            
            chapterCSSRules += `
                @page chapter-${ch.id}:first {
                    ${ch.is_credits === '1' ? `margin-top: ${creditsMarginTop}${unit};
                    margin-bottom: ${creditsMarginBottom}${unit};` : ''}
                    @top-left { content: "" !important; }
                    @top-center { content: "" !important; }
                    @top-right { content: ""; }
                    @bottom-left { content: ""; }
                    @bottom-center { content: ""; }
                    @bottom-right { content: ""; }
                    @${firstPageFooterBox} { content: ${firstPageFooterContent}; }
                }
                @page chapter-${ch.id}:first:left {
                    ${ch.is_credits === '1' ? `margin-top: ${creditsMarginTop}${unit};
                    margin-bottom: ${creditsMarginBottom}${unit};` : ''}
                    @${getHeaderMarginBox(headerAlign, true)} { content: ${firstPageHeaderContent}; }
                    @${getFooterMarginBox(footerAlign, true)} { content: ${firstPageFooterContent}; }
                }
                @page chapter-${ch.id}:first:right {
                    ${ch.is_credits === '1' ? `margin-top: ${creditsMarginTop}${unit};
                    margin-bottom: ${creditsMarginBottom}${unit};` : ''}
                    @${getHeaderMarginBox(headerAlign, false)} { content: ${firstPageHeaderContent}; }
                    @${getFooterMarginBox(footerAlign, false)} { content: ${firstPageFooterContent}; }
                }
                ${hideFooter ? `
                @page chapter-${ch.id} {
                    @bottom-left { content: "" !important; }
                    @bottom-center { content: "" !important; }
                    @bottom-right { content: "" !important; }
                }
                ` : ''}
                ${hideHeader ? `
                @page chapter-${ch.id} {
                    @top-left { content: "" !important; }
                    @top-center { content: "" !important; }
                    @top-right { content: "" !important; }
                }
                ` : ''}
                ${hideTocPageNumber ? `
                @page chapter-${ch.id} {
                    @bottom-left { content: "" !important; }
                    @bottom-center { content: "" !important; }
                    @bottom-right { content: "" !important; }
                }
                ` : ''}
                ${hideTocHeader ? `
                @page chapter-${ch.id} {
                    @top-left { content: "" !important; }
                    @top-center { content: "" !important; }
                    @top-right { content: "" !important; }
                }
                ` : ''}
                @page chapter-${ch.id}:blank {
                    @top-left { content: "" !important; }
                    @top-center { content: "" !important; }
                    @top-right { content: "" !important; }
                    @bottom-left { content: "" !important; }
                    @bottom-center { content: "" !important; }
                    @bottom-right { content: "" !important; }
                }
                .pagedjs_chapter-${ch.id}_page.pagedjs_blank_page .pagedjs_margin {
                    visibility: hidden !important;
                }
            `;
            
        });
    }

    chapterCSSRules += `
        @page chapter-blank-page {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }
        .chapter-metadata-title {
            string-set: chapter-title content();
        }
    `;

    return chapterCSSRules;
}
