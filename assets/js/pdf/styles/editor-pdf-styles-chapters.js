// ============================================================
// MÓDULO: editor-pdf-styles-chapters.js
// Responsabilidad: Generar las reglas de Named Pages y saltos de página
// específicas para cada capítulo (paridad, imágenes de fondo, etc.)
// ============================================================

function getPDFStylesChapters(settings, toPx) {
    const bookTitle = bookState.title || 'Libro';
    let chapterCSSRules = '';
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

    if (bookState.chapters) {
        bookState.chapters.forEach((ch, idx) => {
            const parityImageUrl = ch.parity_image;
            const openingPageMode = getEffectiveOpeningPageMode(ch);
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
                const footerAlign = settings.footer_align || 'center';
                const footerEvenType = settings.footer_even_type || 'page_number';
                const footerOddType = settings.footer_odd_type || 'page_number';
                const hideTocHeader = ch.is_toc === '1' && ch.toc_hide_header !== '0';
                const hideCreditsHeader = ch.is_credits === '1' && ch.credits_hide_header === '1';
                const hideCreditsPageNumber = ch.is_credits === '1' && ch.credits_hide_page_number === '1';
                const hideTocPageNumber = ch.is_toc === '1' && ch.toc_hide_page_numbers !== '0';
                const hideChapterPageNumber = hideCreditsPageNumber || hideTocPageNumber;
                const headerContent = (chapterImageMode === 'image_inner' && imageInnerHeader)
                    ? `
                        @top-left { content: ${(hideTocHeader || hideCreditsHeader) ? '""' : (firstHeaderType === 'custom' ? `"${firstHeaderCustom.replace(/"/g, '\\"')}"` : '""')}; }
                        @top-center { content: ${(hideTocHeader || hideCreditsHeader) ? '""' : (firstHeaderType === 'chapter_title' ? 'string(chapter-title)' : (firstHeaderType === 'book_title' ? `"${bookState.title.replace(/"/g, '\\"')}"` : '""'))}; }
                        @top-right { content: ""; }
                    `
                    : `
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                    `;
                const footerContent = (chapterImageMode === 'image_inner' && imageInnerFooter)
                    ? `
                        @bottom-${getFooterMarginBox(footerAlign, true).replace('bottom-', '')} { content: ${hideChapterPageNumber ? '""' : getFooterContent(footerEvenType, true, bookState.title, settings)}; }
                        @bottom-${getFooterMarginBox(footerAlign, false).replace('bottom-', '')} { content: ${hideChapterPageNumber ? '""' : getFooterContent(footerOddType, false, bookState.title, settings)}; }
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
                        break-before: left;
                        page-break-before: left;
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
                        ${innerPageRules}
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-inner {
                        ${chapterImageMode === 'image_inner' ? 'display: flex !important; align-items: center !important; justify-content: center !important; width: 100% !important; height: 100% !important;' : ''}
                    }
                    .chapter-image-page-section-${ch.id} .chapter-image-page-inner img {
                        ${chapterImageMode === 'image_inner' ? `width: ${imageInnerWidth}% !important; height: auto !important; object-fit: contain !important; display: block !important;` : ''}
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
                    .pagedjs_chapter-${ch.id}-image_page .pagedjs_margin {
                        visibility: ${chapterImageMode === 'image_inner' && (imageInnerHeader || imageInnerFooter) ? 'visible' : 'hidden'} !important;
                    }
                `;
            }
            
            if (shouldSeparateChapterOpening(ch, settings)) {
                const mode = ch.parity_image_mode || 'content';
                let parityPageStyle = '';
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
                
                chapterCSSRules += `
                    .chapter-opening-page-section-${ch.id} {
                        page: chapter-${ch.id}-opening;
                        break-before: ${hasChapterImagePage ? 'page' : (chapterFlowMode === 'left' ? 'left' : 'page')};
                        page-break-before: ${hasChapterImagePage ? 'always' : (chapterFlowMode === 'left' ? 'left' : 'always')};
                        break-after: page;
                        display: flex !important;
                        flex-direction: column !important;
                        justify-content: center !important;
                        align-items: stretch !important;
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
                    }
                    .chapter-section-${ch.id} {
                        page: chapter-${ch.id};
                        break-before: page;
                    }
                    
                    @page chapter-${ch.id}-opening {
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                        @bottom-left { content: "" !important; }
                        @bottom-center { content: "" !important; }
                        @bottom-right { content: "" !important; }
                        ${parityPageStyle}
                    }
                    
                    .pagedjs_chapter-${ch.id}-opening_page .pagedjs_margin {
                        visibility: hidden !important;
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
                } else if (idx > 0) {
                    if (chapterStartParity === 'odd') {
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
            const firstHeaderType = (ch.first_page_header_type && ch.first_page_header_type !== 'global') ? ch.first_page_header_type : (settings.first_page_header_type || 'blank');
            const firstHeaderCustom = ch.first_page_header_custom || settings.first_page_header_custom || '';
            const footerAlign = settings.footer_align || 'center';
            const footerEvenType = settings.footer_even_type || 'page_number';
            const footerOddType = settings.footer_odd_type || 'page_number';
            const hideTocHeader = ch.is_toc === '1' && ch.toc_hide_header !== '0';
            const hideCreditsHeader = ch.is_credits === '1' && ch.credits_hide_header === '1';
            const hideCreditsPageNumber = ch.is_credits === '1' && ch.credits_hide_page_number === '1';
            const hideTocPageNumber = ch.is_toc === '1' && ch.toc_hide_page_numbers !== '0';
            const hideChapterPageNumber = hideCreditsPageNumber || hideTocPageNumber;
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
                    @top-left { content: ${(hideTocHeader || hideCreditsHeader) ? '""' : (firstHeaderType === 'custom' ? `"${firstHeaderCustom.replace(/"/g, '\\"')}"` : '""')}; }
                    @top-center { content: ${(hideTocHeader || hideCreditsHeader) ? '""' : (firstHeaderType === 'chapter_title' ? 'string(chapter-title)' : (firstHeaderType === 'book_title' ? `"${bookTitle.replace(/"/g, '\\"')}"` : '""'))}; }
                    @top-right { content: ""; }
                }
                @page chapter-${ch.id}:first:left {
                    ${ch.is_credits === '1' ? `margin-top: ${creditsMarginTop}${unit};
                    margin-bottom: ${creditsMarginBottom}${unit};` : ''}
                    @bottom-${getFooterMarginBox(footerAlign, true).replace('bottom-', '')} { content: ${hideChapterPageNumber ? '""' : getFooterContent(footerEvenType, true, bookTitle, settings)}; }
                }
                @page chapter-${ch.id}:first:right {
                    ${ch.is_credits === '1' ? `margin-top: ${creditsMarginTop}${unit};
                    margin-bottom: ${creditsMarginBottom}${unit};` : ''}
                    @bottom-${getFooterMarginBox(footerAlign, false).replace('bottom-', '')} { content: ${hideChapterPageNumber ? '""' : getFooterContent(footerOddType, false, bookTitle, settings)}; }
                }
                ${hideCreditsPageNumber ? `
                @page chapter-${ch.id} {
                    @bottom-left { content: "" !important; }
                    @bottom-center { content: "" !important; }
                    @bottom-right { content: "" !important; }
                }
                ` : ''}
                ${hideCreditsHeader ? `
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
            
            // Ocultar cabeceras/pies si está configurado en el capítulo
            if (ch.hide_all_headers_footers === '1') {
                chapterCSSRules += `
                    @page chapter-${ch.id} {
                        @top-left { content: ""; }
                        @top-center { content: ""; }
                        @top-right { content: ""; }
                        @bottom-left { content: ""; }
                        @bottom-center { content: ""; }
                        @bottom-right { content: ""; }
                    }
                `;
            }
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
