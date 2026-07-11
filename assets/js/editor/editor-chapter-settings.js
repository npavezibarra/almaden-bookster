// assets/js/editor-chapter-settings.js

/**
 * Abre el modal de configuración del capítulo actual y carga los valores.
 */
function openChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;
    
    // Buscar el capítulo activo en el estado global
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    if (!activeChapter) return;

    // Cargar los valores del capítulo en el formulario
    const isToc = activeChapter.is_toc === '1';
    const isCredits = activeChapter.is_credits === '1';
    
    // Toggle containers
    const normalContainer = document.getElementById('normal-chapter-settings');
    const tocContainer = document.getElementById('toc-chapter-settings');
    const creditsContainer = document.getElementById('credits-chapter-settings');
    
    // Shared settings
    if (isToc && (!activeChapter.start_parity || activeChapter.start_parity === 'any')) {
        activeChapter.start_parity = 'even';
    }
    document.getElementById('chapter_start_parity').value = isToc ? (activeChapter.start_parity || 'even') : (activeChapter.start_parity || 'any');
    
    if (isToc) {
        normalContainer.classList.add('hidden');
        tocContainer.classList.remove('hidden');
        
        if (typeof switchTocTab === 'function') {
            switchTocTab('toc-tab-general');
        }
        
        // Poblar las tipografías instaladas si no se ha hecho
        const fontSelect = document.getElementById('chapter_toc_font_family');
        const titleFontSelect = document.getElementById('chapter_toc_title_font_family');
        
        if (fontSelect && fontSelect.options.length === 0 && bookState.installedFonts) {
            // Añadir fuente por defecto
            fontSelect.innerHTML = '<option value="">Fuente por defecto (Merriweather)</option>';
            if (titleFontSelect) {
                titleFontSelect.innerHTML = '<option value="">Usar Global</option>';
            }
            bookState.installedFonts.forEach(font => {
                const opt = document.createElement('option');
                opt.value = font.family;
                opt.textContent = font.family;
                fontSelect.appendChild(opt);
                
                if (titleFontSelect) {
                    const optTitle = document.createElement('option');
                    optTitle.value = font.family;
                    optTitle.textContent = font.family;
                    titleFontSelect.appendChild(optTitle);
                }
            });
        }

        // Cargar valores TOC
        document.getElementById('chapter_toc_font_size').value = activeChapter.toc_font_size || '';
        document.getElementById('chapter_toc_enumerate').value = activeChapter.toc_enumerate || 'none';
        document.getElementById('chapter_toc_font_family').value = activeChapter.toc_font_family || '';
        document.getElementById('chapter_toc_font_style').value = activeChapter.toc_font_style || 'normal';
        document.getElementById('chapter_toc_font_weight').value = activeChapter.toc_font_weight || 'normal';
        document.getElementById('chapter_toc_text_transform').value = activeChapter.toc_text_transform || 'none';
        document.getElementById('chapter_toc_letter_spacing').value = activeChapter.toc_letter_spacing || '';
        document.getElementById('chapter_toc_line_height').value = activeChapter.toc_line_height || '';
        document.getElementById('chapter_toc_item_spacing').value = activeChapter.toc_item_spacing || '';
        document.getElementById('chapter_toc_leader_style').value = activeChapter.toc_leader_style || 'dotted';
        document.getElementById('chapter_toc_leader_position').value = activeChapter.toc_leader_position || 'middle';
        document.getElementById('chapter_toc_hide_header').checked = (activeChapter.toc_hide_header === '1');
        document.getElementById('chapter_toc_hide_page_numbers').checked = (activeChapter.toc_hide_page_numbers === '1');
        document.getElementById('chapter_toc_item_align').value = activeChapter.toc_item_align || 'left';
        
        // TOC Title Formats
        document.getElementById('chapter_toc_title_align').value = activeChapter.toc_title_align || '';
        document.getElementById('chapter_toc_title_font_family').value = activeChapter.toc_title_font_family || '';
        document.getElementById('chapter_toc_title_font_size').value = activeChapter.toc_title_font_size || '';
        document.getElementById('chapter_toc_title_font_style').value = activeChapter.toc_title_font_style || '';
        document.getElementById('chapter_toc_title_text_transform').value = activeChapter.toc_title_text_transform || '';
        document.getElementById('chapter_toc_title_font_weight').value = activeChapter.toc_title_font_weight || '';
        document.getElementById('chapter_toc_title_padding_top').value = activeChapter.toc_title_padding_top || '';
        document.getElementById('chapter_toc_title_padding_bottom').value = activeChapter.toc_title_padding_bottom || '';
        document.getElementById('chapter_toc_title_line_height').value = activeChapter.toc_title_line_height || '';
    } else if (isCredits) {
        // Poblar las tipografías de créditos si no se ha hecho
        const creditsFontSelect = document.getElementById('chapter_credits_font_family');
        if (creditsFontSelect && creditsFontSelect.options.length <= 1 && bookState.installedFonts) {
            bookState.installedFonts.forEach(font => {
                const opt = document.createElement('option');
                opt.value = font.family;
                opt.textContent = font.family;
                creditsFontSelect.appendChild(opt);
            });
        }
        
        document.getElementById('chapter_credits_font_family').value = activeChapter.credits_font_family || '';
        document.getElementById('chapter_credits_align').value = activeChapter.credits_align || '';
        document.getElementById('chapter_credits_font_size').value = activeChapter.credits_font_size || '';
        document.getElementById('chapter_credits_letter_spacing').value = activeChapter.credits_letter_spacing || '';
        document.getElementById('chapter_credits_font_weight').value = activeChapter.credits_font_weight || '';
        document.getElementById('chapter_credits_hide_page_number').checked = activeChapter.credits_hide_page_number === '1';
        document.getElementById('chapter_credits_margin_top').value = activeChapter.credits_margin_top ?? '';
        document.getElementById('chapter_credits_margin_bottom').value = activeChapter.credits_margin_bottom ?? '';
        
        normalContainer.classList.add('hidden');
        tocContainer.classList.add('hidden');
        creditsContainer.classList.remove('hidden');
    } else {
        normalContainer.classList.remove('hidden');
        tocContainer.classList.add('hidden');
        creditsContainer.classList.add('hidden');
        
        // Reset a la pestaña Estructura por defecto
        if (typeof switchChapterTab === 'function') {
            switchChapterTab('tab-structure');
        }

        // Poblar tipografía del subtítulo si no se ha hecho
        const subtitleFontSelect = document.getElementById('chapter_subtitle_font_family');
        if (subtitleFontSelect && subtitleFontSelect.options.length <= 1 && bookState.installedFonts) {
            bookState.installedFonts.forEach(font => {
                const opt = document.createElement('option');
                opt.value = font.family;
                opt.textContent = font.family;
                subtitleFontSelect.appendChild(opt);
            });
        }
        const derivedOpeningPageMode = activeChapter.opening_page_mode || (activeChapter.parity_image ? 'image' : 'auto');
        const derivedOpeningBlockEnabled = activeChapter.opening_block_enabled === '0' ? '0' : '1';

        // Cargar valores Normales
        document.getElementById('chapter_opening_page_mode').value = derivedOpeningPageMode;
        document.getElementById('chapter_opening_blank_intentional').checked = activeChapter.opening_blank_intentional === '1';
        document.getElementById('chapter_opening_block_enabled').checked = derivedOpeningBlockEnabled === '1';
        document.getElementById('chapter_hide_title').checked = activeChapter.hide_title === '1';
        document.getElementById('chapter_exclude_from_numbering').checked = activeChapter.exclude_from_numbering === '1';
        document.getElementById('chapter_hide_all_headers_footers').checked = activeChapter.hide_all_headers_footers === '1';
        document.getElementById('chapter_custom_running_header').value = activeChapter.custom_running_header || '';
    document.getElementById('chapter_drop_cap_enabled').checked = activeChapter.drop_cap_enabled === '1';
    document.getElementById('chapter_disable_hyphenation').checked = activeChapter.disable_hyphenation === '1';
    document.getElementById('chapter_first_page_header_type').value = activeChapter.first_page_header_type || 'global';
    document.getElementById('chapter_first_page_header_custom').value = activeChapter.first_page_header_custom || '';
    document.getElementById('chapter_first_page_footer_type').value = activeChapter.first_page_footer_type || 'global';
    document.getElementById('chapter_first_page_footer_custom').value = activeChapter.first_page_footer_custom || '';
    document.getElementById('chapter_parity_image_mode').value = activeChapter.parity_image_mode || 'content';
    document.getElementById('chapter_parity_image_width').value = activeChapter.parity_image_width || '';
    document.getElementById('chapter_parity_image_height').value = activeChapter.parity_image_height || '';
    
    // Valores del Subtítulo
    document.getElementById('chapter_subtitle_text').value = activeChapter.subtitle_text || '';
    document.getElementById('chapter_subtitle_font_family').value = activeChapter.subtitle_font_family || '';
    if (document.getElementById('chapter_subtitle_align')) {
        const subtitleAlign = ['left', 'center', 'right'].includes(String(activeChapter.subtitle_align || '').toLowerCase())
            ? String(activeChapter.subtitle_align).toLowerCase()
            : 'center';
        document.getElementById('chapter_subtitle_align').value = subtitleAlign;
    }
    document.getElementById('chapter_subtitle_font_size').value = activeChapter.subtitle_font_size || '';
    document.getElementById('chapter_subtitle_letter_spacing').value = activeChapter.subtitle_letter_spacing || '';
    document.getElementById('chapter_subtitle_font_style').value = activeChapter.subtitle_font_style || 'normal';
    document.getElementById('chapter_subtitle_text_transform').value = activeChapter.subtitle_text_transform || 'none';
    document.getElementById('chapter_subtitle_font_weight').value = activeChapter.subtitle_font_weight || 'normal';
    document.getElementById('chapter_subtitle_margin_top').value = activeChapter.subtitle_margin_top || '';
    document.getElementById('chapter_subtitle_margin_bottom').value = activeChapter.subtitle_margin_bottom || '';

    }

    toggleChapterCustomFirstPageHeader();
    toggleChapterCustomFirstPageFooter();
    toggleOpeningPageControls();

    // Mostrar modal con animación
    modal.classList.remove('hidden');
    // Pequeño delay para que la transición CSS funcione
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        modal.querySelector('.transform').classList.add('scale-100');
    }, 10);
}

/**
 * Cierra el modal de configuración del capítulo.
 */
function closeChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-100');
    modal.querySelector('.transform').classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300); // duración de la transición
}

/**
 * Guarda los valores del formulario de vuelta al objeto de capítulo y compila el PDF.
 */
function saveChapterSettings() {
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    if (!activeChapter) {
        closeChapterSettingsModal();
        return;
    }

    const isToc = activeChapter.is_toc === '1';
    const isCredits = activeChapter.is_credits === '1';

    // Shared settings
    activeChapter.start_parity = isToc ? 'even' : document.getElementById('chapter_start_parity').value;

    const cleanFloat = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.replace(',', '.') : '';
    };

    if (isToc) {
        activeChapter.toc_font_size = cleanFloat('chapter_toc_font_size');
        activeChapter.toc_enumerate = document.getElementById('chapter_toc_enumerate').value;
        activeChapter.toc_font_family = document.getElementById('chapter_toc_font_family').value;
        activeChapter.toc_font_style = document.getElementById('chapter_toc_font_style').value;
        activeChapter.toc_font_weight = document.getElementById('chapter_toc_font_weight').value;
        activeChapter.toc_text_transform = document.getElementById('chapter_toc_text_transform').value;
        activeChapter.toc_letter_spacing = cleanFloat('chapter_toc_letter_spacing');
        activeChapter.toc_line_height = cleanFloat('chapter_toc_line_height');
        activeChapter.toc_item_spacing = cleanFloat('chapter_toc_item_spacing');
        activeChapter.toc_leader_style = document.getElementById('chapter_toc_leader_style').value;
        activeChapter.toc_leader_position = document.getElementById('chapter_toc_leader_position').value;
        activeChapter.toc_hide_header = document.getElementById('chapter_toc_hide_header').checked ? '1' : '0';
        activeChapter.toc_hide_page_numbers = document.getElementById('chapter_toc_hide_page_numbers').checked ? '1' : '0';
        activeChapter.toc_item_align = document.getElementById('chapter_toc_item_align').value;
        
        // TOC Title formats
        activeChapter.toc_title_align = document.getElementById('chapter_toc_title_align').value;
        activeChapter.toc_title_font_family = document.getElementById('chapter_toc_title_font_family').value;
        activeChapter.toc_title_font_size = cleanFloat('chapter_toc_title_font_size');
        activeChapter.toc_title_font_style = document.getElementById('chapter_toc_title_font_style').value;
        activeChapter.toc_title_text_transform = document.getElementById('chapter_toc_title_text_transform').value;
        activeChapter.toc_title_font_weight = document.getElementById('chapter_toc_title_font_weight').value;
        activeChapter.toc_title_padding_top = cleanFloat('chapter_toc_title_padding_top');
        activeChapter.toc_title_padding_bottom = cleanFloat('chapter_toc_title_padding_bottom');
        activeChapter.toc_title_line_height = cleanFloat('chapter_toc_title_line_height');
    } else if (isCredits) {
        activeChapter.credits_font_family = document.getElementById('chapter_credits_font_family').value;
        activeChapter.credits_align = document.getElementById('chapter_credits_align').value;
        activeChapter.credits_font_size = cleanFloat('chapter_credits_font_size');
        activeChapter.credits_letter_spacing = cleanFloat('chapter_credits_letter_spacing');
        activeChapter.credits_font_weight = document.getElementById('chapter_credits_font_weight').value;
        activeChapter.credits_hide_page_number = document.getElementById('chapter_credits_hide_page_number').checked ? '1' : '0';
        activeChapter.credits_margin_top = cleanFloat('chapter_credits_margin_top');
        activeChapter.credits_margin_bottom = cleanFloat('chapter_credits_margin_bottom');
    } else {
        // Leer valores del formulario
        activeChapter.opening_page_mode = document.getElementById('chapter_opening_page_mode').value;
        activeChapter.opening_blank_intentional = document.getElementById('chapter_opening_blank_intentional').checked ? '1' : '0';
        activeChapter.opening_block_enabled = document.getElementById('chapter_opening_block_enabled').checked ? '1' : '0';
        activeChapter.hide_title = document.getElementById('chapter_hide_title').checked ? '1' : '0';
        activeChapter.exclude_from_numbering = document.getElementById('chapter_exclude_from_numbering').checked ? '1' : '0';
        activeChapter.hide_all_headers_footers = document.getElementById('chapter_hide_all_headers_footers').checked ? '1' : '0';
        activeChapter.custom_running_header = document.getElementById('chapter_custom_running_header').value.trim();
        activeChapter.drop_cap_enabled = document.getElementById('chapter_drop_cap_enabled').checked ? '1' : '0';
        activeChapter.disable_hyphenation = document.getElementById('chapter_disable_hyphenation').checked ? '1' : '0';
        activeChapter.first_page_header_type = document.getElementById('chapter_first_page_header_type').value;
        activeChapter.first_page_header_custom = document.getElementById('chapter_first_page_header_custom').value;
        activeChapter.first_page_footer_type = document.getElementById('chapter_first_page_footer_type').value;
        activeChapter.first_page_footer_custom = document.getElementById('chapter_first_page_footer_custom').value;
        activeChapter.parity_image_mode = document.getElementById('chapter_parity_image_mode').value;
        activeChapter.parity_image_width = document.getElementById('chapter_parity_image_width').value;
        activeChapter.parity_image_height = document.getElementById('chapter_parity_image_height').value;
        
        // Valores del subtítulo
        activeChapter.subtitle_text = document.getElementById('chapter_subtitle_text').value;
        activeChapter.subtitle_font_family = document.getElementById('chapter_subtitle_font_family').value;
        activeChapter.subtitle_align = ['left', 'center', 'right'].includes(String(document.getElementById('chapter_subtitle_align').value || '').toLowerCase())
            ? String(document.getElementById('chapter_subtitle_align').value).toLowerCase()
            : 'center';
        activeChapter.subtitle_font_size = document.getElementById('chapter_subtitle_font_size').value;
        activeChapter.subtitle_letter_spacing = document.getElementById('chapter_subtitle_letter_spacing').value;
        activeChapter.subtitle_font_style = document.getElementById('chapter_subtitle_font_style').value;
        activeChapter.subtitle_text_transform = document.getElementById('chapter_subtitle_text_transform').value;
        activeChapter.subtitle_font_weight = document.getElementById('chapter_subtitle_font_weight').value;
        activeChapter.subtitle_margin_top = document.getElementById('chapter_subtitle_margin_top').value;
        activeChapter.subtitle_margin_bottom = document.getElementById('chapter_subtitle_margin_bottom').value;
    }

    const btn = document.querySelector('#chapter-settings-modal button[onclick="saveChapterSettings()"]');
    const originalBtnText = btn ? btn.innerHTML : '<i class="fa-solid fa-check"></i> Aplicar al Capítulo';

    if (btn) {
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        btn.disabled = true;
        btn.classList.add('opacity-75', 'cursor-not-allowed');
    }

    // Cerrar modal después de un pequeño retraso para mostrar el estado
    setTimeout(() => {
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Guardado';
            btn.classList.replace('bg-black', 'bg-emerald-600');
            btn.classList.replace('hover:bg-neutral-800', 'hover:bg-emerald-700');
        }

        setTimeout(() => {
            closeChapterSettingsModal();

            if (btn) {
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-not-allowed');
                btn.classList.replace('bg-emerald-600', 'bg-black');
                btn.classList.replace('hover:bg-emerald-700', 'hover:bg-neutral-800');
            }

            // Actualizar estilos dinámicos del PDF y re-renderizar
            if (typeof applyDynamicPDFStyles === 'function') {
                applyDynamicPDFStyles();
            } else if (typeof compilePDFPreview === 'function') {
                compilePDFPreview();
            }

            // Marcar como pendiente de guardado y forzar actualización del PDF
            if (typeof saveStateToLocalStorage === 'function') {
                saveStateToLocalStorage(true);
            }
            
            if (typeof showToast === 'function') {
                showToast("Configuración del capítulo actualizada.", "fa-solid fa-check");
            }
        }, 500);
    }, 300); // Pequeño retraso simulado ya que es síncrono localmente
}

function toggleChapterCustomFirstPageHeader() {
    const type = document.getElementById('chapter_first_page_header_type').value;
    const input = document.getElementById('chapter_first_page_header_custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
    }
}

function toggleChapterCustomFirstPageFooter() {
    const type = document.getElementById('chapter_first_page_footer_type').value;
    const input = document.getElementById('chapter_first_page_footer_custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
    }
}

function toggleParityImageSizeInputs() {
    const modeField = document.getElementById('chapter_parity_image_mode');
    const wrapper = document.getElementById('parity_image_custom_size');
    if (!modeField || !wrapper) return;

    if (modeField.value === 'custom') {
        wrapper.classList.remove('hidden');
        wrapper.classList.add('grid');
    } else {
        wrapper.classList.add('hidden');
        wrapper.classList.remove('grid');
    }
}

function toggleOpeningPageControls() {
    const modeField = document.getElementById('chapter_opening_page_mode');
    const blankWrapper = document.getElementById('chapter_opening_blank_intentional_wrapper');
    const imageWrapper = document.getElementById('chapter_opening_image_controls');
    if (!modeField) return;

    const mode = modeField.value;

    if (blankWrapper) {
        blankWrapper.classList.toggle('hidden', mode !== 'blank');
    }

    if (imageWrapper) {
        imageWrapper.classList.toggle('hidden', mode !== 'image');
    }

    toggleParityImageSizeInputs();
}

function switchChapterTab(tabId) {
    // Esconder todos los contenidos
    const contents = document.querySelectorAll('.chapter-tab-content');
    contents.forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    // Desactivar todos los botones
    const btns = document.querySelectorAll('.chapter-tab-btn');
    btns.forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });

    // Activar el contenido y botón seleccionado
    const tabEl = document.getElementById(tabId);
    if (tabEl) {
        tabEl.classList.remove('hidden');
        tabEl.classList.add('block');
    }
    
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
    }
}

function switchTocTab(tabId) {
    // Esconder todos los contenidos
    const contents = document.querySelectorAll('.toc-tab-content');
    contents.forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    // Desactivar todos los botones
    const btns = document.querySelectorAll('.toc-tab-btn');
    btns.forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });

    // Activar el contenido y botón seleccionado
    const tabEl = document.getElementById(tabId);
    if (tabEl) {
        tabEl.classList.remove('hidden');
        tabEl.classList.add('block');
    }
    
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-[var(--text-muted)]');
        activeBtn.classList.add('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
    }
}
