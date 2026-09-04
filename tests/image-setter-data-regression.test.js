const assert = require('node:assert/strict');

global.window = global;
window.bookState = {
    chapters: [],
    settings: {
        page_templates: [
            {
                instance_id: 'tpl-59',
                page_number: 59,
                resolved_page: 59,
                template_id: 'inner-full-page',
                slots: [
                    {
                        id: 'image-1',
                        attachment_id: 1631,
                        url: 'http://almaden.local/wp-content/uploads/example.jpg',
                        original_url: 'http://almaden.local/wp-content/uploads/example.jpg',
                        preview_url: 'http://almaden.local/wp-content/uploads/example-preview.jpg'
                    }
                ]
            }
        ]
    }
};
window.almadenPageTemplateAssetDiagnostics = [
    {
        instance_id: 'tpl-59',
        slot_id: 'image-1',
        assigned: true,
        renderable: false,
        reason: 'source_file_unavailable'
    }
];
window.almadenPageTemplateState = {
    getTemplates: () => window.bookState.settings.page_templates
};

require('../assets/js/pdf/typst/page-templates/editor-image-setter-data.js');

const index = window.almadenImageSetterData.buildIndex();
assert.equal(index.totals.slots, 1);
assert.equal(index.totals.assigned, 0);
assert.equal(index.totals.missing, 1);
assert.equal(index.rows[0].configured, true);
assert.equal(index.rows[0].pdfReady, false);

console.log('Image setter data regression: OK');
