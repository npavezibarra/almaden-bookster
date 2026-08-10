const assert = require('node:assert/strict');
const path = require('node:path');

global.window = global;
global.document = { getElementById: () => null };
global.addEventListener = () => {};
global.bookState = { pdfPreview: {} };

let compileCalls = 0;
global.compilePDFPreview = async () => {
    compileCalls += 1;
    return 1;
};
global.almadenTypstPdf = { hasCurrentPreview: () => false };

require(path.join(__dirname, '../assets/js/pdf/typst/editor-typst-preview-experience.js'));

async function run() {
    const queuedAt = performance.now();
    const requests = [
        global.compilePDFPreview(false, 'pdf-scroller', false),
        global.compilePDFPreview(false, 'pdf-scroller', false),
        global.compilePDFPreview(false, 'pdf-scroller', false)
    ];
    const results = await Promise.all(requests);
    const queuedDuration = performance.now() - queuedAt;

    assert.deepEqual(results, [1, 1, 1]);
    assert.equal(compileCalls, 1, 'Rapid edits must coalesce into one compile.');
    assert.ok(queuedDuration >= 650, 'Text changes must wait for a quiet period.');
    assert.ok(queuedDuration < 1800, 'The quiet compile must respect max wait.');

    const immediateAt = performance.now();
    const immediateResult = await global.compilePDFPreview(true, 'pdf-scroller', true);
    assert.equal(immediateResult, 1);
    assert.equal(compileCalls, 2, 'Forced refresh must compile immediately.');
    assert.ok(performance.now() - immediateAt < 200, 'Forced refresh must not debounce.');
    assert.equal(global.bookState.pdfPreview.lastResponsiveness.applied, true);
    assert.deepEqual(global.almadenTypstPreviewExperience.delays, {
        quiet: 700,
        action: 0,
        maxWait: 1800
    });

    const actionAt = performance.now();
    const actionResult = await global.almadenTypstPreviewExperience.compileEditorAction(true);
    assert.equal(actionResult, 1);
    assert.equal(compileCalls, 3, 'Toolbar actions must compile through the immediate path.');
    assert.ok(performance.now() - actionAt < 200, 'Toolbar actions must not wait for a debounce.');

    console.log('Typst preview experience behavior checks passed.');
}

run().catch(error => {
    console.error(error);
    process.exit(1);
});
