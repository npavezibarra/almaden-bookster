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
    const isEnabled = (value, fallback = true) => {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }

        return !['0', 'false', 'off', 'no'].includes(String(value).trim().toLowerCase());
    };
    const globalSeparate = isEnabled(settings && settings.book_separate_opening_content);

    if (chapter && chapter.is_toc === '1' && chapter.toc_separate_opening_content !== undefined && chapter.toc_separate_opening_content !== '') {
        return isEnabled(chapter.toc_separate_opening_content);
    }

    if (chapter && chapter.opening_separate_content !== undefined && chapter.opening_separate_content !== '') {
        return isEnabled(chapter.opening_separate_content);
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

    const flowMode = window.getBookChapterFlowMode
        ? window.getBookChapterFlowMode(settings)
        : 'continuous';
    const startParity = window.getChapterStartParity
        ? window.getChapterStartParity(chapter, settings)
        : (chapter && chapter.start_parity ? chapter.start_parity : 'any');
    if (flowMode !== 'left' || startParity !== 'even') {
        return false;
    }

    if (window.getEffectiveChapterImageEnabled && !window.getEffectiveChapterImageEnabled(chapter, settings)) {
        return false;
    }

    return ['page_blank', 'image_full_page', 'image_inner'].includes(
        window.getEffectiveChapterImageMode ? window.getEffectiveChapterImageMode(chapter, settings) : 'page_blank'
    );
};

// Every chapter follows the same sequence: optional image, opening, content.
// The image is visual-only and never becomes the editorial TOC anchor.
window.getChapterEditorialStructure = function(chapter, settings) {
    const hasLeadingImage = window.chapterHasLeadingImagePage
        ? window.chapterHasLeadingImagePage(chapter, settings)
        : false;
    const separateOpening = window.shouldSeparateChapterOpening
        ? window.shouldSeparateChapterOpening(chapter, settings)
        : false;

    return {
        hasLeadingImage,
        imageMode: hasLeadingImage && window.getEffectiveChapterImageMode
            ? window.getEffectiveChapterImageMode(chapter, settings)
            : 'none',
        separateOpening,
        openingPlacement: separateOpening ? 'dedicated-page' : 'with-content',
        tocAnchor: separateOpening ? 'opening' : 'content'
    };
};

window.chapterHasOpeningPage = function(chapter) {
    const mode = window.getEffectiveOpeningPageMode ? window.getEffectiveOpeningPageMode(chapter) : 'none';
    return mode === 'blank' || mode === 'image';
};

window.getChapterOpeningVisibility = function(chapter, settings) {
    const hasTitle = !!(chapter && chapter.title && String(chapter.title).trim() !== '');
    const isToc = chapter && chapter.is_toc === '1';
    const isCredits = chapter && chapter.is_credits === '1';
    const hideHeader = String(chapter && chapter.hide_header ? chapter.hide_header : '0') === '1'
        || String(chapter && chapter.hide_all_headers_footers ? chapter.hide_all_headers_footers : '0') === '1';
    const showTitle = hasTitle && chapter.hide_title !== '1' && !isCredits;
    const showPrefix = !isToc
        && !isCredits
        && String(settings && settings.chapter_prefix_show ? settings.chapter_prefix_show : '') === '1'
        && chapter.exclude_from_numbering !== '1'
        && !hideHeader;
    const showSubtitle = !isToc
        && !isCredits
        && !!(chapter && chapter.subtitle_text && String(chapter.subtitle_text).trim() !== '')
        && (settings && (settings.chapter_subtitle_show == 1 || settings.chapter_subtitle_show === undefined));

    return {
        hasTitle,
        showTitle,
        showPrefix,
        showSubtitle,
        hasVisibleContent: showTitle || showPrefix || showSubtitle,
    };
};

window.shouldSeparateChapterOpening = function(chapter, settings) {
    const separateOpening = window.getEffectiveOpeningSeparation
        ? window.getEffectiveOpeningSeparation(chapter, settings)
        : (settings && String(settings.book_separate_opening_content) !== '0');
    if (!separateOpening) {
        return false;
    }

    const openingVisibility = window.getChapterOpeningVisibility
        ? window.getChapterOpeningVisibility(chapter, settings)
        : null;
    const hasVisibleOpeningBlock = openingVisibility
        ? openingVisibility.hasVisibleContent
        : !!(chapter
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
