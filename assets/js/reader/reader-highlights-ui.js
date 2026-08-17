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

function getReaderHighlightComposer() {
    return document.getElementById('highlight-comment-composer');
}

function getReaderHighlightComposerInput() {
    return document.getElementById('highlight-comment-input');
}

function getReaderHighlightComposerSaveButton() {
    return document.getElementById('btn-save-comment-composer');
}

function getReaderHighlightToolbar() {
    return document.getElementById('highlight-toolbar');
}

function getReaderHighlightToolbarSaveButton() {
    return document.getElementById('btn-save-highlight');
}

function getReaderHighlightToolbarCommentButton() {
    return document.getElementById('btn-open-comment-highlight');
}

function getReaderHighlightToolbarCancelButton() {
    return document.getElementById('btn-cancel-highlight');
}

function updateReaderHighlightToolbarMode(mode) {
    const normalizedMode = mode === 'highlight' ? 'highlight' : 'selection';
    const saveBtn = getReaderHighlightToolbarSaveButton();
    const commentBtn = getReaderHighlightToolbarCommentButton();
    const cancelBtn = getReaderHighlightToolbarCancelButton();

    almadenReaderHighlightState.toolbarMode = normalizedMode;

    if (saveBtn) {
        saveBtn.dataset.readerHighlightAction = normalizedMode === 'highlight' ? 'delete' : 'save';
        saveBtn.title = normalizedMode === 'highlight' ? 'Borrar highlight' : 'Resaltar';
        saveBtn.setAttribute('aria-label', saveBtn.title);
        saveBtn.innerHTML = normalizedMode === 'highlight'
            ? '<i class="fa-solid fa-trash-can text-sm"></i>'
            : '<span class="w-4 h-4 rounded-full bg-yellow-500 border border-yellow-600 inline-block"></span>';
    }

    if (commentBtn) {
        commentBtn.title = normalizedMode === 'highlight' ? 'Comentarios' : 'Comentar aquí';
        commentBtn.setAttribute('aria-label', commentBtn.title);
        commentBtn.innerHTML = normalizedMode === 'highlight'
            ? '<i class="fa-solid fa-comment-dots text-sm"></i>'
            : '<i class="fa-solid fa-comment-dots text-sm"></i>';
    }

    if (cancelBtn) {
        cancelBtn.title = normalizedMode === 'highlight' ? 'Cerrar' : 'Cancelar';
        cancelBtn.setAttribute('aria-label', cancelBtn.title);
        cancelBtn.innerHTML = '<i class="fa-solid fa-xmark text-sm"></i>';
    }
}

function clearReaderHighlightToolbarContext() {
    almadenReaderHighlightState.activeToolbarHighlightId = null;
    updateReaderHighlightToolbarMode('selection');
}

function openReaderHighlightToolbarForSelection(range) {
    if (!range) {
        hideHighlightToolbar();
        return;
    }

    clearReaderHighlightToolbarContext();
    showHighlightToolbarAtRange(range);
}

function openReaderHighlightToolbarForHighlight(highlight, anchorElement) {
    if (!highlight) {
        return;
    }

    hideReaderHighlightComposer();
    closeReaderHighlightCommentComposer();
    clearReaderSelection();
    almadenReaderHighlightState.suppressSelectionCapture = true;
    window.setTimeout(() => {
        almadenReaderHighlightState.suppressSelectionCapture = false;
    }, 120);
    almadenReaderHighlightState.activeToolbarHighlightId = getReaderHighlightKey(highlight.id);
    updateReaderHighlightToolbarMode('highlight');

    if (anchorElement) {
        showHighlightToolbarAtElement(anchorElement);
        return;
    }

    hideHighlightToolbar();
}

function closeReaderHighlightToolbar() {
    hideHighlightToolbar();
    clearReaderHighlightToolbarContext();
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

function renderReaderHighlightsPanel() {
    const listContainer = getReaderHighlightsListContainer();
    const countLabel = getReaderHighlightsCountLabel();
    if (!listContainer) return;

    if (typeof renderReaderHighlightsPage === 'function') {
        renderReaderHighlightsPage();
    }

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
            quote.dataset.almadenProtectedExcerpt = 'highlight';
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
    textarea.dataset.almadenCopyAllowed = 'user-note';
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
