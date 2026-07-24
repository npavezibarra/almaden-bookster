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

window.shouldSeparateChapterOpening = function(chapter, settings) {
    const separateOpening = settings && String(settings.book_separate_opening_content) !== '0';
    if (!separateOpening) {
        return false;
    }

    const hasVisibleOpeningBlock = !!(chapter
        && chapter.title
        && String(chapter.title).trim() !== ''
        && chapter.hide_title !== '1'
        && chapter.is_credits !== '1');
    const openingMode = window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none';

    return hasVisibleOpeningBlock || openingMode === 'blank' || openingMode === 'image';
};

window.getBookChapterFlowMode = function(settings) {
    if (settings && settings.book_chapter_flow_mode === 'left') {
        return 'left';
    }
    if (settings && settings.book_chapter_flow_mode === 'continuous') {
        return 'continuous';
    }

    if (settings && settings.chapter_start_parity === 'even') {
        return 'left';
    }

    return 'continuous';
};

window.getChapterStartParity = function(chapter, settings) {
    if (chapter && chapter.is_toc === '1') {
        return 'even';
    }

    if (chapter && chapter.start_parity && chapter.start_parity !== 'any') {
        return chapter.start_parity;
    }

    const flowMode = window.getBookChapterFlowMode ? window.getBookChapterFlowMode(settings) : 'continuous';
    if (flowMode === 'left') {
        return 'even';
    }

    if (settings && settings.chapter_start_parity) {
        return settings.chapter_start_parity;
    }

    return 'any';
};

window.shouldInsertBookStartLeadingPage = function(chapter, settings, chapterLength = null) {
    if (!chapter) {
        return false;
    }

    const isToc = chapter.is_toc === '1';
    const resolvedLength = Number.isFinite(chapterLength) ? chapterLength : null;
    const flowMode = window.getBookChapterFlowMode ? window.getBookChapterFlowMode(settings) : 'continuous';
    if (isToc && flowMode === 'left') {
        // The book-level left-flow rule applies even when the TOC fits on one page.
        return true;
    }

    if (isToc) {
        if (resolvedLength === null) {
            return false;
        }
        return resolvedLength > 1;
    }

    if (flowMode === 'left') {
        return true;
    }

    // In continuous flow, a separated opening still starts on the next
    // available page. Only the left-start book rule needs a leading blank.
    return false;
};
