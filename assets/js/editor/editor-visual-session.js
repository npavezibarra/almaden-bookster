// ============================================================
// MÓDULO: editor-visual-session.js
// Responsabilidad: estado de edición visual y sync de scroll.
// ============================================================

window.visualEditorIsDirty = false;
window.visualEditorIsEditing = false;
window.visualEditorSyncTimer = null;
window.visualEditorMutationObserver = null;
window.visualEditorRevision = 0;

function syncVisualEditorOverlayScroll() {
    // The real Paged.js pages are edited directly; no overlay needs scroll correction.
}

function bindVisualEditorScrollSync(scroller) {
    if (!scroller || scroller.__visualEditorScrollBound) return;
    scroller.__visualEditorScrollBound = true;
    scroller.addEventListener('scroll', () => {
        return;
    }, { passive: true });
}

function detachVisualEditorMutationObserver() {
    if (window.visualEditorMutationObserver) {
        window.visualEditorMutationObserver.disconnect();
        window.visualEditorMutationObserver = null;
    }
    if (window.visualEditorSyncTimer) {
        clearTimeout(window.visualEditorSyncTimer);
        window.visualEditorSyncTimer = null;
    }
}

function attachVisualEditorMutationObserver(surface) {
    detachVisualEditorMutationObserver();
    if (!surface || !window.MutationObserver) return;

    window.visualEditorMutationObserver = new MutationObserver(() => {
        window.visualEditorIsDirty = true;
        window.visualEditorIsEditing = true;
        window.visualEditorRevision += 1;
        if (window.visualEditorSyncTimer) {
            clearTimeout(window.visualEditorSyncTimer);
        }
        window.visualEditorSyncTimer = setTimeout(() => {
            window.visualEditorSyncTimer = null;
            if (typeof syncVisualEditorToState === 'function') {
                syncVisualEditorToState();
            }
        }, 50);
    });

    window.visualEditorMutationObserver.observe(surface, {
        childList: true,
        subtree: true,
        characterData: true
    });
}

window.syncVisualEditorOverlayScroll = syncVisualEditorOverlayScroll;
window.bindVisualEditorScrollSync = bindVisualEditorScrollSync;
window.attachVisualEditorMutationObserver = attachVisualEditorMutationObserver;
window.detachVisualEditorMutationObserver = detachVisualEditorMutationObserver;
