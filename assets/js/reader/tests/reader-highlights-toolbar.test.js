const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function createClassList() {
    const classes = new Set();
    return {
        add(...tokens) {
            tokens.filter(Boolean).forEach(token => classes.add(token));
        },
        remove(...tokens) {
            tokens.filter(Boolean).forEach(token => classes.delete(token));
        },
        contains(token) {
            return classes.has(token);
        },
        toArray() {
            return Array.from(classes);
        }
    };
}

function createNode(id) {
    return {
        id,
        dataset: {},
        style: {},
        title: '',
        textContent: '',
        innerHTML: '',
        value: '',
        attributes: {},
        classList: createClassList(),
        setAttribute(name, value) {
            this.attributes[name] = String(value);
        },
        getBoundingClientRect() {
            return { top: 100, left: 200, width: 120, height: 24 };
        }
    };
}

test('clicking an existing highlight reopens the toolbar in delete mode', () => {
    const listeners = {};
    const toolbar = createNode('highlight-toolbar');
    const saveBtn = createNode('btn-save-highlight');
    const commentBtn = createNode('btn-open-comment-highlight');
    const cancelBtn = createNode('btn-cancel-highlight');
    const composer = createNode('highlight-comment-composer');
    composer.classList.add('hidden');
    const composerInput = createNode('highlight-comment-input');
    const chapterRoot = createNode('chapter-content');
    const selection = { removeAllRanges() {} };
    const highlightElement = {
        dataset: { highlightId: '17' },
        getBoundingClientRect() {
            return { top: 240, left: 120, width: 180, height: 28 };
        }
    };

    const document = {
        addEventListener(type, handler) {
            (listeners[type] ||= []).push(handler);
        },
        getElementById(id) {
            switch (id) {
                case 'highlight-toolbar':
                    return toolbar;
                case 'btn-save-highlight':
                    return saveBtn;
                case 'btn-open-comment-highlight':
                    return commentBtn;
                case 'btn-cancel-highlight':
                    return cancelBtn;
                case 'highlight-comment-composer':
                    return composer;
                case 'highlight-comment-input':
                    return composerInput;
                case 'chapter-content':
                    return chapterRoot;
                default:
                    return null;
            }
        }
    };

    const window = {
        addEventListener() {},
        getSelection() {
            return selection;
        },
        setTimeout(callback) {
            callback();
        }
    };

    const sandbox = {
        window,
        document,
        console,
        bookData: {
            chapters: [{ id: 11, title: 'Capítulo' }],
            highlights: [{ id: 17, chapter_id: 11, status: 'active' }]
        },
        currentChapterIndex: 0
    };

    [
        'reader-highlights-state.js',
        'reader-highlights-dom.js',
        'reader-highlights-ui.js',
        'reader-highlights-api.js',
        'reader-highlights-events.js'
    ].forEach(fileName => {
        const scriptPath = path.resolve(__dirname, '..', fileName);
        vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), sandbox, { filename: scriptPath });
    });

    const clickListeners = listeners.click || [];
    assert.equal(clickListeners.length > 0, true);

    const event = {
        target: {
            closest(selector) {
                return selector === '.reader-highlight' ? highlightElement : null;
            }
        },
        preventDefaultCalled: false,
        stopPropagationCalled: false,
        preventDefault() {
            this.preventDefaultCalled = true;
        },
        stopPropagation() {
            this.stopPropagationCalled = true;
        }
    };

    clickListeners[0](event);

    const state = vm.runInNewContext('almadenReaderHighlightState', sandbox);

    assert.equal(event.preventDefaultCalled, true);
    assert.equal(event.stopPropagationCalled, true);
    assert.equal(state.toolbarMode, 'highlight');
    assert.equal(state.activeToolbarHighlightId, '17');
    assert.equal(saveBtn.dataset.readerHighlightAction, 'delete');
    assert.equal(saveBtn.title, 'Borrar highlight');
    assert.equal(toolbar.classList.contains('hidden'), false);
});
