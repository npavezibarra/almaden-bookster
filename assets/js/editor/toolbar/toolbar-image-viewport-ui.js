let mediaUploader;

function getImageViewportEditorModal() {
    return document.getElementById('image-viewport-modal');
}

function getImageViewportDragSurface() {
    const modal = getImageViewportEditorModal();
    if (!modal) return null;
    return modal.querySelector('#image-viewport-preview-viewport');
}

function startImageViewportDrag(event) {
    const state = getImageViewportState();
    if (!state.src) return;
    if (event.target && typeof event.target.closest === 'function' && event.target.closest('.pdf-book-image-edit-handle')) {
        return;
    }

    const surface = getImageViewportDragSurface();
    if (!surface) return;

    const position = parseImageViewportPosition(state.position);
    setImageViewportState({
        dragging: true,
        dragStartX: event.clientX,
        dragStartY: event.clientY,
        dragStartPositionX: position.x,
        dragStartPositionY: position.y,
    });

    surface.classList.add('is-dragging');
    if (surface.setPointerCapture && event.pointerId !== undefined) {
        try {
            surface.setPointerCapture(event.pointerId);
        } catch (err) {}
    }

    event.preventDefault();
}

function updateImageViewportDrag(event) {
    const state = getImageViewportState();
    if (!state.dragging) return;

    const surface = getImageViewportDragSurface();
    if (!surface) return;

    const rect = surface.getBoundingClientRect();
    if (!rect.width || !rect.height) return;

    const deltaX = event.clientX - state.dragStartX;
    const deltaY = event.clientY - state.dragStartY;
    const nextX = state.dragStartPositionX + (deltaX / rect.width) * 100;
    const nextY = state.dragStartPositionY + (deltaY / rect.height) * 100;

    setImageViewportState({
        position: formatImageViewportPosition(nextX, nextY),
        committed: false,
    });
    updateImageViewportModalView();
}

function endImageViewportDrag() {
    const state = getImageViewportState();
    if (!state.dragging) return;

    const surface = getImageViewportDragSurface();
    if (surface) {
        surface.classList.remove('is-dragging');
    }

    setImageViewportState({ dragging: false });
}

function openImageViewportFromBlock(blockId) {
    const safeBlockId = escapeSelectorValue(blockId);
    const block = document.querySelector(`#pdf-scroller [data-image-block-id="${safeBlockId}"]`);
    if (!block) {
        return false;
    }

    const state = readImageBlockStateFromElement(block);
    setImageViewportState(state);
    openImageViewportModal();

    if (state.layoutNormalized && state.inserted && state.committed) {
        commitImageViewportState();
        if (typeof showToast === 'function') {
            showToast('La imagen se normalizó al máximo permitido para mantenerla dentro de una sola página.', 'fa-solid fa-ruler-combined');
        }
    }

    if (!window.imageViewportEditorState.src) {
        openImageMediaPicker();
    }
    return true;
}

function replaceImageBlockMarkup(blockId, nextMarkup, options = {}) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea || !blockId) return false;

    const escapedId = blockId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const blockRegex = new RegExp(`<figure\\b[^>]*data-image-block-id="${escapedId}"[^>]*>[\\s\\S]*?<\\/figure>`, 'i');
    if (!blockRegex.test(textarea.value)) return false;

    textarea.value = textarea.value.replace(blockRegex, nextMarkup.trim());
    textarea.focus();
    if (!options.skipUpdate) {
        triggerEditorUpdate('raw');
    }
    return true;
}

function removeImageBlockMarkup(blockId, options = {}) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea || !blockId) return false;

    const escapedId = blockId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const blockRegex = new RegExp(`\\n?<figure\\b[^>]*data-image-block-id="${escapedId}"[^>]*>[\\s\\S]*?<\\/figure>\\n?`, 'i');
    if (!blockRegex.test(textarea.value)) return false;

    textarea.value = textarea.value.replace(blockRegex, '\n');
    textarea.focus();
    if (!options.skipUpdate) {
        triggerEditorUpdate('raw');
    }
    return true;
}

function updateImageViewportModalView() {
    let state = getImageViewportState();
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    const constraints = getImageViewportLayoutConstraints();

    const previewImage = modal.querySelector('#image-viewport-preview-image');
    const previewViewport = modal.querySelector('#image-viewport-preview-viewport');
    const captionField = modal.querySelector('#image-viewport-caption');
    const captionCount = modal.querySelector('#image-viewport-caption-count');
    const captionWarning = modal.querySelector('#image-viewport-caption-warning');
    const heightLimit = modal.querySelector('#image-viewport-height-limit');
    const heightWarning = modal.querySelector('#image-viewport-height-warning');
    const emptyState = modal.querySelector('#image-viewport-empty-state');
    const previewFrame = modal.querySelector('#image-viewport-preview-frame');
    const controls = modal.querySelector('#image-viewport-controls');
    const zoomRange = modal.querySelector('#image-viewport-zoom');
    const heightRange = modal.querySelector('#image-viewport-height');
    const zoomValue = modal.querySelector('#image-viewport-zoom-value');
    const heightValue = modal.querySelector('#image-viewport-height-value');
    const transformBtn = modal.querySelector('#image-viewport-transform-btn');
    const transformLabel = modal.querySelector('#image-viewport-transform-label');
    const changeBtn = modal.querySelector('#image-viewport-change-btn');
    const removeBtn = modal.querySelector('#image-viewport-remove-btn');
    const saveBtn = modal.querySelector('#image-viewport-save-btn');
    const saveLabel = modal.querySelector('#image-viewport-save-label');

    if (previewImage) {
        if (state.src) {
            previewImage.src = state.src;
            previewImage.alt = state.alt || 'Vista previa de la imagen';
            previewImage.classList.remove('hidden');
            previewImage.style.width = '100%';
            previewImage.style.height = '100%';
            previewImage.style.objectFit = state.fit || 'cover';
            previewImage.style.objectPosition = state.position || '50% 50%';
            previewImage.style.transform = `scale(${Number(state.zoom || 1)})`;
            previewImage.style.transformOrigin = state.position || '50% 50%';
        } else {
            previewImage.removeAttribute('src');
            previewImage.alt = '';
            previewImage.classList.add('hidden');
            previewImage.style.width = '';
            previewImage.style.height = '';
            previewImage.style.objectFit = '';
            previewImage.style.objectPosition = '';
            previewImage.style.transform = '';
            previewImage.style.transformOrigin = '';
        }
    }

    if (previewViewport) {
        previewViewport.style.width = '100%';
        previewViewport.style.height = 'auto';
        const normalized = normalizeImageViewportStateToLayout(state, constraints);
        if (state.src && normalized.needsNormalization) {
            setImageViewportState({
                viewportHeight: normalized.state.viewportHeight,
                committed: false,
                layoutNormalized: true,
                layoutMaxHeightPercent: normalized.maxHeightPercent,
            });
            state = getImageViewportState();
        } else if (state.layoutNormalized && state.src) {
            setImageViewportState({
                layoutNormalized: false,
            });
            state = getImageViewportState();
        }
        previewViewport.style.aspectRatio = `${getImageViewportAspectRatio('100%', state.viewportHeight)}`;
        previewViewport.classList.toggle('opacity-60', !state.src);
    }

    if (emptyState) {
        emptyState.classList.toggle('hidden', !!state.src);
    }

    if (previewFrame) {
        previewFrame.classList.toggle('opacity-60', !state.src);
    }

    if (zoomRange) zoomRange.value = state.zoom || '1';
    if (heightRange) {
        heightRange.min = String(constraints.minHeightPercent);
        heightRange.max = String(constraints.maxHeightPercent);
        heightRange.value = state.viewportHeight ? parseImageViewportPercentValue(state.viewportHeight, 100) : 100;
    }
    if (zoomValue) zoomValue.textContent = `${Number(state.zoom || 1).toFixed(2)}x`;
    if (heightValue) heightValue.textContent = state.viewportHeight || '100%';
    if (heightLimit) {
        heightLimit.textContent = `Límite calculado: ${constraints.maxHeightPercent}% del ancho del content area`;
    }
    if (heightWarning) {
        const currentHeight = parseImageViewportPercentValue(state.viewportHeight, 100);
        const tooTall = currentHeight >= constraints.maxHeightPercent;
        const normalized = !!state.layoutNormalized;
        heightWarning.classList.toggle('hidden', !((tooTall || normalized) && state.src));
        heightWarning.textContent = normalized
            ? 'Este bloque se normalizó al máximo permitido para evitar que cruce a otra página sin intención explícita.'
            : 'El valor llegó al máximo permitido; si lo superas, el bloque se normaliza para preservar la paginación.';
    }
    if (transformBtn) transformBtn.disabled = !state.src;
    if (changeBtn) changeBtn.disabled = false;
    if (removeBtn) removeBtn.disabled = !state.inserted;
    if (saveBtn) saveBtn.disabled = !state.src;
    if (controls && !state.src) {
        controls.classList.add('hidden');
    }
    if (transformLabel) {
        transformLabel.textContent = controls && !controls.classList.contains('hidden') ? 'Ocultar Transform' : 'Transform';
    }
    if (saveLabel) {
        saveLabel.textContent = state.committed ? 'Guardado' : 'Guardar';
    }
    if (saveBtn) {
        const wordCount = countImageViewportWords(state.caption);
        const tooLong = wordCount > 50;
        saveBtn.classList.toggle('opacity-70', !state.src || tooLong);
        saveBtn.classList.toggle('cursor-not-allowed', !state.src || tooLong);
        saveBtn.disabled = !state.src || tooLong;
        if (captionWarning) {
            captionWarning.classList.toggle('hidden', !tooLong);
        }
        if (captionCount) {
            captionCount.textContent = `${wordCount}/50 palabras`;
            captionCount.classList.toggle('text-rose-600', tooLong);
            captionCount.classList.toggle('text-slate-500', !tooLong);
        }
    }
    if (captionField) {
        captionField.value = state.caption || '';
    }

    const modalTitle = modal.querySelector('#image-viewport-title');
    if (modalTitle) {
        modalTitle.textContent = state.src ? 'Editor de Viewport de Imagen' : 'Agregar Imagen';
    }
}

function openImageViewportModal() {
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.classList.remove('opacity-0');
        const panel = modal.querySelector('[data-image-viewport-panel]');
        if (panel) {
            panel.classList.remove('scale-95');
            panel.classList.add('scale-100');
        }
    });
    updateImageViewportModalView();
}

function closeImageViewportModal() {
    const modal = getImageViewportEditorModal();
    if (!modal) return;

    const state = getImageViewportState();
    if (state.isNewBlock && state.inserted && !state.committed) {
        removeImageBlockMarkup(state.blockId);
    }

    modal.classList.add('opacity-0');
    const panel = modal.querySelector('[data-image-viewport-panel]');
    if (panel) {
        panel.classList.remove('scale-100');
        panel.classList.add('scale-95');
    }
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 220);
}

function commitImageViewportState(options = {}) {
    const state = getImageViewportState();
    if (!state.src) {
        updateImageViewportModalView();
        return;
    }

    const markup = buildImageBlockMarkup({
        blockId: state.blockId,
        src: state.src,
        alt: state.alt,
        caption: state.caption,
        viewportWidth: '100%',
        viewportHeight: state.viewportHeight,
        zoom: state.zoom,
        fit: state.fit,
        position: state.position,
        className: 'pdf-book-image',
    });

    if (state.inserted) {
        replaceImageBlockMarkup(state.blockId, markup, options);
    } else {
        insertAtCursor(markup);
        setImageViewportState({ inserted: true });
    }

    setImageViewportState({ committed: true, isNewBlock: false });
    updateImageViewportModalView();
}

function openImageMediaPicker() {
    if (typeof wp === 'undefined' || !wp.media) {
        if (typeof showToast === 'function') {
            showToast("Error: Media API no está disponible. Guarda y recarga la página.", "fa-solid fa-triangle-exclamation");
        }
        return;
    }

    if (mediaUploader) {
        mediaUploader.open();
        return;
    }

    mediaUploader = wp.media({
        title: 'Seleccionar Imagen',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });

    mediaUploader.on('select', function() {
        const attachment = mediaUploader.state().get('selection').first().toJSON();
        const fullSizeUrl = attachment.sizes && attachment.sizes.full && attachment.sizes.full.url
            ? attachment.sizes.full.url
            : '';
    const imgUrl = attachment.originalImageURL || fullSizeUrl || attachment.url;
    const imgAlt = attachment.alt || attachment.title || 'Imagen del libro';
        setImageViewportState({
            src: imgUrl,
            alt: imgAlt,
            isPlaceholder: false,
            committed: false,
        });
        updateImageViewportModalView();
    });

    mediaUploader.open();
}

function openMediaUploader() {
    const placeholder = createImageViewportState();
    setImageViewportState({
        ...placeholder,
        inserted: false,
        committed: false,
        isPlaceholder: true,
        isNewBlock: true,
    });

    const placeholderMarkup = buildImageBlockMarkup({
        blockId: placeholder.blockId,
        src: '',
        alt: '',
        viewportWidth: '100%',
        viewportHeight: placeholder.viewportHeight,
        zoom: placeholder.zoom,
        fit: placeholder.fit,
        position: placeholder.position,
        caption: placeholder.caption,
        className: 'pdf-book-image',
        isPlaceholder: true,
    });
    insertAtCursor(placeholderMarkup);
    setImageViewportState({
        blockId: placeholder.blockId,
        inserted: true,
        committed: false,
        isPlaceholder: true,
        isNewBlock: true,
    });

    openImageViewportModal();
}

function removeCurrentImageBlock() {
    const state = getImageViewportState();
    if (state.inserted) {
        removeImageBlockMarkup(state.blockId);
    }
    setImageViewportState(createImageViewportState());
    updateImageViewportModalView();
}

function updateImageViewportControl(property, value) {
    const nextState = {};
    if (property === 'zoom') {
        nextState.zoom = String(value);
    } else if (property === 'viewportHeight') {
        const constraints = getImageViewportLayoutConstraints();
        const parsed = parseImageViewportPercentValue(value, 100);
        const clamped = clampImageViewportNumber(parsed, constraints.minHeightPercent, constraints.maxHeightPercent);
        nextState.viewportHeight = `${clamped}%`;
        nextState.layoutNormalized = false;
    } else if (property === 'caption') {
        nextState.caption = String(value || '');
    }
    nextState.committed = false;
    setImageViewportState(nextState);
    updateImageViewportModalView();
}

function saveImageViewportState() {
    const state = getImageViewportState();
    if (!state.src) {
        return;
    }
    const constraints = getImageViewportLayoutConstraints();
    const normalized = normalizeImageViewportStateToLayout(state, constraints);
    if (normalized.needsNormalization) {
        setImageViewportState({
            viewportHeight: normalized.state.viewportHeight,
            committed: false,
            layoutNormalized: true,
            layoutMaxHeightPercent: normalized.maxHeightPercent,
        });
    }
    const normalizedState = getImageViewportState();
    if (countImageViewportWords(normalizedState.caption) > 50) {
        if (typeof showToast === 'function') {
            showToast('La descripción no puede superar 50 palabras.', 'fa-solid fa-triangle-exclamation');
        }
        updateImageViewportModalView();
        return;
    }
    commitImageViewportState({ skipUpdate: true });

    if (typeof syncRawEditorToState === 'function') {
        syncRawEditorToState();
    }
    if (typeof updateWordCounts === 'function') {
        updateWordCounts();
    }

    if (window.splitPreviewRefreshTimer) {
        clearTimeout(window.splitPreviewRefreshTimer);
        window.splitPreviewRefreshTimer = null;
    }

    if (bookState && bookState.viewMode === 'split' && typeof refreshSplitPreview === 'function') {
        refreshSplitPreview(true);
    } else if (typeof compilePDFPreview === 'function') {
        compilePDFPreview(true);
    }

    if (typeof saveStateToLocalStorage === 'function') {
        setTimeout(() => saveStateToLocalStorage(true), 0);
    }

    if (typeof showToast === 'function') {
        showToast('Viewport guardado en el PDF', 'fa-solid fa-floppy-disk');
    }
}

function toggleImageViewportAdvancedControls(forceOpen = null) {
    const modal = getImageViewportEditorModal();
    if (!modal) return;
    const controls = modal.querySelector('#image-viewport-controls');
    if (!controls) return;

    const shouldOpen = forceOpen === null ? controls.classList.contains('hidden') : !!forceOpen;
    controls.classList.toggle('hidden', !shouldOpen);
    const transformLabel = modal.querySelector('#image-viewport-transform-label');
    if (transformLabel) {
        transformLabel.textContent = shouldOpen ? 'Ocultar Transform' : 'Transform';
    }
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = getImageViewportEditorModal();
    if (!modal || modal.classList.contains('hidden')) return;
    closeImageViewportModal();
});

document.addEventListener('pointermove', (event) => {
    if (typeof getImageViewportState !== 'function' || !getImageViewportState().dragging) return;
    updateImageViewportDrag(event);
}, true);

document.addEventListener('pointerup', () => {
    if (typeof endImageViewportDrag === 'function') endImageViewportDrag();
}, true);

document.addEventListener('pointercancel', () => {
    if (typeof endImageViewportDrag === 'function') endImageViewportDrag();
}, true);

document.addEventListener('click', (event) => {
    const handle = event.target && event.target.closest
        ? event.target.closest('.pdf-book-image-edit-handle')
        : null;
    if (!handle) return;

    const block = handle.closest ? handle.closest('[data-image-block-id]') : null;
    if (!block) return;

    event.preventDefault();
    event.stopPropagation();

    const blockId = block.getAttribute('data-image-block-id') || '';
    if (blockId) {
        openImageViewportFromBlock(blockId);
    }
}, true);

document.addEventListener('pointerdown', (event) => {
    const viewport = event.target && typeof event.target.closest === 'function'
        ? event.target.closest('#image-viewport-preview-viewport, #image-viewport-preview-image')
        : null;
    if (!viewport) return;
    startImageViewportDrag(event);
}, true);

window.openMediaUploader = openMediaUploader;
window.openImageViewportModal = openImageViewportModal;
window.closeImageViewportModal = closeImageViewportModal;
window.openImageViewportFromBlock = openImageViewportFromBlock;
window.saveImageViewportState = saveImageViewportState;
