function getImageViewportEditorModal() {
    return document.getElementById('image-viewport-modal');
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

    return true;
}

function replaceImageBlockMarkup(blockId, nextMarkup, options = {}) {
    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
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
    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
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
    const safeMaxHeight = getImageViewportSafeMaxHeightPercent(state, constraints);

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
    const heightMode = modal.querySelector('#image-viewport-height-mode');
    const marginTop = modal.querySelector('#image-viewport-margin-top');
    const marginBottom = modal.querySelector('#image-viewport-margin-bottom');
    const captionGap = modal.querySelector('#image-viewport-caption-gap');
    const captionAlign = modal.querySelector('#image-viewport-caption-align');
    const positionX = modal.querySelector('#image-viewport-position-x');
    const positionY = modal.querySelector('#image-viewport-position-y');
    const zoomValue = modal.querySelector('#image-viewport-zoom-value');
    const heightValue = modal.querySelector('#image-viewport-height-value');
    const previewGrid = modal.querySelector('#image-viewport-preview-grid');
    const transformBtn = modal.querySelector('#image-viewport-transform-btn');
    const transformLabel = modal.querySelector('#image-viewport-transform-label');
    const changeBtn = modal.querySelector('#image-viewport-change-btn');
    const removeBtn = modal.querySelector('#image-viewport-remove-btn');
    const saveBtn = modal.querySelector('#image-viewport-save-btn');
    const saveLabel = modal.querySelector('#image-viewport-save-label');
    const previewShell = modal.querySelector('#image-viewport-preview-viewport-shell');
    const pageShell = modal.querySelector('#image-viewport-page-shell');
    const fitButtons = modal.querySelectorAll('[data-image-viewport-fit]');
    const modeButtons = modal.querySelectorAll('[data-image-viewport-mode]');
    const presetButtons = modal.querySelectorAll('[data-image-viewport-preset]');
    const position = parseImageViewportPosition(state.position);
    const typstAnchor = getImageViewportTypstAnchor(state.position);

    if (previewImage) {
        if (state.src) {
            previewImage.src = state.previewSrc || state.src;
            previewImage.alt = state.alt || 'Vista previa de la imagen';
            previewImage.classList.remove('hidden');
            previewImage.style.width = '100%';
            previewImage.style.height = '100%';
            previewImage.style.objectFit = state.heightMode === 'fixed' ? (state.fit || 'cover') : 'contain';
            previewImage.style.objectPosition = state.heightMode === 'fixed' ? '50% 50%' : (state.position || '50% 50%');
            previewImage.style.transform = state.heightMode === 'fixed' ? `scale(${Number(state.zoom || 1)})` : 'scale(1)';
            previewImage.style.transformOrigin = state.heightMode === 'fixed' ? typstAnchor.css : (state.position || '50% 50%');
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

    if (pageShell) {
        const pageRatio = constraints.contentWidthPx / Math.max(constraints.contentHeightPx, 1);
        pageShell.style.aspectRatio = `${pageRatio}`;
    }

    if (previewViewport) {
        previewViewport.style.width = '100%';
        const normalized = normalizeImageViewportStateToLayout(state, constraints);
        if (state.src && normalized.needsNormalization) {
            setImageViewportState({
                heightPercent: normalized.state.heightPercent,
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
        const shellRect = pageShell ? pageShell.getBoundingClientRect() : { width: 0, height: 0 };
        const naturalRatio = previewImage && previewImage.naturalWidth && previewImage.naturalHeight
            ? previewImage.naturalWidth / previewImage.naturalHeight
            : 1.5;
        const scale = shellRect.width && constraints.contentWidthPx
            ? shellRect.width / constraints.contentWidthPx
            : 1;
        const naturalAutoHeight = shellRect.width && naturalRatio ? Math.round(shellRect.width / naturalRatio) : 260;
        const typstHeightPx = Math.round((constraints.contentHeightPx * Number(state.heightPercent || 45) / 100) * scale);
        previewViewport.style.height = state.heightMode === 'fixed'
            ? `${Math.max(80, Math.min(shellRect.height || typstHeightPx, typstHeightPx))}px`
            : `${Math.min(Math.max(naturalAutoHeight, 180), Math.max(shellRect.height || naturalAutoHeight, 180))}px`;
        previewViewport.style.aspectRatio = 'auto';
        previewViewport.classList.toggle('opacity-60', !state.src);
        previewViewport.classList.toggle('cursor-grab', !!state.src && state.heightMode === 'fixed');
        previewViewport.classList.toggle('cursor-default', state.heightMode !== 'fixed');
        previewViewport.style.top = state.heightMode === 'fixed' ? `${(shellRect.height - Math.max(80, Math.min(shellRect.height || typstHeightPx, typstHeightPx))) / 2}px` : '0px';
        previewViewport.style.left = '0px';
    }

    if (emptyState) {
        emptyState.classList.toggle('hidden', !!state.src);
    }

    if (previewFrame) {
        previewFrame.classList.toggle('opacity-60', !state.src);
    }

    if (zoomRange) {
        zoomRange.value = state.zoom || '1';
        zoomRange.disabled = state.heightMode !== 'fixed';
    }
    if (heightMode) heightMode.value = state.heightMode || 'auto';
    if (heightRange) {
        heightRange.min = String(constraints.minHeightPercent);
        heightRange.max = String(safeMaxHeight);
        heightRange.value = state.heightPercent || '45';
        heightRange.disabled = state.heightMode !== 'fixed';
    }
    if (zoomValue) zoomValue.textContent = `${Number(state.zoom || 1).toFixed(2)}x`;
    if (heightValue) heightValue.textContent = state.heightMode === 'fixed' ? `${state.heightPercent || 45}% del área útil` : 'Automática';
    if (heightLimit) {
        heightLimit.textContent = `Rango seguro: ${constraints.minHeightPercent}%–${safeMaxHeight}% del alto útil`;
    }
    if (heightWarning) {
        const currentHeight = parseImageViewportPercentValue(state.heightPercent, 45);
        const tooTall = currentHeight >= safeMaxHeight;
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
    if (transformLabel) {
        transformLabel.textContent = 'Reiniciar encuadre';
    }
    if (saveLabel) {
        saveLabel.textContent = state.committed ? 'Imagen guardada' : 'Guardar imagen';
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
    if (marginTop) marginTop.value = state.marginTopMm || '0';
    if (marginBottom) marginBottom.value = state.marginBottomMm || '0';
    if (captionGap) captionGap.value = state.captionGapMm || '1.5';
    if (captionAlign) captionAlign.value = state.captionAlign || 'left';
    if (positionX) positionX.value = String(position.x);
    if (positionY) positionY.value = String(position.y);
    if (previewGrid) {
        previewGrid.classList.toggle('hidden', !(state.src && state.heightMode === 'fixed'));
    }
    fitButtons.forEach((button) => {
        const isActive = button.dataset.imageViewportFit === (state.fit || 'cover');
        button.className = `rounded-full border px-3 py-1 text-[11px] font-semibold transition ${isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white/90 text-slate-600 hover:bg-white'}`;
        button.disabled = state.heightMode !== 'fixed';
    });
    modeButtons.forEach((button) => {
        const isActive = button.dataset.imageViewportMode === (state.heightMode || 'auto');
        button.className = `rounded-xl border px-3 py-2 text-xs font-semibold transition ${isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100'}`;
    });
    presetButtons.forEach((button) => {
        const isActive = button.dataset.imageViewportPreset === String(state.heightPercent || '45') && state.heightMode === 'fixed';
        button.className = `rounded-full border px-3 py-1 text-[11px] font-semibold transition ${isActive ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100'}`;
        button.disabled = state.heightMode !== 'fixed';
    });

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
    if (getImageViewportState().src) {
        setImageViewportStage('adjust');
    } else {
        openImageMediaPicker();
    }
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
        originalSrc: state.src,
        previewSrc: state.previewSrc || state.src,
        alt: state.alt,
        caption: state.caption,
        viewportWidth: '100%',
        viewportHeight: state.viewportHeight,
        zoom: state.zoom,
        fit: state.fit,
        position: state.position,
        heightMode: state.heightMode,
        heightPercent: state.heightPercent,
        marginTopMm: state.marginTopMm,
        marginBottomMm: state.marginBottomMm,
        captionGapMm: state.captionGapMm,
        captionAlign: state.captionAlign,
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

function openMediaUploader() {
    const selectedBlockId = window.almadenImageLayout?.getRawBlockIdAtCursor?.();
    if (selectedBlockId && window.almadenImageLayout.openRawBlock(selectedBlockId)) return;
    const placeholder = createImageViewportState();
    setImageViewportState({
        ...placeholder,
        inserted: false,
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
    openImageMediaPicker();
}

function updateImageViewportControl(property, value) {
    const nextState = {};
    if (property === 'zoom') {
        nextState.zoom = String(value);
    } else if (property === 'fit') {
        nextState.fit = value === 'contain' ? 'contain' : 'cover';
    } else if (property === 'viewportHeight') {
        const constraints = getImageViewportLayoutConstraints();
        const parsed = parseImageViewportPercentValue(value, 100);
        const clamped = clampImageViewportNumber(parsed, constraints.minHeightPercent, constraints.maxHeightPercent);
        nextState.viewportHeight = `${clamped}%`;
        nextState.layoutNormalized = false;
    } else if (property === 'heightMode') {
        nextState.heightMode = value === 'fixed' ? 'fixed' : 'auto';
    } else if (property === 'heightPercent') {
        const constraints = getImageViewportLayoutConstraints();
        nextState.heightPercent = String(clampImageViewportNumber(parseImageViewportPercentValue(value, 45), constraints.minHeightPercent, getImageViewportSafeMaxHeightPercent(getImageViewportState(), constraints)));
        nextState.layoutNormalized = false;
    } else if (property === 'positionX' || property === 'positionY') {
        const current = parseImageViewportPosition(getImageViewportState().position);
        const nextX = property === 'positionX' ? Number.parseFloat(value) : current.x;
        const nextY = property === 'positionY' ? Number.parseFloat(value) : current.y;
        nextState.position = formatImageViewportPosition(nextX, nextY);
    } else if (property === 'marginTopMm' || property === 'marginBottomMm') {
        nextState[property] = String(clampImageViewportNumber(Number.parseFloat(value) || 0, 0, 30));
    } else if (property === 'captionGapMm') {
        nextState.captionGapMm = String(clampImageViewportNumber(Number.parseFloat(value) || 0, 0, 10));
    } else if (property === 'captionAlign') {
        nextState.captionAlign = ['left', 'center', 'right'].includes(value) ? value : 'left';
    } else if (property === 'caption') {
        nextState.caption = String(value || '');
    }
    nextState.committed = false;
    setImageViewportState(nextState);
    updateImageViewportModalView();
}

function resetImageViewportTransform() {
    setImageViewportState({
        zoom: '1',
        fit: 'cover',
        position: '50% 50%',
        committed: false,
    });
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
            heightPercent: normalized.state.heightPercent,
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
    closeImageViewportModal();
}

function toggleImageViewportAdvancedControls(forceOpen = null) {
    resetImageViewportTransform();
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const modal = getImageViewportEditorModal();
    if (!modal || modal.classList.contains('hidden')) return;
    closeImageViewportModal();
});

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

window.openMediaUploader = openMediaUploader;
window.openImageViewportModal = openImageViewportModal;
window.closeImageViewportModal = closeImageViewportModal;
window.openImageViewportFromBlock = openImageViewportFromBlock;
window.saveImageViewportState = saveImageViewportState;
window.resetImageViewportTransform = resetImageViewportTransform;
