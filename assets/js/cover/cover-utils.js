// cover-utils.js
window.CoverEditor = window.CoverEditor || {};

window.CoverEditor.utils = window.CoverEditor.utils || {};

window.CoverEditor.utils.generateId = function generateId() {
    return Math.random().toString(36).substr(2, 9);
};

window.CoverEditor.utils.hexToRgba = function hexToRgba(hex, opacity) {
    let r = 0, g = 0, b = 0;
    if (hex.length === 4) {
        r = parseInt(hex[1] + hex[1], 16);
        g = parseInt(hex[2] + hex[2], 16);
        b = parseInt(hex[3] + hex[3], 16);
    } else if (hex.length === 7) {
        r = parseInt(hex.substring(1, 3), 16);
        g = parseInt(hex.substring(3, 5), 16);
        b = parseInt(hex.substring(5, 7), 16);
    }

    return `rgba(${r}, ${g}, ${b}, ${opacity / 100})`;
};

window.CoverEditor.utils.roundUpMm = function roundUpMm(value) {
    const num = parseFloat(value);
    if (!Number.isFinite(num) || num <= 0) {
        return 0;
    }

    return Math.ceil(num);
};

window.CoverEditor.utils.getSpineWidthMm = function getSpineWidthMm() {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    const thicknessMmPerPage = parseFloat(el.paperTypeSelect && el.paperTypeSelect.value) || 0.06;
    let pages = parseInt(el.pageCountInput && el.pageCountInput.value, 10);
    if (isNaN(pages) || pages < 20) pages = 20;

    const autoSpineWidthMm = thicknessMmPerPage * pages;
    const mode = (el.spineWidthMode && el.spineWidthMode.value) ? el.spineWidthMode.value : 'auto';
    const manualSpineWidthMm = parseFloat(el.spineWidthMm && el.spineWidthMm.value);

    if (mode === 'manual' && !isNaN(manualSpineWidthMm) && manualSpineWidthMm > 0) {
        return window.CoverEditor.utils.roundUpMm(manualSpineWidthMm);
    }

    return window.CoverEditor.utils.roundUpMm(autoSpineWidthMm);
};

window.CoverEditor.utils.getCoverSpreadDimensionsPx = function getCoverSpreadDimensionsPx() {
    const s = window.CoverEditor.state;
    const el = window.CoverEditor.elements;

    if (!el.coverSpread) {
        return { widthPx: 0, heightPx: 0 };
    }

    const rect = el.coverSpread.getBoundingClientRect();
    return {
        widthPx: rect.width / (s.zoomLevel || 1),
        heightPx: rect.height / (s.zoomLevel || 1)
    };
};

window.CoverEditor.utils.moveLayerByPixels = function moveLayerByPixels(layer, deltaXpx, deltaYpx, options = {}) {
    const s = window.CoverEditor.state;
    const utils = window.CoverEditor.utils;
    const spread = utils.getCoverSpreadDimensionsPx();
    const widthPx = spread.widthPx || 1;
    const heightPx = spread.heightPx || 1;
    const useCurrentPosition = !!options.useCurrentPosition;

    const deltaXPercent = (deltaXpx / widthPx) * 100;
    const deltaYPercent = (deltaYpx / heightPx) * 100;

    const moveTarget = function(target) {
        if (useCurrentPosition) {
            target.x += deltaXPercent;
            target.y += deltaYPercent;
            return;
        }

        if (target._origX === undefined) {
            target._origX = target.x;
            target._origY = target.y;
        }

        target.x = target._origX + deltaXPercent;
        target.y = target._origY + deltaYPercent;
    };

    if (!layer) {
        return;
    }

    if (layer.type === 'group') {
        const children = s.textLayers.filter(child => child.parentId === layer.id);
        children.forEach(moveTarget);
        return;
    }

    moveTarget(layer);
};

window.CoverEditor.utils.isEditableTarget = function isEditableTarget(target) {
    if (!target || target === document.body) {
        return false;
    }

    const tagName = (target.tagName || '').toLowerCase();
    return target.isContentEditable || ['input', 'textarea', 'select'].includes(tagName);
};

window.CoverEditor.utils.isLayerLocked = function isLayerLocked(layer) {
    if (!layer) {
        return false;
    }

    if (layer.locked) {
        return true;
    }

    if (!layer.parentId || !window.CoverEditor.state || !Array.isArray(window.CoverEditor.state.textLayers)) {
        return false;
    }

    const parent = window.CoverEditor.state.textLayers.find(item => item.id === layer.parentId);
    return parent ? window.CoverEditor.utils.isLayerLocked(parent) : false;
};

window.CoverEditor.utils.getSpineWidthMode = function getSpineWidthMode() {
    const el = window.CoverEditor.elements;
    return (el.spineWidthMode && el.spineWidthMode.value) ? el.spineWidthMode.value : 'auto';
};
