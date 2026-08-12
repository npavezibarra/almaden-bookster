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
