// ============================================================
// MÓDULO: editor-pdf-compiler-dimensions.js
// Responsabilidad: Cálculo de dimensiones físicas y píxeles
// para maquetación y paginación del PDF.
// ============================================================

window.resolvePDFGeometry = function(settings = {}) {
    const unit = settings.unit || 'cm';
    let width = parseFloat(settings.page_width) || 21.0;
    let height = parseFloat(settings.page_height) || 29.7;

    if (settings.page_size === 'A4') {
        width = (unit === 'cm') ? 21.0 : (21.0 / 2.54);
        height = (unit === 'cm') ? 29.7 : (29.7 / 2.54);
    } else if (settings.page_size === 'Letter') {
        width = (unit === 'cm') ? (8.5 * 2.54) : 8.5;
        height = (unit === 'cm') ? (11.0 * 2.54) : 11.0;
    }

    const bleed = Math.max(parseFloat(settings.bleeding) || 0, 0);
    const conversionFactor = (unit === 'cm') ? 37.7952755906 : 96.0;
    const toPx = (value) => value * conversionFactor;

    const sheetWidth = width + (bleed * 2);
    const sheetHeight = height + (bleed * 2);
    const previewWidth = width + bleed;
    const previewHeight = height + (bleed * 2);
    const pageHeightPx = toPx(height);
    const pageWidthPx = toPx(width);
    const bleedPx = toPx(bleed);
    const sheetWidthPx = toPx(sheetWidth);
    const sheetHeightPx = toPx(sheetHeight);
    const previewWidthPx = toPx(previewWidth);
    const previewHeightPx = toPx(previewHeight);

    const headerFontPx = (parseFloat(settings.header_font_size) || 8.5) * 1.333;
    const footerFontPx = (parseFloat(settings.footer_font_size) || 9.0) * 1.333;
    const headerMarginTopPx    = (settings.header_margin_top    !== undefined ? parseFloat(settings.header_margin_top)    : 1.0) * conversionFactor;
    const headerMarginBottomPx = (settings.header_margin_bottom !== undefined ? parseFloat(settings.header_margin_bottom) : 0.5) * conversionFactor;
    const footerMarginTopPx    = (settings.footer_margin_top    !== undefined ? parseFloat(settings.footer_margin_top)    : 0.5) * conversionFactor;
    const footerMarginBottomPx = (settings.footer_margin_bottom !== undefined ? parseFloat(settings.footer_margin_bottom) : 1.0) * conversionFactor;

    const totalHeaderHeightPx = headerMarginTopPx + headerFontPx + headerMarginBottomPx;
    const totalFooterHeightPx = footerMarginTopPx + footerFontPx + footerMarginBottomPx;
    const paddingTopPx    = (parseFloat(settings.padding_top)    || 0) * conversionFactor;
    const paddingBottomPx = (parseFloat(settings.padding_bottom) || 0) * conversionFactor;

    const maxPageContentHeight = pageHeightPx - (totalHeaderHeightPx + totalFooterHeightPx + paddingTopPx + paddingBottomPx) - 20;

    return {
        unit,
        width,
        height,
        bleed,
        conversionFactor,
        pageHeightPx,
        pageWidthPx,
        bleedPx,
        sheetWidth,
        sheetHeight,
        sheetWidthPx,
        sheetHeightPx,
        previewWidth,
        previewHeight,
        previewWidthPx,
        previewHeightPx,
        maxPageContentHeight
    };
};

window.calculatePageDimensions = function(settings) {
    const geometry = window.resolvePDFGeometry(settings);

    return {
        unit: geometry.unit,
        width: geometry.width,
        height: geometry.height,
        conversionFactor: geometry.conversionFactor,
        pageHeightPx: geometry.pageHeightPx,
        pageWidthPx: geometry.pageWidthPx,
        maxPageContentHeight: geometry.maxPageContentHeight,
        bleed: geometry.bleed,
        bleedPx: geometry.bleedPx,
        sheetWidth: geometry.sheetWidth,
        sheetHeight: geometry.sheetHeight,
        sheetWidthPx: geometry.sheetWidthPx,
        sheetHeightPx: geometry.sheetHeightPx,
        previewWidth: geometry.previewWidth,
        previewHeight: geometry.previewHeight,
        previewWidthPx: geometry.previewWidthPx,
        previewHeightPx: geometry.previewHeightPx
    };
};
