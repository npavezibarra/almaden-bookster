// editor-toolbar.js

function getToolbarSurface() {
    const textarea = document.getElementById('editor-textarea');
    const visualSurface = typeof getVisualEditorSurface === 'function' ? getVisualEditorSurface() : null;

    if (window.editorSelectionSurface === 'visual' && visualSurface) return visualSurface;
    if (window.editorSelectionSurface === 'raw' && textarea) return textarea;
    if (document.activeElement === visualSurface) return visualSurface;
    if (document.activeElement === textarea) return textarea;
    if (bookState && bookState.viewMode === 'split' && visualSurface) return visualSurface;
    return textarea;
}

function restoreToolbarSelection(surface) {
    if (!surface) return;
    if (surface.id === 'editor-textarea' && typeof window.editorLastSelection !== 'undefined') {
        surface.selectionStart = window.editorLastSelection.start || 0;
        surface.selectionEnd = window.editorLastSelection.end || 0;
    }
    if (surface.id === 'visual-editor-surface' && typeof restoreVisualSelectionFromRange === 'function') {
        restoreVisualSelectionFromRange(surface);
    }
}

function refreshAfterEditorMutation(source = 'raw') {
    if (typeof updateWordCounts === 'function') updateWordCounts();
    if (typeof saveStateToLocalStorage === 'function') saveStateToLocalStorage();
    if (bookState && bookState.viewMode === 'split') {
        if (typeof scheduleSplitPreviewRefresh === 'function') {
            scheduleSplitPreviewRefresh(true);
        } else if (typeof refreshSplitPreview === 'function') {
            refreshSplitPreview(true);
        } else if (typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        }
    }
}

function wrapText(prefix, suffix) {
    const surface = getToolbarSurface();
    if (!surface) return;

    if (surface.id === 'visual-editor-surface' && typeof execVisualCommand === 'function') {
        restoreToolbarSelection(surface);

        if (prefix === '**' && suffix === '**') {
            execVisualCommand('bold');
        } else if (prefix === '*' && suffix === '*') {
            execVisualCommand('italic');
        } else if (prefix === '<u>' && suffix === '</u>') {
            execVisualCommand('underline');
        } else if (/^<foreign\s+lang=/.test(prefix) && suffix === '</foreign>') {
            const match = prefix.match(/^<foreign\s+lang=(?:"|')?([a-zA-Z-]{2,10})(?:"|')?>/i);
            const lang = match ? match[1] : 'la';
            if (typeof wrapSelectionInVisualSpan === 'function') {
                wrapSelectionInVisualSpan('almaden-foreign', { lang });
            }
        } else {
            document.execCommand('insertHTML', false, `${prefix}${suffix}`);
        }

        refreshAfterEditorMutation('visual');
        return;
    }

    const textarea = surface;
    if (document.activeElement !== textarea && typeof window.editorLastSelection !== 'undefined') {
        textarea.selectionStart = window.editorLastSelection.start || 0;
        textarea.selectionEnd = window.editorLastSelection.end || 0;
    }

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    if (start !== end) {
        const selectedText = text.substring(start, end);
        textarea.value = text.substring(0, start) + prefix + selectedText + suffix + text.substring(end);
        textarea.selectionStart = start + prefix.length;
        textarea.selectionEnd = end + prefix.length;
    } else {
        textarea.value = text.substring(0, start) + prefix + suffix + text.substring(start);
        textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
    }

    textarea.focus();
    triggerEditorUpdate('raw');
}

function addPrefix(prefix) {
    const surface = getToolbarSurface();
    if (!surface) return;

    if (surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);

        if (prefix === '# ') {
            execVisualCommand('formatBlock', 'h1');
        } else if (prefix === '## ') {
            execVisualCommand('formatBlock', 'h2');
        } else if (prefix === '> ') {
            execVisualCommand('formatBlock', 'blockquote');
        } else if (prefix === '- ') {
            execVisualCommand('insertUnorderedList');
        } else {
            document.execCommand('insertHTML', false, prefix);
        }

        refreshAfterEditorMutation('visual');
        return;
    }

    const textarea = surface;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;

    let lineStart = text.lastIndexOf('\n', start - 1);
    lineStart = lineStart === -1 ? 0 : lineStart + 1;

    textarea.value = text.substring(0, lineStart) + prefix + text.substring(lineStart);

    textarea.selectionStart = start + prefix.length;
    textarea.selectionEnd = end + prefix.length;
    textarea.focus();

    triggerEditorUpdate('raw');
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
        const fullSizeUrl = attachment.sizes && attachment.sizes.full && attachment.sizes.full.url
            ? attachment.sizes.full.url
            : '';
        const imgUrl = attachment.originalImageURL || fullSizeUrl || attachment.url;
        const imgAlt = attachment.alt || attachment.title || 'Imagen del libro';
        const imageTag = `\n<img src="${imgUrl}" alt="${imgAlt}" class="pdf-book-image" />\n`;
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

    if (bookState && bookState.settings && bookState.settings.chapter_start_parity === 'odd') {
        btn.classList.remove('hidden');
    } else {
        btn.classList.add('hidden');
    }
}

function insertAtCursor(text) {
    const surface = getToolbarSurface();
    if (!surface) return;

    if (surface.id === 'visual-editor-surface') {
        restoreToolbarSelection(surface);
        document.execCommand('insertHTML', false, text);
        triggerEditorUpdate('visual');
        return;
    }

    const textarea = surface;
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const value = textarea.value;

    textarea.value = value.substring(0, start) + text + value.substring(end);
    textarea.selectionStart = textarea.selectionEnd = start + text.length;
    textarea.focus();
    triggerEditorUpdate('raw');
}

function triggerEditorUpdate(source = 'auto') {
    const textarea = document.getElementById('editor-textarea');
    const visualSurface = typeof getVisualEditorSurface === 'function' ? getVisualEditorSurface() : null;
    const activeId = bookState.activeChapterId;
    const chapter = bookState.chapters.find(c => c.id === activeId);
    if (!chapter) return;

    const resolvedSource = source === 'auto'
        ? (window.editorSelectionSurface === 'visual' && visualSurface ? 'visual' : 'raw')
        : source;

    if (resolvedSource === 'visual' && visualSurface && typeof syncVisualEditorToState === 'function') {
        syncVisualEditorToState();
    } else if (textarea && typeof syncRawEditorToState === 'function') {
        syncRawEditorToState();
    }

    if (typeof updateWordCounts === 'function') updateWordCounts();
    if (bookState.viewMode === 'split') {
        if (typeof scheduleSplitPreviewRefresh === 'function') {
            scheduleSplitPreviewRefresh(true);
        } else if (typeof refreshSplitPreview === 'function') {
            refreshSplitPreview(true);
        } else if (typeof compilePDFPreview === 'function') {
            compilePDFPreview(true);
        }
    }
    if (typeof saveStateToLocalStorage === 'function') saveStateToLocalStorage();
}

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
        const textarea = document.getElementById('editor-textarea');
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
        const textarea = document.getElementById('editor-textarea');
        if (textarea) textarea.focus();
    }

    const select = document.getElementById('toolbar-font-family');
    if (select) select.selectedIndex = 0;
}
