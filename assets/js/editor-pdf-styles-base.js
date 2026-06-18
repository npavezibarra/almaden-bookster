// ============================================================
// MÓDULO: editor-pdf-styles-base.js
// Responsabilidad: Generar el CSS base, @page, márgenes, cabeceras y pies de página.
// ============================================================

function getPDFStylesBase(settings, toPx, widthPx, heightPx, globalBleedPx, unit) {
    return `
        @page {
            size: ${widthPx + globalBleedPx}px ${heightPx + (globalBleedPx * 2)}px;
            margin: 0px;
        }

        /* ── Screen Parity Bleed (3-sided) ── */
        .pdf-page.page-even .parity-bleed-container {
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 100%;
        }

        /* ── Language Highlighting ── */
        .pdf-content span[lang],
        .pdf-content span[lang] em,
        .pdf-content span[lang] i,
        .pdf-content span[lang] b,
        .pdf-content span[lang] strong,
        .pdf-content span[lang] * {
            color: #ff0000 !important; /* Rojo puro e inconfundible */
            background-color: rgba(255, 0, 0, 0.08) !important; /* Fondo rojo ultra-sutil */
        }
        
        @media print {
            .pdf-content span[lang],
            .pdf-content span[lang] em,
            .pdf-content span[lang] * {
                color: inherit !important;
                background-color: transparent !important;
            }
        }

        .pdf-page.page-odd .parity-bleed-container {
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 100%;
        }

        @media print {
            .bleed-guide-line, .global-trim-line {
                display: none !important;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
            }
            .pdf-page {
                box-sizing: border-box !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
        }

        /* ── Página ── */
        .pdf-page {
            display: flex !important;
            flex-direction: column !important;
            width: ${widthPx + globalBleedPx}px !important;
            height: ${heightPx + (globalBleedPx * 2)}px !important;
            min-height: ${heightPx + (globalBleedPx * 2)}px !important;
            margin-top: ${36 + globalBleedPx}px !important;
            margin-bottom: ${36 + globalBleedPx}px !important;
            border: 1px solid #e2e8f0 !important;
            position: relative;
            box-sizing: border-box !important;
            background-color: white !important;
            color: #1e293b !important;
            ${settings.export_grayscale == 1 ? 'filter: grayscale(100%) !important; -webkit-filter: grayscale(100%) !important;' : ''}
        }
        ${settings.export_grayscale == 1 ? `
        .pdf-page *, .pdf-page img, .pdf-page svg {
            filter: grayscale(100%) !important;
            -webkit-filter: grayscale(100%) !important;
        }
        ` : ''}

        .pdf-page.page-even {
            padding: ${globalBleedPx}px 0 ${globalBleedPx}px ${globalBleedPx}px !important;
        }
        .pdf-page.page-odd {
            padding: ${globalBleedPx}px ${globalBleedPx}px ${globalBleedPx}px 0 !important;
        }

        .global-trim-line {
            position: absolute;
            border: 1px dashed gray;
            z-index: 20;
            pointer-events: none;
        }
        .pdf-page.page-even .global-trim-line {
            top: ${globalBleedPx}px;
            bottom: ${globalBleedPx}px;
            left: ${globalBleedPx}px;
            right: 0;
        }
        .pdf-page.page-odd .global-trim-line {
            top: ${globalBleedPx}px;
            bottom: ${globalBleedPx}px;
            right: ${globalBleedPx}px;
            left: 0;
        }

        /* ── Spread View (Pantalla) ── */
        @media screen {
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
            #pdf-scroller.spread-view .pdf-page.page-odd {
                grid-column: 2;
                border-left: none !important; /* Remove double border in the middle */
            }
            /* EVEN pages (Left side of the spread) -> Grid Column 1 */
            #pdf-scroller.spread-view .pdf-page.page-even {
                grid-column: 1;
                border-right: none !important; /* Remove double border in the middle */
            }
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
        .pdf-page.page-odd .pdf-header,
        .pdf-page.page-odd .pdf-content,
        .pdf-page.page-odd .pdf-footnotes,
        .pdf-page.page-odd .pdf-footer {
            padding-left: ${toPx(parseFloat(settings.margin_left_odd  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0))}px !important;
            padding-right: ${toPx(parseFloat(settings.margin_right_odd ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0))}px !important;
        }
        .pdf-page.page-even .pdf-header,
        .pdf-page.page-even .pdf-content,
        .pdf-page.page-even .pdf-footnotes,
        .pdf-page.page-even .pdf-footer {
            padding-left: ${toPx(parseFloat(settings.margin_left_even  ?? settings.margin_left  ?? 2) + parseFloat(settings.padding_left  ?? 0))}px !important;
            padding-right: ${toPx(parseFloat(settings.margin_right_even ?? settings.margin_right ?? 2) + parseFloat(settings.padding_right ?? 0))}px !important;
        }
    `;
}
