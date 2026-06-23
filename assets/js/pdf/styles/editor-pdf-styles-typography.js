// ============================================================
// MÓDULO: editor-pdf-styles-typography.js
// Responsabilidad: Generar el CSS tipográfico, párrafos, alineación, TOC e índices.
// ============================================================

function getPDFStylesTypography(settings, tocSettings, toPx) {
    let styles = `
        /* ── Contenido ── */
        .pdf-content {
            display: block !important;
            flex: none !important;
            min-height: 0 !important;
            box-sizing: border-box !important;
            padding-top: 0px !important;
            padding-bottom: 0px !important;
            margin-left: 0px !important;
            margin-right: 0px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.font_size_content || 11.5, true)}px !important;
            font-weight: ${settings.font_weight_content || 'normal'} !important;
            line-height: ${settings.line_height_content || 1.65} !important;
            overflow: visible !important;
        }
        .pdf-content p, .pdf-content ul, .pdf-content ol {
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.font_size_content || 11.5, true)}px !important;
            font-weight: ${settings.font_weight_content || 'normal'} !important;
            line-height: ${settings.line_height_content || 1.65} !important;
            text-align: ${settings.content_text_align || 'justify'} !important;
            ${(settings.content_text_align === 'justify' || !settings.content_text_align) ? 'text-align-last: left !important;' : ''}
            hyphens: ${(settings.content_hyphenation === 0 || settings.content_hyphenation === '0') ? 'none' : 'auto'} !important;
            -webkit-hyphens: ${(settings.content_hyphenation === 0 || settings.content_hyphenation === '0') ? 'none' : 'auto'} !important;
            hyphenate-limit-chars: 6 3 3 !important;
            hyphenate-limit-lines: 2 !important;
            -webkit-hyphenate-limit-before: 3 !important;
            -webkit-hyphenate-limit-after: 3 !important;
            text-rendering: geometricPrecision !important;
            -webkit-font-smoothing: antialiased !important;
        }
        .pdf-content p {
            margin-bottom: ${toPx(settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0, true)}px !important;
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }

        .pdf-content .almaden-align-center, .pdf-content .almaden-align-center * {
            text-align: center !important;
            text-indent: 0 !important;
        }
        
        .pdf-content .almaden-align-left, .pdf-content .almaden-align-left * {
            text-align: left !important;
            text-indent: 0 !important;
        }

        .pdf-content .almaden-align-right, .pdf-content .almaden-align-right * {
            text-align: right !important;
            text-indent: 0 !important;
        }

        .pdf-content .almaden-align-justify, .pdf-content .almaden-align-justify * {
            text-align: justify !important;
            text-align-last: left !important;
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }

        .pdf-content .split-paragraph-start {
            margin-bottom: 0px !important;
            padding-bottom: 0px !important;
        }

        .pdf-content .deep-split-start {
            text-align-last: left !important;
        } 

        /* Párrafos Divididos y Continuación (Paged.js) */
        .pdf-content [data-split-from] {
            text-indent: 0 !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .pdf-content [data-split-to] {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .pdf-content [data-align-last-split-element='justify'] {
            text-align-last: justify !important;
        }

        /* Safety buffer to prevent sub-pixel layout overflow clipping */
        .pagedjs_page_content {
            height: calc(100% - var(--pagedjs-footnotes-height)) !important;
        }

        .pdf-content .almaden-align-center [data-split-to],
        .pdf-content .almaden-align-left [data-split-to],
        .pdf-content .almaden-align-right [data-split-to],
        .pdf-content [data-split-to].almaden-align-center,
        .pdf-content [data-split-to].almaden-align-left,
        .pdf-content [data-split-to].almaden-align-right {
            text-align-last: auto !important;
        }

        /* ── Párrafos Continuación (segunda mitad tras el salto) ── */
        .pdf-content p.split-paragraph-continuation,
        .pdf-content ul.split-paragraph-continuation,
        .pdf-content ol.split-paragraph-continuation,
        .pdf-content div.split-paragraph-continuation,
        .pdf-content blockquote.split-paragraph-continuation {
            text-indent: 0 !important;
            margin-top: 0 !important;
        }

        .pdf-content p:last-child:not(.split-paragraph-start) {
            margin-bottom: 0 !important;
        }
        
        .pdf-content p.split-paragraph-continuation {
            text-indent: 0 !important;
        }
        .pdf-content .chapter-main-title + p,
        .pdf-content h1 + p,
        .pdf-content h2 + p {
            text-indent: 0 !important;
        }

        .pdf-content p.drop-cap::first-letter {
            float: left;
            font-size: 3.5em;
            line-height: 0.85;
            margin-right: 0.1em;
            margin-top: 0.05em;
            margin-bottom: -0.1em;
            font-weight: bold;
            font-family: '${settings.font_family_h1 || 'Playfair Display'}', serif !important;
        }

        /* ── Imágenes ── */
        .pdf-content img.pdf-book-image {
            max-width: 100% !important;
            height: auto !important;
            display: block !important;
            margin: 20px auto !important;
            page-break-inside: avoid;
        }

        /* ── Título de Capítulo ── */
        .pdf-content .chapter-main-title {
            font-family: '${settings.chapter_title_font_family || 'Playfair Display'}', serif !important;
            font-size: ${toPx(settings.chapter_title_font_size || 24.0, true)}px !important;
            font-weight: ${settings.chapter_title_font_weight || 'bold'} !important;
            font-style: ${settings.chapter_title_font_style || 'normal'} !important;
            text-transform: ${settings.chapter_title_text_transform || 'none'} !important;
            text-align: ${settings.chapter_title_align || 'center'} !important;
            line-height: ${settings.chapter_title_line_height || 1.2} !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: ${toPx(settings.chapter_title_padding_top || 0.0, false)}px !important;
            padding-bottom: ${toPx(settings.chapter_title_padding_bottom || 1.5, false)}px !important;
            padding-left: ${toPx(settings.chapter_title_padding_left || 0.0, false)}px !important;
            padding-right: ${toPx(settings.chapter_title_padding_right || 0.0, false)}px !important;
            color: #000 !important;
            text-indent: 0 !important;
            page-break-after: avoid;
            break-after: avoid;
            width: 100%;
            box-sizing: border-box;
        }

        /* ── Título del Índice (TOC) ── */
        .pdf-content .toc-main-title {
            font-family: '${tocSettings.titleFontFamily || 'Playfair Display'}', serif !important;
            font-size: ${toPx(tocSettings.titleFontSize || 24.0, true)}px !important;
            font-weight: ${tocSettings.titleFontWeight || 'bold'} !important;
            font-style: ${tocSettings.titleFontStyle || 'normal'} !important;
            text-transform: ${tocSettings.titleTextTransform || 'none'} !important;
            text-align: ${tocSettings.titleAlign || 'center'} !important;
            line-height: ${tocSettings.titleLineHeight || 1.2} !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: ${toPx(tocSettings.titlePaddingTop !== undefined ? tocSettings.titlePaddingTop : 0.0, false)}px !important;
            padding-bottom: ${toPx(tocSettings.titlePaddingBottom !== undefined ? tocSettings.titlePaddingBottom : 1.5, false)}px !important;
            padding-left: ${toPx(tocSettings.titlePaddingLeft !== undefined ? tocSettings.titlePaddingLeft : 0.0, false)}px !important;
            padding-right: ${toPx(tocSettings.titlePaddingRight !== undefined ? tocSettings.titlePaddingRight : 0.0, false)}px !important;
            color: #000 !important;
            text-indent: 0 !important;
            page-break-after: avoid;
            break-after: avoid;
            width: 100%;
            box-sizing: border-box;
        }

        /* ── Bloque TOC y Listas ── */
        .toc-list-block {
            margin: 0;
            padding: 0;
            list-style: none;
            width: 100%;
        }

        /* ── Estilos TOC por nivel ── */
        .toc-item {
            display: flex !important;
            align-items: flex-end !important;
            width: 100% !important;
            hyphens: none !important;
        }

        .toc-item-h1 {
            font-family: '${tocSettings.fontFamily}', serif !important;
            font-size: ${toPx(tocSettings.fontSize, true)}px !important;
            font-weight: ${tocSettings.fontWeight} !important;
            font-style: ${tocSettings.fontStyle} !important;
            text-transform: ${tocSettings.textTransform} !important;
            letter-spacing: ${toPx(tocSettings.letterSpacing, true)}px !important;
            line-height: ${tocSettings.lineHeight} !important;
            margin-bottom: ${toPx(tocSettings.itemSpacing, true)}px !important;
            padding-left: 0;
            position: relative;
        }

        .toc-item-h2 {
            font-family: '${tocSettings.fontFamily}', serif !important;
            font-size: ${toPx(tocSettings.fontSize * 0.9, true)}px !important;
            font-weight: normal !important;
            font-style: ${tocSettings.fontStyle} !important;
            text-transform: ${tocSettings.textTransform} !important;
            letter-spacing: ${toPx(tocSettings.letterSpacing, true)}px !important;
            line-height: ${tocSettings.lineHeight} !important;
            margin-bottom: ${toPx(tocSettings.itemSpacing * 0.8, true)}px !important;
            padding-left: 20px;
            position: relative;
        }

        .toc-item-h3 {
            font-family: '${tocSettings.fontFamily}', serif !important;
            font-size: ${toPx(tocSettings.fontSize * 0.85, true)}px !important;
            font-weight: normal !important;
            font-style: italic !important;
            text-transform: ${tocSettings.textTransform} !important;
            letter-spacing: ${toPx(tocSettings.letterSpacing, true)}px !important;
            line-height: ${tocSettings.lineHeight} !important;
            margin-bottom: ${toPx(tocSettings.itemSpacing * 0.6, true)}px !important;
            padding-left: 40px;
            position: relative;
        }

        .toc-number {
            flex-shrink: 0 !important;
            margin-right: 8px !important;
            text-align: left !important;
            align-self: flex-start !important;
        }
        
        .toc-title-wrapper {
            flex-grow: 1 !important;
            display: grid !important;
            grid-template-columns: auto max-content;
            grid-template-areas: "title leader";
            align-items: end !important; /* Aligns dots with the baseline of the last line */
            gap: 0 4px !important;
            overflow: hidden !important;
            min-width: 0 !important;
            position: relative !important;
            text-align: left !important;
        }
        
        .toc-title {
            grid-area: title !important;
            display: inline !important;
            white-space: normal !important;
            word-break: break-word !important;
            position: relative !important;
            text-align: left !important;
        }
        
        .toc-title::after {
            content: ${
                tocSettings.leaderStyle === 'dotted'
                ? '" . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . ."'
                : (tocSettings.leaderStyle === 'dashed'
                    ? '" - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -"'
                    : '""')
            } !important;
            position: absolute !important;
            padding-left: 6px !important;
            white-space: nowrap !important;
            letter-spacing: ${tocSettings.leaderStyle === 'dotted' ? '3px' : '0px'} !important;
            color: #a0aec0 !important;
        }

        /* Si es solid, simulamos la línea continua usando un borde inferior */
        ${tocSettings.leaderStyle === 'solid' ? `
        .toc-title::after {
            content: "" !important;
            position: absolute !important;
            border-bottom: 1.5px solid #a0aec0 !important;
            width: 2000px !important;
            height: 0 !important;
            margin-left: 6px !important;
            bottom: 5px !important;
        }
        ` : ''}

        /* Omitimos .toc-leader de la estructura para usar el pseudo-elemento inline que fluye nativamente en la misma caja de texto */
        .toc-leader {
            display: none !important;
        }
        
        .toc-page {
            flex-shrink: 0 !important;
            white-space: nowrap !important;
            font-variant-numeric: tabular-nums !important;
            text-align: right !important;
            margin-left: 8px !important;
            align-self: end !important;
        }

        .pdf-content .chapter-prefix-line {
            width: 50px;
            height: 1px;
            background-color: #000;
            margin: 10px auto;
        }

        /* Si está alineado a la izquierda o derecha, ajustar el margen de la línea */
        ${settings.chapter_title_align === 'left' ? '.pdf-content .chapter-prefix-line { margin-left: 0; }' : ''}
        ${settings.chapter_title_align === 'right' ? '.pdf-content .chapter-prefix-line { margin-right: 0; }' : ''}

        .pdf-content .chapter-prefix-wrapper[data-ornament="line_above_below"]::before {
            content: '';
            display: block;
            width: 50px;
            height: 1px;
            background-color: #000;
            margin: 10px auto 10px ${settings.chapter_title_align === 'left' ? '0' : (settings.chapter_title_align === 'right' ? 'auto' : 'auto')};
        }
        
        ${settings.chapter_title_align === 'right' && settings.chapter_prefix_ornament === 'line_above_below' ? '.pdf-content .chapter-prefix-wrapper[data-ornament="line_above_below"]::before { margin-left: auto; margin-right: 0; }' : ''}

        .pdf-content .chapter-prefix-asterisks {
            margin: 5px 0;
            letter-spacing: 3px;
        }

        .pdf-content .chapter-prefix-wrapper {
            text-align: ${settings.chapter_title_align || 'center'} !important;
            margin-bottom: 5px;
            page-break-after: avoid !important;
            break-after: avoid !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .pdf-content .chapter-prefix-text {
            font-family: '${settings.chapter_prefix_font_family || 'Playfair Display'}', serif !important;
            font-size: ${toPx(settings.chapter_prefix_font_size || 16.0, true)}px !important;
            font-weight: ${settings.chapter_prefix_font_weight || 'normal'} !important;
            font-style: ${settings.chapter_prefix_font_style || 'normal'} !important;
            letter-spacing: ${settings.chapter_prefix_letter_spacing || 0}px !important;
        }

        /* ── Encabezados h1/h2/h3 ── */
        .pdf-content h1 {
            font-family: '${settings.font_family_h1 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h1 || 'bold'} !important;
            font-style: ${settings.font_style_h1 || 'normal'} !important;
            font-size: ${toPx(settings.font_size_h1 || 24.0, true)}px !important;
            line-height: ${settings.line_height_h1 || 1.3} !important;
            text-align: ${settings.text_align_h1 || 'center'} !important;
            margin-top: ${toPx(settings.margin_top_h1 !== undefined ? settings.margin_top_h1 : 24.0, true)}px !important;
            margin-bottom: ${toPx(settings.margin_bottom_h1 !== undefined ? settings.margin_bottom_h1 : 16.0, true)}px !important;
            color: #0f172a !important;
            text-indent: 0 !important;
            page-break-after: avoid;
            break-after: avoid;
        }

        .pdf-content h2 {
            font-family: '${settings.font_family_h2 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h2 || 'bold'} !important;
            font-style: ${settings.font_style_h2 || 'italic'} !important;
            font-size: ${toPx(settings.font_size_h2 || 18.0, true)}px !important;
            line-height: ${settings.line_height_h2 || 1.4} !important;
            text-align: ${settings.text_align_h2 || 'left'} !important;
            margin-top: ${toPx(settings.margin_top_h2 !== undefined ? settings.margin_top_h2 : 20.0, true)}px !important;
            margin-bottom: ${toPx(settings.margin_bottom_h2 !== undefined ? settings.margin_bottom_h2 : 12.0, true)}px !important;
            color: #1e293b !important;
            text-indent: 0 !important;
            page-break-after: avoid;
            break-after: avoid;
        }

        .pdf-content h3 {
            font-family: '${settings.font_family_h3 || 'Merriweather'}', serif !important;
            font-weight: ${settings.font_weight_h3 || 'bold'} !important;
            font-style: ${settings.font_style_h3 || 'normal'} !important;
            font-size: ${toPx(settings.font_size_h3 || 14.0, true)}px !important;
            line-height: ${settings.line_height_h3 || 1.4} !important;
            text-align: ${settings.text_align_h3 || 'left'} !important;
            margin-top: ${toPx(settings.margin_top_h3 !== undefined ? settings.margin_top_h3 : 16.0, true)}px !important;
            margin-bottom: ${toPx(settings.margin_bottom_h3 !== undefined ? settings.margin_bottom_h3 : 8.0, true)}px !important;
            color: #334155 !important;
            text-indent: 0 !important;
            page-break-after: avoid;
            break-after: avoid;
        }

        /* ── Notas al pie ── */
        .pdf-footnotes {
            padding-top: ${toPx(0.5)}px !important;
            margin-top: 0 !important;
            border-top: none !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx((parseFloat(settings.font_size_content) || 11.5) * 0.8, true)}px !important;
            line-height: 1.4 !important;
            color: #475569 !important;
        }

        .pdf-footnotes::before {
            content: '';
            display: block;
            width: 100%; /* El ancho del contenedor de texto, sin incluir el padding de la página */
            border-top: 0.5px solid #000; /* Línea más fina posible y negra */
            margin-bottom: ${toPx(0.3)}px; /* Espacio entre la línea y las notas */
        }

        .pdf-footnote-item {
            margin-bottom: ${toPx(4, true)}px !important;
            text-align: justify !important;
            text-indent: 0 !important;
            display: flex;
            gap: 4px;
        }

        .pdf-footnote-ref {
            font-size: 0.75em;
            vertical-align: super;
            line-height: 0;
            color: #64748b;
        }
    `;

    // Buscar capítulo de créditos en el estado global para aplicar estilos específicos escalados
    if (typeof bookState !== 'undefined' && bookState.chapters) {
        const creditsChapter = bookState.chapters.find(c => c.is_credits === '1');
        if (creditsChapter) {
            let creditsSpecificCss = '';
            if (creditsChapter.credits_font_family) creditsSpecificCss += `font-family: "${creditsChapter.credits_font_family}", serif !important;\n`;
            if (creditsChapter.credits_font_size) creditsSpecificCss += `font-size: ${toPx(creditsChapter.credits_font_size, true)}px !important;\n`;
            if (creditsChapter.credits_font_weight) creditsSpecificCss += `font-weight: ${creditsChapter.credits_font_weight} !important;\n`;
            
            // Si la alineación es vacía (opción "Global / Centro"), aplicamos center por defecto
            const align = creditsChapter.credits_align || 'center';
            creditsSpecificCss += `text-align: ${align} !important;\n`;
            
            if (creditsChapter.credits_letter_spacing) creditsSpecificCss += `letter-spacing: ${toPx(creditsChapter.credits_letter_spacing, true)}px !important;\n`;
            
            if (creditsSpecificCss) {
                styles += `
                .credits-page-content, 
                .credits-page-content p, 
                .credits-page-content div, 
                .credits-page-content ul, 
                .credits-page-content ol,
                .credits-page-content h1,
                .credits-page-content h2,
                .credits-page-content h3 {
                    ${creditsSpecificCss}
                }
                `;
            }
        }
    }

    return styles;
}
