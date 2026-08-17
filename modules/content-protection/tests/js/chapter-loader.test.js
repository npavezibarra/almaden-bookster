const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

class FakeFormData {
    constructor() { this.values = new Map(); }
    append(key, value) { this.values.set(key, value); }
    get(key) { return this.values.get(key); }
}

function createHarness(options = {}) {
    const timers = [];
    const calls = [];
    const telemetry = [];
    const chapters = [{id: 11}, {id: 12}, {id: 13}];
    const content = new Map([[11, 'Capítulo 1'], [12, 'Capítulo 2'], [13, 'Capítulo 3']]);
    const window = {
        almadenContentProtectionConfig: {
            enabled: true,
            chapterDelivery: 'on_demand',
            ajaxUrl: '/wp-admin/admin-ajax.php',
            bookId: 9,
            chapterNonce: 'nonce',
            chapterError: 'Error'
        },
        bookData: {chapters},
        console: {debug: () => {}},
        AlmadenProtectionTelemetry: {record: event => telemetry.push(event)},
        setTimeout: callback => { timers.push(callback); return timers.length; }
    };
    const fetch = async (url, requestOptions) => {
        const chapterId = Number(requestOptions.body.get('chapter_id'));
        calls.push({chapterId, options: requestOptions, url});
        return options.fail ? {
            ok: false,
            json: async () => ({success: false, data: {message: 'No disponible'}})
        } : {
            ok: true,
            json: async () => ({success: true, data: {chapterId, content: content.get(chapterId)}})
        };
    };
    const context = {AbortController, DOMException, FormData: FakeFormData, fetch, console, window};
    const scriptPath = path.resolve(__dirname, '../../assets/js/chapter-loader.js');
    vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), context, {filename: scriptPath});
    return {calls, chapters, telemetry, timers, window};
}

async function flushTimers(harness) {
    while (harness.timers.length) harness.timers.shift()();
    await new Promise(resolve => setTimeout(resolve, 0));
}

test('loads one chapter without writing its body into bookData', async () => {
    const harness = createHarness();
    const result = await harness.window.AlmadenChapterLoader.ensureChapterContent(0);

    assert.equal(result, 'Capítulo 1');
    assert.equal(Object.hasOwn(harness.chapters[0], 'content'), false);
    assert.equal(harness.calls[0].options.cache, 'no-store');
    assert.equal(harness.calls[0].options.credentials, 'same-origin');
});

test('retains only the current and next chapter in memory', async () => {
    const harness = createHarness();
    await harness.window.AlmadenChapterLoader.ensureChapterContent(0);
    await flushTimers(harness);
    assert.deepEqual(Array.from(harness.window.AlmadenChapterLoader.diagnostics().cachedIndexes), [0, 1]);

    await harness.window.AlmadenChapterLoader.ensureChapterContent(1);
    await flushTimers(harness);
    assert.deepEqual(Array.from(harness.window.AlmadenChapterLoader.diagnostics().cachedIndexes), [1, 2]);
});

test('release purges cached chapter bodies', async () => {
    const harness = createHarness();
    await harness.window.AlmadenChapterLoader.ensureChapterContent(0);
    harness.window.AlmadenChapterLoader.release();
    assert.deepEqual(Array.from(harness.window.AlmadenChapterLoader.diagnostics().cachedIndexes), []);
    assert.equal(harness.window.AlmadenChapterLoader.diagnostics().activeIndex, -1);
});

test('reports chapter delivery errors without including chapter content', async () => {
    const harness = createHarness({fail: true});
    await assert.rejects(() => harness.window.AlmadenChapterLoader.ensureChapterContent(0), /No disponible/);
    assert.deepEqual(harness.telemetry, ['chapter_load_error']);
});
