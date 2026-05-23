// ============================================================
// MÓDULO: editor-pdf-styles.js
// Responsabilidad: Inyección dinámica de CSS de maquetación
// basado en la configuración del libro (bookState.settings).
// Depende de: editor-pdf-compiler.js (compilePDFPreview)
// ============================================================

// Inyecta dinámicamente las reglas de CSS en base a la configuración de maquetación actual
function applyDynamicPDFStyles() {
    const settings = bookState.settings;
    const styleEl  = document.getElementById('dynamic-pdf-settings');
    if (!settings || !styleEl) return;

    const unit = settings.unit || 'cm';
    let width  = parseFloat(settings.page_width)  || 21;
    let height = parseFloat(settings.page_height) || 29.7;

    if (settings.page_size === 'A4') {
        width  = (unit === 'cm') ? 21.0  : (21.0  / 2.54);
        height = (unit === 'cm') ? 29.7  : (29.7  / 2.54);
    } else if (settings.page_size === 'Letter') {
        width  = (unit === 'cm') ? (8.5  * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    const bleeding = parseFloat(settings.bleeding) || 0;

    const cmToPx = 37.7952755906;
    const ptToPx = 1.3333333333;

    // Helper para convertir cualquier valor a píxeles según su unidad de origen
    const toPx = (value, isPt = false) => {
        if (!value && value !== 0) return 0;
        return isPt ? (parseFloat(value) * ptToPx) : (parseFloat(value) * (unit === 'cm' ? cmToPx : (cmToPx * 2.54)));
    };

    const widthPx = toPx(width);
    const heightPx = toPx(height);
    const bleedingPx = toPx(bleeding);
    // Sangría fija de 5mm para el modo de imagen de paridad con sangría
    const parityBleedingPx = toPx(unit === 'cm' ? 0.5 : (0.5 / 2.54));

    styleEl.innerHTML = `
        @page {
            size: ${widthPx + bleedingPx}px ${heightPx + (bleedingPx * 2)}px;
            margin: 0px;
        }
        @page parity_bleed_page {
            size: ${widthPx + parityBleedingPx}px ${heightPx + (parityBleedingPx * 2)}px;
            margin: 0px;
        }

        /* ── Screen Parity Bleed (3-sided) ── */
        .pdf-page:nth-child(even) .parity-bleed-container {
            top: -${parityBleedingPx}px;
            bottom: -${parityBleedingPx}px;
            left: -${parityBleedingPx}px;
            right: 0;
        }
        .pdf-page:nth-child(odd) .parity-bleed-container {
            top: -${parityBleedingPx}px;
            bottom: -${parityBleedingPx}px;
            left: 0;
            right: -${parityBleedingPx}px;
        }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .pdf-page {
                width: ${widthPx + bleedingPx}px !important;
                height: ${heightPx + (bleedingPx * 2)}px !important;
                min-height: ${heightPx + (bleedingPx * 2)}px !important;
                box-sizing: border-box !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                position: relative !important;
            }
            /* Even pages (spine is on the right, so no right bleed) */
            .pdf-page:nth-child(even) {
                padding: ${bleedingPx}px 0 ${bleedingPx}px ${bleedingPx}px !important;
            }
            /* Odd pages (spine is on the left, so no left bleed) */
            .pdf-page:nth-child(odd) {
                padding: ${bleedingPx}px ${bleedingPx}px ${bleedingPx}px 0 !important;
            }

            .pdf-page.pdf-page-has-bleed {
                page: parity_bleed_page;
                width: ${widthPx + parityBleedingPx}px !important;
                height: ${heightPx + (parityBleedingPx * 2)}px !important;
                min-height: ${heightPx + (parityBleedingPx * 2)}px !important;
            }
            .pdf-page.pdf-page-has-bleed:nth-child(even) {
                padding: ${parityBleedingPx}px 0 ${parityBleedingPx}px ${parityBleedingPx}px !important;
            }
            .pdf-page.pdf-page-has-bleed:nth-child(odd) {
                padding: ${parityBleedingPx}px ${parityBleedingPx}px ${parityBleedingPx}px 0 !important;
            }
            .pdf-page::after { display: none !important; }
            .parity-bleed-container {
                top: 0 !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
            }
        }

        /* ── Página ── */
        .pdf-page {
            display: flex !important;
            flex-direction: column !important;
            width: ${widthPx}px !important;
            height: ${heightPx}px !important;
            min-height: ${heightPx}px !important;
            padding: 0 !important;
            margin-top: ${36 + bleedingPx}px !important;
            margin-bottom: ${36 + bleedingPx}px !important;
            border: ${bleeding > 0 ? '2px dashed #f59e0b' : '1px solid #e2e8f0'} !important;
            position: relative;
            box-sizing: border-box !important;
            background-color: white !important;
            color: #1e293b !important;
        }
        ${bleeding > 0 ? `
        .pdf-page::after {
            content: "Línea de Sangría (${bleeding}${unit})";
            position: absolute;
            top: -15px; left: 0;
            font-size: 8px;
            color: #f59e0b;
            font-family: sans-serif;
            font-weight: bold;
        }` : ''}

        /* ── Spread View (Pantalla) ── */
        #pdf-scroller.spread-view {
            display: grid !important;
            grid-template-columns: max-content max-content;
            justify-content: center;
            column-gap: 0;
            padding: 40px 0; /* Fallback top/bottom spacing instead of row-gap */
        }
        #pdf-scroller.spread-view .pdf-page {
            margin-top: 40px !important;
            margin-bottom: 40px !important;
        }
        /* ODD pages (Right side of the spread) -> Grid Column 2 */
        #pdf-scroller.spread-view .pdf-page:nth-child(odd) {
            grid-column: 2;
            border-left: none !important; /* Remove double border in the middle */
        }
        /* EVEN pages (Left side of the spread) -> Grid Column 1 */
        #pdf-scroller.spread-view .pdf-page:nth-child(even) {
            grid-column: 1;
            border-right: none !important; /* Remove double border in the middle */
        }

        /* ── Cabecera ── */
        .pdf-header {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${toPx(settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0)}px !important;
            padding-bottom: ${toPx(settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5)}px !important;
            font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.header_font_size || 8.5, true)}px !important;
            font-weight: ${settings.header_font_weight || 'normal'} !important;
            font-style: ${settings.header_font_style || 'normal'} !important;
            letter-spacing: ${toPx(settings.header_letter_spacing || 0.1, true)}px !important;
            color: #475569 !important;
            text-align: ${settings.header_align || 'center'} !important;
        }

        /* ── Contenido ── */
        .pdf-content {
            flex: 1 !important;
            box-sizing: border-box !important;
            padding-top: ${toPx(settings.padding_top)}px !important;
            padding-bottom: ${toPx(settings.padding_bottom)}px !important;
            margin-left: 0px !important;
            margin-right: 0px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.font_size_content || 11.5, true)}px !important;
            line-height: ${settings.line_height_content || 1.65} !important;
        }
        .pdf-content p, .pdf-content ul, .pdf-content ol {
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.font_size_content || 11.5, true)}px !important;
            line-height: ${settings.line_height_content || 1.65} !important;
            text-align: ${settings.content_text_align || 'justify'} !important;
            hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
            text-rendering: geometricPrecision !important;
            -webkit-font-smoothing: antialiased !important;
        }
        .pdf-content p {
            margin-bottom: ${toPx(settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0, true)}px !important;
            text-indent: ${toPx(settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0, true)}px !important;
        }
        .pdf-content p.split-paragraph-start {
            text-align-last: justify !important;
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
            width: 100% !important;
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
            text-align: ${settings.chapter_title_align || 'center'} !important;
            padding-top: ${toPx(settings.chapter_title_padding_top || 0.0)}px !important;
            padding-bottom: ${toPx(settings.chapter_title_padding_bottom || 1.5)}px !important;
            line-height: ${settings.chapter_title_line_height || 1.2} !important;
            margin: 0 !important;
            page-break-after: avoid;
            width: 100%;
        }

        /* ── Prefijo de Capítulo ── */
        .pdf-content .chapter-prefix-wrapper {
            text-align: ${settings.chapter_title_align || 'center'} !important;
            margin-bottom: ${settings.chapter_prefix_position === 'above' ? '15px' : '0'} !important;
            margin-top: ${settings.chapter_prefix_position === 'below' ? '15px' : '0'} !important;
        }
        
        .pdf-content .chapter-prefix-text {
            font-family: '${settings.chapter_prefix_font_family || 'Playfair Display'}', serif !important;
            font-size: ${toPx(settings.chapter_prefix_font_size || 16.0, true)}px !important;
            font-weight: ${settings.chapter_prefix_font_weight || 'normal'} !important;
            font-style: ${settings.chapter_prefix_font_style || 'normal'} !important;
            letter-spacing: ${toPx(settings.chapter_prefix_letter_spacing || 0, true)}px !important;
            text-transform: uppercase;
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
            margin-top: ${toPx(0.5)}px !important;
            padding-top: ${toPx(0.3)}px !important;
            border-top: 1px solid #cbd5e1 !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${toPx((parseFloat(settings.font_size_content) || 11.5) * 0.8, true)}px !important;
            line-height: 1.4 !important;
            color: #475569 !important;
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

        /* ── Pie de página ── */
        .pdf-footer {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${toPx(settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5)}px !important;
            padding-bottom: ${toPx(settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0)}px !important;
            font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
            font-size: ${toPx(settings.footer_font_size || 9.0, true)}px !important;
            font-weight: ${settings.footer_font_weight || 'normal'} !important;
            font-style: ${settings.footer_font_style || 'normal'} !important;
            letter-spacing: ${toPx(settings.footer_letter_spacing || 0.0, true)}px !important;
            color: #475569 !important;
            text-align: ${settings.footer_align || 'center'} !important;
        }

        /* ── Márgenes laterales por paridad ── */
        .pdf-page:nth-child(odd) .pdf-header,
        .pdf-page:nth-child(odd) .pdf-content,
        .pdf-page:nth-child(odd) .pdf-footnotes,
        .pdf-page:nth-child(odd) .pdf-footer {
            padding-left: ${toPx(parseFloat(settings.margin_left_odd  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0))}px !important;
            padding-right: ${toPx(parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0))}px !important;
        }
        .pdf-page:nth-child(even) .pdf-header,
        .pdf-page:nth-child(even) .pdf-content,
        .pdf-page:nth-child(even) .pdf-footnotes,
        .pdf-page:nth-child(even) .pdf-footer {
            padding-left: ${toPx(parseFloat(settings.margin_left_even  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0))}px !important;
            padding-right: ${toPx(parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0))}px !important;
        }
    `;

    compilePDFPreview();
}
