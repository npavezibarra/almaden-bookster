// ============================================================
// MÓDULO: editor-pdf-styles-flow.js
// Responsabilidad: Reglas de flujo, fragmentación y notas al pie.
// ============================================================

function getPDFStylesFlow(settings, toPx) {
    const contentTextAlign = settings.content_text_align || 'justify';
    const contentTextAlignLast = settings.content_text_align_last
        || (contentTextAlign === 'justify' ? 'left' : contentTextAlign);
    const footnoteFontFamily = settings.footnote_font_family || settings.font_family_content || 'Merriweather';
    const footnoteFontSize = settings.footnote_font_size !== undefined && settings.footnote_font_size !== ''
        ? parseFloat(settings.footnote_font_size)
        : Math.max(parseFloat(settings.font_size_content || 11.5) * 0.75, 8.0);
    const footnoteFontWeight = settings.footnote_font_weight || 'normal';
    const normalizeFootnoteLineHeight = (value, fontSize, fallback = 11.5) => {
        const parsed = parseFloat(value);
        if (!Number.isFinite(parsed)) {
            return fallback;
        }
        const resolvedFontSize = Number.isFinite(parseFloat(fontSize)) && parseFloat(fontSize) > 0 ? parseFloat(fontSize) : 8.5;
        const resolved = parsed > 0 && parsed <= 4 ? parsed * resolvedFontSize : parsed;
        return Math.max(4, Math.min(40, resolved));
    };
    const footnoteAlign = ['left', 'center', 'right', 'justify'].includes(String(settings.footnote_align || '').toLowerCase())
        ? String(settings.footnote_align).toLowerCase()
        : 'left';
    const footnoteAlignLast = footnoteAlign === 'justify' ? 'left' : footnoteAlign;
    const footnoteLineHeight = normalizeFootnoteLineHeight(settings.footnote_line_height, footnoteFontSize);
    const footnoteLetterSpacing = settings.footnote_letter_spacing !== undefined && settings.footnote_letter_spacing !== ''
        ? Math.max(-20, Math.min(20, parseFloat(settings.footnote_letter_spacing)))
        : 0;
    const footnoteEntrySpacing = settings.footnote_entry_spacing !== undefined && settings.footnote_entry_spacing !== ''
        ? Math.max(0, Math.min(40, parseFloat(settings.footnote_entry_spacing)))
        : 6;
    const footnoteHyphenate = Number(settings.footnote_hyphenate || 0) === 1;
    const footnoteCallScale = settings.footnote_call_scale !== undefined && settings.footnote_call_scale !== ''
        ? parseFloat(settings.footnote_call_scale)
        : 0.65;
    const footnoteCallRaise = settings.footnote_call_raise !== undefined && settings.footnote_call_raise !== ''
        ? parseFloat(settings.footnote_call_raise)
        : 0.18;
    const footnotePaddingTop = settings.footnote_padding_top !== undefined ? settings.footnote_padding_top : 0.15;
    const footnotePaddingBottom = settings.footnote_padding_bottom !== undefined ? settings.footnote_padding_bottom : 0.15;
    const footnotePaddingLeft = settings.footnote_padding_left !== undefined ? settings.footnote_padding_left : 0;
    const footnotePaddingRight = settings.footnote_padding_right !== undefined ? settings.footnote_padding_right : 0;
    const footnoteSeparatorEnabled = Number(settings.footnote_separator_show || 0) === 1;
    const footnoteSeparatorAlign = ['left', 'center', 'right'].includes(String(settings.footnote_separator_align || '').toLowerCase())
        ? String(settings.footnote_separator_align).toLowerCase()
        : 'left';
    const footnoteSeparatorWidth = ['100', '75', '50', '25'].includes(String(settings.footnote_separator_width || '100'))
        ? String(settings.footnote_separator_width)
        : '100';
    const footnoteSeparatorThickness = settings.footnote_separator_thickness !== undefined && settings.footnote_separator_thickness !== ''
        ? parseFloat(settings.footnote_separator_thickness)
        : 0.25;
    const footnoteSeparatorMarginBottom = settings.footnote_separator_margin_bottom !== undefined && settings.footnote_separator_margin_bottom !== ''
        ? settings.footnote_separator_margin_bottom
        : 0.15;
    const footnoteSeparatorCss = footnoteSeparatorEnabled ? `
        .pagedjs_pagebox > .pagedjs_area > .pagedjs_footnote_area > .pagedjs_footnote_content::before {
            content: "";
            display: block;
            width: ${footnoteSeparatorWidth}%;
            height: ${footnoteSeparatorThickness}pt;
            background-color: #cbd5e1;
            margin: 0 0 ${toPx(footnoteSeparatorMarginBottom, false)}px 0;
            margin-left: ${footnoteSeparatorAlign === 'center' ? 'auto' : footnoteSeparatorAlign === 'right' ? 'auto' : '0'};
            margin-right: ${footnoteSeparatorAlign === 'center' ? 'auto' : footnoteSeparatorAlign === 'left' ? 'auto' : '0'};
            flex: 0 0 auto;
        }

        .pagedjs_pagebox > .pagedjs_area > .pagedjs_footnote_area > .pagedjs_footnote_content.pagedjs_footnote_empty::before {
            display: none;
        }
    ` : '';
    const pageColumnsEnabled = Number(settings.page_columns_enabled || 0) === 1;
    const pageColumnsCount = Math.max(1, Math.min(4, parseInt(settings.page_columns_count || 2, 10) || 2));
    const pageColumnsGap = settings.page_columns_gap !== undefined && settings.page_columns_gap !== ''
        ? Math.max(0, parseFloat(settings.page_columns_gap))
        : 0.8;
    const pageColumnsCss = pageColumnsEnabled ? `
        .pdf-content .chapter-editable-content {
            column-count: ${pageColumnsCount} !important;
            column-gap: ${toPx(pageColumnsGap, false)}px !important;
            column-fill: auto !important;
        }

        .pdf-content .chapter-editable-content > p,
        .pdf-content .chapter-editable-content > ul,
        .pdf-content .chapter-editable-content > ol,
        .pdf-content .chapter-editable-content > blockquote,
        .pdf-content .chapter-editable-content > div,
        .pdf-content .chapter-editable-content > figure,
        .pdf-content .chapter-editable-content > table {
            break-inside: avoid-column !important;
            page-break-inside: avoid !important;
        }
    ` : '';
    return `
        .pdf-content .chapter-editable-content,
        .pdf-content .chapter-editable-content > p,
        .pdf-content .chapter-editable-content > ul,
        .pdf-content .chapter-editable-content > ol,
        .pdf-content .almaden-align-left,
        .pdf-content .almaden-align-center,
        .pdf-content .almaden-align-right,
        .pdf-content .almaden-align-justify {
            break-inside: auto !important;
            page-break-inside: auto !important;
        }

        .pdf-content .split-paragraph-start {
            margin-bottom: 0px !important;
            padding-bottom: 0px !important;
        }

        /* Párrafos divididos y continuación (Paged.js) */
        .pdf-content [data-split-from] {
            text-indent: 0 !important;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .pdf-content [data-split-to] {
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        /*
         * El fragmento anterior a un salto de página no es el final semántico
         * del párrafo. Si el cuerpo está justificado, su última línea visible
         * debe justificarse igual que cualquier línea intermedia.
         */
        .pdf-content p[data-split-to],
        .pdf-content li[data-split-to],
        .pdf-content div[data-split-to] {
            text-align-last: ${contentTextAlign === 'justify' ? 'justify' : contentTextAlignLast} !important;
        }

        /* Las alineaciones editoriales explícitas prevalecen sobre el cuerpo. */
        .pdf-content .almaden-align-left[data-split-to],
        .pdf-content .almaden-align-left [data-split-to] {
            text-align-last: left !important;
        }

        .pdf-content .almaden-align-center[data-split-to],
        .pdf-content .almaden-align-center [data-split-to] {
            text-align-last: center !important;
        }

        .pdf-content .almaden-align-right[data-split-to],
        .pdf-content .almaden-align-right [data-split-to] {
            text-align-last: right !important;
        }

        .pdf-content .almaden-align-justify[data-split-to],
        .pdf-content .almaden-align-justify [data-split-to] {
            text-align-last: justify !important;
        }

        /* Las citas nunca deben recibir el estiramiento artificial del último renglón. */
        .pdf-content blockquote,
        .pdf-content blockquote[data-split-to],
        .pdf-content blockquote [data-split-to] {
            text-align: left !important;
            text-align-last: left !important;
        }

        /* Safety buffer to prevent sub-pixel layout overflow clipping */
        .pagedjs_page_content {
            height: calc(100% - var(--pagedjs-footnotes-height)) !important;
        }

        .pdf-content span[data-footnote-id] {
            float: footnote !important;
            footnote-policy: auto !important;
            footnote-display: block !important;
            hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
        }

        .pagedjs_area [data-footnote-call] {
            all: unset;
            counter-increment: footnote;
            display: inline;
        }

        .pagedjs_area [data-footnote-call]::after {
            content: attr(data-footnote-number) !important;
            font-size: ${footnoteCallScale}em !important;
            line-height: 1 !important;
            vertical-align: baseline !important;
            position: relative !important;
            top: -${footnoteCallRaise}em !important;
            font-variant-position: super !important;
        }

        .pagedjs_pagebox > .pagedjs_area > .pagedjs_footnote_area {
            font-family: '${footnoteFontFamily}', serif !important;
            font-size: ${toPx(footnoteFontSize, true)}px !important;
            font-weight: ${footnoteFontWeight} !important;
            color: #475569 !important;
        }

        .pagedjs_pagebox > .pagedjs_area > .pagedjs_footnote_area > .pagedjs_footnote_content {
            position: relative !important;
            padding-top: ${toPx(footnotePaddingTop, false)}px !important;
            padding-bottom: ${toPx(footnotePaddingBottom, false)}px !important;
            padding-left: ${toPx(footnotePaddingLeft, false)}px !important;
            padding-right: ${toPx(footnotePaddingRight, false)}px !important;
            box-sizing: border-box !important;
            width: 100% !important;
        }
        ${footnoteSeparatorCss}

        .pagedjs_pagebox > .pagedjs_area > .pagedjs_footnote_area > .pagedjs_footnote_content > .pagedjs_footnote_inner_content {
            font-family: '${footnoteFontFamily}', serif !important;
            font-size: ${toPx(footnoteFontSize, true)}px !important;
            font-weight: ${footnoteFontWeight} !important;
            line-height: ${toPx(footnoteLineHeight, true)}px !important;
            letter-spacing: ${toPx(footnoteLetterSpacing, true)}px !important;
            text-align: ${footnoteAlign} !important;
            text-align-last: ${footnoteAlignLast} !important;
            hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
            color: #475569 !important;
            width: 100% !important;
        }

        .pagedjs_footnote_area [data-note='footnote'] {
            margin: 0 0 ${toPx(footnoteEntrySpacing, true)}px 0 !important;
            text-indent: 0 !important;
            font-family: '${footnoteFontFamily}', serif !important;
            font-size: ${toPx(footnoteFontSize, true)}px !important;
            font-weight: ${footnoteFontWeight} !important;
            line-height: ${toPx(footnoteLineHeight, true)}px !important;
            letter-spacing: ${toPx(footnoteLetterSpacing, true)}px !important;
            text-align: ${footnoteAlign} !important;
            text-align-last: ${footnoteAlignLast} !important;
            color: #475569 !important;
            hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${footnoteHyphenate ? 'auto' : 'none'} !important;
        }

        .pagedjs_footnote_area [data-note='footnote'] p,
        .pagedjs_footnote_area [data-note='footnote'] div {
            margin: 0 !important;
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

        ${pageColumnsCss}
    `;
}
