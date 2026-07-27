function applyLanguage(langCode) {
    const surface = getToolbarSurface();
    if (surface && surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);
        if (typeof wrapSelectionInVisualSpan === 'function') {
            wrapSelectionInVisualSpan('almaden-foreign', { lang: langCode });
        }
        triggerEditorUpdate('visual');
    } else {
        wrapText(`<foreign lang="${langCode}">`, '</foreign>');
    }

    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

function removeLanguage() {
    const surface = getToolbarSurface();
    if (!surface) return;

    if (surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);
        if (typeof execVisualCommand === 'function') {
            execVisualCommand('removeFormat');
        }
        triggerEditorUpdate('visual');
        const dropdown = document.getElementById('lang-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        return;
    }

    const textarea = surface;

    if (document.activeElement !== textarea && typeof window.editorLastSelection !== 'undefined') {
        textarea.selectionStart = window.editorLastSelection.start || 0;
        textarea.selectionEnd = window.editorLastSelection.end || 0;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

    if (start === end) {
        if (typeof showToast === 'function') {
            showToast("Selecciona el fragmento con marca de idioma para quitarla.", "fa-solid fa-circle-info");
        }
        const dropdown = document.getElementById('lang-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
        return;
    }

    const selectedText = textarea.value.substring(start, end);
    const cleanedText = selectedText
        .replace(/^\s*\[lang:[a-zA-Z-]{2,10}\]/i, '')
        .replace(/\[\/lang\]\s*$/i, '')
        .replace(/^\s*<foreign\s+lang=(?:"|')([a-zA-Z-]{2,10})(?:"|')\s*>/i, '')
        .replace(/<\/foreign>\s*$/i, '')
        .replace(/^\s*<lang\s+code=(?:"|')([a-zA-Z-]{2,10})(?:"|')\s*>/i, '')
        .replace(/<\/lang>\s*$/i, '');

    if (cleanedText === selectedText) {
        if (typeof showToast === 'function') {
            showToast("Selecciona incluyendo la etiqueta de idioma para quitarla.", "fa-solid fa-circle-info");
        }
    } else {
        textarea.value = textarea.value.substring(0, start) + cleanedText + textarea.value.substring(end);
        textarea.selectionStart = start;
        textarea.selectionEnd = start + cleanedText.length;
        textarea.focus();
        triggerEditorUpdate('raw');
    }

    const dropdown = document.getElementById('lang-dropdown');
    if (dropdown) dropdown.classList.add('hidden');
}

function applyFontSize() {
    const input = document.getElementById('toolbar-font-size');
    if (!input || !input.value) return;

    const surface = getToolbarSurface();
    if (surface && surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);
        if (typeof wrapSelectionInVisualSpan === 'function') {
            wrapSelectionInVisualSpan('', { style: `font-size: ${input.value}px;` });
        }
        triggerEditorUpdate('visual');
    } else {
        wrapText(`[size=${input.value}]`, '[/size]');
        const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
        if (textarea) textarea.focus();
    }
}

function applyFontFamily(fontName) {
    if (!fontName) return;

    const surface = getToolbarSurface();
    if (surface && surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);
        if (typeof wrapSelectionInVisualSpan === 'function') {
            wrapSelectionInVisualSpan('', { style: `font-family: '${fontName}', serif;` });
        }
        triggerEditorUpdate('visual');
    } else {
        wrapText(`[font="${fontName}"]`, '[/font]');
        const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
        if (textarea) textarea.focus();
    }

    const select = document.getElementById('toolbar-font-family');
    if (select) select.selectedIndex = 0;
}
