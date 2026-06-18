// ============================================================
// MÓDULO: editor-pdf-styles-typography.js
// Responsabilidad: Generar el CSS tipográfico, párrafos, alineación, TOC e índices.
// ============================================================

function getPDFStylesTypography(settings, tocSettings, toPx) {
    return `
        /* ── Contenido ── */
        .pdf-content {
            flex: 1 !important;
            min-height: 0 !important;
            box-sizing: border-box !important;
            padding-top: ${toPx(settings.padding_top)}px !important;
            padding-bottom: ${toPx(settings.padding_bottom)}px !important;
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
            hyphens: auto !important;
            -webkit-hyphens: auto !important;
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
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }

        .pdf-content .split-paragraph-start {
            margin-bottom: 0px !important;
            padding-bottom: 0px !important;
        }

        .pdf-content .deep-split-start {
            text-align-last: justify !important;
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

        /* ── Bloque TOC y Listas ── */
        .toc-list-block {
            margin: 0;
            padding: 0;
            list-style: none;
            width: 100%;
        }

        /* ── Estilos TOC por nivel ── */
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
            display: flex;
            align-items: baseline;
            width: 100%;
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
            display: flex;
            align-items: baseline;
            width: 100%;
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
            display: flex;
            align-items: baseline;
            width: 100%;
            position: relative;
        }
        
        .toc-spacer-left {
            /* Spacer izquierdo no lleva puntos */
        }
        .toc-spacer-right {
            border-bottom: ${tocSettings.leaderStyle === 'none' ? 'none' : '1.5px ' + tocSettings.leaderStyle + ' #a0aec0'};
            position: relative;
            bottom: ${tocSettings.leaderPosition === 'bottom' ? '0' : '4px'};
            min-width: 5px; /* Pequeño margen para los puntos */
        }
        .toc-title {
            padding-left: ${tocSettings.itemAlign !== 'left' ? '5px' : '0'};
            padding-right: 5px;
            text-align: ${tocSettings.itemAlign};
            /* Background no longer strictly needed to mask dots, but good for safety */
        }
        .toc-page {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            text-align: right;
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
}
