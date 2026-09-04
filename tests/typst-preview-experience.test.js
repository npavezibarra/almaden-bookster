const assert = require('node:assert/strict');
const path = require('node:path');

global.window = global;
global.document = { getElementById: () => null };
global.addEventListener = () => {};
global.bookState = { pdfPreview: {} };

let compileCalls = 0;
let activeCompiles = 0;
let maxActiveCompiles = 0;
let compileBlocker = null;
global.compilePDFPreview = async () => {
    compileCalls += 1;
    activeCompiles += 1;
    maxActiveCompiles = Math.max(maxActiveCompiles, activeCompiles);
    if (compileBlocker) await compileBlocker;
    activeCompiles -= 1;
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
    assert.ok(queuedDuration >= 950, 'Text changes must wait for a quiet period.');
    assert.ok(queuedDuration < 3000, 'The quiet compile must respect max wait.');

    const immediateAt = performance.now();
    const immediateResult = await global.compilePDFPreview(true, 'pdf-scroller', true);
    assert.equal(immediateResult, 1);
    assert.equal(compileCalls, 2, 'Forced refresh must compile immediately.');
    assert.ok(performance.now() - immediateAt < 200, 'Forced refresh must not debounce.');
    assert.equal(global.bookState.pdfPreview.lastResponsiveness.applied, true);
    assert.deepEqual(global.almadenTypstPreviewExperience.delays, {
        quiet: 1000,
        action: 0,
        maxWait: 3000
    });

    const actionAt = performance.now();
    const actionResult = await global.almadenTypstPreviewExperience.compileEditorAction(true);
    assert.equal(actionResult, 1);
    assert.equal(compileCalls, 3, 'Toolbar actions must compile through the immediate path.');
    assert.ok(performance.now() - actionAt < 200, 'Toolbar actions must not wait for a debounce.');

    let releaseCompile;
    compileBlocker = new Promise(resolve => {
        releaseCompile = resolve;
    });
    const firstSlowCompile = global.compilePDFPreview(true, 'pdf-scroller', true);
    await new Promise(resolve => setTimeout(resolve, 20));
    const trailingCompile = global.compilePDFPreview(true, 'pdf-scroller', true);
    await new Promise(resolve => setTimeout(resolve, 20));
    assert.equal(compileCalls, 4, 'A second compile must wait while the first one is active.');
    releaseCompile();
    await Promise.all([firstSlowCompile, trailingCompile]);
    compileBlocker = null;
    assert.equal(compileCalls, 5, 'The latest queued revision must compile after the active one.');
    assert.equal(maxActiveCompiles, 1, 'Typst compiles must never overlap.');

    console.log('Typst preview experience behavior checks passed.');
}

run().catch(error => {
    console.error(error);
    process.exit(1);
});
