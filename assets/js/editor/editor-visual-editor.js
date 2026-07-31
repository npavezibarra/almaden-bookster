// editor visual editable para el panel derecho.
// Sincroniza cambios con el raw del capítulo activo.
window.visualEditorLastSelection = null;
window.splitPreviewRefreshTimer = null;

function isVisualEditorSurface(node) {
    const surface = getVisualEditorSurface();
    return !!(node && surface && (node === surface || surface.contains(node)));
}

function getVisualEditorSurface() {
    return document.querySelector('#pdf-scroller .pagedjs_pages[data-visual-editor-surface="1"]');
}

function getActiveEditableSurface() {
    const active = document.activeElement;
    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    if (active && textarea && active === textarea) {
        return active;
    }
    if (active && isVisualEditorSurface(active)) {
        return getVisualEditorSurface();
    }
    return null;
}

function getActiveChapter() {
    return (bookState.chapters || []).find(ch => ch.id === bookState.activeChapterId) || null;
}

function getChapterRenderHtml(chapter) {
    if (!chapter) return '';
    const index = (bookState.chapters || []).findIndex(ch => ch.id === chapter.id);
    const settings = bookState.settings || {};
    if (typeof window.buildChapterHTML === 'function') {
        return window.buildChapterHTML(chapter, index, settings, bookState);
    }
    return chapter.content || '';
}

function clearVisualEditorOverlay() {
    const scroller = document.getElementById('pdf-scroller');
    if (typeof detachVisualEditorMutationObserver === 'function') {
        detachVisualEditorMutationObserver();
    }
    if (scroller) {
        scroller.classList.remove('visual-editor-mode', 'visual-editor-readonly');
        const surface = scroller.querySelector('.pagedjs_pages[data-visual-editor-surface="1"]');
        if (surface) {
            surface.removeAttribute('data-visual-editor-surface');
            surface.removeAttribute('id');
        }
        scroller.querySelectorAll('[data-editor-content="chapter"][contenteditable]').forEach((node) => {
            node.removeAttribute('contenteditable');
            node.removeAttribute('spellcheck');
        });
    }
}

function stripIdsFromClone(root) {
    if (!root) return;
    if (root.removeAttribute) root.removeAttribute('id');
    root.querySelectorAll('[id]').forEach((el) => el.removeAttribute('id'));
}

function extractAlignFromNode(node) {
    const className = String(node.className || '');
    const classMatch = className.match(/almaden-align-(left|center|right|justify)/i);
    if (classMatch) return classMatch[1].toLowerCase();

    const styleAlign = node.style && node.style.textAlign ? String(node.style.textAlign).toLowerCase() : '';
    if (['left', 'center', 'right', 'justify'].includes(styleAlign)) {
        return styleAlign;
    }

    return '';
}

function getSpanStyleValue(node, property) {
    if (!node || !node.style) return '';
    const value = node.style[property];
    return value ? String(value).trim() : '';
}

function isImageBlockWrapper(node) {
    if (!node || node.nodeType !== Node.ELEMENT_NODE) return false;

    const tag = node.tagName.toLowerCase();
    if (tag !== 'figure' && tag !== 'div') return false;

    const className = String(node.className || '');
    const hasBlockClass = className.split(/\s+/).includes('pdf-book-image-block');
    const hasBlockAttr = node.getAttribute('data-image-block') === '1';

    return hasBlockClass || hasBlockAttr;
}

function escapeTextValue(value) {
    return String(value || '')
        .replace(/\r\n/g, '\n')
        .replace(/\r/g, '\n');
}

function normalizeSerializedTextValue(node, value) {
    let text = escapeTextValue(value);
    if (!text) return text;

    // Paged.js inserts its own hyphen glyph at layout breaks. Strip only that
    // generated marker so we keep real author hyphens untouched.
    if (node && node.parentElement && node.parentElement.closest('.pagedjs_hyphen')) {
        text = text.replace(/[\u00AD\u2010\u2011]+$/g, '');
    }

    return text.replace(/\u00AD/g, '');
}

function serializeInlineNode(node) {
    if (!node) return '';

    if (node.nodeType === Node.TEXT_NODE) {
        return normalizeSerializedTextValue(node, node.textContent || '');
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    const tag = node.tagName.toLowerCase();

    if (tag === 'br') return '\n';
    if (tag === 'strong' || tag === 'b') return `**${serializeInlineChildren(node)}**`;
    if (tag === 'em' || tag === 'i') return `*${serializeInlineChildren(node)}*`;
    if (tag === 'u') return `<u>${serializeInlineChildren(node)}</u>`;
    if (tag === 'img') {
        const src = node.getAttribute('src') || '';
        const alt = node.getAttribute('alt') || '';
        const cls = node.getAttribute('class') || 'pdf-book-image';
        return `\n<img src="${src}" alt="${alt}" class="${cls}" />\n`;
    }

    if (tag === 'span') {
        const lang = String(node.getAttribute('lang') || '');
        const className = String(node.className || '');
        const isForeign = className.includes('almaden-foreign') || !!lang;
        if (isForeign) {
            const safeLang = lang || 'la';
            return `<foreign lang="${safeLang}">${serializeTextOnlyChildren(node)}</foreign>`;
        }

        const fontSize = getSpanStyleValue(node, 'fontSize');
        if (fontSize) {
            return `[size=${fontSize}]${serializeInlineChildren(node)}[/size]`;
        }

        const fontFamily = getSpanStyleValue(node, 'fontFamily');
        if (fontFamily) {
            return `[font="${fontFamily.replace(/['"]/g, '')}"]${serializeInlineChildren(node)}[/font]`;
        }
    }

    return serializeInlineChildren(node);
}

function serializeInlineChildren(node) {
    return Array.from(node.childNodes || []).map(serializeInlineNode).join('');
}

function serializeTextOnlyChildren(node) {
    return Array.from(node.childNodes || []).map((child) => {
        if (child.nodeType === Node.TEXT_NODE) return normalizeSerializedTextValue(child, child.textContent || '');
        if (child.nodeType === Node.ELEMENT_NODE && child.tagName.toLowerCase() === 'br') return '\n';
        return normalizeSerializedTextValue(child, child.textContent || '');
    }).join('');
}

function serializeParagraphLike(node, prefix = '', suffix = '') {
    const text = serializeInlineChildren(node).trim();
    if (!text) return '';
    return `${prefix}${text}${suffix}`;
}

function serializeBlockNode(node) {
    if (!node) return '';

    if (node.nodeType === Node.TEXT_NODE) {
        const text = normalizeSerializedTextValue(node, node.textContent || '').trim();
        return text;
    }

    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }

    const tag = node.tagName.toLowerCase();

    if (isImageBlockWrapper(node)) {
        const clone = node.cloneNode(true);
        if (clone.removeAttribute) {
            clone.removeAttribute('style');
        }
        clone.querySelectorAll('.pdf-book-image-edit-handle').forEach((el) => el.remove());
        clone.querySelectorAll('[style]').forEach((el) => el.removeAttribute('style'));
        return `\n${clone.outerHTML}\n`;
    }

    if (tag === 'p') return serializeParagraphLike(node);
    if (tag === 'h1') return `# ${serializeInlineChildren(node).trim()}`;
    if (tag === 'h2') return `## ${serializeInlineChildren(node).trim()}`;
    if (tag === 'h3') return `### ${serializeInlineChildren(node).trim()}`;
    if (tag === 'blockquote') {
        return `> ${serializeInlineChildren(node).trim().replace(/\n+/g, '\n> ')}`;
    }
    if (tag === 'ul') {
        return Array.from(node.children || [])
            .filter(li => li && li.tagName && li.tagName.toLowerCase() === 'li')
            .map(li => `- ${serializeInlineChildren(li).trim()}`)
            .join('\n');
    }
    if (tag === 'ol') {
        let counter = 1;
        return Array.from(node.children || [])
            .filter(li => li && li.tagName && li.tagName.toLowerCase() === 'li')
            .map(li => `${counter++}. ${serializeInlineChildren(li).trim()}`)
            .join('\n');
    }
    if (tag === 'img') return serializeInlineNode(node).trim();

    if (tag === 'div') {
        const align = extractAlignFromNode(node);
        if (align) {
            const inner = serializeChildrenAsBlocks(node);
            return `[align=${align}]\n${inner}\n[/align]`;
        }

        const className = String(node.className || '');
        if (className.includes('almaden-box')) {
            return `[box]\n${serializeChildrenAsBlocks(node)}\n[/box]`;
        }
        if (className.includes('almaden-columns')) {
            return `[columns]\n${serializeChildrenAsBlocks(node)}\n[/columns]`;
        }
        if (className.includes('almaden-col')) {
            return `[col]\n${serializeChildrenAsBlocks(node)}\n[/col]`;
        }
        if (className.includes('chapter-opening-block') || className.includes('chapter-opening-content') || className.includes('chapter-subtitle')) {
            return serializeChildrenAsBlocks(node);
        }
    }

    return serializeChildrenAsBlocks(node);
}

function serializeChildrenAsBlocks(node) {
    return Array.from(node.childNodes || [])
        .map(serializeBlockNode)
        .filter(Boolean)
        .join('\n\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function serializeVisualEditorSurface(surface) {
    if (!surface) return '';
    const chapter = getActiveChapter();
    if (!chapter) return '';
    const fragments = Array.from(surface.querySelectorAll(`.chapter-section-${chapter.id} [data-editor-content="chapter"]`));
    if (!fragments.length) {
        // Algunos capítulos, como Créditos o TOC, no exponen un bloque editable
        // dentro de la vista visual. En ese caso no debemos sobrescribir el
        // contenido guardado con una cadena vacía.
        return null;
    }
    const blocks = [];
    const blockMap = new Map();

    fragments.forEach((fragment) => {
        Array.from(fragment.childNodes || []).forEach((node) => {
            const blockId = node.nodeType === Node.ELEMENT_NODE ? node.getAttribute('data-editor-block-id') : '';
            if (!blockId || !blockMap.has(blockId)) {
                const clone = node.cloneNode(true);
                blocks.push(clone);
                if (blockId) blockMap.set(blockId, clone);
                return;
            }

            const target = blockMap.get(blockId);
            Array.from(node.childNodes || []).forEach(child => target.appendChild(child.cloneNode(true)));
        });
    });

    const serialized = blocks.map(serializeBlockNode).filter(Boolean).join('\n\n');
    return serialized.replace(/\n{3,}/g, '\n\n').trim();
}

function setRawChapterContent(nextContent) {
    const chapter = getActiveChapter();
    if (!chapter) return;

    chapter.content = nextContent;
    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    if (textarea && textarea.value !== nextContent) {
        textarea.value = nextContent;
    }
}

function syncRawEditorToState() {
    const textarea = typeof getRawEditorSurface === 'function' ? getRawEditorSurface() : null;
    const chapter = getActiveChapter();
    if (!textarea || !chapter) return;

    setRawChapterContent(textarea.value);
}

function syncVisualEditorToState() {
    const chapter = getActiveChapter();
    if (!chapter) return null;

    // Paged.js fragments are a render artifact and must never replace RAW.
    syncRawEditorToState();
    return chapter.content;
}

function updateVisualEditorFromState() {
    const surface = getVisualEditorSurface();
    const chapter = getActiveChapter();
    if (!surface || !chapter) return;

    renderVisualEditorPane();
}

function renderVisualEditorPane() {
    const scroller = document.getElementById('pdf-scroller');
    if (!scroller) return;

    clearVisualEditorOverlay();
    window.visualEditorIsDirty = false;
    window.visualEditorIsEditing = false;
    scroller.classList.add('visual-editor-readonly');
}

function trackVisualEditorSelection() {
    const surface = getVisualEditorSurface();
    const selection = window.getSelection ? window.getSelection() : null;
    if (!surface || !selection || selection.rangeCount === 0) return;
    const range = selection.getRangeAt(0);
    if (!surface.contains(range.commonAncestorContainer)) return;
    window.visualEditorLastSelection = captureVisualSelection(surface, range);
    window.editorSelectionSurface = 'visual';
}

function restoreVisualSelectionFromRange(surface = getVisualEditorSurface()) {
    if (!surface || !window.visualEditorLastSelection) return false;
    return restoreVisualSelectionSnapshot(surface, window.visualEditorLastSelection);
}

function execVisualCommand(command, value = null) {
    const surface = getVisualEditorSurface();
    if (!surface) return false;
    if (!isVisualEditorSurface(document.activeElement)) {
        restoreVisualSelectionFromRange(surface);
    }
    try {
        document.execCommand(command, false, value);
        return true;
    } catch (err) {
        return false;
    }
}

function wrapVisualSelection(prefix, suffix) {
    const surface = getVisualEditorSurface();
    if (!surface) return false;

    if (!isVisualEditorSurface(document.activeElement)) {
        restoreVisualSelectionFromRange(surface);
    }

    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection || selection.rangeCount === 0) return false;

    const range = selection.getRangeAt(0);
    if (!surface.contains(range.commonAncestorContainer)) return false;

    const wrapper = document.createElement('span');
    wrapper.dataset.almadenWrapper = '1';
    wrapper.innerHTML = `${prefix}${range.toString()}${suffix}`;
    range.deleteContents();
    range.insertNode(wrapper);
    selection.removeAllRanges();
    const newRange = document.createRange();
    newRange.selectNodeContents(wrapper);
    selection.addRange(newRange);
    return true;
}

function wrapSelectionInVisualSpan(className, attributes = {}) {
    const surface = getVisualEditorSurface();
    if (!surface) return false;

    if (!isVisualEditorSurface(document.activeElement)) {
        restoreVisualSelectionFromRange(surface);
    }

    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection || selection.rangeCount === 0) return false;

    const range = selection.getRangeAt(0);
    if (!surface.contains(range.commonAncestorContainer)) return false;

    const wrapper = document.createElement('span');
    if (className) wrapper.className = className;
    Object.entries(attributes).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            wrapper.setAttribute(key, value);
        }
    });
    wrapper.appendChild(range.extractContents());
    if (!wrapper.textContent.trim()) {
        wrapper.textContent = '\u200B';
    }
    range.insertNode(wrapper);
    selection.removeAllRanges();
    const newRange = document.createRange();
    newRange.selectNodeContents(wrapper);
    selection.addRange(newRange);
    return true;
}

function applyVisualBlockAlignment(alignment) {
    if (!execVisualCommand(`justify${alignment.charAt(0).toUpperCase()}${alignment.slice(1)}`)) {
        execVisualCommand('justifyLeft');
    }
}

function refreshEditorDisplay(scrollToActive = false) {
    if (bookState.viewMode === 'preview' || bookState.viewMode === 'split') {
        if (bookState.viewMode === 'split' && (window.visualEditorIsEditing || window.visualEditorIsDirty)) {
            return Promise.resolve();
        }
        clearVisualEditorOverlay();
        if (typeof compilePDFPreview === 'function') {
            const result = compilePDFPreview(scrollToActive, 'pdf-scroller', false);
            if (bookState.viewMode === 'split' && result && typeof result.then === 'function') {
                return result.then(() => {
                    if (typeof renderVisualEditorPane === 'function') {
                        renderVisualEditorPane();
                    }
                });
            }
            return result;
        }
        return Promise.resolve();
    }

    return Promise.resolve();
}

function refreshSplitPreview(scrollToActive = false) {
    if (window.visualEditorIsEditing || window.visualEditorIsDirty) {
        return Promise.resolve();
    }
    clearVisualEditorOverlay();
    if (typeof compilePDFPreview !== 'function') return Promise.resolve();
    const result = compilePDFPreview(scrollToActive, 'pdf-scroller', false);
    if (result && typeof result.then === 'function') {
        return result.then(() => {
            if (bookState.viewMode === 'split' && typeof renderVisualEditorPane === 'function') {
                renderVisualEditorPane();
            }
        });
    }
    return Promise.resolve();
}

function scheduleSplitPreviewRefresh(scrollToActive = false, delay = 180) {
    if (window.visualEditorIsEditing || window.visualEditorIsDirty) {
        return;
    }
    if (window.splitPreviewRefreshTimer) {
        clearTimeout(window.splitPreviewRefreshTimer);
    }

    window.splitPreviewRefreshTimer = setTimeout(() => {
        window.splitPreviewRefreshTimer = null;
        if (bookState && bookState.viewMode === 'split' && typeof refreshSplitPreview === 'function') {
            refreshSplitPreview(scrollToActive);
        }
    }, delay);
}

window.getActiveEditableSurface = getActiveEditableSurface;
window.getVisualEditorSurface = getVisualEditorSurface;
window.renderVisualEditorPane = renderVisualEditorPane;
window.syncRawEditorToState = syncRawEditorToState;
window.syncVisualEditorToState = syncVisualEditorToState;
window.updateVisualEditorFromState = updateVisualEditorFromState;
window.refreshEditorDisplay = refreshEditorDisplay;
window.refreshSplitPreview = refreshSplitPreview;
window.scheduleSplitPreviewRefresh = scheduleSplitPreviewRefresh;
window.restoreVisualSelectionFromRange = restoreVisualSelectionFromRange;
window.restoreVisualSelectionSnapshot = restoreVisualSelectionSnapshot;
window.execVisualCommand = execVisualCommand;
window.wrapVisualSelection = wrapVisualSelection;
window.wrapSelectionInVisualSpan = wrapSelectionInVisualSpan;
window.applyVisualBlockAlignment = applyVisualBlockAlignment;
window.trackVisualEditorSelection = trackVisualEditorSelection;
