const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function createHarness() {
    const listeners = new Map();
    const protectedRoot = {
        nodeType: 1,
        parentElement: null,
        contains: () => true,
        closest: selector => selector.includes('data-almaden-protected') ? protectedRoot : null
    };
    const protectedTarget = {
        nodeType: 1,
        parentElement: protectedRoot,
        closest: selector => selector.includes('data-almaden-protected') ? protectedRoot : null
    };
    const allowedTarget = {
        nodeType: 1,
        parentElement: null,
        closest: selector => selector.includes('textarea') || selector.includes('data-almaden-copy-allowed') ? allowedTarget : null
    };
    const notice = {dataset: {}, hidden: true, textContent: ''};
    const range = {intersectsNode: node => node === protectedRoot};
    const selection = {isCollapsed: false, rangeCount: 1, getRangeAt: () => range};
    const document = {
        addEventListener: (type, handler, capture) => listeners.set(type, {capture, handler}),
        getElementById: id => id === 'almaden-content-protection-notice' ? notice : null,
        querySelectorAll: () => [protectedRoot]
    };
    const window = {
        almadenContentProtectionConfig: {notice: 'Copia bloqueada.'},
        clearTimeout: () => {},
        getSelection: () => selection,
        setTimeout: () => 1
    };
    const context = {console, document, Node: {ELEMENT_NODE: 1}, window};
    const scriptPath = path.resolve(__dirname, '../../assets/js/clipboard-guard.js');
    vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), context, {filename: scriptPath});

    return {allowedTarget, listeners, notice, protectedTarget, selection, window};
}

function createEvent(target, withClipboard = false) {
    const event = {
        target,
        defaultPrevented: false,
        propagationStopped: false,
        immediatePropagationStopped: false,
        preventDefault() { this.defaultPrevented = true; },
        stopPropagation() { this.propagationStopped = true; },
        stopImmediatePropagation() { this.immediatePropagationStopped = true; }
    };
    if (withClipboard) {
        event.clipboardData = {
            cleared: false,
            data: {'text/plain': 'sentinel'},
            clearData() { this.cleared = true; this.data = {}; },
            setData(type, value) { this.data[type] = value; }
        };
    }
    return event;
}

test('registers phase-one guards in capture mode', () => {
    const harness = createHarness();
    assert.equal(harness.window.AlmadenContentProtection.initialized, true);
    for (const type of ['copy', 'cut', 'keydown', 'dragstart']) {
        assert.equal(harness.listeners.get(type).capture, true);
    }
});

test('blocks protected copy and cut data while preserving selection', () => {
    const harness = createHarness();
    const originalSelection = harness.selection;
    for (const type of ['copy', 'cut']) {
        const event = createEvent(harness.protectedTarget, true);
        harness.listeners.get(type).handler(event);
        assert.equal(event.defaultPrevented, true);
        assert.equal(event.immediatePropagationStopped, true);
        assert.equal(event.clipboardData.cleared, true);
        assert.equal(event.clipboardData.data['text/plain'], '');
        assert.equal(event.clipboardData.data['text/html'], '');
    }
    assert.equal(harness.window.getSelection(), originalSelection);
    assert.equal(harness.notice.hidden, false);
    assert.equal(harness.notice.textContent, 'Copia bloqueada.');
});

test('allows copying user-authored form content', () => {
    const harness = createHarness();
    const event = createEvent(harness.allowedTarget, true);

    harness.listeners.get('copy').handler(event);

    assert.equal(event.defaultPrevented, false);
    assert.equal(event.clipboardData.cleared, false);
    assert.equal(harness.notice.hidden, true);
});

test('blocks dragging protected content', () => {
    const harness = createHarness();
    const event = createEvent(harness.protectedTarget);

    harness.listeners.get('dragstart').handler(event);

    assert.equal(event.defaultPrevented, true);
    assert.equal(event.immediatePropagationStopped, true);
    assert.equal(harness.notice.hidden, false);
});
