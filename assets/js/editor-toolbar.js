// editor-toolbar.js

// Funciones para la barra de formato Markdown
function wrapText(prefix, suffix) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    // Si el textarea no tiene el foco (ej: se hizo clic en un botón de la toolbar),
    // restaurar la última selección conocida
    if (document.activeElement !== textarea && typeof window.editorLastSelection !== 'undefined') {
        textarea.selectionStart = window.editorLastSelection.start || 0;
        textarea.selectionEnd = window.editorLastSelection.end || 0;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    // Si hay texto seleccionado, lo envuelve
    if (start !== end) {
        const selectedText = text.substring(start, end);
        textarea.value = text.substring(0, start) + prefix + selectedText + suffix + text.substring(end);
        textarea.selectionStart = start + prefix.length;
        textarea.selectionEnd = end + prefix.length;
    } else {
        // Si no hay selección, inserta los marcadores y pone el cursor en medio
        textarea.value = text.substring(0, start) + prefix + suffix + text.substring(start);
        textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
    }
    
    textarea.focus();
    triggerEditorUpdate();
}

function addPrefix(prefix) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    
    // Encontrar el inicio de la línea donde está el cursor
    let lineStart = text.lastIndexOf('\n', start - 1);
    lineStart = lineStart === -1 ? 0 : lineStart + 1;
    
    textarea.value = text.substring(0, lineStart) + prefix + text.substring(lineStart);
    
    textarea.selectionStart = start + prefix.length;
    textarea.selectionEnd = end + prefix.length;
    textarea.focus();
    
    triggerEditorUpdate();
}

let mediaUploader;
function openMediaUploader() {
    if (mediaUploader) {
        mediaUploader.open();
        return;
    }

    if (typeof wp === 'undefined' || !wp.media) {
        if (typeof showToast === 'function') {
            showToast("Error: Media API no está disponible. Guarda y recarga la página.", "fa-solid fa-triangle-exclamation");
        }
        return;
    }

    mediaUploader = wp.media({
        title: 'Seleccionar Imagen',
        button: { text: 'Insertar en el capítulo' },
        multiple: false,
        library: { type: 'image' }
    });

    mediaUploader.on('select', function() {
        const attachment = mediaUploader.state().get('selection').first().toJSON();
        const imgUrl = attachment.url;
        const imgAlt = attachment.alt || attachment.title || 'Imagen del libro';
        
        // El wrapper HTML con tamaño pequeño para el editor, que el compilador pasará tal cual
        const imageTag = `\n<img src="${imgUrl}" alt="${imgAlt}" width="150" class="pdf-book-image" />\n`;
        
        insertAtCursor(imageTag);
    });

    mediaUploader.open();
}

let parityMediaUploader;
function openParityImageUploader() {
    if (!bookState || !bookState.activeChapterId) {
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
            
            // Mark as dirty and auto-save
            triggerEditorUpdate();
            
            // Si el motor ya está compilando, podríamos forzar un refresco
            if (typeof compilePDFPreview === 'function') {
                compilePDFPreview();
            }
        }
    });

    parityMediaUploader.open();
}

function updateParityButtonVisibility() {
    const btn = document.getElementById('btn-parity-image');
    if (!btn) return;
    
    if (bookState && bookState.settings && bookState.settings.chapter_start_parity === 'odd') {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
}

function insertAtCursor(text) {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;
    
    textarea.value = value.substring(0, start) + text + value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
    triggerEditorUpdate();
}

function triggerEditorUpdate() {
    const textarea = document.getElementById('editor-textarea');
    if (!textarea) return;
    
    const activeId = bookState.activeChapterId;
    const chapter = bookState.chapters.find(c => c.id === activeId);
    if (chapter) {
        chapter.content = textarea.value;
        if (typeof updateWordCounts === 'function') updateWordCounts();
        if (typeof compilePDFPreview === 'function') compilePDFPreview();
        if (typeof saveStateToLocalStorage === 'function') saveStateToLocalStorage();
    }
}

function applyLanguage(langCode) {
    wrapText(`[lang:${langCode}]`, '[/lang]');
    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

function removeLanguage() {
    if (typeof showToast === 'function') {
        showToast("Borra las etiquetas [lang] manualmente en el editor.", "fa-solid fa-circle-info");
    }
    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

// Funciones para aplicar estilos desde la barra de herramientas
function applyFontSize() {
    const input = document.getElementById('toolbar-font-size');
    if (input && input.value) {
        wrapText(`[size=${input.value}]`, '[/size]');
        // Devolvemos el foco al editor
        const textarea = document.getElementById('editor-textarea');
        if (textarea) textarea.focus();
    }
}

function applyFontFamily(fontName) {
    if (fontName) {
        wrapText(`[font="${fontName}"]`, '[/font]');
        // Reset the select box to show "Fuente..." again
        const select = document.getElementById('toolbar-font-family');
        if (select) select.selectedIndex = 0;
        
        const textarea = document.getElementById('editor-textarea');
        if (textarea) textarea.focus();
    }
}
