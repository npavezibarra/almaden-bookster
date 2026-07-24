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

// Actualizar las etiquetas de unidad en el formulario (cm o in)
function updateUnitFields() {
    const unit = document.getElementById('setting-unit').value;
    document.querySelectorAll('.unit-label').forEach(lbl => {
        lbl.textContent = unit;
    });
}

function toggleParityImageMode() {
    const parity = document.getElementById('setting-chapter-start-parity').value;
    const wrapper = document.getElementById('parity-image-mode-wrapper');
    if (wrapper) {
        if (parity === 'odd') {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
        }
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
