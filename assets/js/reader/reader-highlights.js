let almadenReaderHighlightState = {
    selection: null,
    selectionText: '',
    saveInFlight: false,
    panelOpen: false,
    deleteInFlight: false,
    pendingFocusHighlightId: null,
    commentsByHighlightId: {},
    commentsLoadingByHighlightId: {},
    openCommentsHighlightId: null,
    commentSaveInFlight: false,
    commentDeleteInFlight: false,
    commentComposerHighlightId: null,
    commentEditingId: null,
    commentEditingText: '',
    composerOpen: false,
    composerSelectionHighlightId: null,
    composerSelectionSnapshot: null,
    lastSelectionSnapshot: null
};

function getReaderChapterRoot() {
    return document.getElementById('chapter-content');
}

function getReaderBookId() {
    return bookData && bookData.bookId ? parseInt(bookData.bookId, 10) : 0;
}

function getReaderCurrentChapterId() {
    if (typeof currentChapterIndex !== 'number' || !bookData || !bookData.chapters || !bookData.chapters[currentChapterIndex]) {
        return 0;
    }
    return parseInt(bookData.chapters[currentChapterIndex].id, 10) || 0;
}

function getAbsoluteTextOffset(root, container, offset) {
    const range = document.createRange();
    range.selectNodeContents(root);
    range.setEnd(container, offset);
    return range.toString().length;
}

function getPositionFromAbsoluteOffset(root, absoluteOffset) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
    let currentOffset = 0;
    let node = walker.nextNode();

    while (node) {
        const nodeLength = node.nodeValue.length;
        if (currentOffset + nodeLength >= absoluteOffset) {
            return {
                node: node,
                offset: absoluteOffset - currentOffset
            };
        }
        currentOffset += nodeLength;
        node = walker.nextNode();
    }

    return null;
}

function buildRangeFromOffsets(root, startOffset, endOffset) {
    const start = getPositionFromAbsoluteOffset(root, startOffset);
    const end = getPositionFromAbsoluteOffset(root, endOffset);

    if (!start || !end) {
        return null;
    }

    const range = document.createRange();
    range.setStart(start.node, start.offset);
    range.setEnd(end.node, end.offset);
    return range;
}

function hideHighlightToolbar() {
    const toolbar = document.getElementById('highlight-toolbar');
    if (toolbar) {
        toolbar.classList.add('hidden');
    }
}

function showHighlightToolbarAtRange(range) {
    const toolbar = document.getElementById('highlight-toolbar');
    if (!toolbar) return;

    const rect = range.getBoundingClientRect();
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

function getReaderHighlightsPanel() {
    return document.getElementById('reader-highlights-panel');
}

function getReaderHighlightsBackdrop() {
    return document.getElementById('reader-highlights-backdrop');
}

function getReaderHighlightsListContainer() {
    return document.getElementById('reader-highlights-list');
}

function getReaderHighlightsCountLabel() {
    return document.getElementById('reader-highlights-count');
}

function openReaderHighlightCommentComposer(highlight, comment = null) {
    if (!highlight) {
        return;
    }

    almadenReaderHighlightState.commentComposerHighlightId = getReaderHighlightKey(highlight.id);
    almadenReaderHighlightState.commentEditingId = comment && comment.id ? String(comment.id) : null;
    almadenReaderHighlightState.commentEditingText = comment && comment.comment_text ? String(comment.comment_text) : '';
    renderReaderHighlightsPanel();
}

function closeReaderHighlightCommentComposer() {
    almadenReaderHighlightState.commentComposerHighlightId = null;
    almadenReaderHighlightState.commentEditingId = null;
    almadenReaderHighlightState.commentEditingText = '';
}

function getReaderHighlightComposer() {
    return document.getElementById('highlight-comment-composer');
}

function getReaderHighlightComposerInput() {
    return document.getElementById('highlight-comment-input');
}

function getReaderHighlightComposerSaveButton() {
    return document.getElementById('btn-save-comment-composer');
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

function getReaderHighlightKey(highlightId) {
    return String(parseInt(highlightId, 10) || 0);
}

function getReaderAjaxUrl() {
    return typeof almadenAjaxUrl !== 'undefined' ? almadenAjaxUrl : (window.almadenAjaxUrl || '');
}

function formatReaderDate(dateString) {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return dateString;
    }

    try {
        return new Intl.DateTimeFormat('es-CL', {
            dateStyle: 'medium',
            timeStyle: 'short'
        }).format(date);
    } catch (error) {
        return dateString;
    }
}

function normalizeReaderSelectionSnapshot(snapshot) {
    if (!snapshot || !snapshot.selection) {
        return null;
    }

    const rawText = String(snapshot.text || '');
    const cleanedText = rawText.trim();

    if (!cleanedText) {
        return null;
    }

    return {
        selection: {
            startOffset: Math.max(0, parseInt(snapshot.selection.startOffset, 10) || 0),
            endOffset: Math.max(0, parseInt(snapshot.selection.endOffset, 10) || 0)
        },
        text: cleanedText
    };
}

function getSortedBookHighlights() {
    if (!bookData || !Array.isArray(bookData.highlights)) {
        return [];
    }

    return bookData.highlights
        .filter(highlight => String(highlight.status || 'active') === 'active')
        .sort((a, b) => {
            const chapterA = parseInt(a.chapter_id, 10) || 0;
            const chapterB = parseInt(b.chapter_id, 10) || 0;
            if (chapterA !== chapterB) {
                const chapterIndexA = Array.isArray(bookData.chapters) ? bookData.chapters.findIndex(chapter => parseInt(chapter.id, 10) === chapterA) : -1;
                const chapterIndexB = Array.isArray(bookData.chapters) ? bookData.chapters.findIndex(chapter => parseInt(chapter.id, 10) === chapterB) : -1;
                return chapterIndexA - chapterIndexB;
            }

            const startDiff = parseInt(a.start_offset, 10) - parseInt(b.start_offset, 10);
            if (startDiff !== 0) return startDiff;
            return parseInt(a.id, 10) - parseInt(b.id, 10);
        });
}

function getReaderHighlightChapterTitle(highlight) {
    if (!bookData || !Array.isArray(bookData.chapters) || !highlight) {
        return 'Capítulo';
    }

    const chapter = bookData.chapters.find(item => parseInt(item.id, 10) === parseInt(highlight.chapter_id, 10));
    return chapter ? chapter.title : 'Capítulo';
}

function getReaderHighlightChapterIndex(highlight) {
    if (!bookData || !Array.isArray(bookData.chapters) || !highlight) {
        return -1;
    }

    return bookData.chapters.findIndex(item => parseInt(item.id, 10) === parseInt(highlight.chapter_id, 10));
}

function closeReaderHighlightsPanel() {
    const panel = getReaderHighlightsPanel();
    const backdrop = getReaderHighlightsBackdrop();
    if (panel) {
        panel.classList.add('hidden');
    }
    if (backdrop) {
        backdrop.classList.add('hidden');
    }
    almadenReaderHighlightState.panelOpen = false;
    almadenReaderHighlightState.openCommentsHighlightId = null;
}

function hideReaderHighlightComposer() {
    const composer = getReaderHighlightComposer();
    const input = getReaderHighlightComposerInput();
    if (composer) {
        composer.classList.add('hidden');
    }
    if (input) {
        input.value = '';
    }
    almadenReaderHighlightState.composerOpen = false;
    almadenReaderHighlightState.composerSelectionHighlightId = null;
    almadenReaderHighlightState.composerSelectionSnapshot = null;
}

function positionReaderHighlightComposer(range) {
    const composer = getReaderHighlightComposer();
    if (!composer || !range) return;

    const rect = range.getBoundingClientRect();
    if (!rect || (!rect.width && !rect.height)) {
        return;
    }

    const top = Math.max(12, rect.top - 14);
    const left = Math.max(12, rect.left + (rect.width / 2));
    composer.style.top = `${top}px`;
    composer.style.left = `${left}px`;
    composer.style.transform = 'translate(-50%, -100%)';
}

function openReaderHighlightComposer() {
    const composer = getReaderHighlightComposer();
    const input = getReaderHighlightComposerInput();
    const root = getReaderChapterRoot();
    const selection = window.getSelection();

    if (!composer || !input || !root || !selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);
    if (!root.contains(range.commonAncestorContainer)) {
        return;
    }

    const selectionSnapshot = almadenReaderHighlightState.selection || (almadenReaderHighlightState.lastSelectionSnapshot ? almadenReaderHighlightState.lastSelectionSnapshot.selection : null);
    const textSnapshot = almadenReaderHighlightState.selectionText || (almadenReaderHighlightState.lastSelectionSnapshot ? almadenReaderHighlightState.lastSelectionSnapshot.text : '') || selection.toString() || '';
    almadenReaderHighlightState.composerSelectionSnapshot = {
        selection: selectionSnapshot ? {
            startOffset: selectionSnapshot.startOffset,
            endOffset: selectionSnapshot.endOffset
        } : {
            startOffset: getAbsoluteTextOffset(root, range.startContainer, range.startOffset),
            endOffset: getAbsoluteTextOffset(root, range.endContainer, range.endOffset)
        },
        text: textSnapshot
    };
    positionReaderHighlightComposer(range);
    hideHighlightToolbar();
    composer.classList.remove('hidden');
    almadenReaderHighlightState.composerOpen = true;
    almadenReaderHighlightState.composerSelectionHighlightId = null;

    window.setTimeout(() => {
        input.focus();
    }, 0);
}

async function submitReaderHighlightComposer() {
    const input = getReaderHighlightComposerInput();
    const commentText = input ? input.value.trim() : '';
    if (!commentText) {
        return;
    }

    await saveReaderHighlightCore(commentText);
}

function openReaderHighlightsPanel() {
    const panel = getReaderHighlightsPanel();
    const backdrop = getReaderHighlightsBackdrop();
    if (!panel || !backdrop) return;

    renderReaderHighlightsPanel();
    panel.classList.remove('hidden');
    backdrop.classList.remove('hidden');
    almadenReaderHighlightState.panelOpen = true;
}

function toggleReaderHighlightsPanel(forceOpen) {
    const panel = getReaderHighlightsPanel();
    if (!panel) return;

    const shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : panel.classList.contains('hidden');
    if (shouldOpen) {
        openReaderHighlightsPanel();
    } else {
        closeReaderHighlightsPanel();
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

function renderReaderHighlightsPanel() {
    const listContainer = getReaderHighlightsListContainer();
    const countLabel = getReaderHighlightsCountLabel();
    if (!listContainer) return;

    const highlights = getSortedBookHighlights();
    if (countLabel) {
        countLabel.textContent = highlights.length === 1
            ? '1 destacado guardado'
            : `${highlights.length} destacados guardados`;
    }

    listContainer.innerHTML = '';

    if (!highlights.length) {
        const empty = document.createElement('div');
        empty.className = 'text-sm text-gray-500 bg-gray-50 border border-gray-100 rounded-2xl p-4 leading-relaxed';
        empty.textContent = 'Todavía no tienes highlights en este libro. Selecciona un fragmento y guárdalo para verlo aquí.';
        listContainer.appendChild(empty);
        return;
    }

    const grouped = new Map();
    highlights.forEach(highlight => {
        const chapterKey = String(highlight.chapter_id || 0);
        if (!grouped.has(chapterKey)) {
            grouped.set(chapterKey, []);
        }
        grouped.get(chapterKey).push(highlight);
    });

    Array.from(grouped.entries()).forEach(([chapterId, chapterHighlights]) => {
        const section = document.createElement('section');
        section.className = 'reader-highlights-section';

        const header = document.createElement('div');
        header.className = 'reader-highlights-section-header';

        const title = document.createElement('span');
        title.textContent = getReaderHighlightChapterTitle(chapterHighlights[0]);

        const badge = document.createElement('span');
        badge.className = 'reader-highlights-badge';
        badge.textContent = String(chapterHighlights.length);

        header.appendChild(title);
        header.appendChild(badge);
        section.appendChild(header);

        const list = document.createElement('div');
        list.className = 'reader-highlights-items';

        chapterHighlights.forEach(highlight => {
            const highlightId = getReaderHighlightKey(highlight.id);
            const item = document.createElement('article');
            item.className = 'reader-highlight-item';
            item.dataset.highlightId = highlightId;

            const body = document.createElement('button');
            body.type = 'button';
            body.className = 'reader-highlight-item-body';
            const quote = document.createElement('div');
            quote.className = 'reader-highlight-item-quote';
            quote.textContent = (highlight.selected_text || '').replace(/\s+/g, ' ').trim();

            body.appendChild(quote);
            body.addEventListener('click', () => {
                const chapterIndex = getReaderHighlightChapterIndex(highlight);
                almadenReaderHighlightState.pendingFocusHighlightId = String(highlight.id || '');
                closeReaderHighlightsPanel();
                if (chapterIndex >= 0 && chapterIndex !== currentChapterIndex && typeof showChapterView === 'function') {
                    showChapterView(chapterIndex);
                    return;
                }
                window.setTimeout(() => {
                    focusReaderHighlightInCurrentChapter(highlightId);
                }, 40);
            });

            const actions = document.createElement('div');
            actions.className = 'reader-highlight-item-actions';

            const commentsBtn = document.createElement('button');
            commentsBtn.type = 'button';
            commentsBtn.className = 'reader-highlight-comments-btn';
            commentsBtn.title = almadenReaderHighlightState.openCommentsHighlightId === highlightId ? 'Ocultar comentarios' : 'Comentarios';
            commentsBtn.setAttribute('aria-label', commentsBtn.title);
            commentsBtn.innerHTML = '<i class="fa-solid fa-comment-dots"></i>';
            commentsBtn.addEventListener('click', event => {
                event.stopPropagation();
                toggleReaderHighlightComments(highlight);
            });

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'reader-highlight-delete-btn';
            deleteBtn.title = 'Borrar';
            deleteBtn.setAttribute('aria-label', 'Borrar');
            deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
            deleteBtn.addEventListener('click', event => {
                event.stopPropagation();
                deleteReaderHighlight(highlight);
            });

            actions.appendChild(commentsBtn);
            actions.appendChild(deleteBtn);
            item.appendChild(body);
            item.appendChild(actions);

            if (almadenReaderHighlightState.openCommentsHighlightId === highlightId) {
                const commentsBox = document.createElement('div');
                commentsBox.className = 'reader-highlight-comments-box';
                commentsBox.dataset.highlightId = highlightId;
                renderReaderHighlightCommentsSection(highlight, commentsBox);
                item.appendChild(commentsBox);
            }

            list.appendChild(item);
        });

        section.appendChild(list);
        listContainer.appendChild(section);
    });
}

function getReaderHighlightComments(highlightId) {
    return almadenReaderHighlightState.commentsByHighlightId[getReaderHighlightKey(highlightId)] || [];
}

function isReaderHighlightCommentsLoading(highlightId) {
    return Boolean(almadenReaderHighlightState.commentsLoadingByHighlightId[getReaderHighlightKey(highlightId)]);
}

async function loadReaderHighlightComments(highlight, forceRefresh = false) {
    const bookId = getReaderBookId();
    const highlightId = parseInt(highlight && highlight.id, 10) || 0;
    if (!bookId || !highlightId) {
        return [];
    }

    const key = getReaderHighlightKey(highlightId);
    if (!forceRefresh && Array.isArray(almadenReaderHighlightState.commentsByHighlightId[key])) {
        return almadenReaderHighlightState.commentsByHighlightId[key];
    }

    almadenReaderHighlightState.commentsLoadingByHighlightId[key] = true;
    renderReaderHighlightsPanel();

    try {
        const params = new URLSearchParams();
        params.set('action', 'almaden_list_book_highlight_comments');
        params.set('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        params.set('book_id', String(bookId));
        params.set('highlight_id', String(highlightId));
        params.set('chapter_id', String(parseInt(highlight.chapter_id, 10) || 0));

        const response = await fetch(`${getReaderAjaxUrl()}?${params.toString()}`, {
            method: 'GET',
            credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!payload || !payload.success || !payload.data || !Array.isArray(payload.data.comments)) {
            throw new Error((payload && payload.data) || 'No se pudieron cargar los comentarios.');
        }

        almadenReaderHighlightState.commentsByHighlightId[key] = payload.data.comments;
        return payload.data.comments;
    } catch (error) {
        console.error(error);
        almadenReaderHighlightState.commentsByHighlightId[key] = [];
        return [];
    } finally {
        delete almadenReaderHighlightState.commentsLoadingByHighlightId[key];
        renderReaderHighlightsPanel();
    }
}

function toggleReaderHighlightComments(highlight) {
    const highlightId = getReaderHighlightKey(highlight && highlight.id);
    if (!highlightId) {
        return;
    }

    if (almadenReaderHighlightState.openCommentsHighlightId === highlightId) {
        almadenReaderHighlightState.openCommentsHighlightId = null;
        closeReaderHighlightCommentComposer();
        renderReaderHighlightsPanel();
        return;
    }

    almadenReaderHighlightState.openCommentsHighlightId = highlightId;
    openReaderHighlightCommentComposer(highlight);
    renderReaderHighlightsPanel();
    loadReaderHighlightComments(highlight).then(() => {
        if (almadenReaderHighlightState.openCommentsHighlightId === highlightId) {
            renderReaderHighlightsPanel();
        }
    });
}

function renderReaderHighlightCommentsSection(highlight, container) {
    if (!highlight || !container) {
        return;
    }

    const highlightId = getReaderHighlightKey(highlight.id);
    const comments = getReaderHighlightComments(highlightId);

    container.innerHTML = '';

    const list = document.createElement('div');
    list.className = 'reader-highlight-comments-list';

    comments.forEach(comment => {
        const item = document.createElement('article');
        item.className = 'reader-highlight-comment-item';

        const meta = document.createElement('div');
        meta.className = 'reader-highlight-comment-meta';

        const author = document.createElement('span');
        author.className = 'reader-highlight-comment-author';
        author.textContent = comment.user_name || 'Usuario';

        const date = document.createElement('span');
        date.className = 'reader-highlight-comment-date';
        date.textContent = formatReaderDate(comment.created_at);

        meta.appendChild(author);
        meta.appendChild(date);

        const text = document.createElement('p');
        text.className = 'reader-highlight-comment-text';
        text.textContent = comment.comment_text || '';

        item.appendChild(meta);
        item.appendChild(text);

        const actions = document.createElement('div');
        actions.className = 'reader-highlight-comment-actions';

        if (comment.can_edit) {
            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'reader-highlight-comment-edit-btn';
            editBtn.innerHTML = '<i class="fa-solid fa-pen"></i>';
            editBtn.setAttribute('aria-label', 'Editar comentario');
            editBtn.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                openReaderHighlightCommentComposer(highlight, comment);
            });
            actions.appendChild(editBtn);
        }

        if (comment.can_delete) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'reader-highlight-comment-delete-btn';
            deleteBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
            deleteBtn.setAttribute('aria-label', 'Borrar comentario');
            deleteBtn.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                deleteReaderHighlightComment(comment, highlight);
            });
            actions.appendChild(deleteBtn);
        }

        if (actions.childNodes.length) {
            item.appendChild(actions);
        }

        list.appendChild(item);
    });

    if (comments.length) {
        container.appendChild(list);
    }

    const composerOpen = almadenReaderHighlightState.commentComposerHighlightId === highlightId;
    if (!composerOpen) {
        return;
    }

    const form = document.createElement('form');
    form.className = 'reader-highlight-comment-form';

    const textarea = document.createElement('textarea');
    textarea.className = 'reader-highlight-comment-input';
    textarea.rows = 2;
    textarea.placeholder = '';
    textarea.value = almadenReaderHighlightState.commentEditingText || '';

    const submitRow = document.createElement('div');
    submitRow.className = 'reader-highlight-comment-form-actions';

    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'reader-highlight-comment-submit-btn';
    submitBtn.textContent = almadenReaderHighlightState.commentEditingId ? 'Guardar' : 'Publicar';

    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'reader-highlight-comment-cancel-btn';
    cancelBtn.textContent = 'X';
    cancelBtn.setAttribute('aria-label', 'Cancelar');
    cancelBtn.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        closeReaderHighlightCommentComposer();
        renderReaderHighlightsPanel();
    });

    submitRow.appendChild(cancelBtn);
    submitRow.appendChild(submitBtn);
    form.appendChild(textarea);
    form.appendChild(submitRow);

    form.addEventListener('submit', event => {
        event.preventDefault();
        saveReaderHighlightComment(highlight, textarea, submitBtn, almadenReaderHighlightState.commentEditingId);
    });

    container.appendChild(form);
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
    showHighlightToolbarAtRange(range);
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

async function saveReaderHighlight() {
    return saveReaderHighlightCore('');
}

async function saveReaderHighlightCore(commentText = '') {
    const root = getReaderChapterRoot();
    const bookId = getReaderBookId();
    const chapterId = getReaderCurrentChapterId();
    const activeSelectionSnapshot = normalizeReaderSelectionSnapshot(
        almadenReaderHighlightState.selection
            ? {
                selection: almadenReaderHighlightState.selection,
                text: almadenReaderHighlightState.selectionText
            }
            : almadenReaderHighlightState.composerSelectionSnapshot
    );
    const activeSelection = activeSelectionSnapshot ? activeSelectionSnapshot.selection : null;

    if (!root || !bookId || !chapterId || !activeSelection || almadenReaderHighlightState.saveInFlight) {
        return;
    }

    almadenReaderHighlightState.saveInFlight = true;

    try {
        const formData = new FormData();
        formData.append('action', 'almaden_save_book_highlight');
        formData.append('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        formData.append('book_id', String(bookId));
        formData.append('chapter_id', String(chapterId));
        formData.append('selected_text', activeSelectionSnapshot.text || '');
        formData.append('start_offset', String(activeSelection.startOffset));
        formData.append('end_offset', String(activeSelection.endOffset));

        const ajaxUrl = typeof almadenAjaxUrl !== 'undefined' ? almadenAjaxUrl : (window.almadenAjaxUrl || '');
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const payload = await response.json();
        if (!payload || !payload.success || !payload.data || !payload.data.highlight) {
            throw new Error((payload && payload.data) || 'No se pudo guardar el highlight.');
        }

        const savedHighlight = payload.data.highlight;
        if (!Array.isArray(bookData.highlights)) {
            bookData.highlights = [];
        }
        bookData.highlights.push(savedHighlight);
        bookData.highlights.sort((a, b) => {
            if (parseInt(a.chapter_id, 10) !== parseInt(b.chapter_id, 10)) {
                return parseInt(a.chapter_id, 10) - parseInt(b.chapter_id, 10);
            }
            const startDiff = parseInt(a.start_offset, 10) - parseInt(b.start_offset, 10);
            if (startDiff !== 0) return startDiff;
            return parseInt(a.id, 10) - parseInt(b.id, 10);
        });

        wrapRangeWithHighlight(root, savedHighlight);
        renderReaderHighlightsPanel();

        const trimmedCommentText = String(commentText || '').trim();
        if (trimmedCommentText) {
            await saveReaderHighlightComment(savedHighlight, trimmedCommentText);
        }

        clearReaderSelection();
        hideHighlightToolbar();
        hideReaderHighlightComposer();
        almadenReaderHighlightState.selection = null;
        almadenReaderHighlightState.selectionText = '';
    } catch (error) {
        console.error(error);
        alert('No se pudo guardar el highlight. Intenta de nuevo.');
    } finally {
        almadenReaderHighlightState.saveInFlight = false;
    }
}

async function deleteReaderHighlight(highlight) {
    if (!highlight || almadenReaderHighlightState.deleteInFlight) {
        return;
    }

    const highlightId = parseInt(highlight.id, 10) || 0;
    const bookId = getReaderBookId();
    if (!highlightId || !bookId) {
        return;
    }

    almadenReaderHighlightState.deleteInFlight = true;

    try {
        const formData = new FormData();
        formData.append('action', 'almaden_delete_book_highlight');
        formData.append('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        formData.append('book_id', String(bookId));
        formData.append('highlight_id', String(highlightId));

        const ajaxUrl = typeof almadenAjaxUrl !== 'undefined' ? almadenAjaxUrl : (window.almadenAjaxUrl || '');
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const payload = await response.json();
        if (!payload || !payload.success) {
            throw new Error((payload && payload.data) || 'No se pudo borrar el highlight.');
        }

        if (Array.isArray(bookData.highlights)) {
            bookData.highlights = bookData.highlights.filter(item => parseInt(item.id, 10) !== highlightId);
        }

        const deletedKey = getReaderHighlightKey(highlightId);
        delete almadenReaderHighlightState.commentsByHighlightId[deletedKey];
        delete almadenReaderHighlightState.commentsLoadingByHighlightId[deletedKey];
        if (almadenReaderHighlightState.openCommentsHighlightId === deletedKey) {
            almadenReaderHighlightState.openCommentsHighlightId = null;
        }

        renderReaderHighlightsPanel();

        if (parseInt(highlight.chapter_id, 10) === getReaderCurrentChapterId() && typeof showChapterView === 'function') {
            showChapterView(currentChapterIndex);
        }
    } catch (error) {
        console.error(error);
        alert('No se pudo borrar el highlight. Intenta de nuevo.');
    } finally {
        almadenReaderHighlightState.deleteInFlight = false;
    }
}

async function saveReaderHighlightComment(highlight, textarea, submitBtn, commentId = null) {
    const bookId = getReaderBookId();
    const highlightId = parseInt(highlight && highlight.id, 10) || 0;
    const chapterId = parseInt(highlight && highlight.chapter_id, 10) || 0;
    const commentText = typeof textarea === 'string'
        ? textarea.trim()
        : (textarea ? textarea.value.trim() : '');

    if (!bookId || !highlightId || !chapterId || !commentText || almadenReaderHighlightState.commentSaveInFlight) {
        return;
    }

    almadenReaderHighlightState.commentSaveInFlight = true;
    if (submitBtn && submitBtn.disabled !== undefined) {
        submitBtn.disabled = true;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'almaden_save_book_highlight_comment');
        formData.append('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        formData.append('book_id', String(bookId));
        formData.append('chapter_id', String(chapterId));
        formData.append('highlight_id', String(highlightId));
        formData.append('comment_text', commentText);
        if (commentId) {
            formData.append('comment_id', String(parseInt(commentId, 10) || 0));
        }

        const response = await fetch(getReaderAjaxUrl(), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const payload = await response.json();
        if (!payload || !payload.success || !payload.data || !payload.data.comment) {
            throw new Error((payload && payload.data) || 'No se pudo guardar el comentario.');
        }

        const key = getReaderHighlightKey(highlightId);
        if (!Array.isArray(almadenReaderHighlightState.commentsByHighlightId[key])) {
            almadenReaderHighlightState.commentsByHighlightId[key] = [];
        }
        const updatedComment = payload.data.comment;
        const existingIndex = almadenReaderHighlightState.commentsByHighlightId[key].findIndex(item => parseInt(item.id, 10) === parseInt(updatedComment.id, 10));
        if (existingIndex >= 0) {
            almadenReaderHighlightState.commentsByHighlightId[key][existingIndex] = updatedComment;
        } else {
            almadenReaderHighlightState.commentsByHighlightId[key].push(updatedComment);
        }

        if (textarea && typeof textarea !== 'string') {
            textarea.value = '';
        }

        closeReaderHighlightCommentComposer();
        renderReaderHighlightsPanel();
    } catch (error) {
        console.error(error);
        alert('No se pudo guardar el comentario. Intenta de nuevo.');
    } finally {
        almadenReaderHighlightState.commentSaveInFlight = false;
        if (submitBtn && submitBtn.disabled !== undefined) {
            submitBtn.disabled = false;
        }
    }
}

async function deleteReaderHighlightComment(comment, highlight) {
    if (!comment || almadenReaderHighlightState.commentDeleteInFlight) {
        return;
    }

    const commentId = parseInt(comment.id, 10) || 0;
    const bookId = getReaderBookId();
    if (!commentId || !bookId) {
        return;
    }

    almadenReaderHighlightState.commentDeleteInFlight = true;

    try {
        const formData = new FormData();
        formData.append('action', 'almaden_delete_book_highlight_comment');
        formData.append('nonce', typeof almadenReaderHighlightNonce !== 'undefined' ? almadenReaderHighlightNonce : '');
        formData.append('book_id', String(bookId));
        formData.append('comment_id', String(commentId));

        const response = await fetch(getReaderAjaxUrl(), {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });

        const payload = await response.json();
        if (!payload || !payload.success) {
            throw new Error((payload && payload.data) || 'No se pudo borrar el comentario.');
        }

        const key = getReaderHighlightKey(highlight && highlight.id);
        if (Array.isArray(almadenReaderHighlightState.commentsByHighlightId[key])) {
            almadenReaderHighlightState.commentsByHighlightId[key] = almadenReaderHighlightState.commentsByHighlightId[key].filter(item => parseInt(item.id, 10) !== commentId);
        }

        if (almadenReaderHighlightState.commentEditingId && parseInt(almadenReaderHighlightState.commentEditingId, 10) === commentId) {
            closeReaderHighlightCommentComposer();
        }

        renderReaderHighlightsPanel();
    } catch (error) {
        console.error(error);
        alert('No se pudo borrar el comentario. Intenta de nuevo.');
    } finally {
        almadenReaderHighlightState.commentDeleteInFlight = false;
    }
}

function cancelReaderHighlight() {
    clearReaderSelection();
    almadenReaderHighlightState.selection = null;
    almadenReaderHighlightState.selectionText = '';
    hideHighlightToolbar();
    hideReaderHighlightComposer();
}

document.addEventListener('selectionchange', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('mouseup', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('touchend', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('mousedown', event => {
    const toolbar = document.getElementById('highlight-toolbar');
    const root = getReaderChapterRoot();
    const composer = getReaderHighlightComposer();
    if (!toolbar || !root) return;
    const clickedInsideComposer = composer && composer.contains(event.target);

    if (!clickedInsideComposer && composer && !composer.classList.contains('hidden')) {
        hideReaderHighlightComposer();
    }

    if (!toolbar.contains(event.target) && !root.contains(event.target) && !clickedInsideComposer) {
        hideHighlightToolbar();
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.getElementById('btn-save-highlight');
    const openCommentBtn = document.getElementById('btn-open-comment-highlight');
    const cancelBtn = document.getElementById('btn-cancel-highlight');
    const closePanelBtn = document.getElementById('btn-close-reader-highlights');
    const backdrop = getReaderHighlightsBackdrop();
    const composer = getReaderHighlightComposer();
    const composerSaveBtn = getReaderHighlightComposerSaveButton();
    const composerCloseBtn = document.getElementById('btn-close-comment-composer');
    const composerCancelBtn = document.getElementById('btn-cancel-comment-composer');

    if (saveBtn) {
        saveBtn.addEventListener('click', saveReaderHighlight);
    }

    if (openCommentBtn) {
        openCommentBtn.addEventListener('click', openReaderHighlightComposer);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelReaderHighlight);
    }

    if (closePanelBtn) {
        closePanelBtn.addEventListener('click', closeReaderHighlightsPanel);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeReaderHighlightsPanel);
    }

    if (composerSaveBtn) {
        composerSaveBtn.addEventListener('click', async () => {
            await submitReaderHighlightComposer();
        });
    }

    if (composerCloseBtn) {
        composerCloseBtn.addEventListener('click', cancelReaderHighlight);
    }

    if (composerCancelBtn) {
        composerCancelBtn.addEventListener('click', cancelReaderHighlight);
    }

    if (composer) {
        composer.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                cancelReaderHighlight();
            }
        });
    }
});

window.addEventListener('resize', () => {
    if (typeof applyReaderHighlightsToCurrentChapter === 'function' && typeof currentChapterIndex === 'number' && currentChapterIndex >= 0) {
        window.setTimeout(() => applyReaderHighlightsToCurrentChapter(), 50);
    }
});
