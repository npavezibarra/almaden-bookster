const assert = require('node:assert/strict');

global.window = global;
window.bookState = {
    chapters: [
        { id: 10, title: 'Capítulo uno' },
        { id: 20, title: 'Capítulo dos' },
        { id: 30, title: 'Capítulo sin imágenes' }
    ],
    pdfPreview: {
        universalCounter: {
            chapters: [
                { id: '10', startPage: 1, endPage: 8, sequence: 1 },
                { id: '20', startPage: 9, endPage: 16, sequence: 2 },
                { id: '30', startPage: 17, endPage: 24, sequence: 3 }
            ]
        }
    }
};

window.almadenPageTemplateRegistry = {
    'one-image': {
        label: 'Una imagen',
        slots: [
            { id: 'image-1', label: 'Imagen 1', aspect_ratio: { width: 3, height: 5 } }
        ]
    },
    'four-images': {
        label: 'Cuatro imágenes',
        slots: Array.from({ length: 4 }, (_, index) => ({
            id: `image-${index + 1}`,
            label: `Imagen ${index + 1}`,
            aspect_ratio: { width: 5, height: 4 }
        }))
    }
};

const templates = [
    {
        instance_id: 'tpl-one',
        template_id: 'one-image',
        resolved_page: 5,
        slots: [
            {
                id: 'image-1',
                attachment_id: 88,
                original_url: 'https://example.test/heavy-original.tif',
                preview_url: 'https://example.test/light-preview.jpg'
            }
        ]
    },
    {
        instance_id: 'tpl-four',
        template_id: 'four-images',
        resolved_page: 12,
        slots: Array.from({ length: 4 }, (_, index) => ({ id: `image-${index + 1}` }))
    }
];

window.almadenPageTemplateState = {
    getAppliedTemplates: () => templates,
    getTemplates: () => templates,
    getResolvedPage: template => template.resolved_page,
    getInstanceId: template => template.instance_id
};

require('../assets/js/pdf/typst/page-templates/editor-image-setter-data.js');

const index = window.almadenImageSetterData.buildIndex();
assert.equal(index.chapters.length, 3, 'keeps chapters without image slots');
assert.equal(index.rows.length, 5, 'creates one row per template slot');
assert.equal(index.totals.assigned, 1);
assert.equal(index.totals.missing, 4);
assert.equal(index.chapters[0].rows[0].chapterTitle, 'Capítulo uno');
assert.equal(index.chapters[1].rows.length, 4);
assert.equal(index.chapters[2].rows.length, 0);
assert.equal(index.rows[0].previewUrl, 'https://example.test/light-preview.jpg');
assert.equal(index.rows[0].previewUrl.includes('heavy-original'), false, 'never uses the original as a list preview');
assert.deepEqual(
    { width: index.chapters[1].rows[0].ratio.width, height: index.chapters[1].rows[0].ratio.height },
    { width: 5, height: 4 }
);
assert.equal(window.almadenImageSetterData.filterRows(index.rows, 'missing').length, 4);
assert.equal(window.almadenImageSetterData.filterRows(index.rows, 'assigned').length, 1);

console.log('Image Setter data regression: OK');
