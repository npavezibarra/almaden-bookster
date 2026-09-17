// assets/js/editor/editor-chapter-settings-controls.js

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
    const hideOpeningField = document.getElementById('chapter_hide_opening');
    const separateContentWrapper = document.getElementById('chapter_opening_separate_content_wrapper');
    const layoutWrapper = document.getElementById('chapter_opening_layout_controls');
    const imageWrapper = document.getElementById('chapter_opening_image_controls');
    if (!modeField) return;

    const mode = modeField.value;
    const openingIsHidden = !!(hideOpeningField && hideOpeningField.checked);

    if (separateContentWrapper) {
        separateContentWrapper.classList.toggle('hidden', openingIsHidden);
    }

    if (layoutWrapper) {
        layoutWrapper.classList.toggle('hidden', mode !== 'blank');
    }

    // The legacy image controls are data-only now. Keep stale cached markup hidden
    // so the chapter modal always exposes a single image configuration flow.
    if (imageWrapper) {
        imageWrapper.classList.add('hidden');
    }
}

function toggleChapterSubtitleControls() {
    const checkbox = document.getElementById('chapter_subtitle_show');
    const controls = document.getElementById('chapter_subtitle_controls');
    if (!checkbox || !controls) return;
    controls.classList.toggle('hidden', !checkbox.checked);
}

function toggleChapterImageSettingsForChapter() {
    const wrapper = document.getElementById('chapter_image_settings_wrapper');
    const content = document.getElementById('chapter_image_settings_content');
    const overrideField = document.getElementById('chapter_image_override');
    const modeWrapper = document.getElementById('chapter_image_mode_wrapper');
    const modeField = document.getElementById('chapter_image_mode');
    const uploadWrapper = document.getElementById('chapter_image_upload_wrapper');
    const fullPageNote = document.getElementById('chapter_image_fullpage_note');
    const innerControls = document.getElementById('chapter_image_inner_controls');
    const widthInput = document.getElementById('chapter_image_inner_width');
    const widthLabel = document.getElementById('chapter_image_inner_width_label');

    if (!wrapper || !modeField) return;

    wrapper.classList.remove('hidden');

    const bookDefaultEnabled = typeof bookState !== 'undefined'
        && bookState.settings
        && (bookState.settings.chapter_image_default === '1' || bookState.settings.chapter_image_default === 1);
    const imageEnabled = !!(overrideField && (overrideField.value === '1' || (overrideField.value === '' && bookDefaultEnabled)));
    if (content) {
        content.classList.toggle('hidden', !imageEnabled);
    }
    if (modeWrapper) {
        modeWrapper.classList.toggle('hidden', !imageEnabled);
    }
    const mode = modeField.value || 'page_blank';
    const showImageControls = imageEnabled && (mode === 'image_full_page' || mode === 'image_inner' || mode === 'page_blank');
    if (uploadWrapper) {
        uploadWrapper.classList.toggle('hidden', !showImageControls);
    }
    if (fullPageNote) {
        fullPageNote.classList.toggle('hidden', !(imageEnabled && mode === 'image_full_page'));
    }
    if (innerControls) {
        innerControls.classList.toggle('hidden', !(imageEnabled && mode === 'image_inner'));
    }
    if (widthInput && widthLabel) {
        widthLabel.textContent = `${widthInput.value || '100'}%`;
    }
}

let mediaUploaderChapterImageForChapter;
function openChapterImageUploaderForChapter() {
    const applySelection = (attachment) => {
        if (!attachment) return;
        const input = document.getElementById('chapter_image_url');
        if (input) {
            input.value = attachment.originalUrl || attachment.originalImageURL || attachment.url || '';
        }
    };

    if (window.AlmadenBooksterMediaPicker && bookState && bookState.bookId && bookState.mediaPickerNonce) {
        window.AlmadenBooksterMediaPicker.open({
            bookId: bookState.bookId,
            ajaxUrl: bookState.ajaxUrl,
            nonce: bookState.mediaPickerNonce,
            title: 'Seleccionar Imagen de Chapter Image',
            buttonText: 'Usar esta imagen'
        }).then(applySelection).catch(() => {});
        return;
    }

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
        applySelection(attachment);
    });

    mediaUploaderChapterImageForChapter.open();
}

function clearChapterImageSelectionForChapter() {
    const input = document.getElementById('chapter_image_url');
    if (input) {
        input.value = '';
    }
}

function syncChapterImageWidthLabelForChapter() {
    const input = document.getElementById('chapter_image_inner_width');
    const label = document.getElementById('chapter_image_inner_width_label');
    if (input && label) {
        label.textContent = (input.value || '100') + '%';
    }
}

function switchChapterTab(tabId) {
    const contents = document.querySelectorAll('.chapter-tab-content');
    contents.forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    const btns = document.querySelectorAll('.chapter-tab-btn');
    btns.forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });

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
    const contents = document.querySelectorAll('.toc-tab-content');
    contents.forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    const btns = document.querySelectorAll('.toc-tab-btn');
    btns.forEach(btn => {
        btn.classList.remove('border-black', 'text-black', 'dark:border-white', 'dark:text-white');
        btn.classList.add('border-transparent', 'text-[var(--text-muted)]');
    });

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

function toggleTocEnumerateControls() {
    const enumField = document.getElementById('chapter_toc_enumerate');
    const numberBox = document.getElementById('chapter_toc_number_config_box');
    if (!enumField || !numberBox) return;

    const val = String(enumField.value || 'none').trim();
    if (val === 'none' || !val) {
        numberBox.classList.add('hidden');
    } else {
        numberBox.classList.remove('hidden');
    }
}

function switchTocLevelTab(levelKey) {
    const panels = document.querySelectorAll('.toc-lvl-panel');
    panels.forEach(panel => panel.classList.add('hidden'));

    const btns = document.querySelectorAll('.toc-lvl-btn');
    btns.forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-neutral-700', 'shadow-sm', 'text-black', 'dark:text-white');
        btn.classList.add('text-[var(--text-muted)]');
    });

    const activePanel = document.getElementById('toc-lvl-panel-' + levelKey);
    if (activePanel) {
        activePanel.classList.remove('hidden');
    }

    const activeBtn = document.getElementById('btn-toc-lvl-' + levelKey);
    if (activeBtn) {
        activeBtn.classList.remove('text-[var(--text-muted)]');
        activeBtn.classList.add('bg-white', 'dark:bg-neutral-700', 'shadow-sm', 'text-black', 'dark:text-white');
    }
}

function hydrateTocLevelSettings(activeChapter) {
    if (!activeChapter) return;
    let tocLevels = {};
    if (activeChapter.toc_levels) {
        if (typeof activeChapter.toc_levels === 'string') {
            try {
                tocLevels = JSON.parse(activeChapter.toc_levels) || {};
            } catch (e) {
                tocLevels = {};
            }
        } else if (typeof activeChapter.toc_levels === 'object') {
            tocLevels = activeChapter.toc_levels;
        }
    }

    ['chapter', 'h1', 'h2', 'h3', 'h4'].forEach(lvl => {
        const lvlData = (tocLevels && typeof tocLevels === 'object' && tocLevels[lvl]) ? tocLevels[lvl] : {};
        const setVal = (suffix, val) => {
            const el = document.getElementById(`chapter_toc_lvl_${lvl}_${suffix}`);
            if (el) el.value = val;
        };
        setVal('transform', lvlData.text_transform || 'inherit');
        setVal('align', lvlData.align || 'inherit');
        setVal('font_size', lvlData.font_size !== undefined && lvlData.font_size !== null ? lvlData.font_size : '');
        setVal('weight', lvlData.font_weight || 'inherit');
        setVal('style', lvlData.font_style || 'inherit');
        setVal('line_height', lvlData.line_height !== undefined && lvlData.line_height !== null ? lvlData.line_height : '');
        setVal('letter_spacing', lvlData.letter_spacing !== undefined && lvlData.letter_spacing !== null ? lvlData.letter_spacing : '');
        setVal('indent', lvlData.indent !== undefined && lvlData.indent !== null ? lvlData.indent : '');
        setVal('hyphenate', lvlData.hyphenate !== undefined && lvlData.hyphenate !== null ? String(lvlData.hyphenate) : 'inherit');
    });

    switchTocLevelTab('chapter');
}

function collectTocLevelSettings() {
    const tocLevels = {};
    ['chapter', 'h1', 'h2', 'h3', 'h4'].forEach(lvl => {
        const transform = document.getElementById(`chapter_toc_lvl_${lvl}_transform`)?.value || 'inherit';
        const align = document.getElementById(`chapter_toc_lvl_${lvl}_align`)?.value || 'inherit';
        const fontSize = document.getElementById(`chapter_toc_lvl_${lvl}_font_size`)?.value || '';
        const weight = document.getElementById(`chapter_toc_lvl_${lvl}_weight`)?.value || 'inherit';
        const style = document.getElementById(`chapter_toc_lvl_${lvl}_style`)?.value || 'inherit';
        const lineHeight = document.getElementById(`chapter_toc_lvl_${lvl}_line_height`)?.value || '';
        const letterSpacing = document.getElementById(`chapter_toc_lvl_${lvl}_letter_spacing`)?.value || '';
        const indent = document.getElementById(`chapter_toc_lvl_${lvl}_indent`)?.value || '';
        const hyphenate = document.getElementById(`chapter_toc_lvl_${lvl}_hyphenate`)?.value || 'inherit';

        const lvlObj = {};
        if (transform !== 'inherit') lvlObj.text_transform = transform;
        if (align !== 'inherit') lvlObj.align = align;
        if (fontSize !== '') lvlObj.font_size = parseFloat(fontSize);
        if (weight !== 'inherit') lvlObj.font_weight = weight;
        if (style !== 'inherit') lvlObj.font_style = style;
        if (lineHeight !== '') lvlObj.line_height = parseFloat(lineHeight);
        if (letterSpacing !== '') lvlObj.letter_spacing = parseFloat(letterSpacing);
        if (indent !== '') lvlObj.indent = parseFloat(indent);
        if (hyphenate !== 'inherit') lvlObj.hyphenate = parseInt(hyphenate, 10);

        if (Object.keys(lvlObj).length > 0) {
            tocLevels[lvl] = lvlObj;
        }
    });
    return Object.keys(tocLevels).length > 0 ? tocLevels : null;
}
