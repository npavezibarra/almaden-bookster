const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function createElement(tagName) {
    return {
        tagName,
        id: '',
        dataset: {},
        attributes: {},
        children: [],
        style: {},
        textContent: '',
        appendChild(child) { this.children.push(child); },
        setAttribute(name, value) { this.attributes[name] = value; }
    };
}

test('keeps watermark compatibility inert and does not render an overlay', () => {
    const chapterView = createElement('div');
    const document = {
        createElement,
        getElementById(id) {
            if (id === 'almaden-view-chapter') return chapterView;
            return chapterView.children.find(child => child.id === id) || null;
        }
    };
    const window = {
        almadenContentProtectionConfig: {
            enabled: true
        }
    };
    const scriptPath = path.resolve(__dirname, '../../assets/js/watermark.js');
    vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), {document, window}, {filename: scriptPath});

    assert.equal(chapterView.children.length, 0);
    assert.equal(window.AlmadenWatermark.currentChapterId(), 0);

    window.AlmadenWatermark.update(91);
    window.AlmadenWatermark.update(92);
    assert.equal(window.AlmadenWatermark.currentChapterId(), 92);
});
