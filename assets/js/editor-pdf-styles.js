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

    styleEl.innerHTML = `
        @page {
            size: ${width}${unit} ${height}${unit};
            margin: 0mm;
        }
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .pdf-page {
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .pdf-page::after { display: none !important; }
        }

        /* ── Página ── */
        .pdf-page {
            display: flex !important;
            flex-direction: column !important;
            width: ${width}${unit} !important;
            height: ${height}${unit} !important;
            min-height: ${height}${unit} !important;
            padding: 0 !important;
            margin-top: calc(12px + ${bleeding}${unit}) !important;
            margin-bottom: calc(12px + ${bleeding}${unit}) !important;
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

        /* ── Cabecera ── */
        .pdf-header {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0}${unit} !important;
            padding-bottom: ${settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5}${unit} !important;
            font-family: '${settings.header_font_family || 'Merriweather'}', serif !important;
            font-size: ${settings.header_font_size || 8.5}pt !important;
            font-weight: ${settings.header_font_weight || 'normal'} !important;
            font-style: ${settings.header_font_style || 'normal'} !important;
            letter-spacing: ${settings.header_letter_spacing || 0.1}pt !important;
            color: #475569 !important;
            text-align: ${settings.header_align || 'center'} !important;
        }

        /* ── Contenido ── */
        .pdf-content {
            flex: 1 !important;
            box-sizing: border-box !important;
            padding-top: ${settings.padding_top}${unit} !important;
            padding-bottom: ${settings.padding_bottom}${unit} !important;
            margin-left: 0px !important;
            margin-right: 0px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${settings.font_size_content || 11.5}pt !important;
            line-height: ${settings.line_height_content || 1.65} !important;
        }
        .pdf-content p, .pdf-content ul, .pdf-content ol {
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            font-size: ${settings.font_size_content || 11.5}pt !important;
            line-height: ${settings.line_height_content || 1.65} !important;
            text-align: ${settings.content_text_align || 'justify'} !important;
            hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
            -webkit-hyphens: ${parseInt(settings.content_hyphenation) === 1 ? 'auto' : 'none'} !important;
        }
        .pdf-content p {
            margin-bottom: ${settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0}pt !important;
            text-indent: ${settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0}pt !important;
        }
        .pdf-content .chapter-main-title + p,
        .pdf-content h1 + p,
        .pdf-content h2 + p {
            text-indent: 0 !important;
        }

        /* ── Encabezados h1/h2/h3 ── */
        .pdf-content h1 {
            font-family: '${settings.font_family_h1 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h1 || 'bold'} !important;
            font-size: ${settings.font_size_h1 || 24}pt !important;
            margin-top: 30px !important; margin-bottom: 20px !important;
            line-height: 1.3 !important; page-break-after: avoid;
        }
        .pdf-content h2 {
            font-family: '${settings.font_family_h2 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h2 || 'bold'} !important;
            font-size: ${settings.font_size_h2 || 16}pt !important;
            margin-top: 25px !important; margin-bottom: 15px !important;
            line-height: 1.3 !important; page-break-after: avoid;
        }
        .pdf-content h3 {
            font-family: '${settings.font_family_h3 || 'Playfair Display'}', serif !important;
            font-weight: ${settings.font_weight_h3 || 'bold'} !important;
            font-size: ${settings.font_size_h3 || 13}pt !important;
            margin-top: 20px !important; margin-bottom: 10px !important;
            line-height: 1.3 !important; page-break-after: avoid;
        }

        /* ── Título de Capítulo ── */
        .pdf-content .chapter-main-title {
            font-family: '${settings.chapter_title_font_family || 'Playfair Display'}', serif !important;
            font-size: ${settings.chapter_title_font_size || 24}pt !important;
            font-weight: ${settings.chapter_title_font_weight || 'bold'} !important;
            font-style: ${settings.chapter_title_font_style || 'normal'} !important;
            text-align: ${settings.chapter_title_align || 'center'} !important;
            padding-top: ${parseFloat(settings.chapter_title_padding_top ?? 0)}${unit} !important;
            padding-bottom: ${parseFloat(settings.chapter_title_padding_bottom ?? 1.5)}${unit} !important;
            page-break-after: avoid;
            width: 100%;
        }

        /* ── Notas al pie ── */
        .pdf-footnotes {
            box-sizing: border-box !important;
            margin-top: 8px !important;
            padding-top: 6px !important;
            padding-bottom: 4px !important;
            font-family: '${settings.font_family_content || 'Merriweather'}', serif !important;
            background-color: white !important;
            color: #475569 !important;
        }
        .pdf-footnote-item {
            margin-bottom: 4px !important;
            line-height: 1.4 !important;
            text-align: left !important;
            font-size: ${Math.round((settings.font_size_content || 11.5) * 0.75 * 10) / 10}pt !important;
        }

        /* ── Pie de página ── */
        .pdf-footer {
            box-sizing: border-box !important; margin: 0 !important;
            padding-top: ${settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5}${unit} !important;
            padding-bottom: ${settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0}${unit} !important;
            font-family: '${settings.footer_font_family || 'Merriweather'}', serif !important;
            font-size: ${settings.footer_font_size || 9.0}pt !important;
            font-weight: ${settings.footer_font_weight || 'normal'} !important;
            font-style: ${settings.footer_font_style || 'normal'} !important;
            letter-spacing: ${settings.footer_letter_spacing || 0.0}pt !important;
            color: #475569 !important;
            text-align: ${settings.footer_align || 'center'} !important;
        }

        /* ── Márgenes laterales por paridad ── */
        .pdf-page:nth-child(odd) .pdf-header,
        .pdf-page:nth-child(odd) .pdf-content,
        .pdf-page:nth-child(odd) .pdf-footnotes,
        .pdf-page:nth-child(odd) .pdf-footer {
            padding-left: ${parseFloat(settings.margin_left_odd  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0)}${unit} !important;
            padding-right: ${parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0)}${unit} !important;
        }
        .pdf-page:nth-child(even) .pdf-header,
        .pdf-page:nth-child(even) .pdf-content,
        .pdf-page:nth-child(even) .pdf-footnotes,
        .pdf-page:nth-child(even) .pdf-footer {
            padding-left: ${parseFloat(settings.margin_left_even  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0)}${unit} !important;
            padding-right: ${parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0)}${unit} !important;
        }
    `;

    compilePDFPreview();
}
