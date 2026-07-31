// ============================================================
// MÓDULO: editor-pdf-styles-typography-extended.js
// Responsabilidad: Secciones visuales complejas del CSS PDF
// (imágenes, títulos, TOC, prefijos de capítulo y subtítulos).
// ============================================================

function getPDFStylesTypographyExtended(settings, tocSettings, toPx, chapterTitleAlign, chapterTitleJustify) {
    const tocItemAlign = ['left', 'center', 'right'].includes(String(tocSettings.itemAlign || '').toLowerCase())
        ? String(tocSettings.itemAlign).toLowerCase()
        : 'left';
    const tocItemJustify = tocItemAlign === 'right'
        ? 'flex-end'
        : (tocItemAlign === 'center' ? 'center' : 'flex-start');

    return `
        /* ── Imágenes ── */
        .pdf-content img.pdf-book-image {
            width: auto !important;
            max-width: 100% !important;
            height: auto !important;
            display: block !important;
            margin: 20px auto !important;
            page-break-inside: avoid;
        }

        .pdf-content figure.pdf-book-image-block,
        .pdf-content .pdf-book-image-frame {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 20px auto 10px auto !important;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box !important;
            position: relative !important;
        }

        .pdf-content figure.pdf-book-image-block {
            overflow: visible !important;
        }

        .pdf-content .pdf-book-image-frame {
            overflow: hidden !important;
        }

        .pdf-content .pdf-book-image-frame > img {
            display: block !important;
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }

        .pdf-content .pdf-book-image-caption {
            display: block !important;
            margin-top: 0.5px !important;
            font-size: 10px !important;
            line-height: 1.2 !important;
            color: #4b5563 !important;
            text-align: left !important;
            font-style: italic !important;
            word-break: break-word !important;
        }

        .pdf-content .pdf-book-image-frame.is-empty {
            min-height: 180px !important;
            border-radius: 18px !important;
            border: 2px dashed #cbd5e1 !important;
            background: #f8fafc !important;
            color: #64748b !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 24px !important;
        }

        .pdf-content .pdf-book-image-placeholder {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 100% !important;
            min-height: 140px !important;
            text-align: center !important;
            font-family: 'Urbanist', sans-serif !important;
            font-weight: 700 !important;
            letter-spacing: 0.02em !important;
            color: #475569 !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
        }

        .pdf-content .pdf-book-image-edit-handle {
            position: absolute !important;
            top: 12px !important;
            right: 12px !important;
            z-index: 4 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            padding: 8px 12px !important;
            border: 1px solid rgba(15, 23, 42, 0.12) !important;
            border-radius: 999px !important;
            background: rgba(255, 255, 255, 0.94) !important;
            color: #0f172a !important;
            font-family: 'Urbanist', sans-serif !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12) !important;
            cursor: pointer !important;
            opacity: 0.55 !important;
            transition: opacity 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease !important;
        }

        .pdf-content .pdf-book-image-edit-handle:hover,
        .pdf-content .pdf-book-image-block:hover .pdf-book-image-edit-handle {
            opacity: 1 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16) !important;
        }

        .pdf-content .pdf-book-image-edit-handle i {
            font-size: 11px !important;
        }

        .pdf-content .pdf-book-image-edit-handle span {
            white-space: nowrap !important;
        }

        /* ── Título de Capítulo ── */
        .pdf-content .chapter-main-title {
            font-family: '${settings.chapter_title_font_family || 'Playfair Display'}', serif !important;
            font-size: ${toPx(settings.chapter_title_font_size || 24.0, true)}px !important;
            font-weight: ${settings.chapter_title_font_weight || 'bold'} !important;
            font-style: ${settings.chapter_title_font_style || 'normal'} !important;
            text-transform: ${settings.chapter_title_text_transform || 'none'} !important;
            letter-spacing: ${toPx(settings.chapter_title_letter_spacing || 0, true)}px !important;
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

        .pdf-content .chapter-opening-page-block--blank {
            width: 100% !important;
            box-sizing: border-box !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .pdf-content .chapter-opening-page-block--blank .chapter-opening-content {
            width: 100% !important;
            max-width: 100% !important;
        }

        .pdf-content .chapter-opening-content {
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .pdf-content .chapter-opening-content[data-align="left"] { text-align: left !important; }
        .pdf-content .chapter-opening-content[data-align="center"] { text-align: center !important; }
        .pdf-content .chapter-opening-content[data-align="right"] { text-align: right !important; }

        /* ── Título del Índice (TOC) ── */
        .pdf-content .toc-main-title {
            font-family: '${tocSettings.titleFontFamily || 'Playfair Display'}', serif !important;
            font-size: ${toPx(tocSettings.titleFontSize || 24.0, true)}px !important;
            font-weight: ${tocSettings.titleFontWeight || 'bold'} !important;
            font-style: ${tocSettings.titleFontStyle || 'normal'} !important;
            text-transform: ${tocSettings.titleTextTransform || 'none'} !important;
            letter-spacing: ${toPx(tocSettings.titleLetterSpacing || 0, true)}px !important;
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

        .toc-item-h1,
        .toc-item-h2,
        .toc-item-h3 {
            font-family: '${tocSettings.fontFamily}', serif !important;
            font-style: ${tocSettings.fontStyle} !important;
            text-transform: ${tocSettings.textTransform} !important;
            letter-spacing: ${toPx(tocSettings.letterSpacing, true)}px !important;
            line-height: ${tocSettings.lineHeight} !important;
        }

        .toc-item-h1 {
            font-size: ${toPx(tocSettings.fontSize, true)}px !important;
            font-weight: ${tocSettings.fontWeight} !important;
            margin-bottom: ${toPx(tocSettings.itemSpacing, true)}px !important;
            padding-left: 0;
            position: relative;
        }

        .toc-item-h2 {
            font-size: ${toPx(tocSettings.fontSize * 0.9, true)}px !important;
            font-weight: normal !important;
            margin-bottom: ${toPx(tocSettings.itemSpacing * 0.8, true)}px !important;
            padding-left: 20px;
            position: relative;
        }

        .toc-item-h3 {
            font-size: ${toPx(tocSettings.fontSize * 0.85, true)}px !important;
            font-weight: normal !important;
            font-style: italic !important;
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
            grid-template-columns: minmax(0, 1fr) max-content;
            grid-template-areas: "title leader";
            align-items: baseline !important;
            gap: 0 10px !important;
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
            display: block !important;
            white-space: normal !important;
            word-break: break-word !important;
            position: relative !important;
            min-width: 0 !important;
            overflow: hidden !important;
            padding-right: 6px !important;
            text-align: ${tocItemAlign} !important;
            text-align-last: ${tocItemAlign} !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }

        .toc-title::after {
            content: ${
                tocSettings.leaderStyle === 'dotted'
                    ? '" . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . . ."'
                    : (tocSettings.leaderStyle === 'dashed'
                        ? '" - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -"'
                        : '""')
            } !important;
            position: absolute !important;
            padding-left: 6px !important;
            white-space: nowrap !important;
            letter-spacing: ${tocSettings.leaderStyle === 'dotted' ? '3px' : '0px'} !important;
            color: #a0aec0 !important;
        }

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

        .toc-leader {
            display: none !important;
        }

        .toc-page {
            flex-shrink: 0 !important;
            white-space: nowrap !important;
            font-variant-numeric: tabular-nums !important;
            text-align: ${tocItemAlign} !important;
            margin-left: 0 !important;
            padding-left: 2px !important;
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
    `;
}
