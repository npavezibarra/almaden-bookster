// Per-page style editor for Typst page previews.
(function () {
    let modalBound = false;
    let mediaFrame = null;

    function normalizeId(value) {
        return String(value || '').toLowerCase().replace(/[^a-z0-9_-]/g, '');
    }

    function normalizeColor(value, fallback = '#111111') {
        const raw = String(value || '').trim().toLowerCase();
        return /^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/.test(raw) ? raw : fallback;
    }

    function getSelectedPageNumber() {
        return Number(window.almadenPageTemplateUI?.getSelectedPageNumber?.() || 0);
    }

    function getStyles() {
        const settings = window.bookState?.settings || {};
        return Array.isArray(settings.page_styles) ? settings.page_styles : [];
    }

    function getStyleInstanceId(style) {
        return normalizeId(style?.instance_id || style?.id || '');
    }

    function createInstanceId() {
        const uuid = typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
            ? crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
        return `sty-${normalizeId(uuid)}`;
    }

    function cloneStyles(styles) {
        return JSON.parse(JSON.stringify(Array.isArray(styles) ? styles : []));
    }

    function getStyleForPage(pageNumber) {
        const target = Number(pageNumber) || 0;
        return getStyles().find(style => Number(style?.resolved_page || style?.page_number) === target) || null;
    }

    function getStyleAtSelectedPage() {
        return getStyleForPage(getSelectedPageNumber());
    }

    function getAnchorForPage(pageNumber) {
        return window.almadenPageTemplateState?.getAnchorForPage?.(pageNumber) || { flow_id: '' };
    }

    function getDefaultStyle() {
        return {
            background: {
                type: 'color',
                color: '#ffffff',
                gradient: {
                    kind: 'linear',
                    angle: 135,
                    stops: [
                        { color: '#ffffff', position: 0 },
                        { color: '#f3f4f6', position: 100 }
                    ]
                },
                image: {
                    attachment_id: 0,
                    url: '',
                    preview_url: '',
                    original_url: '',
                    fit: 'cover',
                    position: 'center'
                },
                overlay: {
                    color: '#000000',
                    opacity: 0.35
                }
            },
            text_colors: {
                content: '#111111',
                header: '#111111',
                footer: '#111111',
                opening: '#111111'
            }
        };
    }

    function normalizeStyleData(style) {
        const defaults = getDefaultStyle();
        const source = style && typeof style === 'object' ? style : {};
        const background = source.background && typeof source.background === 'object' ? source.background : {};
        const gradient = background.gradient && typeof background.gradient === 'object' ? background.gradient : {};
        const stops = Array.isArray(gradient.stops) && gradient.stops.length
            ? gradient.stops.slice(0, 2)
            : defaults.background.gradient.stops;
        const image = background.image && typeof background.image === 'object' ? background.image : {};
        const overlay = background.overlay && typeof background.overlay === 'object' ? background.overlay : {};
        const textColors = source.text_colors && typeof source.text_colors === 'object' ? source.text_colors : {};

        return {
            background: {
                type: ['color', 'gradient', 'image'].includes(String(background.type || 'color')) ? String(background.type) : 'color',
                color: normalizeColor(background.color, defaults.background.color),
                gradient: {
                    kind: 'linear',
                    angle: Number.isFinite(Number(gradient.angle)) ? Math.max(0, Math.min(360, Number(gradient.angle))) : defaults.background.gradient.angle,
                    stops: [
                        {
                            color: normalizeColor(stops[0]?.color, defaults.background.gradient.stops[0].color),
                            position: Number.isFinite(Number(stops[0]?.position)) ? Math.max(0, Math.min(100, Number(stops[0]?.position))) : 0
                        },
                        {
                            color: normalizeColor(stops[1]?.color, defaults.background.gradient.stops[1].color),
                            position: Number.isFinite(Number(stops[1]?.position)) ? Math.max(0, Math.min(100, Number(stops[1]?.position))) : 100
                        }
                    ]
                },
                image: {
                    attachment_id: Number(image.attachment_id) || 0,
                    url: String(image.url || ''),
                    preview_url: String(image.preview_url || ''),
                    original_url: String(image.original_url || ''),
                    fit: ['cover', 'contain', 'fill', 'none'].includes(String(image.fit || 'cover')) ? String(image.fit) : 'cover',
                    position: String(image.position || 'center')
                },
                overlay: {
                    color: normalizeColor(overlay.color, defaults.background.overlay.color),
                    opacity: Number.isFinite(Number(overlay.opacity)) ? Math.max(0, Math.min(1, Number(overlay.opacity))) : defaults.background.overlay.opacity
                }
            },
            text_colors: {
                content: normalizeColor(textColors.content, defaults.text_colors.content),
                header: normalizeColor(textColors.header, defaults.text_colors.header),
                footer: normalizeColor(textColors.footer, defaults.text_colors.footer),
                opening: normalizeColor(textColors.opening, defaults.text_colors.opening)
            }
        };
    }

    function getFormStyleData() {
        const backgroundType = document.getElementById('page-style-background-type')?.value || 'color';
        const backgroundColor = normalizeColor(document.getElementById('page-style-background-color')?.value, '#ffffff');
        const gradientFrom = normalizeColor(document.getElementById('page-style-gradient-from')?.value, '#ffffff');
        const gradientTo = normalizeColor(document.getElementById('page-style-gradient-to')?.value, '#f3f4f6');
        const gradientAngle = Number(document.getElementById('page-style-gradient-angle')?.value || 135);
        const overlayColor = normalizeColor(document.getElementById('page-style-background-overlay-color')?.value, '#000000');
        const overlayOpacity = Math.max(0, Math.min(1, Number(document.getElementById('page-style-background-overlay-opacity')?.value || 0.35)));
        const imagePreview = document.getElementById('page-style-background-image-preview');
        const imageLabel = document.getElementById('page-style-background-image-label');
        const imageUrl = imagePreview?.dataset.originalUrl || '';
        const imagePreviewUrl = imagePreview?.dataset.previewUrl || '';
        const imageAttachmentId = Number(imagePreview?.dataset.attachmentId || 0);

        return {
            background: {
                type: backgroundType,
                color: backgroundColor,
                gradient: {
                    kind: 'linear',
                    angle: Number.isFinite(gradientAngle) ? Math.max(0, Math.min(360, gradientAngle)) : 135,
                    stops: [
                        { color: gradientFrom, position: 0 },
                        { color: gradientTo, position: 100 }
                    ]
                },
                image: {
                    attachment_id: imageAttachmentId,
                    url: imageUrl,
                    preview_url: imagePreviewUrl,
                    original_url: imageUrl,
                    fit: 'cover',
                    position: 'center'
                },
                overlay: {
                    color: overlayColor,
                    opacity: overlayOpacity
                }
            },
            text_colors: {
                content: normalizeColor(document.getElementById('page-style-text-color-content')?.value, '#111111'),
                header: normalizeColor(document.getElementById('page-style-text-color-header')?.value, '#111111'),
                footer: normalizeColor(document.getElementById('page-style-text-color-footer')?.value, '#111111'),
                opening: normalizeColor(document.getElementById('page-style-text-color-opening')?.value, '#111111')
            }
        };
    }

    function setImageFieldValues(image) {
        const preview = document.getElementById('page-style-background-image-preview');
        const empty = document.getElementById('page-style-background-image-empty');
        const label = document.getElementById('page-style-background-image-label');
        if (!preview || !empty || !label) return;

        const hasImage = !!(image?.attachment_id || image?.preview_url || image?.url || image?.original_url);
        preview.classList.toggle('hidden', !hasImage);
        empty.classList.toggle('hidden', hasImage);
        label.textContent = hasImage
            ? (image.original_url || image.preview_url || image.url || 'Imagen seleccionada')
            : 'No hay imagen cargada.';
        preview.src = image.preview_url || image.url || image.original_url || '';
        preview.dataset.attachmentId = String(image?.attachment_id || 0);
        preview.dataset.previewUrl = image.preview_url || image.url || image.original_url || '';
        preview.dataset.originalUrl = image.original_url || image.url || '';
    }

    function applyBackgroundSections() {
        const type = document.getElementById('page-style-background-type')?.value || 'color';
        document.querySelectorAll('[data-page-style-section]').forEach(section => {
            section.classList.toggle('hidden', section.dataset.pageStyleSection !== type);
        });
    }

    function updateOpacityLabel() {
        const opacity = document.getElementById('page-style-background-overlay-opacity');
        const label = document.getElementById('page-style-background-overlay-opacity-value');
        if (opacity && label) {
            label.textContent = Number(opacity.value || 0).toFixed(2);
        }
    }

    function syncBackgroundColorFields() {
        const color = document.getElementById('page-style-background-color');
        const text = document.getElementById('page-style-background-color-text');
        if (!color || !text) return;

        const syncFromColor = () => {
            text.value = String(color.value || '#ffffff').toUpperCase();
        };
        const syncFromText = () => {
            const normalized = normalizeColor(text.value, color.value || '#ffffff');
            color.value = normalized;
            text.value = normalized.toUpperCase();
        };

        if (!color.dataset.bound) {
            color.dataset.bound = '1';
            color.addEventListener('input', syncFromColor);
        }
        if (!text.dataset.bound) {
            text.dataset.bound = '1';
            text.addEventListener('input', syncFromText);
        }
        syncFromColor();
    }

    function populateForm(styleRecord) {
        const normalized = normalizeStyleData(styleRecord?.style || styleRecord || getDefaultStyle());
        const background = normalized.background;

        const backgroundType = document.getElementById('page-style-background-type');
        const backgroundColor = document.getElementById('page-style-background-color');
        const backgroundColorText = document.getElementById('page-style-background-color-text');
        const gradientFrom = document.getElementById('page-style-gradient-from');
        const gradientTo = document.getElementById('page-style-gradient-to');
        const gradientAngle = document.getElementById('page-style-gradient-angle');
        const overlayColor = document.getElementById('page-style-background-overlay-color');
        const overlayOpacity = document.getElementById('page-style-background-overlay-opacity');
        const textContent = document.getElementById('page-style-text-color-content');
        const textHeader = document.getElementById('page-style-text-color-header');
        const textFooter = document.getElementById('page-style-text-color-footer');
        const textOpening = document.getElementById('page-style-text-color-opening');

        if (backgroundType) backgroundType.value = background.type || 'color';
        if (backgroundColor) backgroundColor.value = background.color || '#ffffff';
        if (backgroundColorText) backgroundColorText.value = String(background.color || '#ffffff').toUpperCase();
        if (gradientFrom) gradientFrom.value = background.gradient?.stops?.[0]?.color || '#ffffff';
        if (gradientTo) gradientTo.value = background.gradient?.stops?.[1]?.color || '#f3f4f6';
        if (gradientAngle) gradientAngle.value = String(background.gradient?.angle ?? 135);
        if (overlayColor) overlayColor.value = background.overlay?.color || '#000000';
        if (overlayOpacity) overlayOpacity.value = String(background.overlay?.opacity ?? 0.35);
        if (textContent) textContent.value = normalized.text_colors?.content || '#111111';
        if (textHeader) textHeader.value = normalized.text_colors?.header || '#111111';
        if (textFooter) textFooter.value = normalized.text_colors?.footer || '#111111';
        if (textOpening) textOpening.value = normalized.text_colors?.opening || '#111111';
        setImageFieldValues(background.image || getDefaultStyle().background.image);
        applyBackgroundSections();
        updateOpacityLabel();
        syncBackgroundColorFields();
    }

    function updateActionButtons() {
        const hasStyle = !!getStyleAtSelectedPage();
        const removeButton = document.getElementById('page-style-remove');
        const confirmButton = document.getElementById('page-style-confirm');
        if (removeButton) {
            removeButton.classList.toggle('hidden', !hasStyle);
            removeButton.setAttribute('aria-hidden', hasStyle ? 'false' : 'true');
        }
        if (confirmButton) {
            confirmButton.textContent = hasStyle ? 'Actualizar estilo' : 'Guardar estilo';
        }
    }

    function closeModal() {
        const modal = document.getElementById('page-template-modal');
        const dialog = modal?.querySelector('[data-page-template-dialog]');
        if (!modal || !dialog) return;
        modal.classList.add('opacity-0');
        dialog.classList.remove('scale-100');
        dialog.classList.add('scale-95');
        window.setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
        }, 180);
    }

    async function persistStyles() {
        return typeof window.savePDFSettings === 'function'
            ? window.savePDFSettings(true, true)
            : true;
    }

    async function rollbackStyles(previousStyles) {
        window.bookState.settings.page_styles = previousStyles;
        await persistStyles();
        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true, 'pdf-scroller', true);
        }
    }

    async function saveStyle() {
        const selectedPageNumber = getSelectedPageNumber();
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;

        window.bookState.settings = window.bookState.settings || {};
        const previousStyles = cloneStyles(getStyles());
        const existingStyle = getStyleAtSelectedPage();
        const instanceId = existingStyle
            ? getStyleInstanceId(existingStyle)
            : createInstanceId();

        const styleData = normalizeStyleData(getFormStyleData());
        const nextStyle = {
            id: instanceId,
            instance_id: instanceId,
            page_number: Number(existingStyle?.page_number) || selectedPageNumber,
            resolved_page: selectedPageNumber,
            anchor: existingStyle?.anchor?.flow_id
                ? existingStyle.anchor
                : getAnchorForPage(selectedPageNumber),
            style: styleData
        };

        const existingStyles = getStyles();
        window.bookState.settings.page_styles = [
            ...existingStyles.filter(style => getStyleInstanceId(style) !== instanceId),
            nextStyle
        ].sort((left, right) => Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number));

        const saved = await persistStyles();
        if (!saved) {
            window.bookState.settings.page_styles = previousStyles;
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo guardar el estilo.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true, 'pdf-scroller', true);
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Estilo guardado en la página ${selectedPageNumber}.`, 'fa-solid fa-paintbrush');
        }
        refresh();
    }

    async function removeStyle() {
        const selectedPageNumber = getSelectedPageNumber();
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1 || !window.bookState) return;

        window.bookState.settings = window.bookState.settings || {};
        const existingStyles = getStyles();
        const selectedStyle = getStyleAtSelectedPage();
        const instanceId = getStyleInstanceId(selectedStyle);
        const nextStyles = existingStyles.filter(style => getStyleInstanceId(style) !== instanceId);
        if (nextStyles.length === existingStyles.length) {
            return;
        }

        window.bookState.settings.page_styles = nextStyles.sort((left, right) => (
            Number(left.resolved_page || left.page_number) - Number(right.resolved_page || right.page_number)
        ));

        const saved = await persistStyles();
        if (!saved) {
            if (typeof window.showToast === 'function') {
                window.showToast('No se pudo quitar el estilo.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (typeof window.compilePDFPreview === 'function') {
            await window.compilePDFPreview(true, 'pdf-scroller', true);
        }
        if (typeof window.showToast === 'function') {
            window.showToast(`Estilo quitado de la página ${selectedPageNumber}.`, 'fa-solid fa-droplet-slash');
        }
        refresh();
    }

    function openMediaUploader() {
        const applySelection = (attachment) => {
            if (!attachment) return;
            const originalUrl = attachment.originalUrl || attachment.originalImageURL || attachment.url || '';
            const previewUrl = attachment.previewUrl || attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url || originalUrl;
            setImageFieldValues({
                attachment_id: Number(attachment.id) || 0,
                original_url: originalUrl || previewUrl || '',
                preview_url: previewUrl || originalUrl || '',
                url: originalUrl || previewUrl || ''
            });
            updateActionButtons();
        };

        if (window.AlmadenBooksterMediaPicker && bookState && bookState.bookId && bookState.mediaPickerNonce) {
            window.AlmadenBooksterMediaPicker.open({
                bookId: bookState.bookId,
                ajaxUrl: bookState.ajaxUrl,
                nonce: bookState.mediaPickerNonce,
                title: 'Seleccionar imagen de fondo',
                buttonText: 'Usar esta imagen'
            }).then(applySelection).catch(() => {});
            return;
        }

        if (typeof wp === 'undefined' || !wp.media) {
            if (typeof window.showToast === 'function') {
                window.showToast('La biblioteca multimedia no está disponible.', 'fa-solid fa-circle-exclamation');
            }
            return;
        }

        if (!mediaFrame) {
            mediaFrame = wp.media({
                title: 'Seleccionar imagen de fondo',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });
        }

        mediaFrame.off('select');
        mediaFrame.on('select', function () {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            applySelection(attachment);
        });

        mediaFrame.open();
    }

    function bind() {
        if (modalBound) return;
        modalBound = true;

        const modal = document.getElementById('page-template-modal');
        if (!modal) return;

        const confirm = document.getElementById('page-style-confirm');
        const remove = document.getElementById('page-style-remove');
        const imageSelect = document.getElementById('page-style-background-image-select');
        const imageClear = document.getElementById('page-style-background-image-clear');
        const backgroundType = document.getElementById('page-style-background-type');
        const overlayOpacity = document.getElementById('page-style-background-overlay-opacity');
        const backgroundColor = document.getElementById('page-style-background-color');
        const backgroundColorText = document.getElementById('page-style-background-color-text');

        if (confirm) {
            confirm.addEventListener('click', saveStyle);
        }
        if (remove) {
            remove.addEventListener('click', removeStyle);
        }
        if (imageSelect) {
            imageSelect.addEventListener('click', openMediaUploader);
        }
        if (imageClear) {
            imageClear.addEventListener('click', () => {
                setImageFieldValues({
                    attachment_id: 0,
                    preview_url: '',
                    original_url: '',
                    url: ''
                });
                updateActionButtons();
            });
        }
        if (backgroundType) {
            backgroundType.addEventListener('change', applyBackgroundSections);
        }
        if (overlayOpacity) {
            overlayOpacity.addEventListener('input', updateOpacityLabel);
        }
        if (backgroundColor) {
            backgroundColor.addEventListener('input', syncBackgroundColorFields);
        }
        if (backgroundColorText) {
            backgroundColorText.addEventListener('input', syncBackgroundColorFields);
        }

        document.addEventListener('click', event => {
            if (event.target?.closest?.('[data-page-template-tab-button="style"]')) {
                refresh();
            }
        });
    }

    function refresh() {
        const selectedPageNumber = getSelectedPageNumber();
        if (!Number.isFinite(selectedPageNumber) || selectedPageNumber < 1) return;
        populateForm(getStyleAtSelectedPage());
        updateActionButtons();
    }

    window.almadenPageStyleState = {
        getStyles,
        getStyleForPage,
        getStyleAtSelectedPage,
        getStyleInstanceId,
        createInstanceId,
        getAnchorForPage,
        normalizeStyleData
    };

    window.almadenPageStyleUI = {
        bind,
        refresh,
        openMediaUploader,
        closeModal
    };

    document.addEventListener('DOMContentLoaded', bind);
})();
