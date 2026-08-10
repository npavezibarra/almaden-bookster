// assets/js/editor/editor-chapters-actions.js

function loadActiveChapter() {
    const activeId = bookState.activeChapterId;
    const chapter = bookState.chapters.find(c => c.id === activeId);

    const titleInput = document.getElementById('chapter-title-input');
    const textInput = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    const rawEditorContainer = document.getElementById('raw-editor-container');

    if (chapter) {
        if (titleInput) titleInput.value = chapter.title;

        const creditsContainer = document.getElementById('credits-editor-container');

        if (chapter.is_credits === '1') {
            if (rawEditorContainer) rawEditorContainer.classList.add('hidden');
            if (textInput) {
                textInput.classList.remove('flex-1');
                textInput.style.minHeight = '150px';
                textInput.style.flex = 'none';
                textInput.value = chapter.content || '';
                textInput.placeholder = 'Escribe contenido o inserta imágenes para la sección superior de los créditos...';
                textInput.readOnly = false;
                textInput.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            if (creditsContainer) creditsContainer.classList.remove('hidden');
            if (typeof initCreditsForm === 'function') initCreditsForm();
        } else {
            if (creditsContainer) creditsContainer.classList.add('hidden');
            if (rawEditorContainer) rawEditorContainer.classList.remove('hidden');
            if (textInput) {
                textInput.classList.add('flex-1');
                textInput.style.minHeight = '400px';
                textInput.style.flex = '';
                textInput.value = chapter.content || '';
                textInput.placeholder = 'Escribe tu historia aquí utilizando formato simple o las herramientas de arriba...';
                if (chapter.is_toc === '1') {
                    textInput.readOnly = true;
                    textInput.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    textInput.readOnly = false;
                    textInput.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        updateWordCounts();
        if (bookState.viewMode === 'preview' && typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        } else if (typeof refreshEditorDisplay === 'function') {
            refreshEditorDisplay(false);
        }
    } else if (bookState.chapters.length > 0) {
        bookState.activeChapterId = bookState.chapters[0].id;
        loadActiveChapter();
    } else {
        if (titleInput) titleInput.value = '';
        if (textInput) textInput.value = '';
        if (rawEditorContainer) rawEditorContainer.classList.remove('hidden');
        updateWordCounts();
        if (bookState.viewMode === 'preview' && typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        } else if (typeof refreshEditorDisplay === 'function') {
            refreshEditorDisplay(false);
        }
    }
}

function selectChapter(id) {
    bookState.activeChapterId = id;
    localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, id);
    loadActiveChapter();
    renderSidebar();
    saveStateToLocalStorage();
}

function createNewChapter(isToc = false, isCredits = false) {
    if (isToc && bookState.chapters.some(c => c.is_toc == '1')) {
        showToast('Ya existe un Índice', 'fa-solid fa-circle-exclamation');
        return;
    }

    if (isCredits && bookState.chapters.some(c => c.is_credits == '1')) {
        showToast('Ya existe una página de Créditos', 'fa-solid fa-circle-exclamation');
        return;
    }

    const newIndex = bookState.chapters.length + 1;
    const newId = `cap-${Date.now()}`;
    const newChapter = {
        id: newId,
        title: isToc ? 'Índice' : (isCredits ? 'Créditos' : `Capítulo ${newIndex}: Título Nuevo`),
        content: isToc ? 'En este capítulo se generará automáticamente el Índice de contenidos.' : (isCredits ? '' : `# Capítulo ${newIndex}\n## Título Nuevo\n\nComienza a escribir la historia de este capítulo aquí...`),
        is_toc: isToc ? '1' : '0',
        is_credits: isCredits ? '1' : '0',
        start_parity: isToc ? 'even' : 'any',
        opening_separate_content: '',
        chapter_blank_before: '0',
        chapter_blank_after: '0',
        hide_header: '0',
        hide_footer: '0',
        hide_all_headers_footers: '0',
        credits_hide_header: '0',
        credits_hide_page_number: '0',
        credits_margin_top: '',
        credits_margin_bottom: '',
        toc_hide_header: isToc ? '1' : '0',
        toc_hide_page_numbers: isToc ? '1' : '0',
        toc_separate_opening_content: '',
        toc_hide_title: isToc ? '0' : '0',
        toc_title_text: isToc ? 'Índice' : ''
    };

    bookState.chapters.push(newChapter);
    bookState.activeChapterId = newId;

    renderSidebar();
    loadActiveChapter();
    saveStateToLocalStorage();
    showToast('Capítulo creado', 'fa-solid fa-plus-circle');
}

function deleteChapter(id) {
    const chapterIndex = bookState.chapters.findIndex(c => c.id === id);
    if (chapterIndex === -1) return;

    if (confirm(`¿Estás seguro de que deseas eliminar "${bookState.chapters[chapterIndex].title}"? Esta acción no se puede deshacer.`)) {
        const chapter = bookState.chapters[chapterIndex];
        const chapterIdIsPersisted = typeof chapter.id === 'string' ? /^[0-9]+$/.test(chapter.id) : Number.isInteger(chapter.id);

        const removeChapterLocally = () => {
            bookState.chapters.splice(chapterIndex, 1);

            if (bookState.activeChapterId === id) {
                if (bookState.chapters.length > 0) {
                    bookState.activeChapterId = bookState.chapters[Math.max(0, chapterIndex - 1)].id;
                } else {
                    bookState.activeChapterId = null;
                }
            }

            if (bookState.activeChapterId) {
                localStorage.setItem(`almaden_active_chapter_${bookState.bookId}`, bookState.activeChapterId);
            } else {
                localStorage.removeItem(`almaden_active_chapter_${bookState.bookId}`);
            }

            renderSidebar();
            loadActiveChapter();
            saveStateToLocalStorage(true);
            showToast(`"${chapter.title}" fue eliminado`, 'fa-solid fa-trash-can');
        };

        if (!chapterIdIsPersisted) {
            removeChapterLocally();
            return;
        }

        const data = new FormData();
        data.append('action', 'almaden_delete_book_chapter');
        data.append('book_id', bookState.bookId);
        data.append('chapter_id', id);
        data.append('nonce', bookState.nonce);

        fetch(bookState.ajaxUrl, {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                const message = res && res.data ? res.data : 'No se pudo eliminar el capítulo.';
                showToast(message, 'fa-solid fa-circle-exclamation');
                return;
            }
            removeChapterLocally();
        })
        .catch(() => {
            showToast('Error al eliminar el capítulo', 'fa-solid fa-circle-exclamation');
        });
    }
}

function moveChapterUp(index) {
    if (index === 0) return;

    const temp = bookState.chapters[index];
    bookState.chapters[index] = bookState.chapters[index - 1];
    bookState.chapters[index - 1] = temp;

    renderSidebar();
    saveStateToLocalStorage();
    showToast('Índice reordenado', 'fa-solid fa-sort');
}

window.loadActiveChapter = loadActiveChapter;
window.selectChapter = selectChapter;
window.createNewChapter = createNewChapter;
window.deleteChapter = deleteChapter;
window.moveChapterUp = moveChapterUp;
