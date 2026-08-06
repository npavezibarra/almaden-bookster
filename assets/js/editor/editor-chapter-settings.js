// assets/js/editor-chapter-settings.js

/**
 * Abre el modal de configuración del capítulo actual y carga los valores.
 */
function getChapterNomenclatureGuideText() {
    return `Guía rápida de nomenclatura para el editor raw de Almaden Bookster

Regla general
- Usa una sola forma canónica por concepto.
- No inventes tags nuevos.
- Si existe un alias viejo, úsalo sólo para compatibilidad de lectura, no como salida preferida.

1. Idioma extranjero
Forma preferida:
<foreign lang="la">carpe diem</foreign>

Lenguajes principales:
es = español
en = inglés
fr = francés
de = alemán
it = italiano
pt = portugués

2. Citas
> Esta es una cita.
> Puede ocupar varias líneas.

3. Notas al pie
Texto con nota[^1].

[^1]: Explicación de la nota al pie.

4. Maquetación
[box]
Contenido destacado.
[/box]

[columns]
[col]Columna izquierda[/col]
[col]Columna derecha[/col]
[/columns]

[align=center]
Texto centrado.
[/align]

[gap:10mm]
[pagebreak]
[logo]

[html]
<div>HTML crudo</div>
[/html]

5. Formato inline
[size=12px]texto[/size]
[font="Merriweather"]texto[/font]

Preferencia final
- Para idioma, usa siempre <foreign lang="xx"> cuando generes contenido nuevo.
- Para citas, usa bloque Markdown con >.
- Para notas, usa [^id] y su definición al final.`;
}

function openChapterNomenclatureGuideModal() {
    const modal = document.getElementById('chapter-nomenclature-modal');
    if (!modal) return;

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        const panel = modal.querySelector('div');
        if (panel) {
            panel.classList.remove('scale-95');
            panel.classList.add('scale-100');
        }
    }, 10);
}

function closeChapterNomenclatureGuideModal() {
    const modal = document.getElementById('chapter-nomenclature-modal');
    if (!modal) return;

    modal.classList.add('opacity-0');
    const panel = modal.querySelector('div');
    if (panel) {
        panel.classList.remove('scale-100');
        panel.classList.add('scale-95');
    }

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

function copyChapterNomenclatureGuide() {
    const text = getChapterNomenclatureGuideText();
    const done = () => {
        if (typeof showToast === 'function') {
            showToast('Guía copiada al portapapeles.', 'fa-solid fa-copy');
        }
    };
    const failed = () => {
        if (typeof showToast === 'function') {
            showToast('No se pudo copiar la guía.', 'fa-solid fa-triangle-exclamation');
        }
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done).catch(failed);
        return;
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'true');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        textarea.remove();
        if (ok) {
            done();
        } else {
            failed();
        }
    } catch (error) {
        failed();
    }
}

function getChapterSettingsModalLabels(chapter) {
    if (!chapter) {
        return {
            title: 'Ajustes del Capítulo de Contenido',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el capítulo de contenido actual.',
            startLabel: '¿Dónde debe iniciar el contenido de este capítulo?',
            startSubtitle: 'Define el lado donde empieza el contenido. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar al Capítulo de Contenido'
        };
    }

    if (chapter.is_toc === '1') {
        return {
            title: 'Ajustes del Índice',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el índice actual.',
            startLabel: '¿Dónde debe iniciar el contenido del Índice?',
            startSubtitle: 'Define el lado donde empieza el Índice. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar al Índice'
        };
    }

    if (chapter.is_credits === '1') {
        return {
            title: 'Ajustes de la Página de Créditos',
            subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para la página de créditos actual.',
            startLabel: '¿Dónde debe iniciar el contenido de la Página de Créditos?',
            startSubtitle: 'Define el lado donde empieza la Página de Créditos. La apertura se configura aparte en la pestaña "Apertura".',
            saveLabel: 'Aplicar a Créditos'
        };
    }

    return {
        title: 'Ajustes del Capítulo de Contenido',
        subtitle: 'Estas configuraciones sobrescriben las reglas globales solo para el capítulo de contenido actual.',
        startLabel: '¿Dónde debe iniciar el contenido de este capítulo?',
        startSubtitle: 'Define el lado donde empieza el contenido. La apertura se configura aparte en la pestaña "Apertura".',
        saveLabel: 'Aplicar al Capítulo de Contenido'
    };
}

function applyChapterSettingsModalLabels(chapter) {
    const labels = getChapterSettingsModalLabels(chapter);
    const titleEl = document.getElementById('chapter-settings-modal-title');
    const subtitleEl = document.getElementById('chapter-settings-modal-subtitle');
    const startLabelEl = document.getElementById('chapter-settings-modal-start-label');
    const startSubtitleEl = document.getElementById('chapter-settings-modal-start-subtitle');
    const saveBtn = document.querySelector('#chapter-settings-modal button[onclick="saveChapterSettings()"]');

    if (titleEl) {
        titleEl.innerHTML = `<i class="fa-solid fa-gear text-black dark:text-white"></i> ${labels.title}`;
    }
    if (subtitleEl) subtitleEl.textContent = labels.subtitle;
    if (startLabelEl) startLabelEl.textContent = labels.startLabel;
    if (startSubtitleEl) startSubtitleEl.textContent = labels.startSubtitle;
    if (saveBtn) {
        saveBtn.dataset.defaultLabel = labels.saveLabel;
        saveBtn.innerHTML = `<i class="fa-solid fa-check"></i> ${labels.saveLabel}`;
    }
}

function openChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;
    
    // Buscar el capítulo activo en el estado global
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    if (!activeChapter) return;

    // Cargar los valores del capítulo en el formulario
    const isToc = activeChapter.is_toc === '1';
    const isCredits = activeChapter.is_credits === '1';

    applyChapterSettingsModalLabels(activeChapter);
    
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
        document.getElementById('chapter_toc_separate_opening_content').value = activeChapter.toc_separate_opening_content ?? '';
        document.getElementById('chapter_toc_hide_header').checked = (activeChapter.toc_hide_header !== '0');
        document.getElementById('chapter_toc_hide_page_numbers').checked = (activeChapter.toc_hide_page_numbers !== '0');
        document.getElementById('chapter_toc_item_align').value = activeChapter.toc_item_align || 'left';
        
        // TOC Title Formats
        document.getElementById('chapter_toc_hide_title').checked = activeChapter.toc_hide_title === '1';
        document.getElementById('chapter_toc_title_text').value = activeChapter.toc_title_text || activeChapter.title || 'Índice';
        document.getElementById('chapter_toc_title_align').value = activeChapter.toc_title_align || '';
        document.getElementById('chapter_toc_title_font_family').value = activeChapter.toc_title_font_family || '';
        document.getElementById('chapter_toc_title_font_size').value = activeChapter.toc_title_font_size || '';
        document.getElementById('chapter_toc_title_font_style').value = activeChapter.toc_title_font_style || '';
        document.getElementById('chapter_toc_title_text_transform').value = activeChapter.toc_title_text_transform || '';
        document.getElementById('chapter_toc_title_font_weight').value = activeChapter.toc_title_font_weight || '';
        document.getElementById('chapter_toc_title_letter_spacing').value = activeChapter.toc_title_letter_spacing || '';
        document.getElementById('chapter_toc_title_padding_top').value = activeChapter.toc_title_padding_top || '';
        document.getElementById('chapter_toc_title_padding_bottom').value = activeChapter.toc_title_padding_bottom || '';
        document.getElementById('chapter_toc_title_line_height').value = activeChapter.toc_title_line_height || '';
    } else if (isCredits) {
        // Poblar las tipografías de créditos si no se ha hecho
        const settings = bookState.settings || {};
        const creditFontFamilyDefault = settings.font_family_content || '';
        const creditAlignDefault = ['left', 'center', 'right'].includes(String(settings.content_text_align || '').toLowerCase())
            ? String(settings.content_text_align).toLowerCase()
            : 'center';
        const creditFontSizeDefault = settings.font_size_content || '';
        const creditFontWeightDefault = settings.font_weight_content || '';

        const creditsFontSelect = document.getElementById('chapter_credits_font_family');
        if (creditsFontSelect && creditsFontSelect.options.length <= 1 && bookState.installedFonts) {
            bookState.installedFonts.forEach(font => {
                const opt = document.createElement('option');
                opt.value = font.family;
                opt.textContent = font.family;
                creditsFontSelect.appendChild(opt);
            });
        }
        
        document.getElementById('chapter_credits_font_family').value = activeChapter.credits_font_family || creditFontFamilyDefault;
        document.getElementById('chapter_credits_align').value = activeChapter.credits_align || creditAlignDefault;
        document.getElementById('chapter_credits_font_size').value = activeChapter.credits_font_size || creditFontSizeDefault;
        document.getElementById('chapter_credits_letter_spacing').value = activeChapter.credits_letter_spacing || '';
        document.getElementById('chapter_credits_font_weight').value = activeChapter.credits_font_weight || creditFontWeightDefault;
        document.getElementById('chapter_credits_hide_title').checked = activeChapter.hide_title === '1';
        const creditsConfig = typeof creditsNormalizeConfig === 'function'
            ? creditsNormalizeConfig(settings.credits_config || settings)
            : (settings.credits_config || {});
        const creditsEditorial = creditsConfig && creditsConfig.editorial ? creditsConfig.editorial : {};
        document.getElementById('chapter_credits_blank_before').value = creditsEditorial.blank_before ?? settings.credits_blank_before ?? 0;
        document.getElementById('chapter_credits_blank_after').value = creditsEditorial.blank_after ?? settings.credits_blank_after ?? 0;
        document.getElementById('chapter_credits_hide_header').checked = activeChapter.credits_hide_header === '1';
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
            switchChapterTab('chapter-tab-structure');
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
        const settings = bookState.settings || {};
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
        };

        const openingPageAlignment = deriveOpeningPageAlignment();
        const derivedOpeningBlockHorizontalAlign = ['left', 'center', 'right'].includes(String(activeChapter.opening_block_horizontal_align || '').toLowerCase())
            ? String(activeChapter.opening_block_horizontal_align).toLowerCase()
            : openingPageAlignment.horizontal;
        const derivedOpeningBlockVerticalAlign = ['top', 'center', 'bottom'].includes(String(activeChapter.opening_block_vertical_align || '').toLowerCase())
            ? String(activeChapter.opening_block_vertical_align).toLowerCase()
            : openingPageAlignment.vertical;

        // Cargar valores Normales
        document.getElementById('chapter_opening_page_mode').value = derivedOpeningPageMode;
        document.getElementById('chapter_opening_blank_intentional').checked = activeChapter.opening_blank_intentional === '1';
        document.getElementById('chapter_opening_block_enabled').checked = derivedOpeningBlockEnabled === '1';
        document.getElementById('chapter_opening_block_horizontal_align').value = derivedOpeningBlockHorizontalAlign;
        document.getElementById('chapter_opening_block_vertical_align').value = derivedOpeningBlockVerticalAlign;
        document.getElementById('chapter_hide_opening').checked = activeChapter.hide_opening === '1';
        document.getElementById('chapter_hide_title').checked = activeChapter.hide_title === '1';
        document.getElementById('chapter_exclude_from_numbering').checked = activeChapter.exclude_from_numbering === '1';
        const derivedHideAllHeadersFooters = activeChapter.hide_all_headers_footers === '1';
        const derivedHideHeader = activeChapter.hide_header === '1' || derivedHideAllHeadersFooters;
        const derivedHideFooter = activeChapter.hide_footer === '1' || derivedHideAllHeadersFooters;
        document.getElementById('chapter_hide_header').checked = derivedHideHeader;
        document.getElementById('chapter_hide_footer').checked = derivedHideFooter;
        document.getElementById('chapter_custom_running_header').value = activeChapter.custom_running_header || '';
    document.getElementById('chapter_drop_cap_enabled').checked = activeChapter.drop_cap_enabled === '1';
    document.getElementById('chapter_disable_hyphenation').checked = activeChapter.disable_hyphenation === '1';
    document.getElementById('chapter_first_page_header_type').value = activeChapter.first_page_header_type || 'global';
    document.getElementById('chapter_first_page_header_custom').value = activeChapter.first_page_header_custom || '';
        document.getElementById('chapter_first_page_footer_type').value = activeChapter.first_page_footer_type || 'global';
        document.getElementById('chapter_first_page_footer_custom').value = activeChapter.first_page_footer_custom || '';
        const legacyChapterImageDefaults = bookState.settings || {};
        const hasLegacyChapterImage = !!(
            activeChapter.chapter_image_mode
                && activeChapter.chapter_image_mode !== 'page_blank'
            || (activeChapter.chapter_image_url && String(activeChapter.chapter_image_url).trim() !== '')
            || (legacyChapterImageDefaults.chapter_image_mode && legacyChapterImageDefaults.chapter_image_mode !== 'page_blank')
            || (legacyChapterImageDefaults.chapter_image_url && String(legacyChapterImageDefaults.chapter_image_url).trim() !== '')
        );
        document.getElementById('chapter_opening_separate_content').value = activeChapter.opening_separate_content ?? '';
        document.getElementById('chapter_image_mode').value = activeChapter.chapter_image_mode || legacyChapterImageDefaults.chapter_image_mode || 'page_blank';
        document.getElementById('chapter_image_url').value = activeChapter.chapter_image_url || legacyChapterImageDefaults.chapter_image_url || '';
        document.getElementById('chapter_image_inner_width').value = activeChapter.chapter_image_inner_width || legacyChapterImageDefaults.chapter_image_inner_width || '100';
        document.getElementById('chapter_image_inner_header').checked = (activeChapter.chapter_image_inner_header ?? legacyChapterImageDefaults.chapter_image_inner_header ?? 0) === 1 || (activeChapter.chapter_image_inner_header ?? legacyChapterImageDefaults.chapter_image_inner_header ?? 0) === '1';
        document.getElementById('chapter_image_inner_footer').checked = (activeChapter.chapter_image_inner_footer ?? legacyChapterImageDefaults.chapter_image_inner_footer ?? 0) === 1 || (activeChapter.chapter_image_inner_footer ?? legacyChapterImageDefaults.chapter_image_inner_footer ?? 0) === '1';

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
    toggleLegacyOpeningCompatibilityNotice(activeChapter);
    toggleChapterImageSettingsForChapter();

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
        activeChapter.toc_separate_opening_content = document.getElementById('chapter_toc_separate_opening_content').value;
        activeChapter.toc_hide_header = document.getElementById('chapter_toc_hide_header').checked ? '1' : '0';
        activeChapter.toc_hide_page_numbers = document.getElementById('chapter_toc_hide_page_numbers').checked ? '1' : '0';
        activeChapter.toc_item_align = document.getElementById('chapter_toc_item_align').value;
        
        // TOC Title formats
        activeChapter.toc_hide_title = document.getElementById('chapter_toc_hide_title').checked ? '1' : '0';
        activeChapter.toc_title_text = document.getElementById('chapter_toc_title_text').value.trim();
        activeChapter.toc_title_align = document.getElementById('chapter_toc_title_align').value;
        activeChapter.toc_title_font_family = document.getElementById('chapter_toc_title_font_family').value;
        activeChapter.toc_title_font_size = cleanFloat('chapter_toc_title_font_size');
        activeChapter.toc_title_font_style = document.getElementById('chapter_toc_title_font_style').value;
        activeChapter.toc_title_text_transform = document.getElementById('chapter_toc_title_text_transform').value;
        activeChapter.toc_title_font_weight = document.getElementById('chapter_toc_title_font_weight').value;
        activeChapter.toc_title_letter_spacing = cleanFloat('chapter_toc_title_letter_spacing');
        activeChapter.toc_title_padding_top = cleanFloat('chapter_toc_title_padding_top');
        activeChapter.toc_title_padding_bottom = cleanFloat('chapter_toc_title_padding_bottom');
        activeChapter.toc_title_line_height = cleanFloat('chapter_toc_title_line_height');
    } else if (isCredits) {
        activeChapter.credits_font_family = document.getElementById('chapter_credits_font_family').value;
        // An empty value means inherit the book/default section alignment; do not force center.
        activeChapter.credits_align = document.getElementById('chapter_credits_align').value;
        activeChapter.credits_font_size = cleanFloat('chapter_credits_font_size');
        activeChapter.credits_letter_spacing = cleanFloat('chapter_credits_letter_spacing');
        activeChapter.credits_font_weight = document.getElementById('chapter_credits_font_weight').value;
        const blankBefore = Math.min(999, Math.max(0, parseInt(document.getElementById('chapter_credits_blank_before').value, 10) || 0));
        const blankAfter = Math.min(999, Math.max(0, parseInt(document.getElementById('chapter_credits_blank_after').value, 10) || 0));
        const currentCreditsConfig = typeof creditsNormalizeConfig === 'function'
            ? creditsNormalizeConfig(bookState.settings.credits_config || bookState.settings)
            : {
                ...(bookState.settings.credits_config || {}),
                editorial: {
                    ...((bookState.settings.credits_config && bookState.settings.credits_config.editorial) || {}),
                },
            };
        currentCreditsConfig.editorial = currentCreditsConfig.editorial || {};
        currentCreditsConfig.editorial.blank_before = blankBefore;
        currentCreditsConfig.editorial.blank_after = blankAfter;
        bookState.settings.credits_config = currentCreditsConfig;
        bookState.settings.credits_blank_before = blankBefore;
        bookState.settings.credits_blank_after = blankAfter;
        const globalBlankBefore = document.getElementById('setting-credits-blank-before');
        const globalBlankAfter = document.getElementById('setting-credits-blank-after');
        if (globalBlankBefore) globalBlankBefore.value = String(blankBefore);
        if (globalBlankAfter) globalBlankAfter.value = String(blankAfter);
        activeChapter.hide_title = document.getElementById('chapter_credits_hide_title').checked ? '1' : '0';
        activeChapter.credits_hide_header = document.getElementById('chapter_credits_hide_header').checked ? '1' : '0';
        activeChapter.credits_hide_page_number = document.getElementById('chapter_credits_hide_page_number').checked ? '1' : '0';
        activeChapter.credits_margin_top = cleanFloat('chapter_credits_margin_top');
        activeChapter.credits_margin_bottom = cleanFloat('chapter_credits_margin_bottom');
    } else {
        // Leer valores del formulario
        activeChapter.opening_page_mode = document.getElementById('chapter_opening_page_mode').value;
        activeChapter.opening_blank_intentional = document.getElementById('chapter_opening_blank_intentional').checked ? '1' : '0';
        activeChapter.opening_block_enabled = document.getElementById('chapter_opening_block_enabled').checked ? '1' : '0';
        activeChapter.opening_block_horizontal_align = ['left', 'center', 'right'].includes(String(document.getElementById('chapter_opening_block_horizontal_align').value || '').toLowerCase())
            ? String(document.getElementById('chapter_opening_block_horizontal_align').value).toLowerCase()
            : 'center';
        activeChapter.opening_block_vertical_align = ['top', 'center', 'bottom'].includes(String(document.getElementById('chapter_opening_block_vertical_align').value || '').toLowerCase())
            ? String(document.getElementById('chapter_opening_block_vertical_align').value).toLowerCase()
            : 'top';
        activeChapter.hide_opening = document.getElementById('chapter_hide_opening').checked ? '1' : '0';
        activeChapter.hide_title = document.getElementById('chapter_hide_title').checked ? '1' : '0';
        activeChapter.exclude_from_numbering = document.getElementById('chapter_exclude_from_numbering').checked ? '1' : '0';
        activeChapter.hide_header = document.getElementById('chapter_hide_header').checked ? '1' : '0';
        activeChapter.hide_footer = document.getElementById('chapter_hide_footer').checked ? '1' : '0';
        activeChapter.hide_all_headers_footers = (activeChapter.hide_header === '1' && activeChapter.hide_footer === '1') ? '1' : '0';
        activeChapter.custom_running_header = document.getElementById('chapter_custom_running_header').value.trim();
        activeChapter.drop_cap_enabled = document.getElementById('chapter_drop_cap_enabled').checked ? '1' : '0';
        activeChapter.disable_hyphenation = document.getElementById('chapter_disable_hyphenation').checked ? '1' : '0';
        activeChapter.first_page_header_type = document.getElementById('chapter_first_page_header_type').value;
        activeChapter.first_page_header_custom = document.getElementById('chapter_first_page_header_custom').value;
        activeChapter.first_page_footer_type = document.getElementById('chapter_first_page_footer_type').value;
        activeChapter.first_page_footer_custom = document.getElementById('chapter_first_page_footer_custom').value;
        activeChapter.opening_separate_content = document.getElementById('chapter_opening_separate_content').value;
        activeChapter.chapter_image_mode = document.getElementById('chapter_image_mode').value;
        activeChapter.chapter_image_url = document.getElementById('chapter_image_url').value;
        activeChapter.chapter_image_inner_width = document.getElementById('chapter_image_inner_width').value;
        activeChapter.chapter_image_inner_header = document.getElementById('chapter_image_inner_header').checked ? '1' : '0';
        activeChapter.chapter_image_inner_footer = document.getElementById('chapter_image_inner_footer').checked ? '1' : '0';
        activeChapter.chapter_image_enabled = (
            activeChapter.chapter_image_mode !== 'page_blank'
            || (activeChapter.chapter_image_url && String(activeChapter.chapter_image_url).trim() !== '')
        ) ? '1' : '0';
        
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
    const originalBtnText = btn ? (btn.dataset.defaultLabel ? `<i class="fa-solid fa-check"></i> ${btn.dataset.defaultLabel}` : btn.innerHTML) : '<i class="fa-solid fa-check"></i> Aplicar al Capítulo de Contenido';

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
            } else if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
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
    const layoutWrapper = document.getElementById('chapter_opening_layout_controls');
    const layoutHint = document.getElementById('chapter_opening_layout_hint');
    const imageWrapper = document.getElementById('chapter_opening_image_controls');
    if (!modeField) return;

    const mode = modeField.value;

    if (layoutWrapper) {
        layoutWrapper.classList.toggle('hidden', mode !== 'blank');
    }

    if (layoutHint) {
        layoutHint.classList.toggle('hidden', mode === 'blank');
    }

    if (imageWrapper) {
        imageWrapper.classList.toggle('hidden', mode !== 'image');
    }

    toggleParityImageSizeInputs();
}

function toggleLegacyOpeningCompatibilityNotice(chapter) {
    const notice = document.getElementById('chapter_legacy_opening_notice');
    if (!notice) return;

    const openingMode = chapter && chapter.opening_page_mode ? String(chapter.opening_page_mode) : 'auto';
    const hasLegacyOpening = !!(chapter && chapter.parity_image)
        || ['blank', 'image'].includes(openingMode);
    notice.classList.toggle('hidden', !hasLegacyOpening);
}

function toggleChapterImageSettingsForChapter() {
    const wrapper = document.getElementById('chapter_image_settings_wrapper');
    const modeWrapper = document.getElementById('chapter_image_mode_wrapper');
    const modeField = document.getElementById('chapter_image_mode');
    const uploadWrapper = document.getElementById('chapter_image_upload_wrapper');
    const fullPageNote = document.getElementById('chapter_image_fullpage_note');
    const innerControls = document.getElementById('chapter_image_inner_controls');
    const widthInput = document.getElementById('chapter_image_inner_width');
    const widthLabel = document.getElementById('chapter_image_inner_width_label');

    if (!wrapper || !modeField) return;

    const settings = typeof bookState !== 'undefined' && bookState && bookState.settings
        ? bookState.settings
        : {};
    const bookFlowMode = typeof window.getBookChapterFlowMode === 'function'
        ? window.getBookChapterFlowMode(settings)
        : (settings.book_chapter_flow_mode === 'left' ? 'left' : 'continuous');
    const isLeftFlow = bookFlowMode === 'left';
    wrapper.classList.toggle('hidden', !isLeftFlow);

    if (modeWrapper) {
        modeWrapper.classList.toggle('hidden', !isLeftFlow);
    }
    const mode = modeField.value || 'page_blank';
    const showImageControls = isLeftFlow && (mode === 'image_full_page' || mode === 'image_inner');
    if (uploadWrapper) {
        uploadWrapper.classList.toggle('hidden', !showImageControls);
    }
    if (fullPageNote) {
        fullPageNote.classList.toggle('hidden', !(isLeftFlow && mode === 'image_full_page'));
    }
    if (innerControls) {
        innerControls.classList.toggle('hidden', !(isLeftFlow && mode === 'image_inner'));
    }
    if (widthInput && widthLabel) {
        widthLabel.textContent = `${widthInput.value || '100'}%`;
    }
}

let mediaUploaderChapterImageForChapter;
window.openChapterImageUploaderForChapter = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }

    if (mediaUploaderChapterImageForChapter) {
        mediaUploaderChapterImageForChapter.open();
        return;
    }

    mediaUploaderChapterImageForChapter = wp.media({
        title: 'Seleccionar Imagen de Chapter Image',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });

    mediaUploaderChapterImageForChapter.on('select', function() {
        const attachment = mediaUploaderChapterImageForChapter.state().get('selection').first().toJSON();
        const input = document.getElementById('chapter_image_url');
        if (input) {
            input.value = attachment.url;
        }
    });

    mediaUploaderChapterImageForChapter.open();
}

window.clearChapterImageSelectionForChapter = function() {
    const input = document.getElementById('chapter_image_url');
    if (input) {
        input.value = '';
    }
}

window.syncChapterImageWidthLabelForChapter = function() {
    const input = document.getElementById('chapter_image_inner_width');
    const label = document.getElementById('chapter_image_inner_width_label');
    if (input && label) {
        label.textContent = (input.value || '100') + '%';
    }
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
