const assert = require('node:assert/strict');

global.window = global;

function classList() {
    const values = new Set();
    return {
        add: value => values.add(value),
        remove: value => values.delete(value),
        toggle: (value, active) => active ? values.add(value) : values.delete(value),
        contains: value => values.has(value)
    };
}

function page(pageNumber) {
    return {
        dataset: { pageNumber: String(pageNumber), blank: '0' },
        classList: classList(),
        setAttribute() {},
        appendChild() {},
        closest(selector) {
            return selector === '[data-page-number]' ? this : null;
        }
    };
}

const pages = [page(16), page(17)];
const listeners = {};
const root = {
    dataset: {},
    querySelectorAll(selector) {
        return selector === '[data-page-number]' ? pages : [];
    },
    addEventListener(type, listener) {
        listeners[type] = listener;
    }
};

global.document = {
    getElementById: id => id === 'pdf-scroller' ? root : null,
    querySelectorAll: () => [],
    querySelector: () => null,
    createElement: () => ({ dataset: {}, classList: classList() }),
    addEventListener() {}
};

require('../assets/js/pdf/typst/page-templates/editor-page-template-selector.js');
window.almadenPageTemplateUI.bind(root);

function click(target) {
    listeners.click({ target });
}

click(pages[0]);
assert.equal(window.almadenPageTemplateUI.getSelectedPageNumber(), 16);

click(pages[1]);
assert.equal(window.almadenPageTemplateUI.getSelectedPageNumber(), 17);

click(pages[1]);
assert.equal(window.almadenPageTemplateUI.getSelectedPageNumber(), null);

click(pages[0]);
click({ closest: () => null });
assert.equal(window.almadenPageTemplateUI.getSelectedPageNumber(), null);

click(pages[0]);
click({ closest: selector => selector.startsWith('button') ? {} : null });
assert.equal(window.almadenPageTemplateUI.getSelectedPageNumber(), 16);

console.log('Page selection regression: OK');
