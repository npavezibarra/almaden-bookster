// Manejo de pestañas del modal de ajustes
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
        btn.classList.remove('border-indigo-500', 'text-indigo-500');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });
    
    // Resaltar botón activo
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-indigo-500', 'text-indigo-500');
    }
}

// Mostrar u ocultar campos de texto personalizados para cabeceras
function toggleCustomHeaderFields() {
    const evenType = document.getElementById('setting-header-even-type').value;
    const oddType = document.getElementById('setting-header-odd-type').value;

    const evenContainer = document.getElementById('custom-header-even-container');
    const oddContainer = document.getElementById('custom-header-odd-container');

    if (evenContainer) {
        if (evenType === 'custom') evenContainer.classList.remove('hidden');
        else evenContainer.classList.add('hidden');
    }
    if (oddContainer) {
        if (oddType === 'custom') oddContainer.classList.remove('hidden');
        else oddContainer.classList.add('hidden');
    }
}

// Actualizar las etiquetas de unidad en el formulario (cm o in)
function updateUnitFields() {
    const unit = document.getElementById('setting-unit').value;
    document.querySelectorAll('.unit-label').forEach(lbl => {
        lbl.textContent = unit;
    });
}

function toggleParityImageMode() {
    const parity = document.getElementById('setting-chapter-start-parity').value;
    const wrapper = document.getElementById('parity-image-mode-wrapper');
    if (wrapper) {
        if (parity === 'odd') {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
        }
    }
}

// Mostrar / Ocultar campos de dimensiones personalizados
function toggleCustomPageFields() {
    const pageSize = document.getElementById('setting-page-size').value;
    const customFields = document.getElementById('custom-page-dimensions');
    if (customFields) {
        if (pageSize === 'Custom') {
            customFields.classList.remove('hidden');
        } else {
            customFields.classList.add('hidden');
        }
    }
}

// Mostrar / Ocultar el modal de configuración
function toggleSettingsModal(show) {
    const modal = document.getElementById('settings-modal');
    if (!modal) return;
    const modalContent = modal.querySelector('div');
    
    if (show) {
        // Por defecto ir a la primera pestaña
        switchSettingTab('tab-page');

        const settings = bookState.settings;
        
        // Pestaña Página
        document.getElementById('setting-unit').value = settings.unit;
        document.getElementById('setting-page-size').value = settings.page_size;
        document.getElementById('setting-page-width').value = settings.page_width;
        document.getElementById('setting-page-height').value = settings.page_height;
        document.getElementById('setting-margin-top').value = settings.margin_top;
        document.getElementById('setting-margin-bottom').value = settings.margin_bottom;
        document.getElementById('setting-margin-left-odd').value = settings.margin_left_odd ?? settings.margin_left ?? 2.0;
        document.getElementById('setting-margin-right-odd').value = settings.margin_right_odd ?? settings.margin_right ?? 2.0;
        document.getElementById('setting-margin-left-even').value = settings.margin_left_even ?? settings.margin_left ?? 2.0;
        document.getElementById('setting-margin-right-even').value = settings.margin_right_even ?? settings.margin_right ?? 2.0;
        document.getElementById('setting-padding-top').value = settings.padding_top;
        document.getElementById('setting-padding-bottom').value = settings.padding_bottom;
        document.getElementById('setting-padding-left').value = settings.padding_left;
        document.getElementById('setting-padding-right').value = settings.padding_right;
        document.getElementById('setting-bleeding').value = settings.bleeding;

        // Pestaña Tipografía
        document.getElementById('setting-font-family-content').value = settings.font_family_content || 'Merriweather';
        document.getElementById('setting-font-size-content').value = settings.font_size_content || 11.5;
        document.getElementById('setting-line-height-content').value = settings.line_height_content || 1.65;
        document.getElementById('setting-content-text-align').value = settings.content_text_align || 'justify';
        document.getElementById('setting-content-hyphenation').value = settings.content_hyphenation !== undefined ? settings.content_hyphenation : 1;
        document.getElementById('setting-content-language').value = settings.content_language || 'es';
        document.getElementById('setting-content-paragraph-indent').value = settings.content_paragraph_indent !== undefined ? settings.content_paragraph_indent : 0.0;
        document.getElementById('setting-content-paragraph-spacing').value = settings.content_paragraph_spacing !== undefined ? settings.content_paragraph_spacing : 14.0;
        document.getElementById('setting-font-family-h1').value = settings.font_family_h1 || 'Playfair Display';
        document.getElementById('setting-font-family-h2').value = settings.font_family_h2 || 'Playfair Display';
        document.getElementById('setting-font-family-h3').value = settings.font_family_h3 || 'Playfair Display';
        document.getElementById('setting-font-weight-h1').value = settings.font_weight_h1 || 'bold';
        document.getElementById('setting-font-weight-h2').value = settings.font_weight_h2 || 'bold';
        document.getElementById('setting-font-weight-h3').value = settings.font_weight_h3 || 'bold';
        document.getElementById('setting-font-size-h1').value = settings.font_size_h1 || 24;
        document.getElementById('setting-font-size-h2').value = settings.font_size_h2 || 16;
        document.getElementById('setting-font-size-h3').value = settings.font_size_h3 || 13;

        // Pestaña Cabecera y Pie
        document.getElementById('setting-header-font-family').value = settings.header_font_family || 'Merriweather';
        document.getElementById('setting-header-font-size').value = settings.header_font_size || 8.5;
        document.getElementById('setting-header-font-weight').value = settings.header_font_weight || 'normal';
        document.getElementById('setting-header-font-style').value = settings.header_font_style || 'normal';
        document.getElementById('setting-header-letter-spacing').value = settings.header_letter_spacing || 0.1;
        document.getElementById('setting-header-even-type').value = settings.header_even_type || 'book_title';
        document.getElementById('setting-header-even-custom').value = settings.header_even_custom || '';
        document.getElementById('setting-header-odd-type').value = settings.header_odd_type || 'chapter_title';
        document.getElementById('setting-header-odd-custom').value = settings.header_odd_custom || '';
        document.getElementById('setting-footer-font-family').value = settings.footer_font_family || 'Merriweather';
        document.getElementById('setting-footer-font-size').value = settings.footer_font_size || 9;
        document.getElementById('setting-footer-font-weight').value = settings.footer_font_weight || 'normal';
        document.getElementById('setting-footer-font-style').value = settings.footer_font_style || 'normal';
        document.getElementById('setting-footer-letter-spacing').value = settings.footer_letter_spacing || 0;
        document.getElementById('setting-footer-even-type').value = settings.footer_even_type || 'page_number';
        document.getElementById('setting-footer-odd-type').value = settings.footer_odd_type || 'page_number';
        document.getElementById('setting-show-header-page-one').checked = (parseInt(settings.show_header_page_one) === 1);

        document.getElementById('setting-header-margin-top').value = settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0;
        document.getElementById('setting-header-margin-bottom').value = settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5;
        document.getElementById('setting-header-align').value = settings.header_align || 'center';
        document.getElementById('setting-footer-margin-top').value = settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5;
        document.getElementById('setting-footer-margin-bottom').value = settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0;
        document.getElementById('setting-footer-align').value = settings.footer_align || 'center';

        // Pestaña Capítulos
        document.getElementById('setting-chapter-start-parity').value = settings.chapter_start_parity || 'any';
        document.getElementById('setting-parity-image-mode').value = settings.parity_image_mode || 'content';
        document.getElementById('setting-chapter-page-one-align').value = settings.chapter_page_one_align || 'center';
        document.getElementById('setting-chapter-page-one-vertical').value = settings.chapter_page_one_vertical || 'top';
        document.getElementById('setting-chapter-title-font-family').value = settings.chapter_title_font_family || 'Playfair Display';
        document.getElementById('setting-chapter-title-font-size').value = settings.chapter_title_font_size || 24;
        document.getElementById('setting-chapter-title-font-weight').value = settings.chapter_title_font_weight || 'bold';
        document.getElementById('setting-chapter-title-font-style').value = settings.chapter_title_font_style || 'normal';
        document.getElementById('setting-chapter-title-align').value = settings.chapter_title_align || 'center';
        document.getElementById('setting-chapter-title-padding-top').value = settings.chapter_title_padding_top ?? 0;
        document.getElementById('setting-chapter-title-padding-bottom').value = settings.chapter_title_padding_bottom ?? 1.5;
        document.getElementById('setting-chapter-title-line-height').value = settings.chapter_title_line_height ?? 1.2;

        updateUnitFields();
        toggleCustomPageFields();
        toggleCustomHeaderFields();
        toggleParityImageMode();

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

// Enviar ajustes vía AJAX a la base de datos de WordPress
function savePDFSettings() {
    const statusIndicator = document.getElementById('save-status');
    if (statusIndicator) {
        statusIndicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Guardando...';
        statusIndicator.className = 'flex items-center gap-1 font-semibold text-amber-500';
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
    data.append('padding_right', getCleanVal('setting-padding-right'));
    data.append('bleeding', getCleanVal('setting-bleeding'));

    // Tipografía
    data.append('font_family_content', document.getElementById('setting-font-family-content').value);
    data.append('font_size_content', getCleanVal('setting-font-size-content'));
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
    data.append('show_header_page_one', document.getElementById('setting-show-header-page-one').checked ? 1 : 0);

    data.append('header_margin_top', getCleanVal('setting-header-margin-top'));
    data.append('header_margin_bottom', getCleanVal('setting-header-margin-bottom'));
    data.append('header_align', document.getElementById('setting-header-align').value);
    data.append('footer_margin_top', getCleanVal('setting-footer-margin-top'));
    data.append('footer_margin_bottom', getCleanVal('setting-footer-margin-bottom'));
    data.append('footer_align', document.getElementById('setting-footer-align').value);

    // Capítulos
    data.append('chapter_start_parity', document.getElementById('setting-chapter-start-parity').value);
    data.append('parity_image_mode', document.getElementById('setting-parity-image-mode').value);
    data.append('chapter_page_one_align', document.getElementById('setting-chapter-page-one-align').value);
    data.append('chapter_page_one_vertical', document.getElementById('setting-chapter-page-one-vertical').value);
    data.append('chapter_title_font_family', document.getElementById('setting-chapter-title-font-family').value);
    data.append('chapter_title_font_size', getCleanVal('setting-chapter-title-font-size'));
    data.append('chapter_title_font_weight', document.getElementById('setting-chapter-title-font-weight').value);
    data.append('chapter_title_font_style', document.getElementById('setting-chapter-title-font-style').value);
    data.append('chapter_title_align', document.getElementById('setting-chapter-title-align').value);
    data.append('chapter_title_padding_top', getCleanVal('setting-chapter-title-padding-top'));
    data.append('chapter_title_padding_bottom', getCleanVal('setting-chapter-title-padding-bottom'));
    data.append('chapter_title_line_height', getCleanVal('setting-chapter-title-line-height'));

    fetch(bookState.ajaxUrl, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            if (statusIndicator) {
                statusIndicator.innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-xs mr-1"></i> Guardado';
                statusIndicator.className = 'flex items-center gap-1 font-semibold text-emerald-600';
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
                padding_left: parseVal('setting-padding-left', 0),
                padding_right: parseVal('setting-padding-right', 0),
                bleeding: parseVal('setting-bleeding', 0),

                font_family_content: document.getElementById('setting-font-family-content').value,
                font_size_content: parseVal('setting-font-size-content', 11.5),
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
                show_header_page_one: document.getElementById('setting-show-header-page-one').checked ? 1 : 0,

                header_margin_top: parseVal('setting-header-margin-top', 1.0),
                header_margin_bottom: parseVal('setting-header-margin-bottom', 0.5),
                header_align: document.getElementById('setting-header-align').value,
                footer_margin_top: parseVal('setting-footer-margin-top', 0.5),
                footer_margin_bottom: parseVal('setting-footer-margin-bottom', 1.0),
                footer_align: document.getElementById('setting-footer-align').value,

                chapter_start_parity: document.getElementById('setting-chapter-start-parity').value,
                parity_image_mode: document.getElementById('setting-parity-image-mode').value,
                chapter_page_one_align: document.getElementById('setting-chapter-page-one-align').value,
                chapter_page_one_vertical: document.getElementById('setting-chapter-page-one-vertical').value,
                chapter_title_font_family: document.getElementById('setting-chapter-title-font-family').value,
                chapter_title_font_size: parseVal('setting-chapter-title-font-size', 24),
                chapter_title_font_weight: document.getElementById('setting-chapter-title-font-weight').value,
                chapter_title_font_style: document.getElementById('setting-chapter-title-font-style').value,
                chapter_title_align: document.getElementById('setting-chapter-title-align').value,
                chapter_title_padding_top: parseVal('setting-chapter-title-padding-top', 0),
                chapter_title_padding_bottom: parseVal('setting-chapter-title-padding-bottom', 1.5)
            };

            applyDynamicPDFStyles();
            compilePDFPreview(); // RECOMPILAR PDF con los nuevos márgenes/anchos
            if (typeof updateParityButtonVisibility === 'function') updateParityButtonVisibility();
            toggleSettingsModal(false);
            showToast("Configuración del PDF guardada", "fa-solid fa-circle-check");
        } else {
            if (statusIndicator) {
                statusIndicator.innerHTML = '<i class="fa-solid fa-circle-exclamation text-xs mr-1"></i> Error';
                statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
            }
            alert("Error al guardar: " + res.data);
        }
    })
    .catch(err => {
        console.error(err);
        if (statusIndicator) {
            statusIndicator.innerHTML = '<i class="fa-solid fa-wifi text-xs mr-1"></i> Error red';
            statusIndicator.className = 'flex items-center gap-1 font-semibold text-rose-600';
        }
    });
}
