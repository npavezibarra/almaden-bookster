const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { TextDecoder, TextEncoder } = require('node:util');

const context = { window: {}, ArrayBuffer, TextDecoder };
vm.createContext(context);
vm.runInContext(
    fs.readFileSync(path.join(__dirname, '../assets/js/pdf/typst/editor-typst-response.js'), 'utf8'),
    context
);

const metadata = {
    geometry: { width: 27, height: 19 },
    page_flow: Array.from({ length: 400 }, (_, index) => ({ id: `almaden-flow-${index + 1}`, page: index + 1 })),
    page_template_results: Array.from({ length: 80 }, (_, index) => ({ instance_id: `tpl-${index}`, applied: true }))
};
const metadataBytes = new TextEncoder().encode(JSON.stringify(metadata));
const pdfBytes = new TextEncoder().encode('%PDF-1.7\nfixture');
const envelope = new Uint8Array(metadataBytes.length + pdfBytes.length);
envelope.set(metadataBytes, 0);
envelope.set(pdfBytes, metadataBytes.length);

const decoded = context.window.almadenTypstResponse.decode(envelope.buffer, metadataBytes.length);
assert.equal(decoded.metadata.page_flow.length, 400);
assert.equal(new TextDecoder('ascii').decode(decoded.pdfBytes.slice(0, 5)), '%PDF-');
assert.throws(() => context.window.almadenTypstResponse.decode(envelope.buffer, 0), /metadatos/);
assert.throws(() => context.window.almadenTypstResponse.decode(envelope.buffer, envelope.byteLength), /metadatos/);

console.log('Typst response envelope regression: OK');
