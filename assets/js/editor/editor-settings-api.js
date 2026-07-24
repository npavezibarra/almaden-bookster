// Enviar ajustes vía AJAX a la base de datos de WordPress
window.savePDFSettings = function(silent = false) {
    const btn = document.getElementById('btn-save-settings');
    let originalBtnText = 'Guardar Cambios';
    if (btn && !silent) {
        originalBtnText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Guardando...';
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }

    const getVal = (id, fallback = '') => {
        const el = document.getElementById(id);
        return el ? el.value : fallback;
    };

    const getChecked = (id) => {
        const el = document.getElementById(id);
        return el ? (el.checked ? 1 : 0) : 0;
    };

    const getCleanVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.replace(',', '.') : '';
    };

    const parseVal = (id, fallback) => {
        const val = getCleanVal(id);
        const clean = parseFloat(val);
        return isNaN(clean) ? fallback : clean;
    };

    const getBookFlowMode = () => {
        const el = document.getElementById('setting-book-chapter-flow-mode');
        return el && el.value === 'left' ? 'left' : 'continuous';
    };

    const getLegacyParityFromFlowMode = () => (getBookFlowMode() === 'left' ? 'even' : 'any');

    const data = new FormData();
    data.append('action', 'almaden_save_book_settings');
    data.append('book_id', bookState.bookId);
    data.append('nonce', bookState.settingsNonce);
    
    // Página
    data.append('unit', getVal('setting-unit'));
    data.append('page_size', getVal('setting-page-size'));
    data.append('page_width', getCleanVal('setting-page-width'));
    data.append('page_height', getCleanVal('setting-page-height'));
    data.append('margin_top', getCleanVal('setting-margin-top'));
    data.append('margin_bottom', getCleanVal('setting-margin-bottom'));
    data.append('margin_left_odd', getCleanVal('setting-margin-left-odd'));
    data.append('margin_right_odd', getCleanVal('setting-margin-right-odd'));
    data.append('margin_left_even', getCleanVal('setting-margin-left-even'));
    data.append('margin_right_even', getCleanVal('setting-margin-right-even'));
    // Fallback para mantener compatibilidad
    data.append('margin_left', getCleanVal('setting-margin-left-odd'));
    data.append('margin_right', getCleanVal('setting-margin-right-odd'));
    data.append('padding_top', getCleanVal('setting-padding-top'));
    data.append('padding_bottom', getCleanVal('setting-padding-bottom'));
    data.append('padding_left', getCleanVal('setting-padding-left'));
    data.append('padding_right', getCleanVal('setting-padding-right'));
    data.append('bleeding', getCleanVal('setting-bleeding'));
    data.append('export_grayscale', getChecked('setting-export-grayscale'));
    data.append('ebook_bg_type', getVal('setting-ebook-bg-type'));
    data.append('ebook_bg_color', getVal('setting-ebook-bg-color-text'));
    data.append('ebook_bg_image', getVal('setting-ebook-bg-image'));
    data.append('ebook_bg_opacity', getCleanVal('setting-ebook-bg-opacity'));
    data.append('ebook_cover_panel_bg_type', getVal('setting-ebook-cover-panel-bg-type'));
    data.append('ebook_cover_panel_bg_color', getVal('setting-ebook-cover-panel-bg-color-text'));
    data.append('ebook_cover_panel_bg_image', getVal('setting-ebook-cover-panel-bg-image'));
    data.append('ebook_cover_panel_bg_opacity', getCleanVal('setting-ebook-cover-panel-bg-opacity'));
    data.append('ebook_font_family_content', getVal('setting-ebook-font-family-content'));
    data.append('ebook_font_size_content', getCleanVal('setting-ebook-font-size-content'));
    data.append('ebook_font_weight_content', getVal('setting-ebook-font-weight-content'));
    data.append('ebook_line_height_content', getCleanVal('setting-ebook-line-height-content'));
    data.append('ebook_font_family_headings', getVal('setting-ebook-chapter-title-font-family') || '');
    data.append('ebook_font_size_headings', getCleanVal('setting-ebook-chapter-title-font-size'));
    data.append('ebook_font_weight_headings', getVal('setting-ebook-chapter-title-font-weight') || '');
    data.append('ebook_line_height_headings', getCleanVal('setting-ebook-chapter-title-line-height'));
    
    data.append('ebook_chapter_title_align', getCleanVal('setting-ebook-chapter-title-align'));
    data.append('ebook_chapter_title_text_transform', getCleanVal('setting-ebook-chapter-title-text-transform'));
    data.append('ebook_chapter_title_padding_top', getCleanVal('setting-ebook-chapter-title-padding-top'));
    data.append('ebook_chapter_title_padding_bottom', getCleanVal('setting-ebook-chapter-title-padding-bottom'));
    data.append('ebook_chapter_title_padding_left', getCleanVal('setting-ebook-chapter-title-padding-left'));
    data.append('ebook_chapter_title_padding_right', getCleanVal('setting-ebook-chapter-title-padding-right'));

    data.append('ebook_subtitle_show', getChecked('setting-ebook-chapter-subtitle-show'));
    data.append('ebook_subtitle_font_family', getVal('setting-ebook-chapter-subtitle-font-family') || '');
    data.append('ebook_subtitle_font_size', getCleanVal('setting-ebook-chapter-subtitle-font-size'));
    data.append('ebook_subtitle_align', getVal('setting-ebook-chapter-subtitle-align') || '');
    data.append('ebook_subtitle_font_style', getVal('setting-ebook-chapter-subtitle-font-style') || '');
    data.append('ebook_subtitle_text_transform', getVal('setting-ebook-chapter-subtitle-text-transform') || '');
    data.append('ebook_subtitle_font_weight', getVal('setting-ebook-chapter-subtitle-font-weight') || '');
    data.append('ebook_subtitle_padding_top', getCleanVal('setting-ebook-chapter-subtitle-padding-top'));
    data.append('ebook_subtitle_padding_bottom', getCleanVal('setting-ebook-chapter-subtitle-padding-bottom'));
    data.append('ebook_subtitle_letter_spacing', getCleanVal('setting-ebook-chapter-subtitle-letter-spacing'));

    data.append('ebook_chapter_prefix_show', getChecked('setting-ebook-chapter-prefix-show'));
    data.append('ebook_chapter_prefix_template', getVal('setting-ebook-chapter-prefix-template') || '');
    data.append('ebook_chapter_prefix_position', getVal('setting-ebook-chapter-prefix-position') || 'above');
    data.append('ebook_chapter_prefix_font_family', getVal('setting-ebook-chapter-prefix-font-family') || 'Playfair Display');
    data.append('ebook_chapter_prefix_font_size', getCleanVal('setting-ebook-chapter-prefix-font-size'));
    data.append('ebook_chapter_prefix_font_weight', getVal('setting-ebook-chapter-prefix-font-weight') || 'normal');
    data.append('ebook_chapter_prefix_font_style', getVal('setting-ebook-chapter-prefix-font-style') || 'normal');
    data.append('ebook_chapter_prefix_letter_spacing', getCleanVal('setting-ebook-chapter-prefix-letter-spacing'));
    data.append('ebook_chapter_prefix_ornament', getVal('setting-ebook-chapter-prefix-ornament') || 'none');

    data.append('ebook_text_align_justify', getChecked('setting-ebook-text-align-justify'));
    data.append('ebook_hyphenation', getChecked('setting-ebook-hyphenation'));

    // Tipografía
    data.append('font_family_content', getVal('setting-font-family-content'));
    data.append('font_size_content', getCleanVal('setting-font-size-content'));
    data.append('font_weight_content', getCleanVal('setting-font-weight-content'));
    data.append('line_height_content', getCleanVal('setting-line-height-content'));
    data.append('content_text_align', getVal('setting-content-text-align'));
    data.append('content_text_align_last', getVal('setting-content-text-align-last'));
    data.append('content_hyphenation', getVal('setting-content-hyphenation'));
    data.append('content_language', getVal('setting-content-language'));
    data.append('content_hyphenation_exceptions', getVal('setting-content-hyphenation-exceptions'));
    data.append('content_paragraph_indent', getCleanVal('setting-content-paragraph-indent'));
    data.append('content_paragraph_spacing', getCleanVal('setting-content-paragraph-spacing'));
    data.append('font_family_h1', getVal('setting-font-family-h1'));
    data.append('font_family_h2', getVal('setting-font-family-h2'));
    data.append('font_family_h3', getVal('setting-font-family-h3'));
    data.append('font_style_h1', getVal('setting-font-style-h1'));
    data.append('font_style_h2', getVal('setting-font-style-h2'));
    data.append('font_style_h3', getVal('setting-font-style-h3'));
    data.append('font_weight_h1', getVal('setting-font-weight-h1'));
    data.append('font_weight_h2', getVal('setting-font-weight-h2'));
    data.append('font_weight_h3', getVal('setting-font-weight-h3'));
    data.append('font_size_h1', getCleanVal('setting-font-size-h1'));
    data.append('font_size_h2', getCleanVal('setting-font-size-h2'));
    data.append('font_size_h3', getCleanVal('setting-font-size-h3'));

    // Cabecera y Pie
    data.append('header_font_family', getVal('setting-header-font-family'));
    data.append('header_font_size', getCleanVal('setting-header-font-size'));
    data.append('header_font_weight', getVal('setting-header-font-weight'));
    data.append('header_font_style', getVal('setting-header-font-style'));
    data.append('header_text_transform', getVal('setting-header-text-transform'));
    data.append('header_letter_spacing', getCleanVal('setting-header-letter-spacing'));
    data.append('header_even_type', getVal('setting-header-even-type'));
    data.append('header_even_custom', getVal('setting-header-even-custom'));
    data.append('header_odd_type', getVal('setting-header-odd-type'));
    data.append('header_odd_custom', getVal('setting-header-odd-custom'));
    data.append('footer_font_family', getVal('setting-footer-font-family'));
    data.append('footer_font_size', getCleanVal('setting-footer-font-size'));
    data.append('footer_font_weight', getVal('setting-footer-font-weight'));
    data.append('footer_font_style', getVal('setting-footer-font-style'));
    data.append('footer_text_transform', getVal('setting-footer-text-transform'));
    data.append('footer_letter_spacing', getCleanVal('setting-footer-letter-spacing'));
    data.append('footer_even_type', getVal('setting-footer-even-type'));
    data.append('footer_odd_type', getVal('setting-footer-odd-type'));
    data.append('first_page_header_type', getVal('setting-first-page-header-type'));
    data.append('first_page_header_custom', getVal('setting-first-page-header-custom'));
    data.append('first_page_footer_type', getVal('setting-first-page-footer-type'));
    data.append('first_page_footer_custom', getVal('setting-first-page-footer-custom'));
    data.append('book_start_page_footer_type', getVal('setting-book-start-page-footer-type'));
    data.append('book_separate_opening_content', getChecked('setting-book-separate-opening-content'));
    data.append('book_chapter_flow_mode', getBookFlowMode());

    // Footnotes
    data.append('footnote_font_family', getVal('setting-footnote-font-family'));
    data.append('footnote_font_size', getCleanVal('setting-footnote-font-size'));
    data.append('footnote_font_weight', getVal('setting-footnote-font-weight'));
    data.append('footnote_align', getVal('setting-footnote-align'));
    data.append('footnote_call_scale', getCleanVal('setting-footnote-call-scale'));
    data.append('footnote_call_raise', getCleanVal('setting-footnote-call-raise'));
    data.append('footnote_padding_top', getCleanVal('setting-footnote-padding-top'));
    data.append('footnote_padding_bottom', getCleanVal('setting-footnote-padding-bottom'));
    data.append('footnote_padding_left', getCleanVal('setting-footnote-padding-left'));
    data.append('footnote_padding_right', getCleanVal('setting-footnote-padding-right'));
    data.append('footnote_separator_show', getChecked('setting-footnote-separator-show'));
    data.append('footnote_separator_align', getVal('setting-footnote-separator-align'));
    data.append('footnote_separator_width', getVal('setting-footnote-separator-width'));
    data.append('footnote_separator_thickness', getCleanVal('setting-footnote-separator-thickness'));
    data.append('footnote_separator_margin_bottom', getCleanVal('setting-footnote-separator-margin-bottom'));

    data.append('header_margin_top', getCleanVal('setting-header-margin-top'));
    data.append('header_margin_bottom', getCleanVal('setting-header-margin-bottom'));
    data.append('header_align', getVal('setting-header-align'));
    data.append('footer_margin_top', getCleanVal('setting-footer-margin-top'));
    data.append('footer_margin_bottom', getCleanVal('setting-footer-margin-bottom'));
    data.append('footer_align', getVal('setting-footer-align'));

    // Capítulos
    data.append('chapter_start_parity', getLegacyParityFromFlowMode());

    data.append('chapter_page_one_vertical', getVal('setting-chapter-page-one-vertical'));
    data.append('chapter_title_font_family', getVal('setting-chapter-title-font-family'));
    data.append('chapter_title_font_size', getCleanVal('setting-chapter-title-font-size'));
    data.append('chapter_title_font_weight', getVal('setting-chapter-title-font-weight'));
    data.append('chapter_title_font_style', getVal('setting-chapter-title-font-style'));
    data.append('chapter_title_align', getVal('setting-chapter-title-align'));
    data.append('chapter_title_text_transform', getVal('setting-chapter-title-text-transform'));
    data.append('chapter_title_padding_top', getCleanVal('setting-chapter-title-padding-top'));
    data.append('chapter_title_padding_bottom', getCleanVal('setting-chapter-title-padding-bottom'));
    data.append('chapter_title_padding_left', getCleanVal('setting-chapter-title-padding-left'));
    data.append('chapter_title_padding_right', getCleanVal('setting-chapter-title-padding-right'));
    data.append('chapter_title_line_height', getCleanVal('setting-chapter-title-line-height'));

    data.append('chapter_subtitle_show', getChecked('setting-chapter-subtitle-show'));
    data.append('chapter_subtitle_font_family', getVal('setting-chapter-subtitle-font-family') || '');
    data.append('chapter_subtitle_font_size', getCleanVal('setting-chapter-subtitle-font-size'));
    data.append('chapter_subtitle_align', ['left', 'center', 'right'].includes(String(getVal('setting-chapter-subtitle-align') || '').toLowerCase())
        ? String(getVal('setting-chapter-subtitle-align')).toLowerCase()
        : 'center');
    data.append('chapter_subtitle_font_style', getVal('setting-chapter-subtitle-font-style') || '');
    data.append('chapter_subtitle_text_transform', getVal('setting-chapter-subtitle-text-transform') || '');
    data.append('chapter_subtitle_font_weight', getVal('setting-chapter-subtitle-font-weight') || '');
    data.append('chapter_subtitle_margin_top', getCleanVal('setting-chapter-subtitle-margin-top'));
    data.append('chapter_subtitle_margin_bottom', getCleanVal('setting-chapter-subtitle-margin-bottom'));
    data.append('chapter_subtitle_letter_spacing', getCleanVal('setting-chapter-subtitle-letter-spacing'));

    data.append('chapter_prefix_show', getChecked('setting-chapter-prefix-show'));
    data.append('chapter_prefix_template', getVal('setting-chapter-prefix-template'));
    data.append('chapter_prefix_position', getVal('setting-chapter-prefix-position'));
    data.append('chapter_prefix_font_family', getVal('setting-chapter-prefix-font-family'));
    data.append('chapter_prefix_font_size', getCleanVal('setting-chapter-prefix-font-size'));
    data.append('chapter_prefix_font_weight', getVal('setting-chapter-prefix-font-weight'));
    data.append('chapter_prefix_font_style', getVal('setting-chapter-prefix-font-style'));
    data.append('chapter_prefix_letter_spacing', getCleanVal('setting-chapter-prefix-letter-spacing'));
    data.append('chapter_prefix_ornament', getVal('setting-chapter-prefix-ornament'));

    data.append('book_authors', getVal('setting-book-authors'));

    // Ebook Subtitle Settings
    data.append('ebook_subtitle_show', getChecked('setting-ebook-chapter-subtitle-show'));
    data.append('ebook_subtitle_font_family', getVal('setting-ebook-chapter-subtitle-font-family') || '');
    data.append('ebook_subtitle_font_size', getCleanVal('setting-ebook-chapter-subtitle-font-size'));
    data.append('ebook_subtitle_align', getVal('setting-ebook-chapter-subtitle-align') || '');
    data.append('ebook_subtitle_font_style', getVal('setting-ebook-chapter-subtitle-font-style') || '');
    data.append('ebook_subtitle_text_transform', getVal('setting-ebook-chapter-subtitle-text-transform') || '');
    data.append('ebook_subtitle_font_weight', getVal('setting-ebook-chapter-subtitle-font-weight') || '');
    data.append('ebook_subtitle_padding_top', getCleanVal('setting-ebook-chapter-subtitle-padding-top'));
    data.append('ebook_subtitle_padding_bottom', getCleanVal('setting-ebook-chapter-subtitle-padding-bottom'));
    data.append('ebook_subtitle_letter_spacing', getCleanVal('setting-ebook-chapter-subtitle-letter-spacing'));

    // Créditos
    data.append('credits_edition', getVal('setting-credits-edition'));
    data.append('credits_date', getVal('setting-credits-date'));
    data.append('credits_copyright', getVal('setting-credits-copyright'));
    data.append('credits_printer', getVal('setting-credits-printer'));
    data.append('credits_blank_before', getCleanVal('setting-credits-blank-before'));
    data.append('credits_blank_after', getCleanVal('setting-credits-blank-after'));
    data.append('credits_custom', getCustomCreditsJSON());

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            if (btn) {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
            }
            
            bookState.settings = {
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
                // Fallback para CSS
                margin_left: parseVal('setting-margin-left-odd', 2.0),
                margin_right: parseVal('setting-margin-right-odd', 2.0),
                padding_top: parseVal('setting-padding-top', 0),
                padding_bottom: parseVal('setting-padding-bottom', 0),
                padding_left: parseVal('setting-padding-left', 1.0),
                padding_right: parseVal('setting-padding-right', 1.0),
                bleeding: parseVal('setting-bleeding', 0.5),
                export_grayscale: getChecked('setting-export-grayscale'),
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
                ebook_font_family_headings: document.getElementById('setting-ebook-chapter-title-font-family') ? getVal('setting-ebook-chapter-title-font-family') : '',
                ebook_font_size_headings: parseVal('setting-ebook-chapter-title-font-size', 32),
                ebook_font_weight_headings: document.getElementById('setting-ebook-chapter-title-font-weight') ? getVal('setting-ebook-chapter-title-font-weight') : 'bold',
                ebook_line_height_headings: parseVal('setting-ebook-chapter-title-line-height', 1.3),
                ebook_chapter_title_align: document.getElementById('setting-ebook-chapter-title-align') ? getVal('setting-ebook-chapter-title-align') : 'center',
                ebook_chapter_title_text_transform: document.getElementById('setting-ebook-chapter-title-text-transform') ? getVal('setting-ebook-chapter-title-text-transform') : 'none',
                ebook_chapter_title_padding_top: parseVal('setting-ebook-chapter-title-padding-top', 2),
                ebook_chapter_title_padding_bottom: parseVal('setting-ebook-chapter-title-padding-bottom', 2),
                ebook_chapter_title_padding_left: parseVal('setting-ebook-chapter-title-padding-left', 0),
                ebook_chapter_title_padding_right: parseVal('setting-ebook-chapter-title-padding-right', 0),

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

                ebook_chapter_prefix_show: document.getElementById('setting-ebook-chapter-prefix-show') && getChecked('setting-ebook-chapter-prefix-show'),
                ebook_chapter_prefix_template: document.getElementById('setting-ebook-chapter-prefix-template') ? getVal('setting-ebook-chapter-prefix-template') : 'Capítulo {N}',
                ebook_chapter_prefix_position: document.getElementById('setting-ebook-chapter-prefix-position') ? getVal('setting-ebook-chapter-prefix-position') : 'above',
                ebook_chapter_prefix_font_family: document.getElementById('setting-ebook-chapter-prefix-font-family') ? getVal('setting-ebook-chapter-prefix-font-family') : 'Playfair Display',
                ebook_chapter_prefix_font_size: parseVal('setting-ebook-chapter-prefix-font-size', 16),
                ebook_chapter_prefix_font_weight: document.getElementById('setting-ebook-chapter-prefix-font-weight') ? getVal('setting-ebook-chapter-prefix-font-weight') : 'normal',
                ebook_chapter_prefix_font_style: document.getElementById('setting-ebook-chapter-prefix-font-style') ? getVal('setting-ebook-chapter-prefix-font-style') : 'normal',
                ebook_chapter_prefix_letter_spacing: parseVal('setting-ebook-chapter-prefix-letter-spacing', 0),
                ebook_chapter_prefix_ornament: document.getElementById('setting-ebook-chapter-prefix-ornament') ? getVal('setting-ebook-chapter-prefix-ornament') : 'none',
                ebook_text_align_justify: document.getElementById('setting-ebook-text-align-justify') && getChecked('setting-ebook-text-align-justify'),
                ebook_hyphenation: document.getElementById('setting-ebook-hyphenation') && getChecked('setting-ebook-hyphenation'),

                font_family_content: getVal('setting-font-family-content'),
                font_size_content: parseVal('setting-font-size-content', 11.5),
                font_weight_content: getVal('setting-font-weight-content'),
                line_height_content: parseVal('setting-line-height-content', 1.65),
                content_text_align: getVal('setting-content-text-align'),
                content_text_align_last: getVal('setting-content-text-align-last'),
                content_hyphenation: parseInt(getVal('setting-content-hyphenation')),
                content_language: getVal('setting-content-language'),
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
                first_page_header_type: getVal('setting-first-page-header-type'),
                first_page_header_custom: getVal('setting-first-page-header-custom'),
                first_page_footer_type: getVal('setting-first-page-footer-type'),
                first_page_footer_custom: getVal('setting-first-page-footer-custom'),
                book_start_page_footer_type: getVal('setting-book-start-page-footer-type'),
                book_separate_opening_content: getChecked('setting-book-separate-opening-content'),
                book_chapter_flow_mode: getBookFlowMode(),
                chapter_start_parity: getLegacyParityFromFlowMode(),

                footnote_font_family: getVal('setting-footnote-font-family'),
                footnote_font_size: parseVal('setting-footnote-font-size', 8.5),
                footnote_font_weight: getVal('setting-footnote-font-weight'),
                footnote_align: getVal('setting-footnote-align'),
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

                chapter_start_parity: getVal('setting-chapter-start-parity'),

                chapter_page_one_vertical: getVal('setting-chapter-page-one-vertical'),
                chapter_title_font_family: getVal('setting-chapter-title-font-family'),
                chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
                chapter_title_font_weight: getVal('setting-chapter-title-font-weight'),
                chapter_title_font_style: getVal('setting-chapter-title-font-style'),
                chapter_title_align: getVal('setting-chapter-title-align'),
                chapter_title_text_transform: getVal('setting-chapter-title-text-transform'),
                chapter_title_padding_top: parseVal('setting-chapter-title-padding-top', 0),
                chapter_title_padding_bottom: parseVal('setting-chapter-title-padding-bottom', 1.5),
                chapter_title_padding_left: parseVal('setting-chapter-title-padding-left', 0),
                chapter_title_padding_right: parseVal('setting-chapter-title-padding-right', 0),
                chapter_title_line_height: parseVal('setting-chapter-title-line-height', 1.3),
                
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
                chapter_prefix_font_family: getVal('setting-chapter-prefix-font-family'),
                chapter_prefix_font_size: parseVal('setting-chapter-prefix-font-size', 16),
                chapter_prefix_font_weight: getVal('setting-chapter-prefix-font-weight'),
                chapter_prefix_font_style: getVal('setting-chapter-prefix-font-style'),
                chapter_prefix_letter_spacing: parseVal('setting-chapter-prefix-letter-spacing', 0),
                chapter_prefix_ornament: getVal('setting-chapter-prefix-ornament'),

                credits_edition: getVal('setting-credits-edition'),
                credits_date: getVal('setting-credits-date'),
                credits_copyright: getVal('setting-credits-copyright'),
                credits_printer: getVal('setting-credits-printer'),
                credits_blank_before: parseVal('setting-credits-blank-before', 0),
                credits_blank_after: parseVal('setting-credits-blank-after', 0),
                credits_custom: getCustomCreditsJSON()
            };

            if (typeof applyDynamicPDFStyles === 'function') {
                try {
                    applyDynamicPDFStyles();
                } catch (styleErr) {
                    console.error("Error al aplicar los estilos dinámicos del PDF:", styleErr);
                }
            } else if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
            }
            if (typeof updateParityButtonVisibility === 'function') updateParityButtonVisibility();

            if (!silent) {
                if (btn) {
                    btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Guardado';
                    btn.classList.replace('bg-black', 'bg-emerald-600');
                    btn.classList.replace('hover:bg-neutral-800', 'hover:bg-emerald-700');
                }
                
                setTimeout(() => {
                    toggleSettingsModal(false);
                    showToast("Configuración del PDF guardada", "fa-solid fa-circle-check");

                    if (btn) {
                        btn.innerHTML = originalBtnText;
                        btn.disabled = false;
                        btn.classList.remove('opacity-75', 'cursor-not-allowed');
                        btn.classList.replace('bg-emerald-600', 'bg-black');
                        btn.classList.replace('hover:bg-emerald-700', 'hover:bg-neutral-800');
                    }
                }, 800);
            }
        } else {
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-circle-exclamation mr-1"></i> Error';
                btn.classList.replace('bg-black', 'bg-rose-600');
                btn.classList.replace('hover:bg-neutral-800', 'hover:bg-rose-700');
                setTimeout(() => {
                    btn.innerHTML = originalBtnText;
                    btn.disabled = false;
                    btn.classList.remove('opacity-75', 'cursor-not-allowed');
                    btn.classList.replace('bg-rose-600', 'bg-black');
                    btn.classList.replace('hover:bg-rose-700', 'hover:bg-neutral-800');
                }, 3000);
            }
            alert("Error al guardar: " + res.data);
        }
    })
    .catch(err => {
        console.error(err);
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-wifi mr-1"></i> Error red';
            btn.classList.replace('bg-black', 'bg-rose-600');
            btn.classList.replace('hover:bg-neutral-800', 'hover:bg-rose-700');
            setTimeout(() => {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btn.classList.replace('bg-rose-600', 'bg-black');
                btn.classList.replace('hover:bg-rose-700', 'hover:bg-neutral-800');
            }, 3000);
        }
    });
}
