// ============================================================
// MÓDULO: editor-settings-state.js
// Responsabilidad: reconstruir el estado normalizado de ajustes
// del libro después de guardar por AJAX.
// ============================================================

window.almadenBuildPDFSettingsState = function(ctx) {
    const {
        getVal,
        getCleanVal,
        getChecked,
        parseVal,
        getBookLanguage,
        getBookFlowMode,
        getLegacyParityFromFlowMode,
        creditsConfig,
        creditsLegacy
    } = ctx || {};

    const normalizeFootnoteLineHeight = (value, fontSize, fallback = 11.5) => {
        const parsed = parseFloat(value);
        if (!Number.isFinite(parsed)) {
            return fallback;
        }
        return Math.max(0.1, Math.min(40, parsed));
    };

    const normalizeOpeningPageAlignmentValue = (rawValue) => {
        const normalized = String(rawValue || '')
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/\//g, '-');

        const parts = normalized.split('-').filter(Boolean);
        if (parts.length >= 2) {
            const horizontal = ['left', 'center', 'right'].includes(parts[0]) ? parts[0] : '';
            const vertical = ['top', 'center', 'bottom'].includes(parts[1]) ? parts[1] : '';
            if (horizontal && vertical) {
                return `${horizontal}-${vertical}`;
            }
        }

        return '';
    };

    const deriveOpeningPageAlignment = () => {
        const combined = normalizeOpeningPageAlignmentValue(getVal('setting-chapter-page-one-align'));
        if (combined) {
            const [horizontal, vertical] = combined.split('-');
            return { horizontal, vertical, combined };
        }

        const legacyHorizontal = ['left', 'center', 'right'].includes(String(getVal('setting-chapter-page-one-align') || '').toLowerCase())
            ? String(getVal('setting-chapter-page-one-align')).toLowerCase()
            : (['left', 'center', 'right'].includes(String(getVal('setting-chapter-title-align') || '').toLowerCase())
                ? String(getVal('setting-chapter-title-align')).toLowerCase()
                : 'center');
        const legacyVertical = ['top', 'center', 'bottom'].includes(String(getVal('setting-chapter-page-one-vertical') || '').toLowerCase())
            ? String(getVal('setting-chapter-page-one-vertical')).toLowerCase()
            : (String(getVal('setting-chapter-page-one-vertical') || '').toLowerCase() === 'half' ? 'center' : 'top');

        return {
            horizontal: legacyHorizontal,
            vertical: legacyVertical,
            combined: `${legacyHorizontal}-${legacyVertical}`,
        };
    };

    const openingPageAlignment = deriveOpeningPageAlignment();

    return {
        unit: getVal('setting-unit'),
        page_size: getVal('setting-page-size'),
        page_width: parseVal('setting-page-width', 21),
        page_height: parseVal('setting-page-height', 29.7),
        margin_top: parseVal('setting-margin-top', 2.5),
        margin_bottom: parseVal('setting-margin-bottom', 2.5),
        margin_left_odd: parseVal('setting-margin-left-odd', 2.0),
        margin_right_odd: parseVal('setting-margin-right-odd', 2.0),
        margin_left_even: parseVal('setting-margin-left-even', 2.0),
        margin_right_even: parseVal('setting-margin-right-even', 2.0),
        margin_left: parseVal('setting-margin-left-odd', 2.0),
        margin_right: parseVal('setting-margin-right-odd', 2.0),
        padding_top: parseVal('setting-padding-top', 0),
        padding_bottom: parseVal('setting-padding-bottom', 0),
        padding_left: parseVal('setting-padding-left', 1.0),
        padding_right: parseVal('setting-padding-right', 1.0),
        bleeding: parseVal('setting-bleeding', 0.5),
        export_grayscale: getChecked('setting-export-grayscale'),
        page_columns_enabled: getChecked('setting-page-columns-enabled'),
        page_columns_count: parseVal('setting-page-columns-count', 2),
        page_columns_gap: parseVal('setting-page-columns-gap', 0.8),
        ebook_bg_type: getVal('setting-ebook-bg-type'),
        ebook_bg_color: getVal('setting-ebook-bg-color-text'),
        ebook_bg_image: getVal('setting-ebook-bg-image'),
        ebook_bg_opacity: parseVal('setting-ebook-bg-opacity', 1.0),
        ebook_cover_panel_bg_type: getVal('setting-ebook-cover-panel-bg-type'),
        ebook_cover_panel_bg_color: getVal('setting-ebook-cover-panel-bg-color-text'),
        ebook_cover_panel_bg_image: getVal('setting-ebook-cover-panel-bg-image'),
        ebook_cover_panel_bg_opacity: parseVal('setting-ebook-cover-panel-bg-opacity', 1.0),
        ebook_font_family_content: getVal('setting-ebook-font-family-content'),
        ebook_font_size_content: parseVal('setting-ebook-font-size-content', 18.0),
        ebook_font_weight_content: getVal('setting-ebook-font-weight-content'),
        ebook_line_height_content: parseVal('setting-ebook-line-height-content', 1.8),
        ebook_font_family_headings: getVal('setting-ebook-chapter-title-font-family') || '',
        ebook_font_size_headings: parseVal('setting-ebook-chapter-title-font-size', 32),
        ebook_font_weight_headings: getVal('setting-ebook-chapter-title-font-weight') || 'bold',
        ebook_line_height_headings: parseVal('setting-ebook-chapter-title-line-height', 1.3),
        ebook_chapter_title_align: getVal('setting-ebook-chapter-title-align') || 'center',
        ebook_chapter_title_text_transform: getVal('setting-ebook-chapter-title-text-transform') || 'none',
        ebook_chapter_title_padding_top: parseVal('setting-ebook-chapter-title-padding-top', 2),
        ebook_chapter_title_padding_bottom: parseVal('setting-ebook-chapter-title-padding-bottom', 2),
        ebook_chapter_title_padding_left: parseVal('setting-ebook-chapter-title-padding-left', 0),
        ebook_chapter_title_padding_right: parseVal('setting-ebook-chapter-title-padding-right', 0),
        ebook_chapter_title_hyphenate: getChecked('setting-ebook-chapter-title-hyphenate'),

        ebook_subtitle_show: getChecked('setting-ebook-chapter-subtitle-show'),
        ebook_subtitle_font_family: getVal('setting-ebook-chapter-subtitle-font-family') || '',
        ebook_subtitle_font_size: parseVal('setting-ebook-chapter-subtitle-font-size', 18),
        ebook_subtitle_align: getVal('setting-ebook-chapter-subtitle-align') || 'center',
        ebook_subtitle_font_style: getVal('setting-ebook-chapter-subtitle-font-style') || 'normal',
        ebook_subtitle_text_transform: getVal('setting-ebook-chapter-subtitle-text-transform') || 'none',
        ebook_subtitle_font_weight: getVal('setting-ebook-chapter-subtitle-font-weight') || 'normal',
        ebook_subtitle_padding_top: parseVal('setting-ebook-chapter-subtitle-padding-top', 0.5),
        ebook_subtitle_padding_bottom: parseVal('setting-ebook-chapter-subtitle-padding-bottom', 0.5),
        ebook_subtitle_letter_spacing: parseVal('setting-ebook-chapter-subtitle-letter-spacing', 0),

        ebook_chapter_prefix_show: getChecked('setting-ebook-chapter-prefix-show'),
        ebook_chapter_prefix_template: getVal('setting-ebook-chapter-prefix-template') || 'Capítulo {N}',
        ebook_chapter_prefix_position: getVal('setting-ebook-chapter-prefix-position') || 'above',
        ebook_chapter_prefix_align: getVal('setting-ebook-chapter-prefix-align') || 'center',
        ebook_chapter_prefix_font_family: getVal('setting-ebook-chapter-prefix-font-family') || 'Playfair Display',
        ebook_chapter_prefix_font_size: parseVal('setting-ebook-chapter-prefix-font-size', 16),
        ebook_chapter_prefix_font_weight: getVal('setting-ebook-chapter-prefix-font-weight') || 'normal',
        ebook_chapter_prefix_font_style: getVal('setting-ebook-chapter-prefix-font-style') || 'normal',
        ebook_chapter_prefix_letter_spacing: parseVal('setting-ebook-chapter-prefix-letter-spacing', 0),
        ebook_chapter_prefix_ornament: getVal('setting-ebook-chapter-prefix-ornament') || 'none',
        ebook_text_align_justify: getChecked('setting-ebook-text-align-justify'),
        ebook_hyphenation: getChecked('setting-ebook-hyphenation'),

        font_family_content: getVal('setting-font-family-content'),
        font_size_content: parseVal('setting-font-size-content', 11.5),
        font_weight_content: getVal('setting-font-weight-content'),
        line_height_content: parseVal('setting-line-height-content', 1.65),
        content_text_align: getVal('setting-content-text-align'),
        content_text_align_last: getVal('setting-content-text-align-last'),
        content_hyphenation: parseInt(getVal('setting-content-hyphenation'), 10),
        book_language: getBookLanguage(),
        content_language: getBookLanguage(),
        content_hyphenation_exceptions: getVal('setting-content-hyphenation-exceptions'),
        content_paragraph_indent: parseVal('setting-content-paragraph-indent', 0.0),
        content_paragraph_spacing: parseVal('setting-content-paragraph-spacing', 14.0),
        font_family_h1: getVal('setting-font-family-h1'),
        font_family_h2: getVal('setting-font-family-h2'),
        font_family_h3: getVal('setting-font-family-h3'),
        font_style_h1: getVal('setting-font-style-h1'),
        font_style_h2: getVal('setting-font-style-h2'),
        font_style_h3: getVal('setting-font-style-h3'),
        font_weight_h1: getVal('setting-font-weight-h1'),
        font_weight_h2: getVal('setting-font-weight-h2'),
        font_weight_h3: getVal('setting-font-weight-h3'),
        font_size_h1: parseVal('setting-font-size-h1', 24),
        font_size_h2: parseVal('setting-font-size-h2', 16),
        font_size_h3: parseVal('setting-font-size-h3', 13),

        header_font_family: getVal('setting-header-font-family'),
        header_font_size: parseVal('setting-header-font-size', 8.5),
        header_font_weight: getVal('setting-header-font-weight'),
        header_font_style: getVal('setting-header-font-style'),
        header_text_transform: getVal('setting-header-text-transform'),
        header_hyphenate: getChecked('setting-header-hyphenate'),
        header_letter_spacing: parseVal('setting-header-letter-spacing', 0.1),
        header_even_type: getVal('setting-header-even-type'),
        header_even_custom: getVal('setting-header-even-custom'),
        header_odd_type: getVal('setting-header-odd-type'),
        header_odd_custom: getVal('setting-header-odd-custom'),
        footer_font_family: getVal('setting-footer-font-family'),
        footer_font_size: parseVal('setting-footer-font-size', 9),
        footer_font_weight: getVal('setting-footer-font-weight'),
        footer_font_style: getVal('setting-footer-font-style'),
        footer_text_transform: getVal('setting-footer-text-transform'),
        footer_letter_spacing: parseVal('setting-footer-letter-spacing', 0),
        footer_even_type: getVal('setting-footer-even-type'),
        footer_odd_type: getVal('setting-footer-odd-type'),
        first_page_header_show: getChecked('setting-first-page-header-show'),
        first_page_header_type: getVal('setting-first-page-header-type'),
        first_page_header_custom: getVal('setting-first-page-header-custom'),
        first_page_footer_show: getChecked('setting-first-page-footer-show'),
        first_page_footer_type: getVal('setting-first-page-footer-type'),
        first_page_footer_custom: getVal('setting-first-page-footer-custom'),
        book_separate_opening_content: getChecked('setting-book-separate-opening-content'),
        book_chapter_flow_mode: getBookFlowMode(),
        chapter_transition_blank_mode: getVal('setting-chapter-transition-blank-mode') || 'full_blank',
        chapter_transition_blank_text: getVal('setting-chapter-transition-blank-text') || '...',
        chapter_start_parity: getLegacyParityFromFlowMode(),

	        footnote_mode: getVal('setting-footnote-mode') || 'page',
	        footnote_chapter_new_page: getChecked('setting-footnote-chapter-new-page'),
	        footnote_chapter_title: getVal('setting-footnote-chapter-title') || 'Referencia',
        footnote_book_title: getVal('setting-footnote-book-title') || 'Referencias',
        footnote_font_family: getVal('setting-footnote-font-family'),
        footnote_font_size: parseVal('setting-footnote-font-size', 8.5),
        footnote_font_weight: getVal('setting-footnote-font-weight'),
        footnote_align: getVal('setting-footnote-align'),
        footnote_line_height: normalizeFootnoteLineHeight(getVal('setting-footnote-line-height'), parseVal('setting-footnote-font-size', 8.5)),
        footnote_letter_spacing: parseVal('setting-footnote-letter-spacing', 0),
        footnote_entry_spacing: parseVal('setting-footnote-entry-spacing', 6),
        footnote_hyphenate: getChecked('setting-footnote-hyphenate'),
        footnote_call_scale: parseVal('setting-footnote-call-scale', 0.65),
        footnote_call_raise: parseVal('setting-footnote-call-raise', 0.18),
        footnote_padding_top: parseVal('setting-footnote-padding-top', 0.15),
        footnote_padding_bottom: parseVal('setting-footnote-padding-bottom', 0.15),
        footnote_padding_left: parseVal('setting-footnote-padding-left', 0),
        footnote_padding_right: parseVal('setting-footnote-padding-right', 0),
        footnote_separator_show: getChecked('setting-footnote-separator-show'),
        footnote_separator_align: getVal('setting-footnote-separator-align') || 'left',
        footnote_separator_width: getVal('setting-footnote-separator-width') || '100',
        footnote_separator_thickness: parseVal('setting-footnote-separator-thickness', 0.25),
        footnote_separator_margin_bottom: parseVal('setting-footnote-separator-margin-bottom', 0.15),

        header_margin_top: parseVal('setting-header-margin-top', 1.0),
        header_margin_bottom: parseVal('setting-header-margin-bottom', 0.5),
        header_align: getVal('setting-header-align'),
        footer_margin_top: parseVal('setting-footer-margin-top', 0.5),
        footer_margin_bottom: parseVal('setting-footer-margin-bottom', 1.0),
        footer_align: getVal('setting-footer-align'),

        chapter_page_one_align: openingPageAlignment.combined,
        chapter_page_one_vertical: openingPageAlignment.vertical,
        chapter_title_font_family: getVal('setting-chapter-title-font-family'),
        chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
        chapter_title_font_weight: getVal('setting-chapter-title-font-weight'),
        chapter_title_font_style: getVal('setting-chapter-title-font-style'),
        chapter_title_align: getVal('setting-chapter-title-align'),
        chapter_title_text_transform: getVal('setting-chapter-title-text-transform'),
        chapter_title_letter_spacing: parseVal('setting-chapter-title-letter-spacing', 0),
        chapter_title_padding_top: parseVal('setting-chapter-title-padding-top', 0),
        chapter_title_padding_bottom: parseVal('setting-chapter-title-padding-bottom', 1.5),
        chapter_title_padding_left: parseVal('setting-chapter-title-padding-left', 0),
        chapter_title_padding_right: parseVal('setting-chapter-title-padding-right', 0),
        chapter_title_line_height: parseVal('setting-chapter-title-line-height', 1.3),
        chapter_title_hyphenate: getChecked('setting-chapter-title-hyphenate'),

        chapter_subtitle_show: getChecked('setting-chapter-subtitle-show'),
        chapter_subtitle_font_family: getVal('setting-chapter-subtitle-font-family') || '',
        chapter_subtitle_font_size: parseVal('setting-chapter-subtitle-font-size', 16),
        chapter_subtitle_align: getVal('setting-chapter-subtitle-align') || 'center',
        chapter_subtitle_font_style: getVal('setting-chapter-subtitle-font-style') || 'normal',
        chapter_subtitle_text_transform: getVal('setting-chapter-subtitle-text-transform') || 'none',
        chapter_subtitle_font_weight: getVal('setting-chapter-subtitle-font-weight') || 'normal',
        chapter_subtitle_margin_top: parseVal('setting-chapter-subtitle-margin-top', 0.5),
        chapter_subtitle_margin_bottom: parseVal('setting-chapter-subtitle-margin-bottom', 0.5),
        chapter_subtitle_letter_spacing: parseVal('setting-chapter-subtitle-letter-spacing', 0),

        chapter_prefix_show: getChecked('setting-chapter-prefix-show'),
        chapter_prefix_template: getVal('setting-chapter-prefix-template'),
        chapter_prefix_position: getVal('setting-chapter-prefix-position'),
        chapter_prefix_align: getVal('setting-chapter-prefix-align') || 'center',
        chapter_prefix_font_family: getVal('setting-chapter-prefix-font-family'),
        chapter_prefix_font_size: parseVal('setting-chapter-prefix-font-size', 16),
        chapter_prefix_font_weight: getVal('setting-chapter-prefix-font-weight'),
        chapter_prefix_font_style: getVal('setting-chapter-prefix-font-style'),
        chapter_prefix_letter_spacing: parseVal('setting-chapter-prefix-letter-spacing', 0),
        chapter_prefix_ornament: getVal('setting-chapter-prefix-ornament'),

        credits_config: creditsConfig,
        credits_edition: creditsLegacy.credits_edition || '',
        credits_date: creditsLegacy.credits_date || '',
        credits_isbn: creditsLegacy.credits_isbn || '',
        credits_copyright: creditsLegacy.credits_copyright || '',
        credits_printer: creditsLegacy.credits_printer || '',
        credits_blank_before: creditsLegacy.credits_blank_before ?? 0,
        credits_blank_after: creditsLegacy.credits_blank_after ?? 0,
        credits_license: creditsLegacy.credits_license || 'all_rights_reserved',
        credits_custom: creditsLegacy.credits_custom || '[]'
    };
};
