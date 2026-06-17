// Manejo de pestañas del modal de ajustes
window.switchFormatTab = function(format) {
    const btnPdf = document.getElementById('btn-format-pdf');
    const btnEbook = document.getElementById('btn-format-ebook');
    const secPdf = document.getElementById('format-pdf-section');
    const secEbook = document.getElementById('format-ebook-section');

    if (format === 'pdf') {
        btnPdf.classList.add('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-indigo-600');
        btnPdf.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        btnEbook.classList.remove('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-indigo-600');
        btnEbook.classList.add('border-transparent', 'text-[var(--text-muted)]');
        
        secPdf.classList.remove('hidden');
        secEbook.classList.add('hidden');
    } else {
        btnEbook.classList.add('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-indigo-600');
        btnEbook.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        btnPdf.classList.remove('bg-[var(--bg-sidebar)]', 'shadow-sm', 'border-[var(--border-color)]', 'text-indigo-600');
        btnPdf.classList.add('border-transparent', 'text-[var(--text-muted)]');
        
        secEbook.classList.remove('hidden');
        secPdf.classList.add('hidden');
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

function toggleCustomFirstPageHeader() {
    const type = document.getElementById('setting-first-page-header-type').value;
    const input = document.getElementById('setting-first-page-header-custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
    }
}

function toggleCustomFirstPageFooter() {
    const type = document.getElementById('setting-first-page-footer-type').value;
    const input = document.getElementById('setting-first-page-footer-custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
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

// ---- Funciones para Créditos Dinámicos ----
window.addCustomCreditRow = function(role = '', name = '') {
    const container = document.getElementById('custom-credits-container');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 mb-2 custom-credit-row';
    row.innerHTML = `
        <input type="text" placeholder="Rol (ej: Traducción)" value="${role}" class="credit-role w-1/3 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="text" placeholder="Nombre (ej: Ana Pérez)" value="${name}" class="credit-name w-1/2 bg-[var(--bg-sidebar)] border border-[var(--border-color)] rounded-lg p-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1">
        <button type="button" onclick="this.parentElement.remove()" class="w-8 h-8 flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar fila">
            <i class="fa-solid fa-trash-can text-xs"></i>
        </button>
    `;
    container.appendChild(row);
};

window.getCustomCreditsJSON = function() {
    const container = document.getElementById('custom-credits-container');
    if (!container) return '[]';
    const rows = container.querySelectorAll('.custom-credit-row');
    const credits = [];
    rows.forEach(row => {
        const role = row.querySelector('.credit-role').value.trim();
        const name = row.querySelector('.credit-name').value.trim();
        if (role || name) {
            credits.push({ role, name });
        }
    });
    return JSON.stringify(credits);
};

window.renderCustomCredits = function(creditsJSON) {
    const container = document.getElementById('custom-credits-container');
    if (!container) return;
    container.innerHTML = '';
    let credits = [];
    try {
        if (creditsJSON) {
            credits = typeof creditsJSON === 'string' ? JSON.parse(creditsJSON) : creditsJSON;
        }
    } catch(e) {
        console.error("Error parsing custom credits JSON", e);
    }
    
    if (Array.isArray(credits) && credits.length > 0) {
        credits.forEach(c => addCustomCreditRow(c.role, c.name));
    } else {
        // Add one empty row by default
        addCustomCreditRow();
    }
};
// ------------------------------------------

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
        document.getElementById('setting-bleeding').value = settings.bleeding !== undefined ? settings.bleeding : 0.5;

        // Grayscale Setting
        document.getElementById('setting-export-grayscale').checked = settings.export_grayscale == 1;

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

        // Ebook Fonts
        if (document.getElementById('setting-ebook-font-family-content')) document.getElementById('setting-ebook-font-family-content').value = settings.ebook_font_family_content || 'Merriweather';
        if (document.getElementById('setting-ebook-font-size-content')) document.getElementById('setting-ebook-font-size-content').value = settings.ebook_font_size_content || 18.0;
        if (document.getElementById('setting-ebook-font-weight-content')) document.getElementById('setting-ebook-font-weight-content').value = settings.ebook_font_weight_content || 'normal';
        if (document.getElementById('setting-ebook-line-height-content')) document.getElementById('setting-ebook-line-height-content').value = settings.ebook_line_height_content || 1.8;
        if (document.getElementById('setting-ebook-font-family-headings')) document.getElementById('setting-ebook-font-family-headings').value = settings.ebook_font_family_headings || 'Playfair Display';
        if (document.getElementById('setting-ebook-font-size-headings')) document.getElementById('setting-ebook-font-size-headings').value = settings.ebook_font_size_headings || 32.0;
        if (document.getElementById('setting-ebook-font-weight-headings')) document.getElementById('setting-ebook-font-weight-headings').value = settings.ebook_font_weight_headings || 'bold';
        if (document.getElementById('setting-ebook-line-height-headings')) document.getElementById('setting-ebook-line-height-headings').value = settings.ebook_line_height_headings || 1.3;
        if (document.getElementById('setting-ebook-text-align-justify')) document.getElementById('setting-ebook-text-align-justify').checked = settings.ebook_text_align_justify == 1;
        if (document.getElementById('setting-ebook-hyphenation')) document.getElementById('setting-ebook-hyphenation').checked = settings.ebook_hyphenation == 1;

        switchEbookSettingTab('tab-ebook-theme');

        // Pestaña Tipografía
        document.getElementById('setting-font-family-content').value = settings.font_family_content || 'Merriweather';
        document.getElementById('setting-font-size-content').value = settings.font_size_content || 11.5;
        document.getElementById('setting-font-weight-content').value = settings.font_weight_content || 'normal';
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
        document.getElementById('setting-first-page-header-type').value = settings.first_page_header_type || 'blank';
        document.getElementById('setting-first-page-header-custom').value = settings.first_page_header_custom || '';
        document.getElementById('setting-first-page-footer-type').value = settings.first_page_footer_type || 'page_number';
        document.getElementById('setting-first-page-footer-custom').value = settings.first_page_footer_custom || '';

        document.getElementById('setting-header-margin-top').value = settings.header_margin_top !== undefined ? settings.header_margin_top : 1.0;
        document.getElementById('setting-header-margin-bottom').value = settings.header_margin_bottom !== undefined ? settings.header_margin_bottom : 0.5;
        document.getElementById('setting-header-align').value = settings.header_align || 'center';
        document.getElementById('setting-footer-margin-top').value = settings.footer_margin_top !== undefined ? settings.footer_margin_top : 0.5;
        document.getElementById('setting-footer-margin-bottom').value = settings.footer_margin_bottom !== undefined ? settings.footer_margin_bottom : 1.0;
        document.getElementById('setting-footer-align').value = settings.footer_align || 'center';

        // Pestaña Capítulos
        document.getElementById('setting-chapter-start-parity').value = settings.chapter_start_parity || 'any';

        document.getElementById('setting-chapter-page-one-align').value = settings.chapter_page_one_align || 'center';
        document.getElementById('setting-chapter-page-one-vertical').value = settings.chapter_chapter_page_one_vertical || 'top';
        document.getElementById('setting-chapter-title-font-family').value = settings.chapter_title_font_family || 'Playfair Display';
        document.getElementById('setting-chapter-title-font-size').value = settings.chapter_title_font_size || 24;
        document.getElementById('setting-chapter-title-font-weight').value = settings.chapter_title_font_weight || 'bold';
        document.getElementById('setting-chapter-title-font-style').value = settings.chapter_title_font_style || 'normal';
        document.getElementById('setting-chapter-title-align').value = settings.chapter_title_align || 'center';
        document.getElementById('setting-chapter-title-text-transform').value = settings.chapter_title_text_transform || 'none';
        document.getElementById('setting-chapter-title-padding-top').value = settings.chapter_title_padding_top ?? 0;
        document.getElementById('setting-chapter-title-padding-bottom').value = settings.chapter_title_padding_bottom ?? 1.5;
        document.getElementById('setting-chapter-title-padding-left').value = settings.chapter_title_padding_left ?? 0;
        document.getElementById('setting-chapter-title-padding-right').value = settings.chapter_title_padding_right ?? 0;
        document.getElementById('setting-chapter-title-line-height').value = settings.chapter_title_line_height ?? 1.2;

        document.getElementById('setting-chapter-prefix-show').checked = settings.chapter_prefix_show == 1;
        document.getElementById('setting-chapter-prefix-template').value = settings.chapter_prefix_template || 'Capítulo {N}';
        document.getElementById('setting-chapter-prefix-position').value = settings.chapter_prefix_position || 'above';
        document.getElementById('setting-chapter-prefix-font-family').value = settings.chapter_prefix_font_family || 'Playfair Display';
        document.getElementById('setting-chapter-prefix-font-size').value = settings.chapter_prefix_font_size || 16;
        document.getElementById('setting-chapter-prefix-font-weight').value = settings.chapter_prefix_font_weight || 'normal';
        document.getElementById('setting-chapter-prefix-font-style').value = settings.chapter_prefix_font_style || 'normal';
        document.getElementById('setting-chapter-prefix-letter-spacing').value = settings.chapter_prefix_letter_spacing ?? 0;
        document.getElementById('setting-chapter-prefix-ornament').value = settings.chapter_prefix_ornament || 'none';

        // Pestaña Créditos
        document.getElementById('setting-credits-edition').value = settings.credits_edition || '';
        document.getElementById('setting-credits-date').value = settings.credits_date || '';
        document.getElementById('setting-credits-copyright').value = settings.credits_copyright || 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.';
        document.getElementById('setting-credits-printer').value = settings.credits_printer || '';
        document.getElementById('setting-credits-blank-before').value = settings.credits_blank_before || 0;
        document.getElementById('setting-credits-blank-after').value = settings.credits_blank_after || 0;
        renderCustomCredits(settings.credits_custom);

        updateUnitFields();
        toggleCustomPageFields();
        toggleCustomHeaderFields();
        toggleCustomFirstPageHeader();
        toggleCustomFirstPageFooter();
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
// Event Listeners for Color Inputs
document.addEventListener('DOMContentLoaded', () => {
    const ebookBgColor = document.getElementById('setting-ebook-bg-color');
    if (ebookBgColor) {
        ebookBgColor.addEventListener('input', function(e) {
            document.getElementById('setting-ebook-bg-color-text').value = e.target.value.toUpperCase();
        });
    }

    const coverPanelBgColor = document.getElementById('setting-ebook-cover-panel-bg-color');
    if (coverPanelBgColor) {
        coverPanelBgColor.addEventListener('input', function(e) {
            document.getElementById('setting-ebook-cover-panel-bg-color-text').value = e.target.value.toUpperCase();
        });
    }
});

window.toggleEbookBgType = function() {
    const type = document.getElementById('setting-ebook-bg-type').value;
    if (type === 'color') {
        document.getElementById('ebook-bg-color-wrap').classList.remove('hidden');
        document.getElementById('ebook-bg-image-wrap').classList.add('hidden');
    } else {
        document.getElementById('ebook-bg-color-wrap').classList.add('hidden');
        document.getElementById('ebook-bg-image-wrap').classList.remove('hidden');
    }
}

let mediaUploaderEbookBg;
window.openMediaUploaderEbookBg = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }
    if (mediaUploaderEbookBg) {
        mediaUploaderEbookBg.open();
        return;
    }
    mediaUploaderEbookBg = wp.media({
        title: 'Seleccionar Imagen de Fondo General',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });
    mediaUploaderEbookBg.on('select', function() {
        let attachment = mediaUploaderEbookBg.state().get('selection').first().toJSON();
        document.getElementById('setting-ebook-bg-image').value = attachment.url;
    });
    mediaUploaderEbookBg.open();
}

window.toggleCoverPanelBgType = function() {
    const type = document.getElementById('setting-ebook-cover-panel-bg-type').value;
    if (type === 'color') {
        document.getElementById('ebook-cover-panel-color-wrap').classList.remove('hidden');
        document.getElementById('ebook-cover-panel-image-wrap').classList.add('hidden');
    } else {
        document.getElementById('ebook-cover-panel-color-wrap').classList.add('hidden');
        document.getElementById('ebook-cover-panel-image-wrap').classList.remove('hidden');
    }
}

let mediaUploaderCoverPanel;
window.openMediaUploaderCoverPanel = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }
    if (mediaUploaderCoverPanel) {
        mediaUploaderCoverPanel.open();
        return;
    }
    mediaUploaderCoverPanel = wp.media({
        title: 'Seleccionar Imagen de Fondo',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });
    mediaUploaderCoverPanel.on('select', function() {
        let attachment = mediaUploaderCoverPanel.state().get('selection').first().toJSON();
        document.getElementById('setting-ebook-cover-panel-bg-image').value = attachment.url;
    });
    mediaUploaderCoverPanel.open();
}
