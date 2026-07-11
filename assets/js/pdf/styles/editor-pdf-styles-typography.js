// ============================================================
// MÓDULO: editor-pdf-styles-typography.js
// Responsabilidad: Generar el CSS tipográfico, párrafos, alineación, TOC e índices.
// ============================================================

function getPDFStylesTypography(settings, tocSettings, toPx) {
    const contentTextAlign = settings.content_text_align || 'justify';
    const contentTextAlignLast = settings.content_text_align_last
        || (contentTextAlign === 'justify' ? 'left' : contentTextAlign);
    const contentLanguage = String(settings.content_language || 'es').toLowerCase();
    const contentHyphenation = settings.content_hyphenation == 1
        ? (contentLanguage.startsWith('es') ? 'manual' : 'auto')
        : 'none';
    const tocItemAlign = ['left', 'center', 'right'].includes(String(tocSettings.itemAlign || '').toLowerCase())
        ? String(tocSettings.itemAlign).toLowerCase()
        : 'left';
    const tocItemJustify = tocItemAlign === 'right'
        ? 'flex-end'
        : (tocItemAlign === 'center' ? 'center' : 'flex-start');
    const chapterTitleAlign = ['left', 'center', 'right'].includes(String(settings.chapter_title_align || '').toLowerCase())
        ? String(settings.chapter_title_align).toLowerCase()
        : 'center';
    const chapterTitleJustify = chapterTitleAlign === 'left'
        ? 'flex-start'
        : (chapterTitleAlign === 'right' ? 'flex-end' : 'center');
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
            text-align: ${contentTextAlign} !important;
            text-align-last: ${contentTextAlignLast} !important;
            hyphens: ${contentHyphenation} !important;
            -webkit-hyphens: ${contentHyphenation} !important;
            hyphenate-limit-chars: auto !important;
            hyphenate-limit-lines: 2 !important;
            -webkit-hyphenate-limit-before: auto !important;
            -webkit-hyphenate-limit-after: auto !important;
            orphans: 2 !important;
            widows: 2 !important;
            text-rendering: geometricPrecision !important;
            -webkit-font-smoothing: antialiased !important;
        }
        .pdf-content p {
            margin-bottom: ${toPx(settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0, true)}px !important;
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }

        .pdf-content .almaden-foreign,
        .pdf-content .almaden-foreign * {
            hyphens: auto !important;
            -webkit-hyphens: auto !important;
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
            text-align-last: ${contentTextAlignLast} !important;
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }

        .pdf-content .chapter-opening-block + p,
        .pdf-content .chapter-opening-content + p,
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
            width: auto !important;
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
            text-align: ${chapterTitleAlign} !important;
            text-align-last: auto !important;
            word-spacing: normal !important;
            text-wrap: balance !important;
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

        .pdf-content .chapter-opening-block {
            width: 100% !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: stretch !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .pdf-content .chapter-opening-content {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .pdf-content .chapter-opening-content[data-align="left"] {
            text-align: left !important;
        }

        .pdf-content .chapter-opening-content[data-align="center"] {
            text-align: center !important;
        }

        .pdf-content .chapter-opening-content[data-align="right"] {
            text-align: right !important;
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
            text-align: ${tocItemAlign} !important;
            text-align-last: ${tocItemAlign} !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }

        /* ── Estilos TOC por nivel ── */
        .toc-item {
            display: flex !important;
            justify-content: ${tocItemJustify} !important;
            align-items: baseline !important;
            width: 100% !important;
            hyphens: none !important;
            text-align: ${tocItemAlign} !important;
            text-align-last: ${tocItemAlign} !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
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
            text-align: ${tocItemAlign} !important;
            align-self: baseline !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }

        .toc-title-wrapper {
            flex-grow: 1 !important;
            display: grid !important;
            grid-template-columns: auto max-content;
            grid-template-areas: "title leader";
            align-items: baseline !important; /* Aligns dots with the baseline of the last line */
            gap: 0 4px !important;
            overflow: hidden !important;
            min-width: 0 !important;
            position: relative !important;
            text-align: ${tocItemAlign} !important;
            text-align-last: ${tocItemAlign} !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }

        .toc-title {
            grid-area: title !important;
            display: inline !important;
            white-space: normal !important;
            word-break: break-word !important;
            position: relative !important;
            text-align: ${tocItemAlign} !important;
            text-align-last: ${tocItemAlign} !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
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
            text-align: ${tocItemAlign} !important;
            margin-left: 8px !important;
            align-self: baseline !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }

        .pdf-content .chapter-prefix-line {
            width: 50px;
            height: 1px;
            background-color: #000;
            margin: 10px auto;
        }

        /* Si está alineado a la izquierda o derecha, ajustar el margen de la línea */
        ${chapterTitleAlign === 'left' ? '.pdf-content .chapter-prefix-line { margin-left: 0; }' : ''}
        ${chapterTitleAlign === 'right' ? '.pdf-content .chapter-prefix-line { margin-right: 0; }' : ''}

        .pdf-content .chapter-prefix-wrapper[data-ornament="line_above_below"]::before {
            content: '';
            display: block;
            width: 50px;
            height: 1px;
            background-color: #000;
            margin: 10px auto 10px ${chapterTitleAlign === 'left' ? '0' : (chapterTitleAlign === 'right' ? 'auto' : 'auto')};
        }

        ${chapterTitleAlign === 'right' && settings.chapter_prefix_ornament === 'line_above_below' ? '.pdf-content .chapter-prefix-wrapper[data-ornament="line_above_below"]::before { margin-left: auto; margin-right: 0; }' : ''}

        .pdf-content .chapter-prefix-asterisks {
            margin: 5px 0;
            letter-spacing: 3px;
        }

        .pdf-content .chapter-prefix-wrapper {
            text-align: ${chapterTitleAlign} !important;
            width: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: ${chapterTitleJustify} !important;
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
            word-spacing: normal !important;
            display: inline-flex !important;
            justify-content: center !important;
            align-items: baseline !important;
            width: auto !important;
            white-space: nowrap !important;
            text-indent: 0 !important;
        }

        .pdf-content .chapter-prefix-number {
            display: inline-block !important;
            margin-left: 0.25em !important;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
            white-space: nowrap !important;
        }

        .pdf-content .chapter-subtitle {
            word-spacing: normal !important;
            text-align-last: auto !important;
            text-wrap: balance !important;
            hyphens: none !important;
            -webkit-hyphens: none !important;
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
                .credits-page-content ul,
                .credits-page-content ol,
                .credits-page-content h1,
                .credits-page-content h2,
                .credits-page-content h3 {
                    ${creditsSpecificCss}
                }
                .credits-page-content .credits-copyright,
                .credits-page-content .credits-copyright p {
                    text-align: justify !important;
                    text-align-last: left !important;
                    text-indent: 0 !important;
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }
                .credits-page-content .credits-copyright p {
                    margin-top: 0 !important;
                    margin-bottom: 0 !important;
                }
                `;
            }
        }
    }

    return styles;
}
