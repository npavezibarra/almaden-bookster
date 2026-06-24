// ============================================================
// MÓDULO: editor-pdf-styles-base.js
// Responsabilidad: Generar el CSS base de Paged.js, @page, márgenes, cabeceras y pies de página.
// ============================================================

// ── Helpers de ámbito de archivo para cabeceras y pies de página ──
function getMarginBox(align, isEven) {
    if (align === 'center') return 'top-center';
    if (align === 'left') return 'top-left';
    if (align === 'right') return 'top-right';
    if (align === 'outer') return isEven ? 'top-left' : 'top-right';
    if (align === 'inner') return isEven ? 'top-right' : 'top-left';
    return 'top-center';
}

function getFooterMarginBox(align, isEven) {
    if (align === 'center') return 'bottom-center';
    if (align === 'left') return 'bottom-left';
    if (align === 'right') return 'bottom-right';
    if (align === 'outer') return isEven ? 'bottom-left' : 'bottom-right';
    if (align === 'inner') return isEven ? 'bottom-right' : 'bottom-left';
    return 'bottom-center';
}

function getHeaderContent(type, isEven, bookTitle, settings) {
    if (type === 'blank') return '""';
    if (type === 'book_title') return `"${bookTitle.replace(/"/g, '\\"')}"`;
    if (type === 'chapter_title') return 'string(chapter-title)';
    if (type === 'page_number') return 'counter(page)';
    if (type === 'author') return '"Autor"';
    if (type === 'custom') {
        const customText = isEven ? (settings.header_even_custom || '') : (settings.header_odd_custom || '');
        return `"${customText.replace(/"/g, '\\"')}"`;
    }
    return '""';
}

function getFooterContent(type, isEven, bookTitle, settings) {
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
}

// ── Generador principal de estilos base ──
function getPDFStylesBase(settings, toPx, widthPx, heightPx, globalBleedPx, unit) {
    const bookTitle = bookState.title || 'Libro';
    
    // Compute the width and height using unit
    let width = parseFloat(settings.page_width) || 14;
    let height = parseFloat(settings.page_height) || 21;
    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    // Compute total header and footer dimensions to align the page margins using native units
    const ptToUnit = (pt) => {
        return unit === 'cm' ? (pt * 2.54 / 72) : (pt / 72);
    };

    const headerFontPt = parseFloat(settings.header_font_size) || 8.5;
    const footerFontPt = parseFloat(settings.footer_font_size) || 9.0;
    const headerMarginTop = parseFloat(settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0);
    const headerMarginBottom = parseFloat(settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5);
    const footerMarginTop = parseFloat(settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5);
    const footerMarginBottom = parseFloat(settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0);
    const paddingTop = parseFloat(settings.padding_top || 0);
    const paddingBottom = parseFloat(settings.padding_bottom || 0);

    const totalHeaderHeight = headerMarginTop + ptToUnit(headerFontPt) + headerMarginBottom + paddingTop;
    const totalFooterHeight = footerMarginTop + ptToUnit(footerFontPt) + footerMarginBottom + paddingBottom;

    // Convert total height back to px for screen preview variables
    const totalHeaderHeightPx = toPx(totalHeaderHeight);
    const totalFooterHeightPx = toPx(totalFooterHeight);
    
    // Pixel variables for header box paddings inside margins
    const headerMarginTopPx = toPx(headerMarginTop);
    const footerMarginBottomPx = toPx(footerMarginBottom);

    return `
        /* ── Reglas Paged Media de W3C ── */
        @page {
            size: ${width}${unit} ${height}${unit};
            margin-top: ${totalHeaderHeight.toFixed(4)}${unit};
            margin-bottom: ${totalFooterHeight.toFixed(4)}${unit};
            
            /* Estilos globales de las cajas de cabecera individuales */
            @top-left {
                font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.header_font_size || 8.5, true)}px !important;
                font-weight: ${settings.header_font_weight || 'normal'} !important;
                font-style: ${settings.header_font_style || 'normal'} !important;
                text-transform: ${settings.header_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.header_letter_spacing || 0.1, true)}px !important;
                color: #475569 !important;
                padding-top: ${headerMarginTopPx}px !important;
                vertical-align: top !important;
                box-sizing: border-box !important;
            }
            @top-center {
                font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.header_font_size || 8.5, true)}px !important;
                font-weight: ${settings.header_font_weight || 'normal'} !important;
                font-style: ${settings.header_font_style || 'normal'} !important;
                text-transform: ${settings.header_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.header_letter_spacing || 0.1, true)}px !important;
                color: #475569 !important;
                padding-top: ${headerMarginTopPx}px !important;
                vertical-align: top !important;
                box-sizing: border-box !important;
            }
            @top-right {
                font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.header_font_size || 8.5, true)}px !important;
                font-weight: ${settings.header_font_weight || 'normal'} !important;
                font-style: ${settings.header_font_style || 'normal'} !important;
                text-transform: ${settings.header_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.header_letter_spacing || 0.1, true)}px !important;
                color: #475569 !important;
                padding-top: ${headerMarginTopPx}px !important;
                vertical-align: top !important;
                box-sizing: border-box !important;
            }
            
            /* Estilos globales de las cajas de pie de página individuales */
            @bottom-left {
                font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.footer_font_size || 9.0, true)}px !important;
                font-weight: ${settings.footer_font_weight || 'normal'} !important;
                font-style: ${settings.footer_font_style || 'normal'} !important;
                text-transform: ${settings.footer_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.footer_letter_spacing || 0.0, true)}px !important;
                color: #475569 !important;
                padding-bottom: ${footerMarginBottomPx}px !important;
                vertical-align: bottom !important;
                box-sizing: border-box !important;
            }
            @bottom-center {
                font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.footer_font_size || 9.0, true)}px !important;
                font-weight: ${settings.footer_font_weight || 'normal'} !important;
                font-style: ${settings.footer_font_style || 'normal'} !important;
                text-transform: ${settings.footer_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.footer_letter_spacing || 0.0, true)}px !important;
                color: #475569 !important;
                padding-bottom: ${footerMarginBottomPx}px !important;
                vertical-align: bottom !important;
                box-sizing: border-box !important;
            }
            @bottom-right {
                font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
                font-size: ${toPx(settings.footer_font_size || 9.0, true)}px !important;
                font-weight: ${settings.footer_font_weight || 'normal'} !important;
                font-style: ${settings.footer_font_style || 'normal'} !important;
                text-transform: ${settings.footer_text_transform || 'none'} !important;
                letter-spacing: ${toPx(settings.footer_letter_spacing || 0.0, true)}px !important;
                color: #475569 !important;
                padding-bottom: ${footerMarginBottomPx}px !important;
                vertical-align: bottom !important;
                box-sizing: border-box !important;
            }
        }
        
        @page :left {
            margin-left: ${settings.margin_left_even ?? settings.margin_left ?? 2.0}${unit};
            margin-right: ${settings.margin_right_even ?? settings.margin_right ?? 2.0}${unit};
        }
        
        @page :right {
            margin-left: ${settings.margin_left_odd ?? settings.margin_left ?? 2.0}${unit};
            margin-right: ${settings.margin_right_odd ?? settings.margin_right ?? 2.0}${unit};
        }
        
        ${getHeaderFooterCSS(settings, bookTitle)}
        
        .chapter-preview-blank-page {
            break-before: left !important;
            break-after: page !important;
            page: chapter-blank-page !important;
            height: 1px !important;
            line-height: 1px !important;
            font-size: 1px !important;
            color: transparent !important;
            overflow: hidden !important;
            clear: both !important;
        }

        .credits-blank-page {
            break-after: page !important;
            page: chapter-blank-page !important;
            height: 1px !important;
            line-height: 1px !important;
            font-size: 1px !important;
            color: transparent !important;
            overflow: hidden !important;
            clear: both !important;
        }
        
        .book-start-dummy-page {
            break-after: page !important;
            page: chapter-blank-page !important;
            height: 1px !important;
            line-height: 1px !important;
            font-size: 1px !important;
            color: transparent !important;
            overflow: hidden !important;
            clear: both !important;
        }
        
        @media screen {
            .pagedjs_page:has(.book-start-dummy-page) {
                visibility: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                height: 0 !important;
                width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                overflow: hidden !important;
            }
        }
        
        @media print {
            .single-chapter-mode .pagedjs_page:has(.book-start-dummy-page) {
                visibility: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                top: -9999px !important;
                height: 0 !important;
                width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                overflow: hidden !important;
            }
        }
        
        /* ── Estilos de Pantalla e Integración del Panel (editor-pdf-preview) ── */
        @media screen {
            :root {
                --pagedjs-width: ${width}${unit} !important;
                --pagedjs-height: ${height}${unit} !important;
                --pagedjs-width-right: ${width}${unit} !important;
                --pagedjs-height-right: ${height}${unit} !important;
                --pagedjs-width-left: ${width}${unit} !important;
                --pagedjs-height-left: ${height}${unit} !important;
                
                --pagedjs-pagebox-width: ${width}${unit} !important;
                --pagedjs-pagebox-height: ${height}${unit} !important;
                --pagedjs-pagebox-width-right: ${width}${unit} !important;
                --pagedjs-pagebox-height-right: ${height}${unit} !important;
                --pagedjs-pagebox-width-left: ${width}${unit} !important;
                --pagedjs-pagebox-height-left: ${height}${unit} !important;
            }

            /* Forzar el grid interno de Paged.js para que respete los márgenes guardados */
            .pagedjs_right_page {
                --pagedjs-margin-left: ${settings.margin_left_odd ?? settings.margin_left ?? 2.0}${unit} !important;
                --pagedjs-margin-right: ${settings.margin_right_odd ?? settings.margin_right ?? 2.0}${unit} !important;
                --pagedjs-margin-top: ${totalHeaderHeight.toFixed(4)}${unit} !important;
                --pagedjs-margin-bottom: ${totalFooterHeight.toFixed(4)}${unit} !important;
            }

            .pagedjs_left_page {
                --pagedjs-margin-left: ${settings.margin_left_even ?? settings.margin_left ?? 2.0}${unit} !important;
                --pagedjs-margin-right: ${settings.margin_right_even ?? settings.margin_right ?? 2.0}${unit} !important;
                --pagedjs-margin-top: ${totalHeaderHeight.toFixed(4)}${unit} !important;
                --pagedjs-margin-bottom: ${totalFooterHeight.toFixed(4)}${unit} !important;
            }

            #pdf-scroller {
                background-color: #f1f5f9 !important;
                padding: 30px 0 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                overflow-y: auto !important;
            }
            
            #pdf-scroller .pagedjs_pages {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                background: #f1f5f9 !important;
                background-color: #f1f5f9 !important;
            }
            
            .pagedjs_page {
                width: var(--pagedjs-width) !important;
                height: var(--pagedjs-height) !important;
                background-color: white !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                border: 1px solid #e2e8f0 !important;
                margin-bottom: 40px !important;
                box-sizing: border-box !important;
                ${settings.export_grayscale == 1 ? 'filter: grayscale(100%) !important; -webkit-filter: grayscale(100%) !important;' : ''}
            }
            
            /* Vista Spread (Doble Página) en Pantalla */
            #pdf-scroller.spread-view .pagedjs_pages {
                display: grid !important;
                grid-template-columns: var(--pagedjs-width) var(--pagedjs-width) !important;
                justify-content: center !important;
                column-gap: 0px !important;
                row-gap: 40px !important;
                padding: 40px 0 !important;
                background: #f1f5f9 !important;
                background-color: #f1f5f9 !important;
            }
            
            #pdf-scroller.spread-view .pagedjs_page {
                margin-bottom: 0px !important;
            }
            
            /* Ocultar la página en blanco o izquierda al inicio del primer spread, excepto en modo capítulo activo */
            #pdf-scroller.spread-view:not(.single-chapter-mode) .pagedjs_pages > .pagedjs_page.pagedjs_left_page:first-child,
            #pdf-scroller.spread-view:not(.single-chapter-mode) .pagedjs_pages > .pagedjs_page.pagedjs_blank_page:first-child {
                display: none !important;
            }
            
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_page_1,
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_first_page {
                grid-column: 2 !important;
            }
            
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_page_1.pagedjs_right_page,
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_first_page.pagedjs_right_page {
                grid-column: 2 !important;
                justify-self: start !important;
            }
            
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_left_page {
                border-right: 1px solid #e2e8f0 !important; /* Línea divisoria central */
                justify-self: end !important;
            }
            
            #pdf-scroller.spread-view .pagedjs_page.pagedjs_right_page {
                border-left: none !important;
                justify-self: start !important;
            }
        }
        
        @media print {
            .pagedjs_pages {
                background: white !important;
            }
            .pagedjs_page {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
            }
        }

        /* ── Reglas de Bloqueo de Contenido ── */
        .pdf-content span[lang],
        .pdf-content span[lang] em,
        .pdf-content span[lang] i,
        .pdf-content span[lang] b,
        .pdf-content span[lang] strong,
        .pdf-content span[lang] * {
            color: #ff0000 !important;
            background-color: rgba(255, 0, 0, 0.08) !important;
        }
        
        @media print {
            .pdf-content span[lang],
            .pdf-content span[lang] em,
            .pdf-content span[lang] * {
                color: inherit !important;
                background-color: transparent !important;
            }
        }
    `;
}

// ── Generador secundario de reglas de cabecera y pie ──
function getHeaderFooterCSS(settings, bookTitle) {
    const headerAlign = settings.header_align || 'center';
    const footerAlign = settings.footer_align || 'center';
    
    const headerEvenType = settings.header_even_type || 'book_title';
    const headerOddType = settings.header_odd_type || 'chapter_title';
    const footerEvenType = settings.footer_even_type || 'page_number';
    const footerOddType = settings.footer_odd_type || 'page_number';
    
    const headerEvenBox = getMarginBox(headerAlign, true);
    const headerOddBox = getMarginBox(headerAlign, false);
    const footerEvenBox = getFooterMarginBox(footerAlign, true);
    const footerOddBox = getFooterMarginBox(footerAlign, false);
    
    return `
        @page :left {
            @${headerEvenBox} {
                content: ${getHeaderContent(headerEvenType, true, bookTitle, settings)};
            }
            @${footerEvenBox} {
                content: ${getFooterContent(footerEvenType, true, bookTitle, settings)};
            }
        }
        @page :right {
            @${headerOddBox} {
                content: ${getHeaderContent(headerOddType, false, bookTitle, settings)};
            }
            @${footerOddBox} {
                content: ${getFooterContent(footerOddType, false, bookTitle, settings)};
            }
        }
    `;
}
