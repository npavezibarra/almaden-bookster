// ============================================================
// MODULO: editor-pdf-content-integrity.js
// Responsabilidad: Verificar cada bloque antes y despues de paginar.
// ============================================================

(function registerPdfContentIntegrity() {
    const EDITORIAL_CONTENT_SELECTOR = '.chapter-editable-content';
    const EXCLUDED_SELECTOR = [
        '[data-footnote-id]',
        '.pagedjs_footnote_area',
        '.pagedjs_margin',
        'script',
        'style'
    ].join(',');

    function normalizeText(value) {
        return String(value || '')
            .normalize('NFC')
            .replace(/[\u00AD\u2011]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function getChapterKey(node) {
        const section = node.closest('[data-chapter-editorial-role="content"], section');
        if (!section) return 'chapter';

        const classMatch = String(section.className || '').match(/chapter-section-([^\s]+)/);
        return String(
            section.getAttribute('data-id')
            || section.id
            || (classMatch ? classMatch[1] : 'chapter')
        );
    }

    function getCleanText(node) {
        const clone = node.cloneNode(true);
        clone.querySelectorAll(EXCLUDED_SELECTOR).forEach((item) => item.remove());
        clone.querySelectorAll('.pagedjs_hyphen').forEach((item) => {
            item.textContent = String(item.textContent || '').replace(/[\u2010\u2011]+$/g, '');
        });
        return String(clone.textContent || '')
            .normalize('NFC')
            .replace(/[\u00AD\u2011]/g, '');
    }

    function collectBlocks(root) {
        const blocks = new Map();
        const order = [];

        root.querySelectorAll(EDITORIAL_CONTENT_SELECTOR).forEach((content) => {
            Array.from(content.children || []).forEach((block, index) => {
                const blockId = block.getAttribute('data-editor-block-id') || `block-${index}`;
                const key = `${getChapterKey(content)}:${blockId}`;
                if (!blocks.has(key)) {
                    blocks.set(key, '');
                    order.push(key);
                }
                blocks.set(key, blocks.get(key) + getCleanText(block));
            });
        });

        return { blocks, order };
    }

    function getMismatch(expected, rendered) {
        let position = 0;
        const sharedLength = Math.min(expected.length, rendered.length);
        while (position < sharedLength && expected[position] === rendered[position]) {
            position += 1;
        }

        return {
            position,
            missing: expected.slice(position, position + 180),
            unexpected: rendered.slice(position, position + 180)
        };
    }

    window.verifyPdfContentIntegrity = function(sourceHtml, scroller) {
        const source = document.createElement('template');
        source.innerHTML = String(sourceHtml || '');

        const expected = collectBlocks(source.content);
        const rendered = collectBlocks(scroller);
        const mismatches = [];

        expected.order.forEach((key) => {
            const expectedText = normalizeText(expected.blocks.get(key));
            const renderedText = normalizeText(rendered.blocks.get(key));
            if (expectedText !== renderedText) {
                mismatches.push({
                    block: key,
                    ...getMismatch(expectedText, renderedText)
                });
            }
        });

        rendered.order.forEach((key) => {
            if (!expected.blocks.has(key)) {
                mismatches.push({
                    block: key,
                    position: 0,
                    missing: '',
                    unexpected: rendered.blocks.get(key).slice(0, 180)
                });
            }
        });

        return {
            valid: mismatches.length === 0,
            expectedBlocks: expected.order.length,
            renderedBlocks: rendered.order.length,
            mismatch: mismatches[0] || null,
            mismatches
        };
    };
})();
