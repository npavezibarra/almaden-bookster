const fs = require('fs');
const htmlScript = fs.readFileSync('assets/js/pdf/editor-pdf-html.js', 'utf8');

// Mock window and other dependencies
global.window = {};

// Evaluate the script to populate window.buildChapterHTML
eval(htmlScript);

const bookState = {
    settings: {
        credits_edition: 'Primera Edición',
        credits_date: 'Mayo 2024',
        credits_copyright: 'Rigurosamente prohibido...',
        credits_custom: '[{"role":"Autor","name":"Nicolas Perez"}]'
    },
    chapters: []
};

const chapter = {
    id: 'cap-1',
    is_credits: '1'
};

const html = window.buildChapterHTML(chapter, 0, bookState.settings, bookState);
console.log("GENERATED HTML:");
console.log(html);
