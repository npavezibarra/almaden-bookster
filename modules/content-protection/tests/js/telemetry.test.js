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

test('sends allowlisted metadata without selected text and debounces duplicates', () => {
    const calls = [];
    const window = {
        AlmadenWatermark: {currentChapterId: () => 41},
        almadenContentProtectionConfig: {
            enabled: true,
            telemetryEnabled: true,
            telemetryNonce: 'nonce',
            bookId: 7,
            ajaxUrl: '/wp-admin/admin-ajax.php'
        }
    };
    const fetch = (url, options) => { calls.push({options, url}); return Promise.resolve({ok: true}); };
    const scriptPath = path.resolve(__dirname, '../../assets/js/telemetry.js');
    vm.runInNewContext(fs.readFileSync(scriptPath, 'utf8'), {Date, FormData: FakeFormData, Set, fetch, window}, {filename: scriptPath});

    window.AlmadenProtectionTelemetry.record('copy');
    window.AlmadenProtectionTelemetry.record('copy');
    window.AlmadenProtectionTelemetry.record('chapter_load_error');
    window.AlmadenProtectionTelemetry.record('selected_text');

    assert.equal(calls.length, 2);
    assert.equal(calls[0].options.body.get('event_type'), 'copy');
    assert.equal(calls[0].options.body.get('chapter_id'), '41');
    assert.equal(calls[0].options.body.get('selected_text'), undefined);
    assert.equal(calls[0].options.cache, 'no-store');
    assert.equal(calls[1].options.body.get('event_type'), 'chapter_load_error');
});
