// cover-utils.js
window.CoverEditor = window.CoverEditor || {};

window.CoverEditor.utils = window.CoverEditor.utils || {};

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
        return manualSpineWidthMm;
    }

    return autoSpineWidthMm;
};

window.CoverEditor.utils.getSpineWidthMode = function getSpineWidthMode() {
    const el = window.CoverEditor.elements;
    return (el.spineWidthMode && el.spineWidthMode.value) ? el.spineWidthMode.value : 'auto';
};
