// Enviar ajustes vía AJAX a la base de datos de WordPress
window.savePDFSettings = function(silent = false, skipPreview = false) {
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

    const getBookLanguage = () => getVal('setting-book-language', getVal('setting-content-language', 'es'));

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
    data.append('page_columns_enabled', getChecked('setting-page-columns-enabled'));
    data.append('page_columns_count', getCleanVal('setting-page-columns-count'));
    data.append('page_columns_gap', getCleanVal('setting-page-columns-gap'));
    data.append('page_templates', JSON.stringify(bookState.settings?.page_templates || []));
    data.append('page_styles', JSON.stringify(bookState.settings?.page_styles || []));
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
    data.append('book_language', getBookLanguage());
    data.append('content_language', getBookLanguage());
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
    data.append('header_hyphenate', getChecked('setting-header-hyphenate'));
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
    data.append('first_page_header_show', getChecked('setting-first-page-header-show'));
    data.append('first_page_header_type', getVal('setting-first-page-header-type'));
    data.append('first_page_header_custom', getVal('setting-first-page-header-custom'));
    data.append('first_page_footer_show', getChecked('setting-first-page-footer-show'));
    data.append('first_page_footer_type', getVal('setting-first-page-footer-type'));
    data.append('first_page_footer_custom', getVal('setting-first-page-footer-custom'));
    data.append('book_separate_opening_content', getChecked('setting-book-separate-opening-content'));
    data.append('book_chapter_flow_mode', getBookFlowMode());
    data.append('chapter_transition_blank_mode', getVal('setting-chapter-transition-blank-mode') || 'full_blank');
    data.append('chapter_transition_blank_text', getVal('setting-chapter-transition-blank-text') || '...');

    // Footnotes
    data.append('footnote_mode', getVal('setting-footnote-mode') || 'page');
    data.append('footnote_chapter_title', getVal('setting-footnote-chapter-title') || 'Referencia');
    data.append('footnote_book_title', getVal('setting-footnote-book-title') || 'Referencias');
    data.append('footnote_font_family', getVal('setting-footnote-font-family'));
    data.append('footnote_font_size', getCleanVal('setting-footnote-font-size'));
    data.append('footnote_font_weight', getVal('setting-footnote-font-weight'));
    data.append('footnote_align', getVal('setting-footnote-align'));
    data.append('footnote_line_height', getCleanVal('setting-footnote-line-height'));
    data.append('footnote_letter_spacing', getCleanVal('setting-footnote-letter-spacing'));
    data.append('footnote_entry_spacing', getCleanVal('setting-footnote-entry-spacing'));
    data.append('footnote_hyphenate', getChecked('setting-footnote-hyphenate'));
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

    data.append('chapter_page_one_align', openingPageAlignment.combined);
    data.append('chapter_page_one_vertical', openingPageAlignment.vertical);
    data.append('chapter_title_font_family', getVal('setting-chapter-title-font-family'));
    data.append('chapter_title_font_size', getCleanVal('setting-chapter-title-font-size'));
    data.append('chapter_title_font_weight', getVal('setting-chapter-title-font-weight'));
    data.append('chapter_title_font_style', getVal('setting-chapter-title-font-style'));
    data.append('chapter_title_align', getVal('setting-chapter-title-align'));
    data.append('chapter_title_text_transform', getVal('setting-chapter-title-text-transform'));
    data.append('chapter_title_letter_spacing', getCleanVal('setting-chapter-title-letter-spacing'));
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
    const creditsConfig = typeof getCreditsConfigFromForm === 'function'
        ? getCreditsConfigFromForm()
        : (bookState.settings.credits_config || {});
    const creditsLegacy = typeof almadenCreditsConfigToLegacy === 'function'
        ? almadenCreditsConfigToLegacy(creditsConfig)
        : {
            credits_edition: getVal('setting-credits-edition'),
            credits_date: getVal('setting-credits-date'),
            credits_isbn: getVal('setting-credits-isbn'),
            credits_copyright: getVal('setting-credits-copyright'),
            credits_printer: getVal('setting-credits-printer'),
            credits_blank_before: parseVal('setting-credits-blank-before', 0),
            credits_blank_after: parseVal('setting-credits-blank-after', 0),
            credits_license: getVal('setting-credits-license'),
            credits_custom: getCustomCreditsJSON()
        };
    data.append('credits_config', JSON.stringify(creditsConfig));
    data.append('credits_edition', creditsLegacy.credits_edition || '');
    data.append('credits_date', creditsLegacy.credits_date || '');
    data.append('credits_isbn', creditsLegacy.credits_isbn || '');
    data.append('credits_copyright', creditsLegacy.credits_copyright || '');
    data.append('credits_printer', creditsLegacy.credits_printer || '');
    data.append('credits_blank_before', creditsLegacy.credits_blank_before ?? 0);
    data.append('credits_blank_after', creditsLegacy.credits_blank_after ?? 0);
    data.append('credits_license', creditsLegacy.credits_license || 'all_rights_reserved');
    data.append('credits_custom', creditsLegacy.credits_custom || '[]');

    return fetch(bookState.ajaxUrl, {
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
            
            const pageTemplates = Array.isArray(bookState.settings?.page_templates)
                ? bookState.settings.page_templates
                : [];
            const pageStyles = Array.isArray(bookState.settings?.page_styles)
                ? bookState.settings.page_styles
                : [];
            bookState.settings = typeof almadenBuildPDFSettingsState === 'function'
                ? almadenBuildPDFSettingsState({
                    getVal,
                    getCleanVal,
                    getChecked,
                    parseVal,
                    getBookLanguage,
                    getBookFlowMode,
                    getLegacyParityFromFlowMode,
                    creditsConfig,
                    creditsLegacy
                })
                : bookState.settings;
            bookState.settings.page_templates = pageTemplates;
            bookState.settings.page_styles = pageStyles;

            if (!skipPreview && typeof applyDynamicPDFStyles === 'function') {
                try {
                    applyDynamicPDFStyles();
                } catch (styleErr) {
                    console.error("Error al aplicar los estilos dinámicos del PDF:", styleErr);
                }
            } else if (!skipPreview && typeof refreshEditorDisplay === 'function') {
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

            return true;
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
            return false;
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
        return false;
    });
}
