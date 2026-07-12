// ============================================================
// MÓDULO: editor-visual-selection.js
// Responsabilidad: guardar/restaurar selección visual por offsets.
// ============================================================

window.visualEditorLastSelection = null;

function getVisualTextNodes(root) {
    const nodes = [];
    if (!root || !document.createTreeWalker) return nodes;
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    while (walker.nextNode()) nodes.push(walker.currentNode);
    return nodes;
}

function captureVisualSelection(surface, range) {
    let start = null, end = null, pos = 0;
    for (const node of getVisualTextNodes(surface)) {
        const len = (node.textContent || '').length;
        if (start === null && node === range.startContainer) start = pos + range.startOffset;
        if (end === null && node === range.endContainer) end = pos + range.endOffset;
        pos += len;
        if (start !== null && end !== null) break;
    }
    return (start === null || end === null) ? null : { start, end };
}

function restoreVisualSelectionSnapshot(surface, snapshot) {
    if (!surface || !snapshot) return false;
    const nodes = getVisualTextNodes(surface);
    const locate = (target) => {
        let pos = 0;
        for (const node of nodes) {
            const len = (node.textContent || '').length;
            if (target <= pos + len) return { node, offset: Math.max(0, target - pos) };
            pos += len;
        }
        const last = nodes[nodes.length - 1];
        return last ? { node: last, offset: (last.textContent || '').length } : null;
    };
    const start = locate(snapshot.start);
    const end = locate(snapshot.end);
    if (!start || !end) return false;
    const selection = window.getSelection ? window.getSelection() : null;
    if (!selection) return false;
    const range = document.createRange();
    range.setStart(start.node, start.offset);
    range.setEnd(end.node, end.offset);
    selection.removeAllRanges();
    selection.addRange(range);
    window.visualEditorLastSelection = snapshot;
    surface.focus();
    return true;
}

window.captureVisualSelection = captureVisualSelection;
window.restoreVisualSelectionSnapshot = restoreVisualSelectionSnapshot;
