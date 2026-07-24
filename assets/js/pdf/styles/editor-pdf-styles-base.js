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
function getPDFStylesBase(settings, geometry, toPx) {
    const bookTitle = bookState.title || 'Libro';
    const resolvedGeometry = geometry || (typeof window.resolvePDFGeometry === 'function'
        ? window.resolvePDFGeometry(settings)
        : null);
    const unit = (resolvedGeometry && resolvedGeometry.unit) || settings.unit || 'cm';
    const trimWidth = (resolvedGeometry && resolvedGeometry.width) || parseFloat(settings.page_width) || 14;
    const trimHeight = (resolvedGeometry && resolvedGeometry.height) || parseFloat(settings.page_height) || 21;
    const bleedValue = Math.max((resolvedGeometry && resolvedGeometry.bleed) || parseFloat(settings.bleeding) || 0, 0);
    const bleedPx = Math.max((resolvedGeometry && resolvedGeometry.bleedPx) || toPx(bleedValue), 0);
    const sheetWidth = (resolvedGeometry && resolvedGeometry.sheetWidth) || (trimWidth + (bleedValue * 2));
    const sheetHeight = (resolvedGeometry && resolvedGeometry.sheetHeight) || (trimHeight + (bleedValue * 2));
    const previewWidth = (resolvedGeometry && resolvedGeometry.previewWidth) || (trimWidth + bleedValue);
    const previewHeight = (resolvedGeometry && resolvedGeometry.previewHeight) || (trimHeight + (bleedValue * 2));
    const sheetWidthPx = (resolvedGeometry && resolvedGeometry.sheetWidthPx) || toPx(sheetWidth);
    const sheetHeightPx = (resolvedGeometry && resolvedGeometry.sheetHeightPx) || toPx(sheetHeight);
    const previewWidthPx = (resolvedGeometry && resolvedGeometry.previewWidthPx) || toPx(previewWidth);
    const previewHeightPx = (resolvedGeometry && resolvedGeometry.previewHeightPx) || toPx(previewHeight);
    const bleedLength = `${bleedValue.toFixed(4)}${unit}`;
    const zeroLength = `0${unit}`;
    const bookStartFooterBox = getFooterMarginBox(settings.footer_align || 'center', false);
    const bookStartFooterRule = settings.book_start_page_footer_type === 'page_number'
        ? `@bottom-left { content: "" !important; } @bottom-center { content: "" !important; } @bottom-right { content: "" !important; } @${bookStartFooterBox} { content: ${getFooterContent('page_number', false, bookTitle, settings)} !important; }`
        : `@bottom-left { content: "" !important; } @bottom-center { content: "" !important; } @bottom-right { content: "" !important; }`;

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

    const marginLeftOdd = parseFloat(settings.margin_left_odd ?? settings.margin_left ?? 2.0);
    const marginRightOdd = parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2.0);
    const marginLeftEven = parseFloat(settings.margin_left_even ?? settings.margin_left ?? 2.0);
    const marginRightEven = parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2.0);

    return `
        /* ── Reglas Paged Media de W3C ── */
        @page {
            size: ${trimWidth}${unit} ${trimHeight}${unit};
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
                padding-top: ${toPx(headerMarginTop)}px !important;
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
                padding-top: ${toPx(headerMarginTop)}px !important;
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
                padding-top: ${toPx(headerMarginTop)}px !important;
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
                padding-bottom: ${toPx(footerMarginBottom)}px !important;
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
                padding-bottom: ${toPx(footerMarginBottom)}px !important;
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
                padding-bottom: ${toPx(footerMarginBottom)}px !important;
                vertical-align: bottom !important;
                box-sizing: border-box !important;
            }
        }
        
        @page :left {
            bleed: ${bleedLength} ${zeroLength} ${bleedLength} ${bleedLength};
            margin-left: ${marginLeftEven}${unit};
            margin-right: ${marginRightEven}${unit};
        }
        
        @page :right {
            bleed: ${bleedLength} ${bleedLength} ${bleedLength} ${zeroLength};
            margin-left: ${marginLeftOdd}${unit};
            margin-right: ${marginRightOdd}${unit};
        }
        
        ${getHeaderFooterCSS(settings, bookTitle)}

        @page chapter-blank-page {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        @page chapter-blank-page:left {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        @page chapter-blank-page:right {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        @page chapter-blank-page:blank {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        @page book-start-leading-page {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            ${bookStartFooterRule}
        }

        @page book-start-leading-page:left {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        @page book-start-leading-page:right {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            ${bookStartFooterRule}
        }

        @page book-start-leading-page:blank {
            @top-left { content: "" !important; }
            @top-center { content: "" !important; }
            @top-right { content: "" !important; }
            @bottom-left { content: "" !important; }
            @bottom-center { content: "" !important; }
            @bottom-right { content: "" !important; }
        }

        .book-start-leading-page {
            /* The book always starts on the right; the following editorial page
             * must therefore be forced onto the left side of the spread. */
            break-before: right !important;
            break-after: left !important;
            page: book-start-leading-page !important;
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
            line-height: 1px !important;
            font-size: 1px !important;
            color: transparent !important;
            overflow: hidden !important;
            clear: both !important;
        }
        
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

        .chapter-transition-blank-page {
            break-before: right !important;
            page: chapter-blank-page !important;
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
            line-height: 1px !important;
            font-size: 1px !important;
            color: transparent !important;
            overflow: hidden !important;
            clear: both !important;
        }

        .book-end-blank-page {
            break-before: page !important;
            page: chapter-blank-page !important;
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

            .pagedjs_page.pagedjs_blank_page .pagedjs_margin {
                visibility: hidden !important;
            }
        }
        
        @media print {
            .pagedjs_page.pagedjs_blank_page .pagedjs_margin {
                visibility: hidden !important;
            }

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
                --pagedjs-width: ${previewWidth}${unit} !important;
                --pagedjs-height: ${previewHeight}${unit} !important;
                --pagedjs-width-right: ${previewWidth}${unit} !important;
                --pagedjs-height-right: ${previewHeight}${unit} !important;
                --pagedjs-width-left: ${previewWidth}${unit} !important;
                --pagedjs-height-left: ${previewHeight}${unit} !important;

                --pagedjs-pagebox-width: ${trimWidth}${unit} !important;
                --pagedjs-pagebox-height: ${trimHeight}${unit} !important;
                --pagedjs-pagebox-width-right: ${trimWidth}${unit} !important;
                --pagedjs-pagebox-height-right: ${trimHeight}${unit} !important;
                --pagedjs-pagebox-width-left: ${trimWidth}${unit} !important;
                --pagedjs-pagebox-height-left: ${trimHeight}${unit} !important;
                --pagedjs-bleed-top: ${bleedValue}${unit} !important;
                --pagedjs-bleed-right: ${bleedValue}${unit} !important;
                --pagedjs-bleed-bottom: ${bleedValue}${unit} !important;
                --pagedjs-bleed-left: ${bleedValue}${unit} !important;
                --pagedjs-bleed-right-top: ${bleedValue}${unit} !important;
                --pagedjs-bleed-right-right: ${bleedValue}${unit} !important;
                --pagedjs-bleed-right-bottom: ${bleedValue}${unit} !important;
                --pagedjs-bleed-right-left: 0${unit} !important;
                --pagedjs-bleed-left-top: ${bleedValue}${unit} !important;
                --pagedjs-bleed-left-right: 0${unit} !important;
                --pagedjs-bleed-left-bottom: ${bleedValue}${unit} !important;
                --pagedjs-bleed-left-left: ${bleedValue}${unit} !important;
                --bookster-screen-bleed: ${bleedPx}px !important;
                --bookster-screen-page-width: ${previewWidthPx}px !important;
                --bookster-screen-page-height: ${previewHeightPx}px !important;
            }

            /* Forzar el grid interno de Paged.js para que respete los márgenes guardados */
            .pagedjs_right_page {
                --pagedjs-margin-left: ${marginLeftOdd}${unit} !important;
                --pagedjs-margin-right: ${marginRightOdd}${unit} !important;
                --pagedjs-margin-top: ${totalHeaderHeight.toFixed(4)}${unit} !important;
                --pagedjs-margin-bottom: ${totalFooterHeight.toFixed(4)}${unit} !important;
            }

            .pagedjs_left_page {
                --pagedjs-margin-left: ${marginLeftEven}${unit} !important;
                --pagedjs-margin-right: ${marginRightEven}${unit} !important;
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
                width: var(--bookster-screen-page-width) !important;
                height: var(--bookster-screen-page-height) !important;
                background-color: white !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
                border: 1px solid #e2e8f0 !important;
                margin-bottom: 40px !important;
                box-sizing: border-box !important;
                position: relative !important;
                padding: 0 !important;
                overflow: visible !important;
                ${settings.export_grayscale == 1 ? 'background-color: #fff !important; color: #000 !important;' : ''}
            }

            ${settings.export_grayscale == 1 ? `
            .pagedjs_page *,
            .pagedjs_page *::before,
            .pagedjs_page *::after {
                color: #000 !important;
                -webkit-text-fill-color: #000 !important;
                text-shadow: none !important;
                box-shadow: none !important;
            }

            .pagedjs_page img,
            .pagedjs_page svg,
            .pagedjs_page canvas,
            .pagedjs_page video {
                filter: grayscale(100%) !important;
                -webkit-filter: grayscale(100%) !important;
            }
            ` : ''}

            ${bleedPx > 0 ? `
            .pagedjs_page::after {
                content: "" !important;
                position: absolute !important;
                border: 2px dashed #94a3b8 !important;
                pointer-events: none !important;
                box-sizing: border-box !important;
                z-index: 2 !important;
            }

            .pagedjs_page.pagedjs_left_page::after {
                top: var(--bookster-screen-bleed) !important;
                right: 0 !important;
                bottom: var(--bookster-screen-bleed) !important;
                left: var(--bookster-screen-bleed) !important;
            }

            .pagedjs_page.pagedjs_right_page::after {
                top: var(--bookster-screen-bleed) !important;
                right: var(--bookster-screen-bleed) !important;
                bottom: var(--bookster-screen-bleed) !important;
                left: 0 !important;
            }
            ` : ''}
            
            /* Vista Spread (Doble Página) en Pantalla */
            #pdf-scroller.spread-view .pagedjs_pages {
                display: grid !important;
                grid-template-columns: var(--bookster-screen-page-width) var(--bookster-screen-page-width) !important;
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
            
            /* Ocultar la página guía inicial solo cuando no es una página de arranque editorial real */
            #pdf-scroller.spread-view:not(.single-chapter-mode) .pagedjs_pages > .pagedjs_page.pagedjs_left_page:first-child:not(:has(.book-start-leading-page)),
            #pdf-scroller.spread-view:not(.single-chapter-mode) .pagedjs_pages > .pagedjs_page.pagedjs_blank_page:first-child:not(:has(.book-start-leading-page)) {
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

            /* A chapter preview must not duplicate the previous chapter's
             * transition blank. Visually offset its pages so the opening still
             * occupies the left side of the spread. */
            #pdf-scroller.spread-view.single-chapter-left-start .pagedjs_pages > .pagedjs_page.pagedjs_page_1 {
                grid-column: 1 !important;
                justify-self: end !important;
            }
            #pdf-scroller.spread-view.single-chapter-left-start .pagedjs_pages > .pagedjs_page.pagedjs_page_2 {
                grid-column: 2 !important;
                justify-self: start !important;
            }
            #pdf-scroller.spread-view.single-chapter-left-start .pagedjs_pages > .pagedjs_page.pagedjs_page_3 {
                grid-column: 1 !important;
                justify-self: end !important;
            }
            #pdf-scroller.spread-view.single-chapter-left-start .pagedjs_pages > .pagedjs_page.pagedjs_page_4 {
                grid-column: 2 !important;
                justify-self: start !important;
            }

            /* In an individual chapter preview, the transition blank was
             * already emitted by the previous chapter. Do not recreate it
             * through the opening section's left-page break. */
            #pdf-scroller.single-chapter-left-start .single-chapter-opening-preview {
                break-before: auto !important;
                page-break-before: auto !important;
            }

            #pdf-scroller.single-chapter-left-start .single-chapter-main-preview {
                break-before: auto !important;
                page-break-before: auto !important;
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
