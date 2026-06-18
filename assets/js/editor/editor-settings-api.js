// Enviar ajustes vía AJAX a la base de datos de WordPress
function savePDFSettings() {
    const btn = document.getElementById('btn-save-settings');
    const originalBtnText = btn ? btn.innerHTML : 'Guardar Cambios';
    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Guardando...';
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }

    const getCleanVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.replace(',', '.') : '';
    };

    const parseVal = (id, fallback) => {
        const val = getCleanVal(id);
        const clean = parseFloat(val);
        return isNaN(clean) ? fallback : clean;
    };

    const data = new FormData();
    data.append('action', 'almaden_save_book_settings');
    data.append('book_id', bookState.bookId);
    data.append('nonce', bookState.settingsNonce);
    
    // Página
    data.append('unit', document.getElementById('setting-unit').value);
    data.append('page_size', document.getElementById('setting-page-size').value);
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
    data.append('padding_right', document.getElementById('setting-padding-right').value);
    data.append('bleeding', document.getElementById('setting-bleeding').value);
    data.append('export_grayscale', document.getElementById('setting-export-grayscale').checked ? 1 : 0);
    data.append('ebook_bg_type', document.getElementById('setting-ebook-bg-type').value);
    data.append('ebook_bg_color', document.getElementById('setting-ebook-bg-color-text').value);
    data.append('ebook_bg_image', document.getElementById('setting-ebook-bg-image').value);
    data.append('ebook_bg_opacity', getCleanVal('setting-ebook-bg-opacity'));
    data.append('ebook_cover_panel_bg_type', document.getElementById('setting-ebook-cover-panel-bg-type').value);
    data.append('ebook_cover_panel_bg_color', document.getElementById('setting-ebook-cover-panel-bg-color-text').value);
    data.append('ebook_cover_panel_bg_image', document.getElementById('setting-ebook-cover-panel-bg-image').value);
    data.append('ebook_cover_panel_bg_opacity', getCleanVal('setting-ebook-cover-panel-bg-opacity'));
    data.append('ebook_font_family_content', document.getElementById('setting-ebook-font-family-content').value);
    data.append('ebook_font_size_content', getCleanVal('setting-ebook-font-size-content'));
    data.append('ebook_font_weight_content', document.getElementById('setting-ebook-font-weight-content').value);
    data.append('ebook_line_height_content', getCleanVal('setting-ebook-line-height-content'));
    data.append('ebook_font_family_headings', document.getElementById('setting-ebook-chapter-title-font-family')?.value || '');
    data.append('ebook_font_size_headings', getCleanVal('setting-ebook-chapter-title-font-size'));
    data.append('ebook_font_weight_headings', document.getElementById('setting-ebook-chapter-title-font-weight')?.value || '');
    data.append('ebook_line_height_headings', getCleanVal('setting-ebook-chapter-title-line-height'));
    
    data.append('ebook_chapter_title_align', getCleanVal('setting-ebook-chapter-title-align'));
    data.append('ebook_chapter_title_text_transform', getCleanVal('setting-ebook-chapter-title-text-transform'));
    data.append('ebook_chapter_title_padding_top', getCleanVal('setting-ebook-chapter-title-padding-top'));
    data.append('ebook_chapter_title_padding_bottom', getCleanVal('setting-ebook-chapter-title-padding-bottom'));
    data.append('ebook_chapter_title_padding_left', getCleanVal('setting-ebook-chapter-title-padding-left'));
    data.append('ebook_chapter_title_padding_right', getCleanVal('setting-ebook-chapter-title-padding-right'));

    data.append('ebook_subtitle_show', document.getElementById('setting-ebook-chapter-subtitle-show')?.checked ? 1 : 0);
    data.append('ebook_subtitle_font_family', document.getElementById('setting-ebook-chapter-subtitle-font-family')?.value || '');
    data.append('ebook_subtitle_font_size', getCleanVal('setting-ebook-chapter-subtitle-font-size'));
    data.append('ebook_subtitle_align', document.getElementById('setting-ebook-chapter-subtitle-align')?.value || '');
    data.append('ebook_subtitle_font_style', document.getElementById('setting-ebook-chapter-subtitle-font-style')?.value || '');
    data.append('ebook_subtitle_text_transform', document.getElementById('setting-ebook-chapter-subtitle-text-transform')?.value || '');
    data.append('ebook_subtitle_font_weight', document.getElementById('setting-ebook-chapter-subtitle-font-weight')?.value || '');
    data.append('ebook_subtitle_padding_top', getCleanVal('setting-ebook-chapter-subtitle-padding-top'));
    data.append('ebook_subtitle_padding_bottom', getCleanVal('setting-ebook-chapter-subtitle-padding-bottom'));
    data.append('ebook_subtitle_letter_spacing', getCleanVal('setting-ebook-chapter-subtitle-letter-spacing'));

    data.append('ebook_chapter_prefix_show', document.getElementById('setting-ebook-chapter-prefix-show')?.checked ? 1 : 0);
    data.append('ebook_chapter_prefix_template', document.getElementById('setting-ebook-chapter-prefix-template')?.value || '');
    data.append('ebook_chapter_prefix_position', document.getElementById('setting-ebook-chapter-prefix-position')?.value || 'above');
    data.append('ebook_chapter_prefix_font_family', document.getElementById('setting-ebook-chapter-prefix-font-family')?.value || 'Playfair Display');
    data.append('ebook_chapter_prefix_font_size', getCleanVal('setting-ebook-chapter-prefix-font-size'));
    data.append('ebook_chapter_prefix_font_weight', document.getElementById('setting-ebook-chapter-prefix-font-weight')?.value || 'normal');
    data.append('ebook_chapter_prefix_font_style', document.getElementById('setting-ebook-chapter-prefix-font-style')?.value || 'normal');
    data.append('ebook_chapter_prefix_letter_spacing', getCleanVal('setting-ebook-chapter-prefix-letter-spacing'));
    data.append('ebook_chapter_prefix_ornament', document.getElementById('setting-ebook-chapter-prefix-ornament')?.value || 'none');

    data.append('ebook_text_align_justify', document.getElementById('setting-ebook-text-align-justify')?.checked ? 1 : 0);
    data.append('ebook_hyphenation', document.getElementById('setting-ebook-hyphenation')?.checked ? 1 : 0);

    // Tipografía
    data.append('font_family_content', document.getElementById('setting-font-family-content').value);
    data.append('font_size_content', getCleanVal('setting-font-size-content'));
    data.append('font_weight_content', getCleanVal('setting-font-weight-content'));
    data.append('line_height_content', getCleanVal('setting-line-height-content'));
    data.append('content_text_align', document.getElementById('setting-content-text-align').value);
    data.append('content_hyphenation', document.getElementById('setting-content-hyphenation').value);
    data.append('content_language', document.getElementById('setting-content-language').value);
    data.append('content_paragraph_indent', getCleanVal('setting-content-paragraph-indent'));
    data.append('content_paragraph_spacing', getCleanVal('setting-content-paragraph-spacing'));
    data.append('font_family_h1', document.getElementById('setting-font-family-h1').value);
    data.append('font_family_h2', document.getElementById('setting-font-family-h2').value);
    data.append('font_family_h3', document.getElementById('setting-font-family-h3').value);
    data.append('font_weight_h1', document.getElementById('setting-font-weight-h1').value);
    data.append('font_weight_h2', document.getElementById('setting-font-weight-h2').value);
    data.append('font_weight_h3', document.getElementById('setting-font-weight-h3').value);
    data.append('font_size_h1', getCleanVal('setting-font-size-h1'));
    data.append('font_size_h2', getCleanVal('setting-font-size-h2'));
    data.append('font_size_h3', getCleanVal('setting-font-size-h3'));

    // Cabecera y Pie
    data.append('header_font_family', document.getElementById('setting-header-font-family').value);
    data.append('header_font_size', getCleanVal('setting-header-font-size'));
    data.append('header_font_weight', document.getElementById('setting-header-font-weight').value);
    data.append('header_font_style', document.getElementById('setting-header-font-style').value);
    data.append('header_letter_spacing', getCleanVal('setting-header-letter-spacing'));
    data.append('header_even_type', document.getElementById('setting-header-even-type').value);
    data.append('header_even_custom', document.getElementById('setting-header-even-custom').value);
    data.append('header_odd_type', document.getElementById('setting-header-odd-type').value);
    data.append('header_odd_custom', document.getElementById('setting-header-odd-custom').value);
    data.append('footer_font_family', document.getElementById('setting-footer-font-family').value);
    data.append('footer_font_size', getCleanVal('setting-footer-font-size'));
    data.append('footer_font_weight', document.getElementById('setting-footer-font-weight').value);
    data.append('footer_font_style', document.getElementById('setting-footer-font-style').value);
    data.append('footer_letter_spacing', getCleanVal('setting-footer-letter-spacing'));
    data.append('footer_even_type', document.getElementById('setting-footer-even-type').value);
    data.append('footer_odd_type', document.getElementById('setting-footer-odd-type').value);
    data.append('first_page_header_type', document.getElementById('setting-first-page-header-type').value);
    data.append('first_page_header_custom', document.getElementById('setting-first-page-header-custom').value);
    data.append('first_page_footer_type', document.getElementById('setting-first-page-footer-type').value);
    data.append('first_page_footer_custom', document.getElementById('setting-first-page-footer-custom').value);

    data.append('header_margin_top', getCleanVal('setting-header-margin-top'));
    data.append('header_margin_bottom', getCleanVal('setting-header-margin-bottom'));
    data.append('header_align', document.getElementById('setting-header-align').value);
    data.append('footer_margin_top', getCleanVal('setting-footer-margin-top'));
    data.append('footer_margin_bottom', getCleanVal('setting-footer-margin-bottom'));
    data.append('footer_align', document.getElementById('setting-footer-align').value);

    // Capítulos
    data.append('chapter_start_parity', document.getElementById('setting-chapter-start-parity').value);

    data.append('chapter_page_one_vertical', document.getElementById('setting-chapter-page-one-vertical').value);
    data.append('chapter_title_font_family', document.getElementById('setting-chapter-title-font-family').value);
    data.append('chapter_title_font_size', getCleanVal('setting-chapter-title-font-size'));
    data.append('chapter_title_font_weight', document.getElementById('setting-chapter-title-font-weight').value);
    data.append('chapter_title_font_style', document.getElementById('setting-chapter-title-font-style').value);
    data.append('chapter_title_align', document.getElementById('setting-chapter-title-align').value);
    data.append('chapter_title_text_transform', document.getElementById('setting-chapter-title-text-transform').value);
    data.append('chapter_title_padding_top', getCleanVal('setting-chapter-title-padding-top'));
    data.append('chapter_title_padding_bottom', getCleanVal('setting-chapter-title-padding-bottom'));
    data.append('chapter_title_padding_left', getCleanVal('setting-chapter-title-padding-left'));
    data.append('chapter_title_padding_right', getCleanVal('setting-chapter-title-padding-right'));
    data.append('chapter_title_line_height', getCleanVal('setting-chapter-title-line-height'));

    data.append('chapter_subtitle_show', document.getElementById('setting-chapter-subtitle-show')?.checked ? 1 : 0);
    data.append('chapter_subtitle_font_family', document.getElementById('setting-chapter-subtitle-font-family')?.value || '');
    data.append('chapter_subtitle_font_size', getCleanVal('setting-chapter-subtitle-font-size'));
    data.append('chapter_subtitle_align', document.getElementById('setting-chapter-subtitle-align')?.value || '');
    data.append('chapter_subtitle_font_style', document.getElementById('setting-chapter-subtitle-font-style')?.value || '');
    data.append('chapter_subtitle_text_transform', document.getElementById('setting-chapter-subtitle-text-transform')?.value || '');
    data.append('chapter_subtitle_font_weight', document.getElementById('setting-chapter-subtitle-font-weight')?.value || '');
    data.append('chapter_subtitle_margin_top', getCleanVal('setting-chapter-subtitle-margin-top'));
    data.append('chapter_subtitle_margin_bottom', getCleanVal('setting-chapter-subtitle-margin-bottom'));
    data.append('chapter_subtitle_letter_spacing', getCleanVal('setting-chapter-subtitle-letter-spacing'));

    data.append('chapter_prefix_show', document.getElementById('setting-chapter-prefix-show').checked ? 1 : 0);
    data.append('chapter_prefix_template', document.getElementById('setting-chapter-prefix-template').value);
    data.append('chapter_prefix_position', document.getElementById('setting-chapter-prefix-position').value);
    data.append('chapter_prefix_font_family', document.getElementById('setting-chapter-prefix-font-family').value);
    data.append('chapter_prefix_font_size', getCleanVal('setting-chapter-prefix-font-size'));
    data.append('chapter_prefix_font_weight', document.getElementById('setting-chapter-prefix-font-weight').value);
    data.append('chapter_prefix_font_style', document.getElementById('setting-chapter-prefix-font-style').value);
    data.append('chapter_prefix_letter_spacing', getCleanVal('setting-chapter-prefix-letter-spacing'));
    data.append('chapter_prefix_ornament', document.getElementById('setting-chapter-prefix-ornament').value);

    // Ebook Subtitle Settings
    data.append('ebook_subtitle_show', document.getElementById('setting-ebook-chapter-subtitle-show')?.checked ? 1 : 0);
    data.append('ebook_subtitle_font_family', document.getElementById('setting-ebook-chapter-subtitle-font-family')?.value || '');
    data.append('ebook_subtitle_font_size', getCleanVal('setting-ebook-chapter-subtitle-font-size'));
    data.append('ebook_subtitle_align', document.getElementById('setting-ebook-chapter-subtitle-align')?.value || '');
    data.append('ebook_subtitle_font_style', document.getElementById('setting-ebook-chapter-subtitle-font-style')?.value || '');
    data.append('ebook_subtitle_text_transform', document.getElementById('setting-ebook-chapter-subtitle-text-transform')?.value || '');
    data.append('ebook_subtitle_font_weight', document.getElementById('setting-ebook-chapter-subtitle-font-weight')?.value || '');
    data.append('ebook_subtitle_padding_top', getCleanVal('setting-ebook-chapter-subtitle-padding-top'));
    data.append('ebook_subtitle_padding_bottom', getCleanVal('setting-ebook-chapter-subtitle-padding-bottom'));
    data.append('ebook_subtitle_letter_spacing', getCleanVal('setting-ebook-chapter-subtitle-letter-spacing'));

    // Créditos
    data.append('credits_edition', document.getElementById('setting-credits-edition').value);
    data.append('credits_date', document.getElementById('setting-credits-date').value);
    data.append('credits_copyright', document.getElementById('setting-credits-copyright').value);
    data.append('credits_printer', document.getElementById('setting-credits-printer').value);
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
                unit: document.getElementById('setting-unit').value,
                page_size: document.getElementById('setting-page-size').value,
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
                export_grayscale: document.getElementById('setting-export-grayscale').checked ? 1 : 0,
                ebook_bg_type: document.getElementById('setting-ebook-bg-type').value,
                ebook_bg_color: document.getElementById('setting-ebook-bg-color-text').value,
                ebook_bg_image: document.getElementById('setting-ebook-bg-image').value,
                ebook_bg_opacity: parseVal('setting-ebook-bg-opacity', 1.0),
                ebook_cover_panel_bg_type: document.getElementById('setting-ebook-cover-panel-bg-type').value,
                ebook_cover_panel_bg_color: document.getElementById('setting-ebook-cover-panel-bg-color-text').value,
                ebook_cover_panel_bg_image: document.getElementById('setting-ebook-cover-panel-bg-image').value,
                ebook_cover_panel_bg_opacity: parseVal('setting-ebook-cover-panel-bg-opacity', 1.0),
                ebook_font_family_content: document.getElementById('setting-ebook-font-family-content').value,
                ebook_font_size_content: parseVal('setting-ebook-font-size-content', 18.0),
                ebook_font_weight_content: document.getElementById('setting-ebook-font-weight-content').value,
                ebook_line_height_content: parseVal('setting-ebook-line-height-content', 1.8),
                ebook_font_family_headings: document.getElementById('setting-ebook-chapter-title-font-family') ? document.getElementById('setting-ebook-chapter-title-font-family').value : '',
                ebook_font_size_headings: parseVal('setting-ebook-chapter-title-font-size', 32),
                ebook_font_weight_headings: document.getElementById('setting-ebook-chapter-title-font-weight') ? document.getElementById('setting-ebook-chapter-title-font-weight').value : 'bold',
                ebook_line_height_headings: parseVal('setting-ebook-chapter-title-line-height', 1.3),
                ebook_chapter_title_align: document.getElementById('setting-ebook-chapter-title-align') ? document.getElementById('setting-ebook-chapter-title-align').value : 'center',
                ebook_chapter_title_text_transform: document.getElementById('setting-ebook-chapter-title-text-transform') ? document.getElementById('setting-ebook-chapter-title-text-transform').value : 'none',
                ebook_chapter_title_padding_top: parseVal('setting-ebook-chapter-title-padding-top', 2),
                ebook_chapter_title_padding_bottom: parseVal('setting-ebook-chapter-title-padding-bottom', 2),
                ebook_chapter_title_padding_left: parseVal('setting-ebook-chapter-title-padding-left', 0),
                ebook_chapter_title_padding_right: parseVal('setting-ebook-chapter-title-padding-right', 0),

                ebook_subtitle_show: document.getElementById('setting-ebook-chapter-subtitle-show')?.checked ? 1 : 0,
                ebook_subtitle_font_family: document.getElementById('setting-ebook-chapter-subtitle-font-family')?.value || '',
                ebook_subtitle_font_size: parseVal('setting-ebook-chapter-subtitle-font-size', 18),
                ebook_subtitle_align: document.getElementById('setting-ebook-chapter-subtitle-align')?.value || 'center',
                ebook_subtitle_font_style: document.getElementById('setting-ebook-chapter-subtitle-font-style')?.value || 'normal',
                ebook_subtitle_text_transform: document.getElementById('setting-ebook-chapter-subtitle-text-transform')?.value || 'none',
                ebook_subtitle_font_weight: document.getElementById('setting-ebook-chapter-subtitle-font-weight')?.value || 'normal',
                ebook_subtitle_padding_top: parseVal('setting-ebook-chapter-subtitle-padding-top', 0.5),
                ebook_subtitle_padding_bottom: parseVal('setting-ebook-chapter-subtitle-padding-bottom', 0.5),
                ebook_subtitle_letter_spacing: parseVal('setting-ebook-chapter-subtitle-letter-spacing', 0),

                ebook_chapter_prefix_show: document.getElementById('setting-ebook-chapter-prefix-show') && document.getElementById('setting-ebook-chapter-prefix-show').checked ? 1 : 0,
                ebook_chapter_prefix_template: document.getElementById('setting-ebook-chapter-prefix-template') ? document.getElementById('setting-ebook-chapter-prefix-template').value : 'Capítulo {N}',
                ebook_chapter_prefix_position: document.getElementById('setting-ebook-chapter-prefix-position') ? document.getElementById('setting-ebook-chapter-prefix-position').value : 'above',
                ebook_chapter_prefix_font_family: document.getElementById('setting-ebook-chapter-prefix-font-family') ? document.getElementById('setting-ebook-chapter-prefix-font-family').value : 'Playfair Display',
                ebook_chapter_prefix_font_size: parseVal('setting-ebook-chapter-prefix-font-size', 16),
                ebook_chapter_prefix_font_weight: document.getElementById('setting-ebook-chapter-prefix-font-weight') ? document.getElementById('setting-ebook-chapter-prefix-font-weight').value : 'normal',
                ebook_chapter_prefix_font_style: document.getElementById('setting-ebook-chapter-prefix-font-style') ? document.getElementById('setting-ebook-chapter-prefix-font-style').value : 'normal',
                ebook_chapter_prefix_letter_spacing: parseVal('setting-ebook-chapter-prefix-letter-spacing', 0),
                ebook_chapter_prefix_ornament: document.getElementById('setting-ebook-chapter-prefix-ornament') ? document.getElementById('setting-ebook-chapter-prefix-ornament').value : 'none',
                ebook_text_align_justify: document.getElementById('setting-ebook-text-align-justify') && document.getElementById('setting-ebook-text-align-justify').checked ? 1 : 0,
                ebook_hyphenation: document.getElementById('setting-ebook-hyphenation') && document.getElementById('setting-ebook-hyphenation').checked ? 1 : 0,

                font_family_content: document.getElementById('setting-font-family-content').value,
                font_size_content: parseVal('setting-font-size-content', 11.5),
                font_weight_content: document.getElementById('setting-font-weight-content').value,
                line_height_content: parseVal('setting-line-height-content', 1.65),
                content_text_align: document.getElementById('setting-content-text-align').value,
                content_hyphenation: parseInt(document.getElementById('setting-content-hyphenation').value),
                content_language: document.getElementById('setting-content-language').value,
                content_paragraph_indent: parseVal('setting-content-paragraph-indent', 0.0),
                content_paragraph_spacing: parseVal('setting-content-paragraph-spacing', 14.0),
                font_family_h1: document.getElementById('setting-font-family-h1').value,
                font_family_h2: document.getElementById('setting-font-family-h2').value,
                font_family_h3: document.getElementById('setting-font-family-h3').value,
                font_weight_h1: document.getElementById('setting-font-weight-h1').value,
                font_weight_h2: document.getElementById('setting-font-weight-h2').value,
                font_weight_h3: document.getElementById('setting-font-weight-h3').value,
                font_size_h1: parseVal('setting-font-size-h1', 24),
                font_size_h2: parseVal('setting-font-size-h2', 16),
                font_size_h3: parseVal('setting-font-size-h3', 13),

                header_font_family: document.getElementById('setting-header-font-family').value,
                header_font_size: parseVal('setting-header-font-size', 8.5),
                header_font_weight: document.getElementById('setting-header-font-weight').value,
                header_font_style: document.getElementById('setting-header-font-style').value,
                header_letter_spacing: parseVal('setting-header-letter-spacing', 0.1),
                header_even_type: document.getElementById('setting-header-even-type').value,
                header_even_custom: document.getElementById('setting-header-even-custom').value,
                header_odd_type: document.getElementById('setting-header-odd-type').value,
                header_odd_custom: document.getElementById('setting-header-odd-custom').value,
                footer_font_family: document.getElementById('setting-footer-font-family').value,
                footer_font_size: parseVal('setting-footer-font-size', 9),
                footer_font_weight: document.getElementById('setting-footer-font-weight').value,
                footer_font_style: document.getElementById('setting-footer-font-style').value,
                footer_letter_spacing: parseVal('setting-footer-letter-spacing', 0),
                footer_even_type: document.getElementById('setting-footer-even-type').value,
                footer_odd_type: document.getElementById('setting-footer-odd-type').value,
                first_page_header_type: document.getElementById('setting-first-page-header-type').value,
                first_page_header_custom: document.getElementById('setting-first-page-header-custom').value,
                first_page_footer_type: document.getElementById('setting-first-page-footer-type').value,
                first_page_footer_custom: document.getElementById('setting-first-page-footer-custom').value,

                header_margin_top: parseVal('setting-header-margin-top', 1.0),
                header_margin_bottom: parseVal('setting-header-margin-bottom', 0.5),
                header_align: document.getElementById('setting-header-align').value,
                footer_margin_top: parseVal('setting-footer-margin-top', 0.5),
                footer_margin_bottom: parseVal('setting-footer-margin-bottom', 1.0),
                footer_align: document.getElementById('setting-footer-align').value,

                chapter_start_parity: document.getElementById('setting-chapter-start-parity').value,

                chapter_page_one_vertical: document.getElementById('setting-chapter-page-one-vertical').value,
                chapter_title_font_family: document.getElementById('setting-chapter-title-font-family').value,
                chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
                chapter_title_font_weight: document.getElementById('setting-chapter-title-font-weight').value,
                chapter_title_font_style: document.getElementById('setting-chapter-title-font-style').value,
                chapter_title_align: document.getElementById('setting-chapter-title-align').value,
                chapter_title_text_transform: document.getElementById('setting-chapter-title-text-transform').value,
                chapter_title_padding_top: parseVal('setting-chapter-title-padding-top', 0),
                chapter_title_padding_bottom: parseVal('setting-chapter-title-padding-bottom', 1.5),
                chapter_title_padding_left: parseVal('setting-chapter-title-padding-left', 0),
                chapter_title_padding_right: parseVal('setting-chapter-title-padding-right', 0),
                chapter_title_line_height: parseVal('setting-chapter-title-line-height', 1.3),
                
                chapter_subtitle_show: document.getElementById('setting-chapter-subtitle-show')?.checked ? 1 : 0,
                chapter_subtitle_font_family: document.getElementById('setting-chapter-subtitle-font-family')?.value || '',
                chapter_subtitle_font_size: parseVal('setting-chapter-subtitle-font-size', 16),
                chapter_subtitle_align: document.getElementById('setting-chapter-subtitle-align')?.value || 'center',
                chapter_subtitle_font_style: document.getElementById('setting-chapter-subtitle-font-style')?.value || 'normal',
                chapter_subtitle_text_transform: document.getElementById('setting-chapter-subtitle-text-transform')?.value || 'none',
                chapter_subtitle_font_weight: document.getElementById('setting-chapter-subtitle-font-weight')?.value || 'normal',
                chapter_subtitle_margin_top: parseVal('setting-chapter-subtitle-margin-top', 0.5),
                chapter_subtitle_margin_bottom: parseVal('setting-chapter-subtitle-margin-bottom', 0.5),
                chapter_subtitle_letter_spacing: parseVal('setting-chapter-subtitle-letter-spacing', 0),

                chapter_prefix_show: document.getElementById('setting-chapter-prefix-show').checked ? 1 : 0,
                chapter_prefix_template: document.getElementById('setting-chapter-prefix-template').value,
                chapter_prefix_position: document.getElementById('setting-chapter-prefix-position').value,
                chapter_prefix_font_family: document.getElementById('setting-chapter-prefix-font-family').value,
                chapter_prefix_font_size: parseVal('setting-chapter-prefix-font-size', 16),
                chapter_prefix_font_weight: document.getElementById('setting-chapter-prefix-font-weight').value,
                chapter_prefix_font_style: document.getElementById('setting-chapter-prefix-font-style').value,
                chapter_prefix_letter_spacing: parseVal('setting-chapter-prefix-letter-spacing', 0),
                chapter_prefix_ornament: document.getElementById('setting-chapter-prefix-ornament').value,

                credits_edition: document.getElementById('setting-credits-edition').value,
                credits_date: document.getElementById('setting-credits-date').value,
                credits_copyright: document.getElementById('setting-credits-copyright').value,
                credits_printer: document.getElementById('setting-credits-printer').value,
                credits_blank_before: parseVal('setting-credits-blank-before', 0),
                credits_blank_after: parseVal('setting-credits-blank-after', 0),
                credits_custom: getCustomCreditsJSON()
            };

            if (typeof applyDynamicPDFStyles === 'function') applyDynamicPDFStyles();
            if (typeof compilePDFPreview === 'function') compilePDFPreview(); // RECOMPILAR PDF con los nuevos márgenes/anchos
            if (typeof updateParityButtonVisibility === 'function') updateParityButtonVisibility();

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
