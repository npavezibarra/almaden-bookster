// cover-layers-interactions.js
document.addEventListener('DOMContentLoaded', () => {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;
    const utils = window.CoverEditor.utils;

    function finishDrag() {
        s.isDragging = false;
        s.textLayers.forEach(layer => {
            delete layer._origX;
            delete layer._origY;
        });
    }

    function renderAfterMove() {
        if (window.CoverEditor.actions.renderTextLayers) {
            window.CoverEditor.actions.renderTextLayers();
        }
    }

    function moveActiveLayerByPixels(deltaXpx, deltaYpx) {
        const activeLayer = s.textLayers.find(layer => layer.id === s.activeLayerId);
        if (!activeLayer || (window.CoverEditor.utils.isLayerLocked && window.CoverEditor.utils.isLayerLocked(activeLayer))) {
            return;
        }

        utils.moveLayerByPixels(activeLayer, deltaXpx, deltaYpx);
        renderAfterMove();
    }

    document.addEventListener('mousemove', (e) => {
        if (!s.isDragging || !s.activeLayerId) return;

        const dx = e.clientX - s.dragStartX;
        const dy = e.clientY - s.dragStartY;
        moveActiveLayerByPixels(dx, dy);
    });

    document.addEventListener('mouseup', finishDrag);

    document.addEventListener('keydown', (e) => {
        const activeLayer = s.textLayers.find(layer => layer.id === s.activeLayerId);
        if (!s.activeLayerId || utils.isEditableTarget(e.target) || !activeLayer || (window.CoverEditor.utils.isLayerLocked && window.CoverEditor.utils.isLayerLocked(activeLayer))) {
            return;
        }

        const stepPx = e.shiftKey ? 10 : 1;
        let deltaX = 0;
        let deltaY = 0;

        switch (e.key) {
            case 'ArrowLeft':
                deltaX = -stepPx;
                break;
            case 'ArrowRight':
                deltaX = stepPx;
                break;
            case 'ArrowUp':
                deltaY = -stepPx;
                break;
            case 'ArrowDown':
                deltaY = stepPx;
                break;
            default:
                return;
        }

        e.preventDefault();
        e.stopPropagation();
        utils.moveLayerByPixels(
            s.textLayers.find(layer => layer.id === s.activeLayerId),
            deltaX,
            deltaY,
            { useCurrentPosition: true }
        );
        renderAfterMove();
    });

    el.workspaceContainer.addEventListener('mousedown', (e) => {
        if (e.target === el.workspaceContainer || e.target === el.coverScaler || e.target === el.coverSpread || e.target.classList.contains('cover-part')) {
            finishDrag();
        }
    });
});
