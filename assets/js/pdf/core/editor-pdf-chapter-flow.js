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

window.getEffectiveOpeningSeparation = function(chapter, settings) {
    const globalSeparate = settings && String(settings.book_separate_opening_content) !== '0';

    if (chapter && chapter.is_toc === '1' && chapter.toc_separate_opening_content !== undefined && chapter.toc_separate_opening_content !== '') {
        return String(chapter.toc_separate_opening_content) !== '0';
    }

    if (chapter && chapter.opening_separate_content !== undefined && chapter.opening_separate_content !== '') {
        return String(chapter.opening_separate_content) !== '0';
    }

    return globalSeparate;
};

window.getEffectiveChapterImageMode = function(chapter, settings) {
    const configuredMode = chapter && chapter.chapter_image_mode
        ? String(chapter.chapter_image_mode)
        : (settings && settings.chapter_image_mode ? String(settings.chapter_image_mode) : 'page_blank');
    if (['page_blank', 'image_full_page', 'image_inner'].includes(configuredMode)) {
        return configuredMode;
    }
    return 'page_blank';
};

window.getEffectiveChapterImageEnabled = function(chapter, settings) {
    if (chapter && chapter.chapter_image_enabled !== undefined && chapter.chapter_image_enabled !== '') {
        return String(chapter.chapter_image_enabled) === '1';
    }

    const configuredMode = window.getEffectiveChapterImageMode
        ? window.getEffectiveChapterImageMode(chapter, settings)
        : 'page_blank';
    const chapterImageUrl = chapter && chapter.chapter_image_url ? String(chapter.chapter_image_url).trim() : '';
    const legacySettingsMode = settings && settings.chapter_image_mode ? String(settings.chapter_image_mode) : 'page_blank';
    const legacySettingsUrl = settings && settings.chapter_image_url ? String(settings.chapter_image_url).trim() : '';

    return configuredMode !== 'page_blank' || chapterImageUrl !== '' || legacySettingsMode !== 'page_blank' || legacySettingsUrl !== '';
};

window.chapterHasLeadingImagePage = function(chapter, settings) {
    if (chapter && chapter.is_credits === '1') {
        return false;
    }

    const startParity = chapter && chapter.start_parity ? chapter.start_parity : 'any';
    if (startParity !== 'even') {
        return false;
    }

    if (window.getEffectiveChapterImageEnabled && !window.getEffectiveChapterImageEnabled(chapter, settings)) {
        return false;
    }

    return ['page_blank', 'image_full_page', 'image_inner'].includes(
        window.getEffectiveChapterImageMode ? window.getEffectiveChapterImageMode(chapter, settings) : 'page_blank'
    );
};

window.chapterHasOpeningPage = function(chapter) {
    const mode = window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none';
    return mode === 'blank' || mode === 'image';
};

window.shouldSeparateChapterOpening = function(chapter, settings) {
    const separateOpening = window.getEffectiveOpeningSeparation
        ? window.getEffectiveOpeningSeparation(chapter, settings)
        : (settings && String(settings.book_separate_opening_content) !== '0');
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

window.getBookTransitionBlankMode = function(settings) {
    const mode = settings && settings.chapter_transition_blank_mode ? String(settings.chapter_transition_blank_mode) : 'full_blank';
    if (['full_blank', 'blank_with_header_footer', 'intentional_text'].includes(mode)) {
        return mode;
    }
    return 'full_blank';
};

window.getBookTransitionBlankText = function(settings) {
    const text = settings && settings.chapter_transition_blank_text !== undefined ? String(settings.chapter_transition_blank_text) : '...';
    return text.trim() === '' ? '...' : text;
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

    if (window.chapterHasLeadingImagePage && window.chapterHasLeadingImagePage(chapter, settings)) {
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

    if (chapter && chapter.start_parity === 'even') {
        return true;
    }

    // In continuous flow, a separated opening still starts on the next
    // available page. Only the left-start book rule needs a leading blank.
    return false;
};
