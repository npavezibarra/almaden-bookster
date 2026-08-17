// assets/js/editor/editor-chapter-settings-modal.js

function getChapterSettingsActiveChapter() {
    if (typeof bookState === 'undefined' || !bookState || !Array.isArray(bookState.chapters)) {
        return null;
    }

    return bookState.chapters.find(c => c.id === bookState.activeChapterId) || null;
}

function openChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;

    const activeChapter = getChapterSettingsActiveChapter();
    if (!activeChapter) return;

    const isToc = activeChapter.is_toc === '1';
    const isCredits = activeChapter.is_credits === '1';

    applyChapterSettingsModalLabels(activeChapter);

    const normalContainer = document.getElementById('normal-chapter-settings');
    const tocContainer = document.getElementById('toc-chapter-settings');
    const creditsContainer = document.getElementById('credits-chapter-settings');

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

        const fontSelect = document.getElementById('chapter_toc_font_family');
        const titleFontSelect = document.getElementById('chapter_toc_title_font_family');

        if (fontSelect && fontSelect.options.length === 0 && bookState.installedFonts) {
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
        const tocSeparateOpeningContentEl = document.getElementById('chapter_toc_separate_opening_content');
        if (tocSeparateOpeningContentEl) {
            tocSeparateOpeningContentEl.value = activeChapter.toc_separate_opening_content ?? '';
        }
        document.getElementById('chapter_toc_hide_header').checked = (activeChapter.toc_hide_header !== '0');
        document.getElementById('chapter_toc_hide_page_numbers').checked = (activeChapter.toc_hide_page_numbers !== '0');
        document.getElementById('chapter_toc_item_align').value = activeChapter.toc_item_align || 'left';
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
        const settings = bookState.settings || {};
        const creditFontFamilyDefault = settings.font_family_content || '';
        const creditAlignDefault = ['left', 'center', 'right'].includes(String(settings.content_text_align || '').toLowerCase())
            ? String(settings.content_text_align).toLowerCase()
            : 'center';
        const creditFontSizeDefault = settings.font_size_content || '';
        const creditFontWeightDefault = settings.font_weight_content || '';
        const creditVerticalAlignDefault = ['top', 'center', 'bottom'].includes(String(settings.credits_vertical_align || settings.vertical_align || '').toLowerCase())
            ? String(settings.credits_vertical_align || settings.vertical_align).toLowerCase()
            : 'bottom';

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
        document.getElementById('chapter_credits_vertical_align').value = ['top', 'center', 'bottom'].includes(String(activeChapter.credits_vertical_align || '').toLowerCase())
            ? String(activeChapter.credits_vertical_align).toLowerCase()
            : creditVerticalAlignDefault;
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

        if (typeof switchChapterTab === 'function') {
            switchChapterTab('chapter-tab-structure');
        }

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

        document.getElementById('chapter_opening_page_mode').value = derivedOpeningPageMode;
        document.getElementById('chapter_opening_blank_intentional').checked = activeChapter.opening_blank_intentional === '1';
        document.getElementById('chapter_opening_block_enabled').checked = derivedOpeningBlockEnabled === '1';
        document.getElementById('chapter_opening_block_horizontal_align').value = derivedOpeningBlockHorizontalAlign;
        document.getElementById('chapter_opening_block_vertical_align').value = derivedOpeningBlockVerticalAlign;
        document.getElementById('chapter_hide_opening').checked = activeChapter.hide_opening === '1';
        document.getElementById('chapter_hide_title').checked = activeChapter.hide_title === '1';
        document.getElementById('chapter_exclude_from_numbering').checked = activeChapter.exclude_from_numbering === '1';
        document.getElementById('chapter_blank_before').value = activeChapter.chapter_blank_before ?? '0';
        document.getElementById('chapter_blank_after').value = activeChapter.chapter_blank_after ?? '0';
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
            activeChapter.chapter_image_enabled === '1'
            || (activeChapter.chapter_image_mode && activeChapter.chapter_image_mode !== 'page_blank')
            || (activeChapter.chapter_image_url && String(activeChapter.chapter_image_url).trim() !== '')
            || legacyChapterImageDefaults.chapter_image_enabled === '1'
            || (legacyChapterImageDefaults.chapter_image_mode && legacyChapterImageDefaults.chapter_image_mode !== 'page_blank')
            || (legacyChapterImageDefaults.chapter_image_url && String(legacyChapterImageDefaults.chapter_image_url).trim() !== '')
        );
        const derivedChapterImageEnabled = activeChapter.chapter_image_enabled === '1'
            || legacyChapterImageDefaults.chapter_image_enabled === '1'
            || !!hasLegacyChapterImage;
        document.getElementById('chapter_opening_separate_content').value = activeChapter.opening_separate_content ?? '';
        document.getElementById('chapter_image_enabled').checked = derivedChapterImageEnabled;
        document.getElementById('chapter_image_mode').value = activeChapter.chapter_image_mode || legacyChapterImageDefaults.chapter_image_mode || 'page_blank';
        document.getElementById('chapter_image_url').value = activeChapter.chapter_image_url || legacyChapterImageDefaults.chapter_image_url || '';
        document.getElementById('chapter_image_inner_width').value = activeChapter.chapter_image_inner_width || legacyChapterImageDefaults.chapter_image_inner_width || '100';

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
    toggleChapterImageSettingsForChapter();

    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        modal.querySelector('.transform').classList.add('scale-100');
    }, 10);
}

function closeChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;

    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-100');
    modal.querySelector('.transform').classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

async function saveChapterSettings() {
    const activeChapter = getChapterSettingsActiveChapter();
    if (!activeChapter) {
        closeChapterSettingsModal();
        return;
    }

    const isToc = activeChapter.is_toc === '1';
    const isCredits = activeChapter.is_credits === '1';

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
        const tocSeparateOpeningContentEl = document.getElementById('chapter_toc_separate_opening_content');
        if (tocSeparateOpeningContentEl) {
            activeChapter.toc_separate_opening_content = tocSeparateOpeningContentEl.value;
        }
        activeChapter.toc_hide_header = document.getElementById('chapter_toc_hide_header').checked ? '1' : '0';
        activeChapter.toc_hide_page_numbers = document.getElementById('chapter_toc_hide_page_numbers').checked ? '1' : '0';
        activeChapter.toc_item_align = document.getElementById('chapter_toc_item_align').value;
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
        activeChapter.credits_align = document.getElementById('chapter_credits_align').value;
        activeChapter.credits_vertical_align = ['top', 'center', 'bottom'].includes(String(document.getElementById('chapter_credits_vertical_align').value || '').toLowerCase())
            ? String(document.getElementById('chapter_credits_vertical_align').value).toLowerCase()
            : '';
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
        // Credits use their dedicated controls. Keep the legacy chapter flags in
        // sync so an older hide_footer value cannot override the visible toggle.
        activeChapter.hide_header = activeChapter.credits_hide_header;
        activeChapter.hide_footer = activeChapter.credits_hide_page_number;
        activeChapter.hide_all_headers_footers = (
            activeChapter.hide_header === '1' && activeChapter.hide_footer === '1'
        ) ? '1' : '0';
        activeChapter.credits_margin_top = cleanFloat('chapter_credits_margin_top');
        activeChapter.credits_margin_bottom = cleanFloat('chapter_credits_margin_bottom');
    } else {
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
        activeChapter.chapter_blank_before = Math.min(999, Math.max(0, parseInt(document.getElementById('chapter_blank_before').value, 10) || 0));
        activeChapter.chapter_blank_after = Math.min(999, Math.max(0, parseInt(document.getElementById('chapter_blank_after').value, 10) || 0));
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
        activeChapter.chapter_image_enabled = document.getElementById('chapter_image_enabled').checked ? '1' : '0';
        activeChapter.chapter_image_mode = document.getElementById('chapter_image_mode').value;
        activeChapter.chapter_image_url = document.getElementById('chapter_image_url').value;
        activeChapter.chapter_image_inner_width = document.getElementById('chapter_image_inner_width').value;
		activeChapter.chapter_image_inner_header = '0';
		activeChapter.chapter_image_inner_footer = '0';

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

    const saveSucceeded = typeof saveStateToLocalStorage === 'function'
        ? await saveStateToLocalStorage(true)
        : true;

    if (!saveSucceeded) {
        if (btn) {
            btn.innerHTML = originalBtnText;
            btn.disabled = false;
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
            btn.classList.replace('bg-emerald-600', 'bg-black');
            btn.classList.replace('hover:bg-emerald-700', 'hover:bg-neutral-800');
        }

        if (typeof showToast === 'function') {
            showToast("No se pudieron guardar los cambios del capítulo.", "fa-solid fa-triangle-exclamation");
        }
        return;
    }

    closeChapterSettingsModal();

    if (btn) {
        btn.innerHTML = originalBtnText;
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btn.classList.replace('bg-emerald-600', 'bg-black');
        btn.classList.replace('hover:bg-emerald-700', 'hover:bg-neutral-800');
    }

    if (typeof applyDynamicPDFStyles === 'function') {
        applyDynamicPDFStyles();
    } else if (typeof refreshEditorDisplay === 'function') {
        refreshEditorDisplay(false);
    }

    if (typeof showToast === 'function') {
        showToast("Configuración del capítulo actualizada.", "fa-solid fa-check");
    }
}
