// ============================================================
// MÓDULO: editor-pdf-styles-chapters.js
// Responsabilidad: Generar las reglas de Named Pages y saltos de página
// específicas para cada capítulo (paridad, imágenes de fondo, etc.)
// ============================================================

function getPDFStylesChapters(settings, toPx) {
    const bookTitle = bookState.title || 'Libro';
    let chapterCSSRules = '';

    if (bookState.chapters) {
        bookState.chapters.forEach((ch, idx) => {
            const parityImageUrl = ch.parity_image;
            
            if (parityImageUrl) {
                const mode = ch.parity_image_mode || 'content';
                let parityPageStyle = '';
                if (mode === 'bleed') {
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: cover !important;
                        background-position: center !important;
                        background-repeat: no-repeat !important;
                    `;
                } else if (mode === 'custom') {
                    const w = ch.parity_image_width ? `${ch.parity_image_width}%` : '100%';
                    const h = ch.parity_image_height ? `${ch.parity_image_height}%` : 'auto';
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: ${w} ${h} !important;
                        background-repeat: no-repeat !important;
                        background-position: center !important;
                    `;
                } else { // content
                    parityPageStyle = `
                        background-image: url("${parityImageUrl}") !important;
                        background-size: contain !important;
                        background-repeat: no-repeat !important;
                        background-position: center !important;
                    `;
                }
                
                chapterCSSRules += `
                    .chapter-parity-section-${ch.id} {
                        page: chapter-${ch.id}-blank;
                        break-before: left;
                        break-after: page;
                    }
                    .chapter-section-${ch.id} {
                        page: chapter-${ch.id};
                        break-before: right;
                    }
                    
                    @page chapter-${ch.id}-blank {
                        @top-left { content: "" !important; }
                        @top-center { content: "" !important; }
                        @top-right { content: "" !important; }
                        @bottom-left { content: "" !important; }
                        @bottom-center { content: "" !important; }
                        @bottom-right { content: "" !important; }
                        ${parityPageStyle}
                    }
                    
                    .pagedjs_chapter-${ch.id}-blank_page .pagedjs_margin {
                        visibility: hidden !important;
                    }
                `;
                
                if (mode === 'bleed') {
                    chapterCSSRules += `
                        .pagedjs_chapter-${ch.id}-blank_page {
                            ${parityPageStyle}
                        }
                    `;
                } else {
                    chapterCSSRules += `
                        .pagedjs_chapter-${ch.id}-blank_page .pagedjs_area {
                            ${parityPageStyle}
                        }
                    `;
                }
            } else {
                let chapterStartParity = (ch.start_parity && ch.start_parity !== 'any') ? ch.start_parity : settings.chapter_start_parity;
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
            
            chapterCSSRules += `
                @page chapter-${ch.id}:first {
                    @top-left { content: ${firstHeaderType === 'custom' ? `"${firstHeaderCustom.replace(/"/g, '\\"')}"` : '""'}; }
                    @top-center { content: ${firstHeaderType === 'chapter_title' ? 'string(chapter-title)' : (firstHeaderType === 'book_title' ? `"${bookTitle.replace(/"/g, '\\"')}"` : '""')}; }
                    @top-right { content: ""; }
                    
                    @bottom-left { content: ${firstFooterType === 'custom' ? `"${firstFooterCustom.replace(/"/g, '\\"')}"` : '""'}; }
                    @bottom-center { content: ${firstFooterType === 'page_number' ? 'counter(page)' : '""'}; }
                    @bottom-right { content: ""; }
                }
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
            if (ch.hide_all_headers_footers === '1' || ch.is_toc === '1') {
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
