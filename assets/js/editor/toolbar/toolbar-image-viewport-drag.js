function getImageViewportDragSurface() {
    const modal = getImageViewportEditorModal();
    return modal ? modal.querySelector('#image-viewport-preview-viewport') : null;
}

function startImageViewportDrag(event) {
    const state = getImageViewportState();
    if (!state.src) return;
    if (state.heightMode !== 'fixed') return;
    if (event.target && event.target.closest && event.target.closest('.pdf-book-image-edit-handle')) return;
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
        } catch (error) {}
    }
    event.preventDefault();
}

function updateImageViewportDrag(event) {
    const state = getImageViewportState();
    if (!state.dragging) return;
    if (state.heightMode !== 'fixed') return;
    const surface = getImageViewportDragSurface();
    if (!surface) return;
    const rect = surface.getBoundingClientRect();
    if (!rect.width || !rect.height) return;

    const nextX = state.dragStartPositionX + ((event.clientX - state.dragStartX) / rect.width) * 100;
    const nextY = state.dragStartPositionY + ((event.clientY - state.dragStartY) / rect.height) * 100;
    setImageViewportState({ position: formatImageViewportPosition(nextX, nextY), committed: false });
    updateImageViewportModalView();
}

function endImageViewportDrag() {
    const state = getImageViewportState();
    if (!state.dragging) return;
    const surface = getImageViewportDragSurface();
    if (surface) surface.classList.remove('is-dragging');
    setImageViewportState({ dragging: false });
}

document.addEventListener('pointerdown', (event) => {
    const viewport = event.target && typeof event.target.closest === 'function'
        ? event.target.closest('#image-viewport-preview-viewport, #image-viewport-preview-image')
        : null;
    if (viewport) startImageViewportDrag(event);
}, true);
document.addEventListener('pointermove', updateImageViewportDrag, true);
document.addEventListener('pointerup', endImageViewportDrag, true);
document.addEventListener('pointercancel', endImageViewportDrag, true);

document.addEventListener('wheel', (event) => {
    const viewport = event.target && typeof event.target.closest === 'function'
        ? event.target.closest('#image-viewport-preview-viewport, #image-viewport-preview-image')
        : null;
    if (!viewport) return;
    const state = getImageViewportState();
    if (!state.src || state.heightMode !== 'fixed') return;
    const delta = event.deltaY > 0 ? -0.06 : 0.06;
    const nextZoom = clampImageViewportNumber((Number.parseFloat(state.zoom) || 1) + delta, 0.5, 2.5);
    setImageViewportState({ zoom: String(nextZoom), committed: false });
    updateImageViewportModalView();
    event.preventDefault();
}, { passive: false, capture: true });

document.getElementById('image-viewport-preview-image')?.addEventListener('load', () => {
    if (typeof updateImageViewportModalView === 'function') {
        updateImageViewportModalView();
    }
});
