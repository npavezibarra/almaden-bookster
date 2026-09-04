const assert = require('node:assert/strict');

global.window = global;

window.bookState = {
    settings: {
        page_templates: [
            {
                id: 'tpl-page-52',
                instance_id: 'tpl-page-52',
                page_number: 52,
                resolved_page: 53,
                template_id: 'image-top-two-column-bottom'
            }
        ]
    }
};
window.almadenPageTemplateResults = [
    {
        instance_id: 'tpl-page-52',
        applied: true,
        page: 53,
        resolved_page: 53
    }
];

require('../assets/js/pdf/typst/page-templates/editor-page-template-state.js');

const byResolvedPage = window.almadenPageTemplateState.getTemplateAtPage(53);
assert.equal(byResolvedPage?.instance_id, 'tpl-page-52');

const byAuthoredPage = window.almadenPageTemplateState.getTemplateAtPage(52);
assert.equal(byAuthoredPage?.instance_id, 'tpl-page-52');

console.log('Page template state regression: OK');
