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

    const settings = typeof bookState !== 'undefined' && bookState && bookState.settings ? bookState.settings : {};
    const activeChapter = typeof bookState !== 'undefined' && bookState && Array.isArray(bookState.chapters)
        ? bookState.chapters.find(c => c.id === bookState.activeChapterId)
        : null;
    const chapterParity = activeChapter && activeChapter.start_parity && activeChapter.start_parity !== 'any'
        ? activeChapter.start_parity
        : settings.chapter_start_parity;
    const allowParityImage = settings.book_chapter_flow_mode !== 'left' && chapterParity === 'odd';

    if (allowParityImage) {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
}
