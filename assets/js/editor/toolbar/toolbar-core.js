function getToolbarSurface() {
    const textarea = document.getElementById('editor-textarea');
    const visualSurface = typeof getVisualEditorSurface === 'function' ? getVisualEditorSurface() : null;

    if (window.editorSelectionSurface === 'visual' && visualSurface) return visualSurface;
    if (window.editorSelectionSurface === 'raw' && textarea) return textarea;
    if (document.activeElement === visualSurface) return visualSurface;
    if (document.activeElement === textarea) return textarea;
    if (typeof bookState !== 'undefined' && bookState && bookState.viewMode === 'split' && visualSurface) return visualSurface;
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
    if (typeof bookState !== 'undefined' && bookState && bookState.viewMode === 'split') {
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
    
    if (typeof bookState !== 'undefined' && bookState) {
        const activeId = bookState.activeChapterId;
        const chapter = bookState.chapters.find(c => c.id === activeId);
        if (!chapter) return;
    }

    const resolvedSource = source === 'auto'
        ? (window.editorSelectionSurface === 'visual' && visualSurface ? 'visual' : 'raw')
        : source;

    if (resolvedSource === 'visual' && visualSurface && typeof syncVisualEditorToState === 'function') {
        syncVisualEditorToState();
    } else if (textarea && typeof syncRawEditorToState === 'function') {
        syncRawEditorToState();
    }

    if (typeof updateWordCounts === 'function') updateWordCounts();
    if (typeof bookState !== 'undefined' && bookState && bookState.viewMode === 'split') {
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
