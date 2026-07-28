// ============================================================
// MÓDULO: editor-pdf-styles-typography.js
// Responsabilidad: Generar el CSS tipográfico, párrafos, alineación, TOC e índices.
// ============================================================

function getPDFStylesTypography(settings, tocSettings, toPx) {
    const contentTextAlign = settings.content_text_align || 'justify';
    const contentTextAlignLast = settings.content_text_align_last
        || (contentTextAlign === 'justify' ? 'left' : contentTextAlign);
    const contentLanguage = typeof window.almadenGetBookLanguage === 'function'
        ? window.almadenGetBookLanguage(settings)
        : String(settings.book_language || settings.content_language || 'es').toLowerCase();
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
            text-wrap: wrap !important;
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

        ${typeof getPDFStylesTypographyExtended === 'function'
            ? getPDFStylesTypographyExtended(settings, tocSettings, toPx, chapterTitleAlign, chapterTitleJustify)
            : ''}

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
            text-align: ${settings.text_align_h2 || 'inherit'} !important;
            text-align-last: left !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
            margin-top: ${toPx(settings.margin_top_h2 !== undefined ? settings.margin_top_h2 : 20.0, true)}px !important;
            margin-bottom: ${toPx(settings.margin_bottom_h2 !== undefined ? settings.margin_bottom_h2 : 12.0, true)}px !important;
            color: #1e293b !important;
            text-indent: 0 !important;
            display: block !important;
            width: 100% !important;
            page-break-after: avoid;
            break-after: avoid;
        }

        .pdf-content .chapter-opening-content[data-align="left"] h2,
        .pdf-content .chapter-opening-content[data-align="left"] h2 *,
        .pdf-content .almaden-align-left h2,
        .pdf-content .almaden-align-left h2 * {
            text-align: left !important;
            text-align-last: left !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
            hyphens: none !important;
            -webkit-hyphens: none !important;
        }

        .pdf-content .chapter-opening-content[data-align="center"] h2,
        .pdf-content .chapter-opening-content[data-align="center"] h2 *,
        .pdf-content .almaden-align-center h2,
        .pdf-content .almaden-align-center h2 * {
            text-align: center !important;
            text-align-last: center !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
            hyphens: none !important;
            -webkit-hyphens: none !important;
        }

        .pdf-content .chapter-opening-content[data-align="right"] h2,
        .pdf-content .chapter-opening-content[data-align="right"] h2 *,
        .pdf-content .almaden-align-right h2,
        .pdf-content .almaden-align-right h2 * {
            text-align: right !important;
            text-align-last: right !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
            hyphens: none !important;
            -webkit-hyphens: none !important;
        }

        .pdf-content h3 {
            font-family: '${settings.font_family_h3 || 'Merriweather'}', serif !important;
            font-weight: ${settings.font_weight_h3 || 'bold'} !important;
            font-style: ${settings.font_style_h3 || 'normal'} !important;
            font-size: ${toPx(settings.font_size_h3 || 14.0, true)}px !important;
            line-height: ${settings.line_height_h3 || 1.4} !important;
            text-align: left !important;
            text-align-last: left !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
            margin-top: ${toPx(settings.margin_top_h3 !== undefined ? settings.margin_top_h3 : 16.0, true)}px !important;
            margin-bottom: ${toPx(settings.margin_bottom_h3 !== undefined ? settings.margin_bottom_h3 : 8.0, true)}px !important;
            color: #334155 !important;
            text-indent: 0 !important;
            display: block !important;
            width: 100% !important;
            page-break-after: avoid;
            break-after: avoid;
        }

        .pdf-content .almaden-align-left h3,
        .pdf-content .almaden-align-left h3 *,
        .pdf-content .chapter-semantic-references .almaden-align-left h3,
        .pdf-content .chapter-semantic-references .almaden-align-left h3 * {
            text-align: left !important;
            text-align-last: left !important;
        }

        .pdf-content .almaden-align-center h3,
        .pdf-content .almaden-align-center h3 *,
        .pdf-content .chapter-semantic-references .almaden-align-center h3,
        .pdf-content .chapter-semantic-references .almaden-align-center h3 * {
            text-align: center !important;
            text-align-last: center !important;
        }

        .pdf-content .almaden-align-right h3,
        .pdf-content .almaden-align-right h3 *,
        .pdf-content .chapter-semantic-references .almaden-align-right h3,
        .pdf-content .chapter-semantic-references .almaden-align-right h3 * {
            text-align: right !important;
            text-align-last: right !important;
        }

        .pdf-content .chapter-semantic-references h3 {
            text-align: left !important;
            text-align-last: left !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
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
