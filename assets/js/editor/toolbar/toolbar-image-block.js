window.ALMADEN_IMAGE_BLOCK_DEFAULTS = {
    viewportWidth: '100%',
    viewportHeight: '100%',
    zoom: '1',
    fit: 'cover',
    position: '50% 50%',
    blockId: '',
    caption: '',
};

function escapeHtmlAttribute(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function normalizeImageBlockConfig(config = {}) {
    return {
        src: String(config.src || ''),
        originalSrc: String(config.originalSrc || config.src || ''),
        previewSrc: String(config.previewSrc || config.src || ''),
        alt: String(config.alt || ''),
        caption: String(config.caption || ''),
        className: String(config.className || 'pdf-book-image'),
        viewportWidth: '100%',
        viewportHeight: String(config.viewportHeight || window.ALMADEN_IMAGE_BLOCK_DEFAULTS.viewportHeight),
        zoom: String(config.zoom || window.ALMADEN_IMAGE_BLOCK_DEFAULTS.zoom),
        fit: String(config.fit || window.ALMADEN_IMAGE_BLOCK_DEFAULTS.fit),
        position: String(config.position || window.ALMADEN_IMAGE_BLOCK_DEFAULTS.position),
        blockId: String(config.blockId || window.ALMADEN_IMAGE_BLOCK_DEFAULTS.blockId),
        isPlaceholder: !!config.isPlaceholder,
    };
}

function buildImageBlockMarkup(config = {}) {
    const image = normalizeImageBlockConfig(config);
    const figureClasses = ['pdf-book-image-block'];
    if (image.isPlaceholder || !image.src) {
        figureClasses.push('is-empty');
    }
    const attrs = [
        `class="${escapeHtmlAttribute(figureClasses.join(' '))}"`,
        'data-image-block="1"',
        image.blockId ? `data-image-block-id="${escapeHtmlAttribute(image.blockId)}"` : '',
        'contenteditable="false"',
        `data-viewport-width="${escapeHtmlAttribute(image.viewportWidth)}"`,
        `data-viewport-height="${escapeHtmlAttribute(image.viewportHeight)}"`,
        `data-zoom="${escapeHtmlAttribute(image.zoom)}"`,
        `data-fit="${escapeHtmlAttribute(image.fit)}"`,
        `data-position="${escapeHtmlAttribute(image.position)}"`,
    ].filter(Boolean);

    const mediaHtml = image.src
        ? `<img src="${escapeHtmlAttribute(image.src)}" data-original-src="${escapeHtmlAttribute(image.originalSrc || image.src)}" data-preview-src="${escapeHtmlAttribute(image.previewSrc || image.src)}" alt="${escapeHtmlAttribute(image.alt)}" class="${escapeHtmlAttribute(image.className)}" />`
        : '<div class="pdf-book-image-placeholder">Upload or select Image</div>';
    const captionHtml = String(image.caption || '').trim()
        ? `<figcaption class="pdf-book-image-caption">${escapeHtmlAttribute(String(image.caption).trim())}</figcaption>`
        : '';

    return `\n<figure ${attrs.join(' ')}>\n  <div class="pdf-book-image-frame${image.isPlaceholder || !image.src ? ' is-empty' : ''}">\n    ${mediaHtml}\n  </div>\n  ${captionHtml}\n</figure>\n`;
}

window.buildImageBlockMarkup = buildImageBlockMarkup;
