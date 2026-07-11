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

    if (bookState.chapters) {
        bookState.chapters.forEach((ch, idx) => {
            const parityImageUrl = ch.parity_image;
            const openingPageMode = getEffectiveOpeningPageMode(ch);
            
            if (openingPageMode === 'image' || openingPageMode === 'blank') {
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
                        break-before: left;
                        break-after: page;
                    }
                    .chapter-section-${ch.id} {
                        page: chapter-${ch.id};
                        break-before: right;
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
                let chapterStartParity = ch.is_toc === '1'
                    ? 'even'
                    : ((ch.start_parity && ch.start_parity !== 'any') ? ch.start_parity : settings.chapter_start_parity);
                let breakBefore = 'page';
                if (idx > 0) {
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
                        ${breakBefore !== 'none' ? `break-before: ${breakBefore};` : ''}
                    }
                `;
            }
            
            // Reglas de cabecera y pie específicas de la primera página del capítulo
            const firstHeaderType = (ch.first_page_header_type && ch.first_page_header_type !== 'global') ? ch.first_page_header_type : (settings.first_page_header_type || 'blank');
            const firstFooterType = (ch.first_page_footer_type && ch.first_page_footer_type !== 'global') ? ch.first_page_footer_type : (settings.first_page_footer_type || 'page_number');
            const firstHeaderCustom = ch.first_page_header_custom || settings.first_page_header_custom || '';
            const firstFooterCustom = ch.first_page_footer_custom || settings.first_page_footer_custom || '';
            const hideTocHeader = ch.is_toc === '1' && ch.toc_hide_header === '1';
            const hideCreditsPageNumber = ch.is_credits === '1' && ch.credits_hide_page_number === '1';
            const hideTocPageNumber = ch.is_toc === '1' && ch.toc_hide_page_numbers === '1';
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
                    @top-left { content: ${hideTocHeader ? '""' : (firstHeaderType === 'custom' ? `"${firstHeaderCustom.replace(/"/g, '\\"')}"` : '""')}; }
                    @top-center { content: ${hideTocHeader ? '""' : (firstHeaderType === 'chapter_title' ? 'string(chapter-title)' : (firstHeaderType === 'book_title' ? `"${bookTitle.replace(/"/g, '\\"')}"` : '""'))}; }
                    @top-right { content: ""; }
                    
                    @bottom-left { content: ${hideChapterPageNumber ? '""' : (firstFooterType === 'custom' ? `"${firstFooterCustom.replace(/"/g, '\\"')}"` : '""')}; }
                    @bottom-center { content: ${hideChapterPageNumber ? '""' : (firstFooterType === 'page_number' ? 'counter(page)' : '""')}; }
                    @bottom-right { content: ""; }
                }
                ${hideCreditsPageNumber ? `
                @page chapter-${ch.id} {
                    @top-left { content: "" !important; }
                    @top-center { content: "" !important; }
                    @top-right { content: "" !important; }
                    @bottom-left { content: "" !important; }
                    @bottom-center { content: "" !important; }
                    @bottom-right { content: "" !important; }
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
