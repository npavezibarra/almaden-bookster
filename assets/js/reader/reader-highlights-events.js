document.addEventListener('selectionchange', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('mouseup', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('touchend', () => {
    window.setTimeout(captureReaderSelection, 0);
});

document.addEventListener('click', event => {
    const highlightElement = event.target && typeof event.target.closest === 'function'
        ? event.target.closest('.reader-highlight')
        : null;

    if (!highlightElement) {
        return;
    }

    const highlightId = parseInt(highlightElement.dataset.highlightId, 10) || 0;
    const highlight = getReaderHighlightById(highlightId);
    if (!highlight) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    openReaderHighlightToolbarForHighlight(highlight, highlightElement);
}, true);

document.addEventListener('mousedown', event => {
    const toolbar = getReaderHighlightToolbar();
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
        saveBtn.addEventListener('click', async () => {
            if (almadenReaderHighlightState.toolbarMode === 'highlight') {
                const highlight = getReaderHighlightById(almadenReaderHighlightState.activeToolbarHighlightId);
                if (!highlight) {
                    return;
                }
                await deleteReaderHighlight(highlight);
                return;
            }

            await saveReaderHighlight();
        });
    }

    if (openCommentBtn) {
        openCommentBtn.addEventListener('click', () => {
            if (almadenReaderHighlightState.toolbarMode === 'highlight') {
                const highlight = getReaderHighlightById(almadenReaderHighlightState.activeToolbarHighlightId);
                if (highlight) {
                    toggleReaderHighlightComments(highlight);
                }
                return;
            }

            openReaderHighlightComposer();
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            if (almadenReaderHighlightState.toolbarMode === 'highlight') {
                closeReaderHighlightToolbar();
                return;
            }

            cancelReaderHighlight();
        });
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
