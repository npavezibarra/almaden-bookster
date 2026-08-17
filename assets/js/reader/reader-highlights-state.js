let almadenReaderHighlightState = {
    selection: null,
    selectionText: '',
    saveInFlight: false,
    panelOpen: false,
    deleteInFlight: false,
    pendingFocusHighlightId: null,
    toolbarMode: 'selection',
    activeToolbarHighlightId: null,
    suppressSelectionCapture: false,
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

function getReaderHighlightById(highlightId) {
    const normalizedId = getReaderHighlightKey(highlightId);
    if (!normalizedId || !bookData || !Array.isArray(bookData.highlights)) {
        return null;
    }

    return bookData.highlights.find(highlight => getReaderHighlightKey(highlight.id) === normalizedId && String(highlight.status || 'active') === 'active') || null;
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
