// ============================================================
// MÓDULO: editor-pdf-styles.js
// Responsabilidad: Inyección dinámica de CSS de maquetación
// basado en la configuración del libro (bookState.settings).
// Depende de: editor-pdf-compiler.js (compilePDFPreview)
// ============================================================

// Inyecta dinámicamente las reglas de CSS en base a la configuración de maquetación actual
function applyDynamicPDFStyles() {
    const settings = bookState.settings || {};
    const geometry = typeof window.resolvePDFGeometry === 'function'
        ? window.resolvePDFGeometry(settings)
        : null;
    // Extract TOC chapter settings if it exists
    const tocChapter = bookState.chapters && bookState.chapters.find(c => c.is_toc === '1');
    const tocSettings = tocChapter ? {
        fontFamily: tocChapter.toc_font_family || settings.font_family_content || 'Merriweather',
        fontSize: tocChapter.toc_font_size ? parseFloat(tocChapter.toc_font_size) : parseFloat(settings.font_size_content || 11.5),
        fontStyle: tocChapter.toc_font_style || 'normal',
        fontWeight: tocChapter.toc_font_weight || 'normal',
        textTransform: tocChapter.toc_text_transform || 'none',
        letterSpacing: tocChapter.toc_letter_spacing ? parseFloat(tocChapter.toc_letter_spacing) : 0,
        lineHeight: tocChapter.toc_line_height ? parseFloat(tocChapter.toc_line_height) : 1.8,
        itemSpacing: tocChapter.toc_item_spacing ? parseFloat(tocChapter.toc_item_spacing) : 8,
        leaderStyle: tocChapter.toc_leader_style || 'dotted',
        leaderPosition: tocChapter.toc_leader_position || 'middle',
        itemAlign: tocChapter.toc_item_align || 'left',
        titleFontFamily: tocChapter.toc_title_font_family || settings.chapter_title_font_family || 'Playfair Display',
        titleFontSize: tocChapter.toc_title_font_size ? parseFloat(tocChapter.toc_title_font_size) : parseFloat(settings.chapter_title_font_size || 24.0),
        titleFontStyle: tocChapter.toc_title_font_style || settings.chapter_title_font_style || 'normal',
        titleFontWeight: tocChapter.toc_title_font_weight || settings.chapter_title_font_weight || 'bold',
        titleTextTransform: tocChapter.toc_title_text_transform || settings.chapter_title_text_transform || 'none',
        titleAlign: tocChapter.toc_title_align || settings.chapter_title_align || 'center',
        titlePaddingTop: tocChapter.toc_title_padding_top ? parseFloat(tocChapter.toc_title_padding_top) : parseFloat(settings.chapter_title_padding_top || 0.0),
        titlePaddingBottom: tocChapter.toc_title_padding_bottom ? parseFloat(tocChapter.toc_title_padding_bottom) : parseFloat(settings.chapter_title_padding_bottom || 1.5),
        titlePaddingLeft: tocChapter.toc_title_padding_left ? parseFloat(tocChapter.toc_title_padding_left) : parseFloat(settings.chapter_title_padding_left || 0.0),
        titlePaddingRight: tocChapter.toc_title_padding_right ? parseFloat(tocChapter.toc_title_padding_right) : parseFloat(settings.chapter_title_padding_right || 0.0),
        titleLineHeight: tocChapter.toc_title_line_height ? parseFloat(tocChapter.toc_title_line_height) : parseFloat(settings.chapter_title_line_height || 1.2)
    } : {
        fontFamily: settings.font_family_content || 'Merriweather',
        fontSize: parseFloat(settings.font_size_content || 11.5),
        fontStyle: 'normal',
        fontWeight: 'normal',
        textTransform: 'none',
        letterSpacing: 0,
        lineHeight: 1.8,
        leaderStyle: 'dotted',
        leaderPosition: 'middle',
        titleFontFamily: settings.chapter_title_font_family || 'Playfair Display',
        titleFontSize: parseFloat(settings.chapter_title_font_size || 24.0),
        titleFontStyle: settings.chapter_title_font_style || 'normal',
        titleFontWeight: settings.chapter_title_font_weight || 'bold',
        titleTextTransform: settings.chapter_title_text_transform || 'none',
        titleAlign: settings.chapter_title_align || 'center',
        titlePaddingTop: parseFloat(settings.chapter_title_padding_top || 0.0),
        titlePaddingBottom: parseFloat(settings.chapter_title_padding_bottom || 1.5),
        titlePaddingLeft: parseFloat(settings.chapter_title_padding_left || 0.0),
        titlePaddingRight: parseFloat(settings.chapter_title_padding_right || 0.0),
        titleLineHeight: parseFloat(settings.chapter_title_line_height || 1.2)
    };

    let styleEl = document.getElementById('dynamic-pdf-settings');
    if (!settings) return;
    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.id = 'dynamic-pdf-settings';
        document.head.appendChild(styleEl);
    }

    const unit = settings.unit || 'cm';
    const cmToPx = 37.7952755906;
    const ptToPx = 1.3333333333;

    // Helper para convertir cualquier valor a píxeles según su unidad de origen
    const toPx = (value, isPt = false) => {
        if (!value && value !== 0) return 0;
        return isPt ? (parseFloat(value) * ptToPx) : (parseFloat(value) * (unit === 'cm' ? cmToPx : (cmToPx * 2.54)));
    };

    styleEl.innerHTML = `
        ${getPDFStylesBase(settings, geometry, toPx)}
        ${getPDFStylesChapters(settings, toPx)}
        ${getPDFStylesFlow(settings, toPx)}
        ${getPDFStylesTypography(settings, tocSettings, toPx)}
        ${getPDFStylesSemantic(settings, toPx)}
        ${typeof getSingleChapterBookStyles === 'function' ? getSingleChapterBookStyles(bookState, settings) : ''}
    `;

    if (typeof refreshEditorDisplay === 'function') {
        refreshEditorDisplay(false);
    } else if (typeof compilePDFPreview === 'function') {
        compilePDFPreview();
    }
}
