// assets/js/editor/editor-chapters-utils.js

let chapterWordCountCache = {};

function getChapterItemDomId(chapterId) {
    return `chapter-item-${String(chapterId ?? '').replace(/[^a-zA-Z0-9_-]/g, '-')}`;
}

function getWordCount(text) {
    if (typeof text !== 'string') return 0;
    const cleanText = text.trim();
    return cleanText === '' ? 0 : cleanText.split(/\s+/).length;
}

function updateWordCounts() {
    const activeChapter = bookState.chapters.find(c => c.id === bookState.activeChapterId);
    const text = activeChapter ? (activeChapter.content || '') : '';
    const wordCount = getWordCount(text);

    const currentWordCountEl = document.getElementById('current-word-count');
    if (currentWordCountEl) {
        currentWordCountEl.textContent = `${wordCount} ${wordCount === 1 ? 'palabra' : 'palabras'}`;
    }

    let total = 0;
    bookState.chapters.forEach(c => {
        if (c.id === bookState.activeChapterId) {
            total += wordCount;
            chapterWordCountCache[c.id] = { length: text.length, count: wordCount };
        } else {
            const content = c.content || '';
            const cached = chapterWordCountCache[c.id];
            if (!cached || cached.length !== content.length) {
                const count = getWordCount(content);
                chapterWordCountCache[c.id] = { length: content.length, count };
                total += count;
            } else {
                total += cached.count;
            }
        }
    });

    const totalWordsEl = document.getElementById('total-words');
    if (totalWordsEl) {
        totalWordsEl.textContent = total.toLocaleString();
    }
}

function getExcerpt(content) {
    if (!content) return 'Vacío...';
    const clean = content.replace(/[#*>\-_[\]]/g, '').trim();
    if (clean.length > 35) return `${clean.substring(0, 35)}...`;
    return clean || 'Vacío...';
}

window.getChapterItemDomId = getChapterItemDomId;
window.getWordCount = getWordCount;
window.updateWordCounts = updateWordCounts;
window.getExcerpt = getExcerpt;
