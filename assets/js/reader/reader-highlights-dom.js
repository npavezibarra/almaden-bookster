function hideHighlightToolbar() {
    const toolbar = document.getElementById('highlight-toolbar');
    if (toolbar) {
        toolbar.classList.add('hidden');
    }
}

function showHighlightToolbarAtRect(rect) {
    const toolbar = document.getElementById('highlight-toolbar');
    if (!toolbar) return;

    if (!rect || (!rect.width && !rect.height)) {
        hideHighlightToolbar();
        return;
    }

    const top = Math.max(12, rect.top - 54);
    const left = Math.max(20, rect.left + (rect.width / 2));

    toolbar.style.top = `${top}px`;
    toolbar.style.left = `${left}px`;
    toolbar.classList.remove('hidden');
    toolbar.classList.add('flex');
}

function showHighlightToolbarAtRange(range) {
    if (!range) {
        hideHighlightToolbar();
        return;
    }

    showHighlightToolbarAtRect(range.getBoundingClientRect());
}

function showHighlightToolbarAtElement(element) {
    if (!element || typeof element.getBoundingClientRect !== 'function') {
        hideHighlightToolbar();
        return;
    }

    showHighlightToolbarAtRect(element.getBoundingClientRect());
}

function getReaderHighlightLayer(root = getReaderChapterRoot()) {
    if (!root) {
        return null;
    }

    let layer = root.querySelector(':scope > .reader-highlight-layer');
    if (!layer) {
        layer = document.createElement('div');
        layer.className = 'reader-highlight-layer';
        root.prepend(layer);
    }

    return layer;
}

function clearReaderHighlightLayer(root = getReaderChapterRoot()) {
    const layer = root ? root.querySelector(':scope > .reader-highlight-layer') : null;
    if (layer) {
        layer.innerHTML = '';
    }
}

function clearReaderSelection() {
    const selection = window.getSelection();
    if (selection) {
        selection.removeAllRanges();
    }
}

function captureReaderSelection() {
    const root = getReaderChapterRoot();
    const selection = window.getSelection();

    if (almadenReaderHighlightState.suppressSelectionCapture) {
        return;
    }

    if (!root || !selection || selection.rangeCount === 0 || selection.isCollapsed) {
        almadenReaderHighlightState.selection = null;
        almadenReaderHighlightState.selectionText = '';
        hideHighlightToolbar();
        return;
    }

    const range = selection.getRangeAt(0);
    if (!root.contains(range.commonAncestorContainer)) {
        almadenReaderHighlightState.selection = null;
        almadenReaderHighlightState.selectionText = '';
        hideHighlightToolbar();
        return;
    }

    const selectedText = selection.toString();
    if (!selectedText || !selectedText.trim()) {
        almadenReaderHighlightState.selection = null;
        almadenReaderHighlightState.selectionText = '';
        hideHighlightToolbar();
        return;
    }

    almadenReaderHighlightState.selection = {
        startOffset: getAbsoluteTextOffset(root, range.startContainer, range.startOffset),
        endOffset: getAbsoluteTextOffset(root, range.endContainer, range.endOffset)
    };
    almadenReaderHighlightState.selectionText = selectedText;
    almadenReaderHighlightState.lastSelectionSnapshot = {
        selection: {
            startOffset: almadenReaderHighlightState.selection.startOffset,
            endOffset: almadenReaderHighlightState.selection.endOffset
        },
        text: selectedText
    };
    openReaderHighlightToolbarForSelection(range);
}

function wrapRangeWithHighlight(root, highlight) {
    if (!root || !highlight) return false;

    const highlightId = String(highlight.id || '');
    if (!highlightId) return false;

    if (root.querySelector(`[data-highlight-id="${highlightId}"]`)) {
        return true;
    }

    const startOffset = parseInt(highlight.start_offset, 10);
    const endOffset = parseInt(highlight.end_offset, 10);
    if (Number.isNaN(startOffset) || Number.isNaN(endOffset) || endOffset <= startOffset) {
        return false;
    }

    const range = buildRangeFromOffsets(root, startOffset, endOffset);
    if (!range || range.collapsed) {
        return false;
    }

    const layer = getReaderHighlightLayer(root);
    if (!layer) {
        return false;
    }

    const rootRect = root.getBoundingClientRect();
    const rects = Array.from(range.getClientRects())
        .filter(rect => rect.width > 0 && rect.height > 0);

    if (!rects.length) {
        return false;
    }

    rects.forEach(rect => {
        const box = document.createElement('div');
        box.className = 'reader-highlight';
        box.dataset.highlightId = highlightId;
        box.dataset.chapterId = String(highlight.chapter_id || '');
        box.dataset.startOffset = String(startOffset);
        box.dataset.endOffset = String(endOffset);
        box.setAttribute('aria-hidden', 'true');
        box.style.left = `${Math.max(0, rect.left - rootRect.left)}px`;
        box.style.top = `${Math.max(0, rect.top - rootRect.top)}px`;
        box.style.width = `${rect.width}px`;
        box.style.height = `${rect.height}px`;
        layer.appendChild(box);
    });

    return true;
}

function applyReaderHighlightsToCurrentChapter() {
    const root = getReaderChapterRoot();
    const chapterId = getReaderCurrentChapterId();

    if (!root || !chapterId || !bookData || !Array.isArray(bookData.highlights)) {
        return;
    }

    const chapterHighlights = bookData.highlights
        .filter(highlight => parseInt(highlight.chapter_id, 10) === chapterId)
        .sort((a, b) => {
            const startDiff = parseInt(a.start_offset, 10) - parseInt(b.start_offset, 10);
            if (startDiff !== 0) return startDiff;
            return parseInt(a.id, 10) - parseInt(b.id, 10);
        });

    clearReaderHighlightLayer(root);
    chapterHighlights.forEach(highlight => {
        wrapRangeWithHighlight(root, highlight);
    });

    if (almadenReaderHighlightState.pendingFocusHighlightId) {
        const pendingHighlightId = String(almadenReaderHighlightState.pendingFocusHighlightId);
        almadenReaderHighlightState.pendingFocusHighlightId = null;
        window.setTimeout(() => {
            focusReaderHighlightInCurrentChapter(pendingHighlightId);
        }, 60);
    }
}

function flashReaderHighlight(element) {
    if (!element) return;
    element.classList.add('is-focus');
    window.setTimeout(() => {
        element.classList.remove('is-focus');
    }, 1800);
}

function focusReaderHighlightInCurrentChapter(highlightId) {
    const root = getReaderChapterRoot();
    if (!root || !highlightId) return false;

    const elements = root.querySelectorAll(`[data-highlight-id="${String(highlightId)}"]`);
    const element = elements && elements.length ? elements[0] : null;
    if (!element) return false;

    element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
    elements.forEach(item => flashReaderHighlight(item));
    return true;
}
