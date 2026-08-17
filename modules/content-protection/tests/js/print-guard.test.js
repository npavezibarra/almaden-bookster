const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

test('marks and restores the document around printing', () => {
    const listeners = new Map();
    const classes = new Set();
    const window = {
        almadenContentProtectionConfig: {enabled: true, blockPrint: true},
        addEventListener: (type, handler) => listeners.set(type, handler)
    };
    const document = {
        body: {
            classList: {
                add: value => classes.add(value),
                remove: value => classes.delete(value)
            }
        }
    };
    const scriptPath = path.resolve(__dirname, '../../assets/js/print-guard.js');
    vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), {document, window}, {filename: scriptPath});

    listeners.get('beforeprint')();
    assert.equal(classes.has('almaden-protected-print'), true);
    listeners.get('afterprint')();
    assert.equal(classes.has('almaden-protected-print'), false);
});

