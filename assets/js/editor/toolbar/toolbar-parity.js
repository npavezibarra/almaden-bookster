let parityMediaUploader;

function openParityImageUploader() {
    if (typeof bookState === 'undefined' || !bookState || !bookState.activeChapterId) {
        if (typeof showToast === 'function') {
            showToast("Selecciona un capítulo primero.", "fa-solid fa-circle-exclamation");
        }
        return;
    }

    if (parityMediaUploader) {
        parityMediaUploader.open();
        return;
    }

    if (typeof wp === 'undefined' || !wp.media) {
        if (typeof showToast === 'function') {
            showToast("Error: Media API no está disponible.", "fa-solid fa-triangle-exclamation");
        }
        return;
    }

    parityMediaUploader = wp.media({
        title: 'Seleccionar Imagen para Página en Blanco (Paridad)',
        button: { text: 'Establecer como imagen de paridad' },
        multiple: false,
        library: { type: 'image' }
    });

    parityMediaUploader.on('select', function() {
        const attachment = parityMediaUploader.state().get('selection').first().toJSON();
        const imgUrl = attachment.url;

        const chapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
        if (chapter) {
            chapter.parity_image = imgUrl;
            if (typeof showToast === 'function') {
                showToast("Imagen de paridad asignada al capítulo", "fa-solid fa-image");
            }

            triggerEditorUpdate('raw');

            if (typeof refreshEditorDisplay === 'function') {
                refreshEditorDisplay(false);
            }
        }
    });

    parityMediaUploader.open();
}

function updateParityButtonVisibility() {
    const btn = document.getElementById('btn-parity-image');
    if (!btn) return;

    if (typeof bookState !== 'undefined' && bookState && bookState.settings && bookState.settings.chapter_start_parity === 'odd') {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
}
