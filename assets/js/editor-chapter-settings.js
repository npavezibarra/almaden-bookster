// assets/js/editor-chapter-settings.js

/**
 * Abre el modal de configuración del capítulo actual y carga los valores.
 */
function openChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;
    
    // Buscar el capítulo activo en el estado global
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    if (!activeChapter) return;

    // Cargar los valores del capítulo en el formulario
    document.getElementById('chapter_hide_title').checked = activeChapter.hide_title === '1';
    document.getElementById('chapter_custom_running_header').value = activeChapter.custom_running_header || '';
    document.getElementById('chapter_drop_cap_enabled').checked = activeChapter.drop_cap_enabled === '1';
    document.getElementById('chapter_disable_hyphenation').checked = activeChapter.disable_hyphenation === '1';
    document.getElementById('chapter_page_one_vertical').value = activeChapter.page_one_vertical || 'top';
    document.getElementById('chapter_start_parity').value = activeChapter.start_parity || 'any';
    document.getElementById('chapter_show_header_page_one').checked = activeChapter.show_header_page_one === '1';
    document.getElementById('chapter_parity_image_mode').value = activeChapter.parity_image_mode || 'content';

    // Mostrar modal con animación
    modal.classList.remove('hidden');
    // Pequeño delay para que la transición CSS funcione
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modal.querySelector('.transform').classList.remove('scale-95');
        modal.querySelector('.transform').classList.add('scale-100');
    }, 10);
}

/**
 * Cierra el modal de configuración del capítulo.
 */
function closeChapterSettingsModal() {
    const modal = document.getElementById('chapter-settings-modal');
    if (!modal) return;
    
    modal.classList.add('opacity-0');
    modal.querySelector('.transform').classList.remove('scale-100');
    modal.querySelector('.transform').classList.add('scale-95');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300); // duración de la transición
}

/**
 * Guarda los valores del formulario de vuelta al objeto de capítulo y compila el PDF.
 */
function saveChapterSettings() {
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    if (!activeChapter) {
        closeChapterSettingsModal();
        return;
    }

    // Leer valores del formulario
    activeChapter.hide_title = document.getElementById('chapter_hide_title').checked ? '1' : '0';
    activeChapter.custom_running_header = document.getElementById('chapter_custom_running_header').value.trim();
    activeChapter.drop_cap_enabled = document.getElementById('chapter_drop_cap_enabled').checked ? '1' : '0';
    activeChapter.disable_hyphenation = document.getElementById('chapter_disable_hyphenation').checked ? '1' : '0';
    activeChapter.page_one_vertical = document.getElementById('chapter_page_one_vertical').value;
    activeChapter.start_parity = document.getElementById('chapter_start_parity').value;
    activeChapter.show_header_page_one = document.getElementById('chapter_show_header_page_one').checked ? '1' : '0';
    activeChapter.parity_image_mode = document.getElementById('chapter_parity_image_mode').value;

    // Cerrar modal
    closeChapterSettingsModal();

    // Marcar como pendiente de guardado y forzar actualización del PDF
    if (typeof markPendingChanges === 'function') {
        markPendingChanges();
    }
    
    // Si la función está disponible (debería), compilar para reflejar cambios
    if (typeof compilePDFPreview === 'function') {
        compilePDFPreview();
    }
    
    if (typeof showToast === 'function') {
        showToast("Configuración del capítulo actualizada.", "fa-solid fa-check");
    }
}
