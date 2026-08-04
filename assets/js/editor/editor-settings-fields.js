function toggleCustomHeaderFields() {
    const evenType = document.getElementById('setting-header-even-type').value;
    const oddType = document.getElementById('setting-header-odd-type').value;

    const evenContainer = document.getElementById('custom-header-even-container');
    const oddContainer = document.getElementById('custom-header-odd-container');

    if (evenContainer) {
        if (evenType === 'custom') evenContainer.classList.remove('hidden');
        else evenContainer.classList.add('hidden');
    }
    if (oddContainer) {
        if (oddType === 'custom') oddContainer.classList.remove('hidden');
        else oddContainer.classList.add('hidden');
    }
}

function toggleCustomFirstPageHeader() {
    const type = document.getElementById('setting-first-page-header-type').value;
    const input = document.getElementById('setting-first-page-header-custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
    }
}

function toggleCustomFirstPageFooter() {
    const type = document.getElementById('setting-first-page-footer-type').value;
    const input = document.getElementById('setting-first-page-footer-custom');
    if (input) {
        if (type === 'custom') input.classList.remove('hidden');
        else input.classList.add('hidden');
    }
}

function togglePageColumnsSettings() {
    const enabled = document.getElementById('setting-page-columns-enabled');
    const wrapper = document.getElementById('setting-page-columns-fields');

    if (wrapper) {
        wrapper.classList.toggle('hidden', !(enabled && enabled.checked));
    }
}

// Actualizar las etiquetas de unidad en el formulario (cm o in)
function updateUnitFields() {
    const unit = document.getElementById('setting-unit').value;
    document.querySelectorAll('.unit-label').forEach(lbl => {
        lbl.textContent = unit;
    });
}

function toggleParityImageMode() {
    const flowMode = document.getElementById('setting-book-chapter-flow-mode');
    const wrapper = document.getElementById('parity-image-mode-wrapper');
    if (wrapper) {
        if (flowMode && flowMode.value === 'left') {
            wrapper.classList.remove('hidden');
            toggleChapterImageSettings();
        } else {
            wrapper.classList.add('hidden');
        }
    }
}

function toggleChapterTransitionBlankSettings() {
    const flowMode = document.getElementById('setting-book-chapter-flow-mode');
    const wrapper = document.getElementById('chapter-transition-blank-mode-wrapper');
    const textWrapper = document.getElementById('chapter-transition-blank-text-wrapper');
    const modeField = document.getElementById('setting-chapter-transition-blank-mode');

    const isLeftFlow = !!(flowMode && flowMode.value === 'left');
    if (wrapper) {
        wrapper.classList.toggle('hidden', !isLeftFlow);
    }

    if (!isLeftFlow) {
        if (textWrapper) textWrapper.classList.add('hidden');
        return;
    }

    const mode = modeField ? modeField.value : 'full_blank';
    if (textWrapper) {
        textWrapper.classList.toggle('hidden', mode !== 'intentional_text');
    }
}

function syncBookFlowParityMode() {
    const flowMode = document.getElementById('setting-book-chapter-flow-mode');
    const legacyParity = document.getElementById('setting-chapter-start-parity');

    if (legacyParity) {
        legacyParity.value = flowMode && flowMode.value === 'left' ? 'even' : 'any';
    }

    if (typeof toggleParityImageMode === 'function') {
        toggleParityImageMode();
    }

    if (typeof toggleChapterTransitionBlankSettings === 'function') {
        toggleChapterTransitionBlankSettings();
    }
}

let mediaUploaderChapterImage;
window.openChapterImageUploader = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }

    if (mediaUploaderChapterImage) {
        mediaUploaderChapterImage.open();
        return;
    }

    mediaUploaderChapterImage = wp.media({
        title: 'Seleccionar Imagen para Chapter Image',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });

    mediaUploaderChapterImage.on('select', function() {
        const attachment = mediaUploaderChapterImage.state().get('selection').first().toJSON();
        const input = document.getElementById('setting-chapter-image-url');
        if (input) {
            input.value = attachment.url;
        }
    });

    mediaUploaderChapterImage.open();
}

window.clearChapterImageSelection = function() {
    const input = document.getElementById('setting-chapter-image-url');
    if (input) {
        input.value = '';
    }
}

window.syncChapterImageWidthLabel = function() {
    const input = document.getElementById('setting-chapter-image-inner-width');
    const label = document.getElementById('chapter-image-inner-width-label');
    if (input && label) {
        label.textContent = (input.value || '100') + '%';
    }
}

window.toggleChapterImageSettings = function() {
    const modeField = document.getElementById('setting-chapter-image-mode');
    const uploadWrapper = document.getElementById('chapter-image-upload-wrapper');
    const fullPageNote = document.getElementById('chapter-image-fullpage-note');
    const innerControls = document.getElementById('chapter-image-inner-controls');

    if (!modeField) return;

    const mode = modeField.value;
    const showImageControls = mode === 'image_full_page' || mode === 'image_inner';

    if (uploadWrapper) {
        uploadWrapper.classList.toggle('hidden', !showImageControls);
    }
    if (fullPageNote) {
        fullPageNote.classList.toggle('hidden', mode !== 'image_full_page');
    }
    if (innerControls) {
        innerControls.classList.toggle('hidden', mode !== 'image_inner');
    }

    if (mode === 'image_inner') {
        window.syncChapterImageWidthLabel();
    }
}

// ---- Funciones para Créditos Dinámicos ----
function toggleCustomPageFields() {
    const pageSize = document.getElementById('setting-page-size').value;
    const customFields = document.getElementById('custom-page-dimensions');
    if (customFields) {
        if (pageSize === 'Custom') {
            customFields.classList.remove('hidden');
        } else {
            customFields.classList.add('hidden');
        }
    }
}

// Mostrar / Ocultar el modal de configuración
// Event Listeners for Color Inputs
document.addEventListener('DOMContentLoaded', () => {
    const ebookBgColor = document.getElementById('setting-ebook-bg-color');
    if (ebookBgColor) {
        ebookBgColor.addEventListener('input', function(e) {
            document.getElementById('setting-ebook-bg-color-text').value = e.target.value.toUpperCase();
        });
    }

    const coverPanelBgColor = document.getElementById('setting-ebook-cover-panel-bg-color');
    if (coverPanelBgColor) {
        coverPanelBgColor.addEventListener('input', function(e) {
            document.getElementById('setting-ebook-cover-panel-bg-color-text').value = e.target.value.toUpperCase();
        });
    }
});

window.toggleEbookBgType = function() {
    const type = document.getElementById('setting-ebook-bg-type').value;
    if (type === 'color') {
        document.getElementById('ebook-bg-color-wrap').classList.remove('hidden');
        document.getElementById('ebook-bg-image-wrap').classList.add('hidden');
    } else {
        document.getElementById('ebook-bg-color-wrap').classList.add('hidden');
        document.getElementById('ebook-bg-image-wrap').classList.remove('hidden');
    }
}

let mediaUploaderEbookBg;
window.openMediaUploaderEbookBg = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }
    if (mediaUploaderEbookBg) {
        mediaUploaderEbookBg.open();
        return;
    }
    mediaUploaderEbookBg = wp.media({
        title: 'Seleccionar Imagen de Fondo General',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });
    mediaUploaderEbookBg.on('select', function() {
        let attachment = mediaUploaderEbookBg.state().get('selection').first().toJSON();
        document.getElementById('setting-ebook-bg-image').value = attachment.url;
    });
    mediaUploaderEbookBg.open();
}

window.toggleCoverPanelBgType = function() {
    const type = document.getElementById('setting-ebook-cover-panel-bg-type').value;
    if (type === 'color') {
        document.getElementById('ebook-cover-panel-color-wrap').classList.remove('hidden');
        document.getElementById('ebook-cover-panel-image-wrap').classList.add('hidden');
    } else {
        document.getElementById('ebook-cover-panel-color-wrap').classList.add('hidden');
        document.getElementById('ebook-cover-panel-image-wrap').classList.remove('hidden');
    }
}

let mediaUploaderCoverPanel;
window.openMediaUploaderCoverPanel = function() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('El mecanismo de Media de WordPress no está disponible en esta pantalla. Asegúrate de guardar y recargar la página.');
        return;
    }
    if (mediaUploaderCoverPanel) {
        mediaUploaderCoverPanel.open();
        return;
    }
    mediaUploaderCoverPanel = wp.media({
        title: 'Seleccionar Imagen de Fondo',
        button: { text: 'Usar esta imagen' },
        multiple: false,
        library: { type: 'image' }
    });
    mediaUploaderCoverPanel.on('select', function() {
        let attachment = mediaUploaderCoverPanel.state().get('selection').first().toJSON();
        document.getElementById('setting-ebook-cover-panel-bg-image').value = attachment.url;
    });
    mediaUploaderCoverPanel.open();
}
