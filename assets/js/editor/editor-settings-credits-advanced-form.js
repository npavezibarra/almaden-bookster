function creditsReadSectionStyleValue(root, sectionId) {
    const wrap = root ? root.querySelector(`[data-credits-style-section="${sectionId}"]`) : null;
    const field = (suffix) => (wrap ? wrap.querySelector(`[data-credits-field="section_${sectionId}_${suffix}"]`) : null);
    const numberValue = (suffix, min, max) => {
        const input = field(suffix);
        const raw = input ? String(input.value || '').trim() : '';
        if (!raw) return '';
        const parsed = parseFloat(raw);
        if (!Number.isFinite(parsed)) return '';
        return Math.min(Math.max(parsed, min), max);
    };
    const align = String(field('text_align') && field('text_align').value || '').trim().toLowerCase();

    return {
        show_separator: field('show_separator') && field('show_separator').checked ? 1 : 0,
        font_family: String(field('font_family') && field('font_family').value || '').trim(),
        font_size: numberValue('font_size', 8, 72),
        letter_spacing: numberValue('letter_spacing', -10, 20),
        line_height: numberValue('line_height', 0.5, 3),
        text_align: ['left', 'center', 'right'].includes(align) ? align : '',
        item_gap_px: numberValue('item_gap_px', 0, 80),
    };
}

function creditsReadCollaboratorsStylesValue(root) {
    const field = (name) => (root ? root.querySelector(`[data-credits-field="${name}"]`) : null);
    const textStyle = (prefix) => {
        const fontWeight = String(field(`${prefix}_font_weight`) && field(`${prefix}_font_weight`).value || '400');
        const fontSize = parseInt(field(`${prefix}_font_size`) && field(`${prefix}_font_size`).value || '0', 10) || '';
        const lineHeightRaw = String(field(`${prefix}_line_height`) && field(`${prefix}_line_height`).value || '').trim();
        const lineHeight = lineHeightRaw ? parseFloat(lineHeightRaw.replace(',', '.')) : '';
        return {
            font_family: String(field(`${prefix}_font_family`) && field(`${prefix}_font_family`).value || '').trim(),
            font_size: fontSize,
            font_weight: ['300', '400', '500', '600', '700', '800'].includes(fontWeight) ? fontWeight : '400',
            line_height: Number.isFinite(lineHeight) ? Math.min(Math.max(lineHeight, 0.5), 3) : '',
        };
    };
    const width = parseInt(field('collaborators_image_max_width') && field('collaborators_image_max_width').value || '0', 10);

    return {
        title: textStyle('collaborators_title'),
        item: textStyle('collaborators_item'),
        image_max_width: Number.isFinite(width) ? Math.min(Math.max(width, 60), 140) : 96,
    };
}

function creditsReadLogoValue(root) {
    const field = (name) => (root ? root.querySelector(`[data-credits-field="${name}"]`) : null);
    const source = String(field('logo_source') && field('logo_source').value || 'image').trim().toLowerCase() === 'cover_logo' ? 'cover_logo' : 'image';
    const logoUrl = String(field('logo_url') && field('logo_url').value || '').trim();
    const position = creditsNormalizeLogoPosition(field('logo_position') ? field('logo_position').value : 'center');
    const sizePx = creditsNormalizeLogoSize(field('logo_size_px') ? field('logo_size_px').value : 120);
    const showAuthorName = !!(field('show_author_name') && field('show_author_name').checked);
    const authorFontFamily = String(field('author_font_family') && field('author_font_family').value || '').trim();
    const authorFontSize = parseInt(field('author_font_size') && field('author_font_size').value || '16', 10) || 16;
    const authorFontWeight = String(field('author_font_weight') && field('author_font_weight').value || '').trim();
    const authorLetterSpacing = String(field('author_letter_spacing') && field('author_letter_spacing').value || '').trim();
    const authorGapRaw = field('author_gap_px') ? String(field('author_gap_px').value || '').trim() : '';
    const authorGapPx = authorGapRaw === '' ? 10 : Math.max(0, Math.min(100, parseInt(authorGapRaw, 10) || 0));
    const authorTextTransform = creditsNormalizeLogoTextTransform(field('author_text_transform') ? field('author_text_transform').value : 'none');
    const hasMeaningfulData = source === 'cover_logo' || logoUrl || showAuthorName || authorFontFamily || authorFontSize !== 16 || authorFontWeight || authorLetterSpacing || authorGapPx !== 10 || authorTextTransform !== 'none';

    return hasMeaningfulData ? [{
        logo_source: source,
        logo_url: logoUrl,
        position,
        size_px: sizePx,
        show_author_name: showAuthorName ? 1 : 0,
        author_font_family: authorFontFamily,
        author_font_size: authorFontSize,
        author_font_weight: authorFontWeight,
        author_letter_spacing: authorLetterSpacing,
        author_gap_px: authorGapPx,
        author_text_transform: authorTextTransform,
    }] : [];
}

function creditsReadAdvancedCreditsConfig(root) {
    const sectionIds = creditsGetCreditsSectionDefinitions().map((item) => item.id);
    const tabIds = root
        ? Array.from(root.querySelectorAll('[data-credits-tab]'))
            .map((button) => String(button.getAttribute('data-credits-tab') || '').trim())
            .filter(Boolean)
        : [];
    const order = [];
    tabIds.concat(sectionIds).forEach((sectionId) => {
        if (sectionIds.includes(sectionId) && !order.includes(sectionId)) {
            order.push(sectionId);
        }
    });

    const sectionStyles = {};
    sectionIds.forEach((sectionId) => {
        sectionStyles[sectionId] = creditsReadSectionStyleValue(root, sectionId);
    });

    return {
        collaborators_visible: creditsGetFieldChecked(root, '[data-credits-field="collaborators_visible"]'),
        collaborators_title: String(root && root.querySelector('[data-credits-field="collaborators_title"]') ? root.querySelector('[data-credits-field="collaborators_title"]').value : '').trim(),
        collaborators_styles: creditsReadCollaboratorsStylesValue(root),
        section_order: order.length ? order : sectionIds,
        section_styles: sectionStyles,
        logos: creditsReadLogoValue(root),
    };
}

function creditsUpdateAdvancedLogoPreview(root) {
    if (!root) return;
    const sourceField = root.querySelector('[data-credits-field="logo_source"]');
    const logoUrlField = root.querySelector('[data-credits-field="logo_url"]');
    const imagePreview = root.querySelector('[data-credits-logo-preview]');
    const placeholder = root.querySelector('[data-credits-logo-placeholder]');
    const uploadButton = root.querySelector('[data-credits-action="choose-logo-image"]');
    const authorControls = root.querySelector('[data-credits-logo-author-controls]');
    const authorToggle = root.querySelector('[data-credits-field="show_author_name"]');
    const sizeField = root.querySelector('[data-credits-field="logo_size_px"]');
    const sizeLabel = root.querySelector('[data-credits-logo-size-label]');
    const source = String(sourceField ? sourceField.value : 'image').trim().toLowerCase() === 'cover_logo' ? 'cover_logo' : 'image';
    const coverUrl = creditsGetBookCoverLogoUrlFromEditorState();
    const uploadedUrl = String(logoUrlField ? logoUrlField.value : '').trim();
    const activeUrl = source === 'cover_logo' ? coverUrl : uploadedUrl;

    if (imagePreview) {
        imagePreview.src = activeUrl || 'data:image/gif;base64,R0lGODlhAQABAAAAACw=';
        imagePreview.classList.toggle('hidden', !activeUrl);
    }
    if (placeholder) {
        placeholder.classList.toggle('hidden', !!activeUrl);
        placeholder.textContent = source === 'cover_logo' && !coverUrl
            ? 'No se encontró una capa LOGO en la portada.'
            : 'Sin imagen seleccionada';
    }
    if (uploadButton) {
        uploadButton.disabled = source === 'cover_logo';
        uploadButton.classList.toggle('opacity-50', source === 'cover_logo');
        uploadButton.classList.toggle('cursor-not-allowed', source === 'cover_logo');
    }
    if (authorControls && authorToggle) {
        authorControls.classList.toggle('hidden', !authorToggle.checked);
    }
    if (sizeLabel && sizeField) {
        sizeLabel.textContent = `${creditsNormalizeLogoSize(sizeField.value || 120)} px`;
    }

    root.querySelectorAll('[data-credits-logo-source-option]').forEach((button) => {
        const isActive = button.getAttribute('data-credits-logo-source-option') === source;
        button.classList.toggle('bg-black', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('text-[var(--text-main)]', !isActive);
    });
}

function creditsUpdateCollaboratorsVisibilityState(root) {
    if (!root) return;
    const toggle = root.querySelector('[data-credits-field="collaborators_visible"]');
    const body = root.querySelector('[data-credits-collaborators-body]');
    const addButton = root.querySelector('[data-credits-action="add-collaborator"]');
    if (!toggle || !body) return;

    const enabled = !!toggle.checked;
    body.classList.toggle('pointer-events-none', !enabled);
    body.classList.toggle('opacity-50', !enabled);
    body.querySelectorAll('input, select, textarea, button').forEach((element) => {
        if (element === toggle) return;
        element.disabled = !enabled;
    });

    if (addButton) {
        addButton.disabled = !enabled;
        addButton.classList.toggle('opacity-50', !enabled);
        addButton.classList.toggle('cursor-not-allowed', !enabled);
    }
}

function creditsBindCreditsAdvancedEvents(root) {
    if (!root || root.dataset.creditsAdvancedBound === '1') return;

    root.addEventListener('click', (event) => {
        const sourceButton = event.target.closest('[data-credits-logo-source-option]');
        if (sourceButton && root.contains(sourceButton)) {
            event.preventDefault();
            const option = sourceButton.getAttribute('data-credits-logo-source-option') || 'image';
            const sourceField = root.querySelector('[data-credits-field="logo_source"]');
            if (sourceField) {
                sourceField.value = option === 'cover_logo' ? 'cover_logo' : 'image';
            }
            creditsUpdateAdvancedLogoPreview(root);
            creditsSyncStateFromForm();
            return;
        }

        const chooseLogoButton = event.target.closest('[data-credits-action="choose-logo-image"]');
        if (chooseLogoButton && root.contains(chooseLogoButton)) {
            event.preventDefault();
            if (chooseLogoButton.disabled) return;
            const sourceField = root.querySelector('[data-credits-field="logo_source"]');
            if (sourceField) {
                sourceField.value = 'image';
            }
            const targetInput = root.querySelector('[data-credits-field="logo_url"]');
            const mediaFrame = creditsEnsureMediaFrame((url) => {
                if (targetInput) targetInput.value = url;
                creditsUpdateAdvancedLogoPreview(root);
                creditsSyncStateFromForm();
            });
            if (mediaFrame) mediaFrame.open();
            return;
        }
    });

    root.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLElement)) return;
        if (!input.matches('[data-credits-field="logo_url"], [data-credits-field="logo_source"], [data-credits-field="show_author_name"], [data-credits-field="author_font_family"], [data-credits-field="author_font_size"], [data-credits-field="author_font_weight"], [data-credits-field="author_letter_spacing"], [data-credits-field="author_gap_px"], [data-credits-field="author_text_transform"], [data-credits-field="logo_position"], [data-credits-field="logo_size_px"], [data-credits-field="collaborators_visible"]')) {
            return;
        }
        if (input.matches('[data-credits-field="collaborators_visible"]')) {
            creditsUpdateCollaboratorsVisibilityState(root);
        }
        creditsUpdateAdvancedLogoPreview(root);
    });

    root.addEventListener('dragstart', (event) => {
        const tab = event.target.closest('[data-credits-tab]');
        if (!tab || !root.contains(tab)) return;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', tab.getAttribute('data-credits-tab') || '');
        tab.classList.add('opacity-50');
    });

    root.addEventListener('dragover', (event) => {
        const tab = event.target.closest('[data-credits-tab]');
        if (!tab || !root.contains(tab)) return;
        event.preventDefault();
        tab.classList.add('ring-2', 'ring-black/15');
    });

    root.addEventListener('dragleave', (event) => {
        const tab = event.target.closest('[data-credits-tab]');
        if (!tab || !root.contains(tab)) return;
        tab.classList.remove('ring-2', 'ring-black/15');
    });

    root.addEventListener('drop', (event) => {
        const targetTab = event.target.closest('[data-credits-tab]');
        if (!targetTab || !root.contains(targetTab)) return;
        event.preventDefault();
        const tabsWrap = root.querySelector('[data-credits-tabs]');
        const draggedId = event.dataTransfer.getData('text/plain');
        const draggedTab = tabsWrap ? tabsWrap.querySelector(`[data-credits-tab="${draggedId}"]`) : null;
        if (tabsWrap && draggedTab && draggedTab !== targetTab) {
            tabsWrap.insertBefore(draggedTab, targetTab);
            creditsSyncStateFromForm();
        }
        root.querySelectorAll('[data-credits-tab]').forEach((button) => {
            button.classList.remove('ring-2', 'ring-black/15');
        });
    });

    root.addEventListener('dragend', (event) => {
        const tab = event.target.closest('[data-credits-tab]');
        if (tab) tab.classList.remove('opacity-50');
        root.querySelectorAll('[data-credits-tab]').forEach((button) => {
            button.classList.remove('ring-2', 'ring-black/15');
        });
    });

    root.dataset.creditsAdvancedBound = '1';
    creditsUpdateAdvancedLogoPreview(root);
    creditsUpdateCollaboratorsVisibilityState(root);
}
