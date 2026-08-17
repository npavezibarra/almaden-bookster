// Manejo de pestañas del modal de ajustes
window.switchFormatTab = function(format) {
    const btnPdf = document.getElementById('btn-format-pdf');
    const btnEbook = document.getElementById('btn-format-ebook');
    const btnGlobal = document.getElementById('btn-format-global');
    const secPdf = document.getElementById('format-pdf-section');
    const secEbook = document.getElementById('format-ebook-section');
    const secGlobal = document.getElementById('format-global-section');

    const setActiveButton = (btn) => {
        if (!btn) return;
        btn.classList.add('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-black', 'dark:text-white', 'font-bold');
        btn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
    };

    const setInactiveButton = (btn) => {
        if (!btn) return;
        btn.classList.remove('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-black', 'dark:text-white', 'font-bold');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    };

    if (format === 'pdf') {
        setActiveButton(btnPdf);
        setInactiveButton(btnEbook);
        setInactiveButton(btnGlobal);
        
        secPdf.classList.remove('hidden');
        secEbook.classList.add('hidden');
        secGlobal.classList.add('hidden');
    } else if (format === 'global') {
        setActiveButton(btnGlobal);
        setInactiveButton(btnPdf);
        setInactiveButton(btnEbook);

        secGlobal.classList.remove('hidden');
        secPdf.classList.add('hidden');
        secEbook.classList.add('hidden');
    } else {
        setActiveButton(btnEbook);
        setInactiveButton(btnPdf);
        setInactiveButton(btnGlobal);
        
        secEbook.classList.remove('hidden');
        secPdf.classList.add('hidden');
        secGlobal.classList.add('hidden');
    }
}

function switchSettingTab(tabId) {
    // Ocultar todos los contenidos de pestaña
    document.querySelectorAll('.setting-tab-content').forEach(el => {
        el.classList.add('hidden');
    });
    // Mostrar pestaña seleccionada
    const target = document.getElementById(tabId);
    if (target) target.classList.remove('hidden');

    // Actualizar estilos de los botones
    document.querySelectorAll('.setting-tab-btn').forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });
    
    // Resaltar botón activo
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
    }

    if (tabId === 'tab-chapters' && typeof switchChapterSettingsInnerTab === 'function') {
        switchChapterSettingsInnerTab('chapter-settings-inner-structure');
    }
}

function switchChapterSettingsInnerTab(tabId) {
    document.querySelectorAll('.chapter-settings-inner-tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    document.querySelectorAll('.chapter-settings-inner-tab-btn').forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });

    const target = document.getElementById(tabId);
    if (target) {
        target.classList.remove('hidden');
        target.classList.add('block');
    }

    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
    }
}

window.setFootnoteAlignment = function(alignment) {
    const allowed = ['left', 'center', 'right', 'justify'];
    const value = allowed.includes(String(alignment).toLowerCase()) ? String(alignment).toLowerCase() : 'left';
    const input = document.getElementById('setting-footnote-align');
    if (input) input.value = value;

    document.querySelectorAll('[data-footnote-align]').forEach((button) => {
        const active = button.dataset.footnoteAlign === value;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('bg-black', active);
        button.classList.toggle('text-white', active);
        button.classList.toggle('hover:bg-black', active);
        button.classList.toggle('hover:text-white', active);
    });
};

window.toggleChapterFootnotePageBreakSetting = function() {
    const mode = document.getElementById('setting-footnote-mode');
    const wrapper = document.getElementById('setting-footnote-chapter-new-page-wrap');
    if (!wrapper) return;

    const visible = mode?.value === 'chapter';
    wrapper.classList.toggle('hidden', !visible);
    wrapper.classList.toggle('flex', visible);
};

function normalizeFootnoteLineHeight(value, fontSize, fallback = 11.5) {
    const parsed = parseFloat(value);
    if (!Number.isFinite(parsed)) {
        return fallback;
    }
    return Math.max(0.1, Math.min(40, parsed));
}

function normalizeOpeningPageAlignmentValue(rawValue) {
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
}

function deriveOpeningPageAlignment(settings) {
    const combined = normalizeOpeningPageAlignmentValue(settings.chapter_page_one_align);
    if (combined) {
        const [horizontal, vertical] = combined.split('-');
        return { horizontal, vertical, combined };
    }

    const legacyHorizontal = ['left', 'center', 'right'].includes(String(settings.chapter_page_one_align || '').toLowerCase())
        ? String(settings.chapter_page_one_align).toLowerCase()
        : (['left', 'center', 'right'].includes(String(settings.chapter_title_align || '').toLowerCase())
            ? String(settings.chapter_title_align).toLowerCase()
            : 'center');
    const legacyVertical = ['top', 'center', 'bottom'].includes(String(settings.chapter_page_one_vertical || '').toLowerCase())
        ? String(settings.chapter_page_one_vertical).toLowerCase()
        : (String(settings.chapter_page_one_vertical || '').toLowerCase() === 'half' ? 'center' : 'top');

    return {
        horizontal: legacyHorizontal,
        vertical: legacyVertical,
        combined: `${legacyHorizontal}-${legacyVertical}`,
    };
}

window.switchEbookSettingTab = function(tabId) {
    // Ocultar todos los contenidos de pestaña de ebook
    document.querySelectorAll('.ebook-setting-tab-content').forEach(el => {
        el.classList.add('hidden');
    });
    // Mostrar pestaña seleccionada
    const target = document.getElementById(tabId);
    if (target) target.classList.remove('hidden');

    // Actualizar estilos de los botones
    document.querySelectorAll('.ebook-setting-tab-btn').forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });
    
    // Resaltar botón activo
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
    }
}

// Mostrar u ocultar campos de texto personalizados para cabeceras
window.populateSettingsForm = function() {
    const settings = bookState.settings;
    if (!settings) return;

    // Pestaña Página
    if (document.getElementById('setting-unit')) document.getElementById('setting-unit').value = settings.unit;
    if (document.getElementById('setting-page-size')) document.getElementById('setting-page-size').value = settings.page_size;
    if (document.getElementById('setting-page-width')) document.getElementById('setting-page-width').value = settings.page_width || 14.8;
    if (document.getElementById('setting-page-height')) document.getElementById('setting-page-height').value = settings.page_height || 21;
    if (document.getElementById('setting-margin-top')) document.getElementById('setting-margin-top').value = settings.margin_top ?? 2.5;
    if (document.getElementById('setting-margin-bottom')) document.getElementById('setting-margin-bottom').value = settings.margin_bottom ?? 2.5;
    if (document.getElementById('setting-margin-left-odd')) document.getElementById('setting-margin-left-odd').value = settings.margin_left_odd ?? settings.margin_left ?? 2.0;
    if (document.getElementById('setting-margin-right-odd')) document.getElementById('setting-margin-right-odd').value = settings.margin_right_odd ?? settings.margin_right ?? 2.0;
    if (document.getElementById('setting-margin-left-even')) document.getElementById('setting-margin-left-even').value = settings.margin_left_even ?? settings.margin_left ?? 2.0;
    if (document.getElementById('setting-margin-right-even')) document.getElementById('setting-margin-right-even').value = settings.margin_right_even ?? settings.margin_right ?? 2.0;
    if (document.getElementById('setting-padding-top')) document.getElementById('setting-padding-top').value = settings.padding_top ?? 0;
    if (document.getElementById('setting-padding-bottom')) document.getElementById('setting-padding-bottom').value = settings.padding_bottom ?? 0;
    if (document.getElementById('setting-padding-left')) document.getElementById('setting-padding-left').value = settings.padding_left ?? 0;
    if (document.getElementById('setting-padding-right')) document.getElementById('setting-padding-right').value = settings.padding_right ?? 0;
    if (document.getElementById('setting-bleeding')) document.getElementById('setting-bleeding').value = settings.bleeding !== undefined ? settings.bleeding : 0.5;

    // Grayscale Setting
    if (document.getElementById('setting-export-grayscale')) document.getElementById('setting-export-grayscale').checked = settings.export_grayscale == 1;
    if (document.getElementById('setting-page-columns-enabled')) document.getElementById('setting-page-columns-enabled').checked = settings.page_columns_enabled == 1;
    if (document.getElementById('setting-page-columns-count')) document.getElementById('setting-page-columns-count').value = settings.page_columns_count ?? 2;
    if (document.getElementById('setting-page-columns-gap')) document.getElementById('setting-page-columns-gap').value = settings.page_columns_gap ?? 0.8;
    if (typeof togglePageColumnsSettings === 'function') togglePageColumnsSettings();

    if (document.getElementById('setting-ebook-bg-type')) document.getElementById('setting-ebook-bg-type').value = settings.ebook_bg_type || 'color';
    if (document.getElementById('setting-ebook-bg-color')) {
        const rawColor = settings.ebook_bg_color || '#ffffff';
        // Color inputs require lowercase 6-digit hex
        if (rawColor.startsWith('#') && rawColor.length === 7) {
            document.getElementById('setting-ebook-bg-color').value = rawColor.toLowerCase();
        }
    }
    if (document.getElementById('setting-ebook-bg-color-text')) document.getElementById('setting-ebook-bg-color-text').value = (settings.ebook_bg_color || '#ffffff').toUpperCase();
    if (document.getElementById('setting-ebook-bg-image')) document.getElementById('setting-ebook-bg-image').value = settings.ebook_bg_image || '';
    if (document.getElementById('setting-ebook-cover-panel-bg-type')) document.getElementById('setting-ebook-cover-panel-bg-type').value = settings.ebook_cover_panel_bg_type || 'image';
    
    if (document.getElementById('setting-ebook-cover-panel-bg-color')) {
        const rawCovColor = settings.ebook_cover_panel_bg_color || 'transparent';
        if (rawCovColor !== 'transparent' && rawCovColor.startsWith('#') && rawCovColor.length === 7) {
            document.getElementById('setting-ebook-cover-panel-bg-color').value = rawCovColor.toLowerCase();
        }
    }
    if (document.getElementById('setting-ebook-cover-panel-bg-color-text')) document.getElementById('setting-ebook-cover-panel-bg-color-text').value = (settings.ebook_cover_panel_bg_color || 'transparent').toUpperCase();
    if (document.getElementById('setting-ebook-cover-panel-bg-image')) document.getElementById('setting-ebook-cover-panel-bg-image').value = settings.ebook_cover_panel_bg_image || '';

    // Opacity
    if (document.getElementById('setting-ebook-bg-opacity')) {
        const opacity = settings.ebook_bg_opacity !== undefined ? settings.ebook_bg_opacity : 1.0;
        document.getElementById('setting-ebook-bg-opacity').value = opacity;
        const opVal = document.getElementById('ebook-bg-opacity-val');
        if (opVal) opVal.innerText = Math.round(opacity * 100) + '%';
    }
    if (document.getElementById('setting-ebook-cover-panel-bg-opacity')) {
        const opacity = settings.ebook_cover_panel_bg_opacity !== undefined ? settings.ebook_cover_panel_bg_opacity : 1.0;
        document.getElementById('setting-ebook-cover-panel-bg-opacity').value = opacity;
        const opVal = document.getElementById('ebook-cover-panel-bg-opacity-val');
        if (opVal) opVal.innerText = Math.round(opacity * 100) + '%';
    }

    // Ebook Fonts
    if (document.getElementById('setting-ebook-font-family-content')) document.getElementById('setting-ebook-font-family-content').value = settings.ebook_font_family_content || 'Merriweather';
    if (document.getElementById('setting-ebook-font-size-content')) document.getElementById('setting-ebook-font-size-content').value = settings.ebook_font_size_content || 18.0;
    if (document.getElementById('setting-ebook-font-weight-content')) document.getElementById('setting-ebook-font-weight-content').value = settings.ebook_font_weight_content || 'normal';
    if (document.getElementById('setting-ebook-line-height-content')) document.getElementById('setting-ebook-line-height-content').value = settings.ebook_line_height_content || 1.8;
    if (document.getElementById('setting-ebook-chapter-title-font-family')) document.getElementById('setting-ebook-chapter-title-font-family').value = settings.ebook_font_family_headings || 'Playfair Display';
    if (document.getElementById('setting-ebook-chapter-title-font-size')) document.getElementById('setting-ebook-chapter-title-font-size').value = settings.ebook_font_size_headings || 32.0;
    if (document.getElementById('setting-ebook-chapter-title-font-weight')) document.getElementById('setting-ebook-chapter-title-font-weight').value = settings.ebook_font_weight_headings || 'bold';
    if (document.getElementById('setting-ebook-chapter-title-line-height')) document.getElementById('setting-ebook-chapter-title-line-height').value = settings.ebook_line_height_headings || 1.3;
    
    if (document.getElementById('setting-ebook-chapter-title-align')) document.getElementById('setting-ebook-chapter-title-align').value = settings.ebook_chapter_title_align || 'center';
    if (document.getElementById('setting-ebook-chapter-title-text-transform')) document.getElementById('setting-ebook-chapter-title-text-transform').value = settings.ebook_chapter_title_text_transform || 'none';
    if (document.getElementById('setting-ebook-chapter-title-padding-top')) document.getElementById('setting-ebook-chapter-title-padding-top').value = settings.ebook_chapter_title_padding_top ?? 2;
    if (document.getElementById('setting-ebook-chapter-title-padding-bottom')) document.getElementById('setting-ebook-chapter-title-padding-bottom').value = settings.ebook_chapter_title_padding_bottom ?? 2;
    if (document.getElementById('setting-ebook-chapter-title-padding-left')) document.getElementById('setting-ebook-chapter-title-padding-left').value = settings.ebook_chapter_title_padding_left ?? 0;
    if (document.getElementById('setting-ebook-chapter-title-padding-right')) document.getElementById('setting-ebook-chapter-title-padding-right').value = settings.ebook_chapter_title_padding_right ?? 0;
    if (document.getElementById('setting-ebook-chapter-title-hyphenate')) document.getElementById('setting-ebook-chapter-title-hyphenate').checked = settings.ebook_chapter_title_hyphenate == 1;

    if (document.getElementById('setting-ebook-chapter-subtitle-show')) document.getElementById('setting-ebook-chapter-subtitle-show').checked = settings.ebook_subtitle_show == 1 || settings.ebook_subtitle_show === undefined;
    if (document.getElementById('setting-ebook-chapter-subtitle-font-family')) document.getElementById('setting-ebook-chapter-subtitle-font-family').value = settings.ebook_subtitle_font_family || '';
    if (document.getElementById('setting-ebook-chapter-subtitle-font-size')) document.getElementById('setting-ebook-chapter-subtitle-font-size').value = settings.ebook_subtitle_font_size ?? 18;
    if (document.getElementById('setting-ebook-chapter-subtitle-align')) document.getElementById('setting-ebook-chapter-subtitle-align').value = settings.ebook_subtitle_align || 'center';
    if (document.getElementById('setting-ebook-chapter-subtitle-font-style')) document.getElementById('setting-ebook-chapter-subtitle-font-style').value = settings.ebook_subtitle_font_style || 'normal';
    if (document.getElementById('setting-ebook-chapter-subtitle-text-transform')) document.getElementById('setting-ebook-chapter-subtitle-text-transform').value = settings.ebook_subtitle_text_transform || 'none';
    if (document.getElementById('setting-ebook-chapter-subtitle-font-weight')) document.getElementById('setting-ebook-chapter-subtitle-font-weight').value = settings.ebook_subtitle_font_weight || 'normal';
    if (document.getElementById('setting-ebook-chapter-subtitle-padding-top')) document.getElementById('setting-ebook-chapter-subtitle-padding-top').value = settings.ebook_subtitle_padding_top ?? 0.5;
    if (document.getElementById('setting-ebook-chapter-subtitle-padding-bottom')) document.getElementById('setting-ebook-chapter-subtitle-padding-bottom').value = settings.ebook_subtitle_padding_bottom ?? 0.5;
    if (document.getElementById('setting-ebook-chapter-subtitle-letter-spacing')) document.getElementById('setting-ebook-chapter-subtitle-letter-spacing').value = settings.ebook_subtitle_letter_spacing ?? 0;

    if (document.getElementById('setting-ebook-chapter-prefix-show')) document.getElementById('setting-ebook-chapter-prefix-show').checked = settings.ebook_chapter_prefix_show == 1;
    if (document.getElementById('setting-ebook-chapter-prefix-template')) document.getElementById('setting-ebook-chapter-prefix-template').value = settings.ebook_chapter_prefix_template || 'Capítulo {N}';
    if (document.getElementById('setting-ebook-chapter-prefix-position')) document.getElementById('setting-ebook-chapter-prefix-position').value = settings.ebook_chapter_prefix_position || 'above';
    if (document.getElementById('setting-ebook-chapter-prefix-align')) document.getElementById('setting-ebook-chapter-prefix-align').value = settings.ebook_chapter_prefix_align || 'center';
    if (document.getElementById('setting-ebook-chapter-prefix-font-family')) document.getElementById('setting-ebook-chapter-prefix-font-family').value = settings.ebook_chapter_prefix_font_family || 'Playfair Display';
    if (document.getElementById('setting-ebook-chapter-prefix-font-size')) document.getElementById('setting-ebook-chapter-prefix-font-size').value = settings.ebook_chapter_prefix_font_size || 16;
    if (document.getElementById('setting-ebook-chapter-prefix-font-weight')) document.getElementById('setting-ebook-chapter-prefix-font-weight').value = settings.ebook_chapter_prefix_font_weight || 'normal';
    if (document.getElementById('setting-ebook-chapter-prefix-font-style')) document.getElementById('setting-ebook-chapter-prefix-font-style').value = settings.ebook_chapter_prefix_font_style || 'normal';
    if (document.getElementById('setting-ebook-chapter-prefix-letter-spacing')) document.getElementById('setting-ebook-chapter-prefix-letter-spacing').value = settings.ebook_chapter_prefix_letter_spacing ?? 0;
    if (document.getElementById('setting-ebook-chapter-prefix-ornament')) document.getElementById('setting-ebook-chapter-prefix-ornament').value = settings.ebook_chapter_prefix_ornament || 'none';

    if (document.getElementById('setting-ebook-text-align-justify')) document.getElementById('setting-ebook-text-align-justify').checked = settings.ebook_text_align_justify == 1;
    if (document.getElementById('setting-ebook-hyphenation')) document.getElementById('setting-ebook-hyphenation').checked = settings.ebook_hyphenation == 1;

    switchEbookSettingTab('tab-ebook-theme');

    // Pestaña Tipografía
    if (document.getElementById('setting-font-family-content')) document.getElementById('setting-font-family-content').value = settings.font_family_content || 'Merriweather';
    if (document.getElementById('setting-font-size-content')) document.getElementById('setting-font-size-content').value = settings.font_size_content || 11.5;
    if (document.getElementById('setting-font-weight-content')) document.getElementById('setting-font-weight-content').value = settings.font_weight_content || 'normal';
    if (document.getElementById('setting-line-height-content')) document.getElementById('setting-line-height-content').value = settings.line_height_content || 1.65;
    if (document.getElementById('setting-content-text-align')) document.getElementById('setting-content-text-align').value = settings.content_text_align || 'justify';
    if (document.getElementById('setting-content-text-align-last')) document.getElementById('setting-content-text-align-last').value = settings.content_text_align_last || 'left';
    const bookLanguage = settings.book_language || settings.content_language || 'es';
    if (document.getElementById('setting-book-language')) document.getElementById('setting-book-language').value = bookLanguage;
    if (document.getElementById('setting-content-hyphenation')) document.getElementById('setting-content-hyphenation').value = settings.content_hyphenation !== undefined ? settings.content_hyphenation : 1;
    if (document.getElementById('setting-content-language')) document.getElementById('setting-content-language').value = bookLanguage;
    if (document.getElementById('setting-content-hyphenation-exceptions')) document.getElementById('setting-content-hyphenation-exceptions').value = settings.content_hyphenation_exceptions || '';
    if (document.getElementById('setting-content-paragraph-indent')) document.getElementById('setting-content-paragraph-indent').value = settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0;
    if (document.getElementById('setting-content-paragraph-spacing')) document.getElementById('setting-content-paragraph-spacing').value = settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0;
    if (document.getElementById('setting-font-family-h1')) document.getElementById('setting-font-family-h1').value = settings.font_family_h1 || 'Playfair Display';
    if (document.getElementById('setting-font-family-h2')) document.getElementById('setting-font-family-h2').value = settings.font_family_h2 || 'Playfair Display';
    if (document.getElementById('setting-font-family-h3')) document.getElementById('setting-font-family-h3').value = settings.font_family_h3 || 'Playfair Display';
    if (document.getElementById('setting-font-style-h1')) document.getElementById('setting-font-style-h1').value = settings.font_style_h1 || 'normal';
    if (document.getElementById('setting-font-style-h2')) document.getElementById('setting-font-style-h2').value = settings.font_style_h2 || 'italic';
    if (document.getElementById('setting-font-style-h3')) document.getElementById('setting-font-style-h3').value = settings.font_style_h3 || 'normal';
    if (document.getElementById('setting-font-weight-h1')) document.getElementById('setting-font-weight-h1').value = settings.font_weight_h1 || 'bold';
    if (document.getElementById('setting-font-weight-h2')) document.getElementById('setting-font-weight-h2').value = settings.font_weight_h2 || 'bold';
    if (document.getElementById('setting-font-weight-h3')) document.getElementById('setting-font-weight-h3').value = settings.font_weight_h3 || 'bold';
    if (document.getElementById('setting-font-size-h1')) document.getElementById('setting-font-size-h1').value = settings.font_size_h1 || 24;
    if (document.getElementById('setting-font-size-h2')) document.getElementById('setting-font-size-h2').value = settings.font_size_h2 || 16;
    if (document.getElementById('setting-font-size-h3')) document.getElementById('setting-font-size-h3').value = settings.font_size_h3 || 13;

    // Pestaña Cabecera y Pie
    if (document.getElementById('setting-header-font-family')) document.getElementById('setting-header-font-family').value = settings.header_font_family || 'Merriweather';
    if (document.getElementById('setting-header-font-size')) document.getElementById('setting-header-font-size').value = settings.header_font_size || 8.5;
    if (document.getElementById('setting-header-font-weight')) document.getElementById('setting-header-font-weight').value = settings.header_font_weight || 'normal';
    if (document.getElementById('setting-header-font-style')) document.getElementById('setting-header-font-style').value = settings.header_font_style || 'normal';
    if (document.getElementById('setting-header-text-transform')) document.getElementById('setting-header-text-transform').value = settings.header_text_transform || 'none';
    if (document.getElementById('setting-header-hyphenate')) document.getElementById('setting-header-hyphenate').checked = settings.header_hyphenate == 1;
    if (document.getElementById('setting-header-letter-spacing')) document.getElementById('setting-header-letter-spacing').value = settings.header_letter_spacing || 0.1;
    if (document.getElementById('setting-header-even-type')) document.getElementById('setting-header-even-type').value = settings.header_even_type || 'book_title';
    if (document.getElementById('setting-header-even-custom')) document.getElementById('setting-header-even-custom').value = settings.header_even_custom || '';
    if (document.getElementById('setting-header-odd-type')) document.getElementById('setting-header-odd-type').value = settings.header_odd_type || 'chapter_title';
    if (document.getElementById('setting-header-odd-custom')) document.getElementById('setting-header-odd-custom').value = settings.header_odd_custom || '';
    if (document.getElementById('setting-footer-font-family')) document.getElementById('setting-footer-font-family').value = settings.footer_font_family || 'Merriweather';
    if (document.getElementById('setting-footer-font-size')) document.getElementById('setting-footer-font-size').value = settings.footer_font_size || 9;
    if (document.getElementById('setting-footer-font-weight')) document.getElementById('setting-footer-font-weight').value = settings.footer_font_weight || 'normal';
    if (document.getElementById('setting-footer-font-style')) document.getElementById('setting-footer-font-style').value = settings.footer_font_style || 'normal';
    if (document.getElementById('setting-footer-text-transform')) document.getElementById('setting-footer-text-transform').value = settings.footer_text_transform || 'none';
    if (document.getElementById('setting-footer-letter-spacing')) document.getElementById('setting-footer-letter-spacing').value = settings.footer_letter_spacing || 0;
    if (document.getElementById('setting-footer-even-type')) document.getElementById('setting-footer-even-type').value = settings.footer_even_type || 'page_number';
    if (document.getElementById('setting-footer-odd-type')) document.getElementById('setting-footer-odd-type').value = settings.footer_odd_type || 'page_number';
    if (document.getElementById('setting-first-page-header-show')) document.getElementById('setting-first-page-header-show').checked = settings.first_page_header_show == 1 || settings.first_page_header_show === undefined;
    if (document.getElementById('setting-first-page-footer-show')) document.getElementById('setting-first-page-footer-show').checked = settings.first_page_footer_show == 1 || settings.first_page_footer_show === undefined;
    if (document.getElementById('setting-first-page-header-type')) document.getElementById('setting-first-page-header-type').value = settings.first_page_header_type || 'blank';
    if (document.getElementById('setting-first-page-header-custom')) document.getElementById('setting-first-page-header-custom').value = settings.first_page_header_custom || '';
    if (document.getElementById('setting-first-page-footer-type')) document.getElementById('setting-first-page-footer-type').value = settings.first_page_footer_type || 'page_number';
    if (document.getElementById('setting-first-page-footer-custom')) document.getElementById('setting-first-page-footer-custom').value = settings.first_page_footer_custom || '';
    if (document.getElementById('setting-chapter-transition-blank-mode')) document.getElementById('setting-chapter-transition-blank-mode').value = settings.chapter_transition_blank_mode || 'full_blank';
    if (document.getElementById('setting-chapter-transition-blank-text')) document.getElementById('setting-chapter-transition-blank-text').value = settings.chapter_transition_blank_text || '...';

    // Pestaña Footnotes
    if (document.getElementById('setting-footnote-mode')) document.getElementById('setting-footnote-mode').value = ['page', 'chapter', 'book'].includes(String(settings.footnote_mode || '').toLowerCase()) ? settings.footnote_mode : 'page';
    if (document.getElementById('setting-footnote-chapter-new-page')) document.getElementById('setting-footnote-chapter-new-page').checked = settings.footnote_chapter_new_page == 1;
    toggleChapterFootnotePageBreakSetting();
    if (document.getElementById('setting-footnote-chapter-title')) document.getElementById('setting-footnote-chapter-title').value = settings.footnote_chapter_title || 'Referencia';
    if (document.getElementById('setting-footnote-book-title')) document.getElementById('setting-footnote-book-title').value = settings.footnote_book_title || 'Referencias';
    if (document.getElementById('setting-footnote-font-family')) document.getElementById('setting-footnote-font-family').value = settings.footnote_font_family || 'Merriweather';
    if (document.getElementById('setting-footnote-font-size')) document.getElementById('setting-footnote-font-size').value = settings.footnote_font_size ?? 8.5;
    if (document.getElementById('setting-footnote-font-weight')) document.getElementById('setting-footnote-font-weight').value = settings.footnote_font_weight || 'normal';
    window.setFootnoteAlignment(['left', 'center', 'right', 'justify'].includes(String(settings.footnote_align || '').toLowerCase()) ? settings.footnote_align : 'left');
    if (document.getElementById('setting-footnote-line-height')) document.getElementById('setting-footnote-line-height').value = normalizeFootnoteLineHeight(settings.footnote_line_height, settings.footnote_font_size ?? 8.5);
    if (document.getElementById('setting-footnote-letter-spacing')) document.getElementById('setting-footnote-letter-spacing').value = settings.footnote_letter_spacing ?? 0;
    if (document.getElementById('setting-footnote-entry-spacing')) document.getElementById('setting-footnote-entry-spacing').value = settings.footnote_entry_spacing ?? 6;
    if (document.getElementById('setting-footnote-hyphenate')) document.getElementById('setting-footnote-hyphenate').checked = settings.footnote_hyphenate == 1;
    if (document.getElementById('setting-footnote-call-scale')) document.getElementById('setting-footnote-call-scale').value = settings.footnote_call_scale ?? 0.65;
    if (document.getElementById('setting-footnote-call-raise')) document.getElementById('setting-footnote-call-raise').value = settings.footnote_call_raise ?? 0.18;
    if (document.getElementById('setting-footnote-padding-top')) document.getElementById('setting-footnote-padding-top').value = settings.footnote_padding_top ?? 0.15;
    if (document.getElementById('setting-footnote-padding-bottom')) document.getElementById('setting-footnote-padding-bottom').value = settings.footnote_padding_bottom ?? 0.15;
    if (document.getElementById('setting-footnote-padding-left')) document.getElementById('setting-footnote-padding-left').value = settings.footnote_padding_left ?? 0;
    if (document.getElementById('setting-footnote-padding-right')) document.getElementById('setting-footnote-padding-right').value = settings.footnote_padding_right ?? 0;
    if (document.getElementById('setting-footnote-separator-show')) document.getElementById('setting-footnote-separator-show').checked = settings.footnote_separator_show == 1;
    if (document.getElementById('setting-footnote-separator-align')) document.getElementById('setting-footnote-separator-align').value = settings.footnote_separator_align || 'left';
    if (document.getElementById('setting-footnote-separator-width')) document.getElementById('setting-footnote-separator-width').value = settings.footnote_separator_width || '100';
    if (document.getElementById('setting-footnote-separator-thickness')) document.getElementById('setting-footnote-separator-thickness').value = settings.footnote_separator_thickness ?? 0.25;
    if (document.getElementById('setting-footnote-separator-margin-bottom')) document.getElementById('setting-footnote-separator-margin-bottom').value = settings.footnote_separator_margin_bottom ?? 0.15;

    if (document.getElementById('setting-header-margin-top')) document.getElementById('setting-header-margin-top').value = settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0;
    if (document.getElementById('setting-header-margin-bottom')) document.getElementById('setting-header-margin-bottom').value = settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5;
    if (document.getElementById('setting-header-align')) document.getElementById('setting-header-align').value = settings.header_align || 'center';
    if (document.getElementById('setting-footer-margin-top')) document.getElementById('setting-footer-margin-top').value = settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5;
    if (document.getElementById('setting-footer-margin-bottom')) document.getElementById('setting-footer-margin-bottom').value = settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0;
    if (document.getElementById('setting-footer-align')) document.getElementById('setting-footer-align').value = settings.footer_align || 'center';

    // Pestaña Capítulos / Libro
    const derivedBookFlowMode = (settings.book_chapter_flow_mode === 'left' || settings.chapter_start_parity === 'even')
        ? 'left'
        : 'continuous';
    const derivedLegacyParity = derivedBookFlowMode === 'left' ? 'even' : 'any';
    const bookAuthorsValue = String(
        (typeof bookState !== 'undefined' && bookState && bookState.bookAuthorsInputValue)
            || settings.book_authors_input_value
            || settings.book_authors
            || ''
    );
    if (document.getElementById('setting-book-separate-opening-content')) {
        document.getElementById('setting-book-separate-opening-content').checked = settings.book_separate_opening_content !== 0 && settings.book_separate_opening_content !== '0';
    }
    if (document.getElementById('setting-book-chapter-flow-mode')) {
        document.getElementById('setting-book-chapter-flow-mode').value = derivedBookFlowMode;
    }
    if (document.getElementById('setting-chapter-start-parity')) {
        document.getElementById('setting-chapter-start-parity').value = derivedLegacyParity;
    }

    if (document.getElementById('setting-chapter-page-one-align')) {
        const openingPageAlignment = deriveOpeningPageAlignment(settings);
        document.getElementById('setting-chapter-page-one-align').value = openingPageAlignment.combined;
    }
    if (document.getElementById('setting-chapter-title-font-family')) document.getElementById('setting-chapter-title-font-family').value = settings.chapter_title_font_family || 'Playfair Display';
    if (document.getElementById('setting-chapter-title-font-size')) document.getElementById('setting-chapter-title-font-size').value = settings.chapter_title_font_size || 24;
    if (document.getElementById('setting-chapter-title-font-weight')) document.getElementById('setting-chapter-title-font-weight').value = settings.chapter_title_font_weight || 'bold';
    if (document.getElementById('setting-chapter-title-font-style')) document.getElementById('setting-chapter-title-font-style').value = settings.chapter_title_font_style || 'normal';
    if (document.getElementById('setting-chapter-title-align')) {
        const chapterTitleAlign = ['left', 'center', 'right'].includes(String(settings.chapter_title_align || '').toLowerCase())
            ? String(settings.chapter_title_align).toLowerCase()
            : 'center';
        document.getElementById('setting-chapter-title-align').value = chapterTitleAlign;
    }
    if (document.getElementById('setting-chapter-title-text-transform')) document.getElementById('setting-chapter-title-text-transform').value = settings.chapter_title_text_transform || 'none';
    if (document.getElementById('setting-chapter-title-letter-spacing')) document.getElementById('setting-chapter-title-letter-spacing').value = settings.chapter_title_letter_spacing ?? 0;
    if (document.getElementById('setting-chapter-title-padding-top')) document.getElementById('setting-chapter-title-padding-top').value = settings.chapter_title_padding_top ?? 0;
    if (document.getElementById('setting-chapter-title-padding-bottom')) document.getElementById('setting-chapter-title-padding-bottom').value = settings.chapter_title_padding_bottom ?? 1.5;
    if (document.getElementById('setting-chapter-title-padding-left')) document.getElementById('setting-chapter-title-padding-left').value = settings.chapter_title_padding_left ?? 0;
    if (document.getElementById('setting-chapter-title-padding-right')) document.getElementById('setting-chapter-title-padding-right').value = settings.chapter_title_padding_right ?? 0;
    if (document.getElementById('setting-chapter-title-line-height')) document.getElementById('setting-chapter-title-line-height').value = settings.chapter_title_line_height ?? 1.3;
    if (document.getElementById('setting-chapter-title-hyphenate')) document.getElementById('setting-chapter-title-hyphenate').checked = settings.chapter_title_hyphenate == 1;

    if (document.getElementById('setting-chapter-subtitle-show')) document.getElementById('setting-chapter-subtitle-show').checked = settings.chapter_subtitle_show == 1 || settings.chapter_subtitle_show === undefined;
    if (document.getElementById('setting-chapter-subtitle-font-family')) document.getElementById('setting-chapter-subtitle-font-family').value = settings.chapter_subtitle_font_family || '';
    if (document.getElementById('setting-chapter-subtitle-font-size')) document.getElementById('setting-chapter-subtitle-font-size').value = settings.chapter_subtitle_font_size ?? 16;
    if (document.getElementById('setting-chapter-subtitle-align')) {
        const chapterSubtitleAlign = ['left', 'center', 'right'].includes(String(settings.chapter_subtitle_align || '').toLowerCase())
            ? String(settings.chapter_subtitle_align).toLowerCase()
            : 'center';
        document.getElementById('setting-chapter-subtitle-align').value = chapterSubtitleAlign;
    }
    if (document.getElementById('setting-chapter-subtitle-font-style')) document.getElementById('setting-chapter-subtitle-font-style').value = settings.chapter_subtitle_font_style || 'normal';
    if (document.getElementById('setting-chapter-subtitle-text-transform')) document.getElementById('setting-chapter-subtitle-text-transform').value = settings.chapter_subtitle_text_transform || 'none';
    if (document.getElementById('setting-chapter-subtitle-font-weight')) document.getElementById('setting-chapter-subtitle-font-weight').value = settings.chapter_subtitle_font_weight || 'normal';
    if (document.getElementById('setting-chapter-subtitle-margin-top')) document.getElementById('setting-chapter-subtitle-margin-top').value = settings.chapter_subtitle_margin_top ?? 0.5;
    if (document.getElementById('setting-chapter-subtitle-margin-bottom')) document.getElementById('setting-chapter-subtitle-margin-bottom').value = settings.chapter_subtitle_margin_bottom ?? 0.5;
    if (document.getElementById('setting-chapter-subtitle-letter-spacing')) document.getElementById('setting-chapter-subtitle-letter-spacing').value = settings.chapter_subtitle_letter_spacing ?? 0;

    if (document.getElementById('setting-chapter-prefix-show')) document.getElementById('setting-chapter-prefix-show').checked = settings.chapter_prefix_show == 1;
    if (document.getElementById('setting-chapter-prefix-template')) document.getElementById('setting-chapter-prefix-template').value = settings.chapter_prefix_template || 'Capítulo {N}';
    if (document.getElementById('setting-chapter-prefix-position')) document.getElementById('setting-chapter-prefix-position').value = settings.chapter_prefix_position || 'above';
    if (document.getElementById('setting-chapter-prefix-align')) document.getElementById('setting-chapter-prefix-align').value = settings.chapter_prefix_align || 'center';
    if (document.getElementById('setting-chapter-prefix-font-family')) document.getElementById('setting-chapter-prefix-font-family').value = settings.chapter_prefix_font_family || 'Playfair Display';
    if (document.getElementById('setting-chapter-prefix-font-size')) document.getElementById('setting-chapter-prefix-font-size').value = settings.chapter_prefix_font_size || 16;
    if (document.getElementById('setting-chapter-prefix-font-weight')) document.getElementById('setting-chapter-prefix-font-weight').value = settings.chapter_prefix_font_weight || 'normal';
    if (document.getElementById('setting-chapter-prefix-font-style')) document.getElementById('setting-chapter-prefix-font-style').value = settings.chapter_prefix_font_style || 'normal';
    if (document.getElementById('setting-chapter-prefix-letter-spacing')) document.getElementById('setting-chapter-prefix-letter-spacing').value = settings.chapter_prefix_letter_spacing ?? 0;
    if (document.getElementById('setting-chapter-prefix-ornament')) document.getElementById('setting-chapter-prefix-ornament').value = settings.chapter_prefix_ornament || 'none';
    if (document.getElementById('setting-book-authors')) document.getElementById('setting-book-authors').value = bookAuthorsValue;

    // Pestaña Créditos
    if (document.getElementById('setting-credits-edition')) document.getElementById('setting-credits-edition').value = settings.credits_edition || '';
    if (document.getElementById('setting-credits-date')) document.getElementById('setting-credits-date').value = settings.credits_date || '';
    if (document.getElementById('setting-credits-copyright')) document.getElementById('setting-credits-copyright').value = settings.credits_copyright || 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.';
    if (document.getElementById('setting-credits-printer')) document.getElementById('setting-credits-printer').value = settings.credits_printer || '';
    if (document.getElementById('setting-credits-blank-before')) document.getElementById('setting-credits-blank-before').value = settings.credits_blank_before || 0;
    if (document.getElementById('setting-credits-blank-after')) document.getElementById('setting-credits-blank-after').value = settings.credits_blank_after || 0;
    if (typeof renderCustomCredits === 'function') {
        renderCustomCredits(settings.credits_config || settings.credits_custom);
    }

    if (typeof updateUnitFields === 'function') updateUnitFields();
    if (typeof toggleCustomPageFields === 'function') toggleCustomPageFields();
    if (typeof toggleCustomHeaderFields === 'function') toggleCustomHeaderFields();
    if (typeof toggleCustomFirstPageHeader === 'function') toggleCustomFirstPageHeader();
    if (typeof toggleCustomFirstPageFooter === 'function') toggleCustomFirstPageFooter();
    if (typeof togglePageColumnsSettings === 'function') togglePageColumnsSettings();
    if (typeof syncBookFlowParityMode === 'function') syncBookFlowParityMode();
    else if (typeof toggleParityImageMode === 'function') toggleParityImageMode();
    if (typeof toggleChapterTransitionBlankSettings === 'function') toggleChapterTransitionBlankSettings();
    if (typeof toggleChapterTransitionBlankSettings === 'function') toggleChapterTransitionBlankSettings();
    if (typeof toggleChapterImageSettings === 'function') toggleChapterImageSettings();
    if (typeof toggleEbookBgType === 'function') toggleEbookBgType();
    if (typeof toggleCoverPanelBgType === 'function') toggleCoverPanelBgType();
    if (typeof populateCommerceForm === 'function') populateCommerceForm();
};

function toggleSettingsModal(show) {
    const modal = document.getElementById('settings-modal');
    if (!modal) return;
    const modalContent = modal.querySelector('div');
    
    if (show) {
        switchSettingTab('tab-page');
        window.populateSettingsForm();
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            if (modalContent) {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }
        }, 10);
    } else {
        modal.classList.add('opacity-0');
        if (modalContent) {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
        }
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
}
