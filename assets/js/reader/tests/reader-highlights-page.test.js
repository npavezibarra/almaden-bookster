const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function loadHighlightsPage(overrides = {}) {
    const toolbarTitle = { textContent: '' };
    const toolbarButton = {
        hidden: true,
        dataset: {},
        onclick: null,
        addEventListener() {}
    };
    const sandbox = {
        console,
        document: {
            addEventListener() {},
            getElementById(id) {
                if (id === 'reader-highlights-page-toolbar-title') return toolbarTitle;
                if (id === 'reader-highlights-page-toolbar-read') return toolbarButton;
                return null;
            }
        },
        bookData: {
            chapters: [
                { id: 10, title: 'Índice', is_toc: '1', is_credits: '0' },
                { id: 11, title: 'Uno', is_toc: '0', is_credits: '0' },
                { id: 12, title: 'Dos', is_toc: '0', is_credits: '0' },
                { id: 13, title: 'Créditos', is_toc: '0', is_credits: '1' }
            ],
            highlights: []
        },
        getSortedBookHighlights() {
            return [];
        },
        toolbarTitle,
        toolbarButton,
        ...overrides
    };

    vm.createContext(sandbox);
    const scriptPath = path.resolve(__dirname, '..', 'reader-highlights-page.js');
    vm.runInContext(fs.readFileSync(scriptPath, 'utf8'), sandbox, { filename: scriptPath });
    return sandbox;
}

test('expanded feed defaults to all highlights ordered newest first', () => {
    const highlights = [
        { id: 1, chapter_id: 11, created_at: '2026-08-15 10:00:00' },
        { id: 2, chapter_id: 12, created_at: '2026-08-17 10:00:00' },
        { id: 3, chapter_id: 11, created_at: '2026-08-16 10:00:00' }
    ];
    const sandbox = loadHighlightsPage({
        getSortedBookHighlights() {
            return highlights;
        }
    });

    const ids = vm.runInContext('getReaderHighlightsPageItems().map(item => item.id)', sandbox);
    assert.deepEqual(Array.from(ids), [2, 3, 1]);
});

test('chapter filter limits the feed and hides toc-only chapters', () => {
    const highlights = [
        { id: 1, chapter_id: 11, created_at: '2026-08-15 10:00:00' },
        { id: 2, chapter_id: 12, created_at: '2026-08-17 10:00:00' }
    ];
    const sandbox = loadHighlightsPage({
        getSortedBookHighlights() {
            return highlights;
        }
    });

    const result = vm.runInContext(`
        almadenReaderHighlightsPageState.activeChapterId = '11';
        ({
            highlightIds: getReaderHighlightsPageItems().map(item => item.id),
            chapterIds: getReaderHighlightsPageChapters().map(chapter => chapter.id)
        });
    `, sandbox);

    assert.deepEqual(Array.from(result.highlightIds), [1]);
    assert.deepEqual(Array.from(result.chapterIds), [11, 12]);
});

test('chapter lookup maps the visible filter back to the original chapter index', () => {
    const sandbox = loadHighlightsPage();

    const chapterIndex = vm.runInContext('getReaderHighlightsPageChapterIndexById(12)', sandbox);
    assert.equal(chapterIndex, 2);
});

test('toolbar reflects the selected chapter and hides for all', () => {
    const sandbox = loadHighlightsPage();

    vm.runInContext(`
        almadenReaderHighlightsPageState.activeChapterId = '11';
        renderReaderHighlightsPageToolbar();
    `, sandbox);

    assert.equal(sandbox.toolbarTitle.textContent, 'Uno');
    assert.equal(sandbox.toolbarButton.hidden, false);

    vm.runInContext(`
        almadenReaderHighlightsPageState.activeChapterId = 'all';
        renderReaderHighlightsPageToolbar();
    `, sandbox);

    assert.equal(sandbox.toolbarTitle.textContent, 'Todos los capítulos');
    assert.equal(sandbox.toolbarButton.hidden, true);
});
