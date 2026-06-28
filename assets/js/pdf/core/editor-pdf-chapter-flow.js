// ============================================================
// MÓDULO: editor-pdf-chapter-flow.js
// Responsabilidad: Helpers compartidos para modos de apertura,
// paridad de capítulos y decisiones editoriales comunes.
// ============================================================

window.getEffectiveOpeningPageMode = function(chapter) {
    const configuredMode = chapter && chapter.opening_page_mode ? chapter.opening_page_mode : 'auto';

    if (configuredMode === 'auto') {
        return chapter && chapter.parity_image ? 'image' : 'none';
    }

    if (configuredMode === 'image' && !(chapter && chapter.parity_image)) {
        return 'blank';
    }

    return configuredMode;
};

window.chapterHasOpeningPage = function(chapter) {
    const mode = window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none';
    return mode === 'blank' || mode === 'image';
};

window.getChapterStartParity = function(chapter, settings) {
    return chapter && chapter.is_toc === '1'
        ? 'even'
        : ((chapter && chapter.start_parity && chapter.start_parity !== 'any') ? chapter.start_parity : (settings && settings.chapter_start_parity));
};
