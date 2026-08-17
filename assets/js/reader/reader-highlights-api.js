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

async function submitReaderHighlightComposer() {
    const input = getReaderHighlightComposerInput();
    const commentText = input ? input.value.trim() : '';
    if (!commentText) {
        return;
    }

    await saveReaderHighlightCore(commentText);
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
        if (almadenReaderHighlightState.activeToolbarHighlightId === deletedKey || almadenReaderHighlightState.toolbarMode === 'highlight') {
            closeReaderHighlightToolbar();
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
