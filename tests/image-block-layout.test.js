const assert = require('node:assert');

global.window = global;
require('../assets/js/editor/toolbar/toolbar-image-block.js');

const markup = window.buildImageBlockMarkup({
    blockId: 'photo-joyce',
    src: 'http://almaden.local/image.jpg',
    caption: 'James Joyce en París, 1924.',
    heightMode: 'fixed',
    heightPercent: '42',
    marginTopMm: '4',
    marginBottomMm: '6',
    captionGapMm: '0.5',
    captionAlign: 'center',
});

[
    'data-image-block-id="photo-joyce"',
    'data-height-mode="fixed"',
    'data-height-percent="42"',
    'data-margin-top-mm="4"',
    'data-margin-bottom-mm="6"',
    'data-caption-gap-mm="0.5"',
    'data-caption-align="center"',
    '<figcaption class="pdf-book-image-caption">James Joyce en París, 1924.</figcaption>',
].forEach(expected => assert.ok(markup.includes(expected), `Missing ${expected}`));

const automatic = window.buildImageBlockMarkup({ src: 'image.jpg' });
assert.ok(automatic.includes('data-height-mode="auto"'));
assert.ok(!automatic.includes('<figcaption'));

console.log('Image block layout regression: OK');
